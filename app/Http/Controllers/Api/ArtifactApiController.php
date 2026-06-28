<?php

namespace App\Http\Controllers\Api;

use App\Models\MessageArtifact;
use App\Services\PdfRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Artifact REST surface.
 *
 * Migrated from App\Livewire\ArtifactPanel without altering behaviour:
 *   - GET    /api/artifacts        -> loadArtifacts() / getFilteredArtifactsProperty()
 *   - GET    /api/artifacts/{id}   -> loadCurrentArtifact() / artifactContent() / loadVersions()
 *   - PATCH  /api/artifacts/{id}   -> renameArtifact() / publishArtifact() / unpublishArtifact()
 *   - DELETE /api/artifacts/{id}   -> deleteArtifact()
 *
 * store() is intentionally NOT implemented here. Artifacts are only ever
 * persisted as a side-effect of ChatInterface::generateResponse() parsing an
 * <antArtifact> block out of the streamed reply — and the row requires a
 * message_id, so it cannot exist standalone. That creation path is owned by
 * Phase 5 (Streaming Migration), so store() keeps the uniform 501 contract.
 *
 * Ownership is always resolved through message -> conversation -> user_id (NOT
 * the denormalized message_artifacts.user_id, which is null for older rows),
 * exactly as ArtifactPanel::ownsArtifact() did.
 */
class ArtifactApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $search = trim((string) $request->query('search', ''));

        // Deliberately omits the (potentially huge) `content` column — same as
        // ArtifactPanel::loadArtifacts(). One row per identifier (latest grid item).
        $artifacts = MessageArtifact::query()
            ->whereHas('message.conversation', fn (Builder $q) => $q->where('user_id', $userId))
            ->orderBy('created_at', 'desc')
            ->get(['id', 'identifier', 'title', 'language', 'type', 'created_at', 'is_public'])
            ->unique('identifier')
            ->values();

        if ($search !== '') {
            $needle = strtolower($search);
            $artifacts = $artifacts->filter(function (MessageArtifact $a) use ($needle) {
                return str_contains(strtolower((string) $a->title), $needle)
                    || str_contains(strtolower((string) $a->language), $needle);
            })->values();
        }

        $data = $artifacts->map(fn (MessageArtifact $a) => [
            'id' => $a->id,
            'identifier' => $a->identifier,
            'title' => $a->title,
            'language' => $a->language,
            'type' => $a->type,
            'is_public' => (bool) $a->is_public,
            'created_at' => optional($a->created_at)->toIso8601String(),
        ]);

        return response()->json(['data' => $data]);
    }

    public function store(): JsonResponse
    {
        return $this->pendingMigration('artifacts.store', 'App\\Livewire\\ChatInterface::generateResponse', 'Phase 5');
    }

    public function show(MessageArtifact $artifact): JsonResponse
    {
        $this->authorizeOwnership($artifact);

        return response()->json(['data' => $this->transform($artifact, withContent: true)]);
    }

    public function update(Request $request, MessageArtifact $artifact): JsonResponse
    {
        $this->authorizeOwnership($artifact);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        // Rename: ArtifactPanel::renameArtifact() caps at 120 chars and applies
        // the new title to every version that shares this identifier.
        if (array_key_exists('title', $validated)) {
            $title = Str::limit(trim($validated['title']), 120, '');
            if ($title !== '') {
                $this->ownedByIdentifier($artifact->identifier)->update(['title' => $title]);
                $artifact->title = $title;
            }
        }

        // Publish / unpublish: ArtifactPanel::publishArtifact() mints the token
        // once and flips is_public; unpublishArtifact() just flips it back.
        if (array_key_exists('is_public', $validated)) {
            if ($validated['is_public']) {
                if (empty($artifact->public_token)) {
                    $artifact->public_token = Str::random(32);
                }
                $artifact->is_public = true;
            } else {
                $artifact->is_public = false;
            }
            $artifact->save();
        }

        return response()->json(['data' => $this->transform($artifact->fresh(), withContent: true)]);
    }

    public function destroy(MessageArtifact $artifact): JsonResponse
    {
        $this->authorizeOwnership($artifact);

        // Mirror ArtifactPanel::deleteArtifact() — removes every version sharing
        // this identifier (scoped to the owner so collisions can't leak).
        $this->ownedByIdentifier($artifact->identifier)->delete();

        return response()->json(['deleted' => true]);
    }

    public function downloadPdf(Request $request, MessageArtifact $artifact)
    {
        $this->authorizeOwnership($artifact);
        $mode = $request->query('mode');
        $data = $artifact->toArray();
        $binary = app(PdfRenderer::class)->render($data, $mode);
        $filename = Str::slug($artifact->title ?: 'document') . '.pdf';
        return response()->streamDownload(fn () => echo $binary, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function downloadMarkdown(MessageArtifact $artifact)
    {
        $this->authorizeOwnership($artifact);
        $content = PdfRenderer::stripFrontMatter($artifact->content ?? '');
        $filename = Str::slug($artifact->title ?: 'document') . '.md';
        return response()->streamDownload(fn () => echo $content, $filename, ['Content-Type' => 'text/markdown; charset=utf-8']);
    }

    public function downloadFile(MessageArtifact $artifact)
    {
        $this->authorizeOwnership($artifact);
        $ext = $this->extensionForLanguage($artifact->language ?? 'txt');
        $filename = Str::slug($artifact->title ?: 'artifact') . '.' . $ext;
        return response()->streamDownload(fn () => echo $artifact->content ?? '', $filename, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function extensionForLanguage(string $language): string
    {
        $map = ['html'=>'html','javascript'=>'js','js'=>'js','jsx'=>'jsx','typescript'=>'ts','ts'=>'ts','tsx'=>'tsx','react'=>'jsx','python'=>'py','py'=>'py','php'=>'php','css'=>'css','json'=>'json','markdown'=>'md','md'=>'md','svg'=>'svg','sql'=>'sql','java'=>'java','go'=>'go','rust'=>'rs','ruby'=>'rb','c'=>'c','cpp'=>'cpp','csharp'=>'cs','bash'=>'sh','shell'=>'sh','yaml'=>'yaml','xml'=>'xml'];
        return $map[strtolower($language)] ?? 'txt';
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /** Ensure the artifact belongs to the authenticated user's conversation. */
    private function authorizeOwnership(MessageArtifact $artifact): void
    {
        $owns = MessageArtifact::where('id', $artifact->id)
            ->whereHas('message.conversation', fn (Builder $q) => $q->where('user_id', Auth::id()))
            ->exists();

        abort_unless($owns, 403);
    }

    /**
     * Query scoped to artifacts sharing an identifier AND belonging to the
     * authenticated user's conversations — copied from ArtifactPanel so a bulk
     * delete/rename can never reach another user's rows.
     */
    private function ownedByIdentifier(?string $identifier): Builder
    {
        return MessageArtifact::query()
            ->where('identifier', $identifier)
            ->whereHas('message.conversation', fn (Builder $q) => $q->where('user_id', Auth::id()));
    }

    /**
     * Build the version list for an artifact (ArtifactPanel::loadVersions()):
     * every row sharing the identifier, oldest first, numbered.
     *
     * @return array<int, array<string, mixed>>
     */
    private function versionsFor(MessageArtifact $artifact): array
    {
        if (! $artifact->identifier) {
            return [];
        }

        return $this->ownedByIdentifier($artifact->identifier)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'created_at'])
            ->values()
            ->map(fn (MessageArtifact $v, int $index) => [
                'id' => $v->id,
                'version_number' => $index + 1,
                'is_current' => $v->id === $artifact->id,
            ])
            ->toArray();
    }

    /**
     * Shape an artifact for the API. Content + outline + versions are only
     * included for the detail view (show/update) to keep list payloads light.
     *
     * @return array<string, mixed>
     */
    private function transform(MessageArtifact $artifact, bool $withContent = false): array
    {
        $data = [
            'id' => $artifact->id,
            'identifier' => $artifact->identifier,
            'title' => $artifact->title,
            'language' => $artifact->language,
            'type' => $artifact->type,
            'is_public' => (bool) $artifact->is_public,
            'public_token' => $artifact->public_token,
            'public_url' => ($artifact->is_public && $artifact->public_token)
                ? route('artifact.shared', $artifact->public_token)
                : null,
            'created_at' => optional($artifact->created_at)->toIso8601String(),
            'updated_at' => optional($artifact->updated_at)->toIso8601String(),
        ];

        if ($withContent) {
            $data['content'] = $artifact->content;
            $data['outline'] = $artifact->outline_json ?? [];
            $data['versions'] = $this->versionsFor($artifact);
        }

        return $data;
    }
}
