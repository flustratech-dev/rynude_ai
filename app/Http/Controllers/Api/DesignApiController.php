<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Services\AI\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Design REST surface.
 *
 * Migrated from App\Livewire\DesignPanel:
 *   - GET    /api/designs       -> getDesignsProperty() (search + tab filter)
 *   - POST   /api/designs       -> generate() (create + synchronous AI fill)
 *   - PATCH  /api/designs/{id}  -> toggleStar() / rename
 *   - DELETE /api/designs/{id}  -> deleteDesign()
 *
 * store() reuses AiService exactly the way the Livewire component did — it
 * consumes the streamResponse() generator internally to accumulate the HTML.
 * This is unrelated to the chat wire:stream SSE system. AiService is injected so
 * tests can substitute a fake.
 */
class DesignApiController extends Controller
{
    /** Design type => human label, mirrors DesignPanel::$designTypes. */
    private const TYPES = [
        'slides' => 'Slides',
        'prototype' => 'Product prototype',
        'wireframe' => 'Product wireframe',
        'document' => 'Document',
        'animation' => 'Animation',
        'blank' => 'Blank canvas',
    ];

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $tab = $request->query('tab', 'recent');

        $query = Design::where('user_id', Auth::id());

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('prompt', 'like', "%{$search}%");
            });
        }

        if ($tab === 'yours') {
            $query->whereIn('status', ['ready', 'generating', 'failed']);
        }

        $designs = $query->orderByDesc('created_at')->get()
            ->map(fn (Design $design) => $this->transform($design));

        return response()->json(['data' => $designs]);
    }

    public function store(Request $request, AiService $ai): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPES))],
            'prompt' => ['required', 'string', 'max:2000'],
        ]);

        $design = Design::create([
            'user_id' => Auth::id(),
            'title' => Str::limit($validated['prompt'], 60),
            'type' => $validated['type'],
            'prompt' => $validated['prompt'],
            'status' => 'generating',
        ]);

        $typeLabel = self::TYPES[$validated['type']] ?? 'Design';
        $system = "You are an expert UI/UX designer and front-end engineer. Generate a single, self-contained HTML document (inline CSS, no external assets, no explanations) that fulfils the user's request for a {$typeLabel}. Use modern, clean, responsive design. Output ONLY the raw HTML starting with <!DOCTYPE html>.";

        try {
            $output = '';
            foreach ($ai->streamResponse([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $design->prompt],
            ], 'claude-haiku-4-5') as $chunk) {
                if (!is_string($chunk)) continue; // skip structured thinking deltas
                $output .= $chunk;
            }

            $output = $this->extractHtml($output);
            $isError = str_starts_with(trim($output), '[Error')
                || str_contains($output, 'API key is not configured');

            $design->update([
                'content' => $output,
                'status' => $isError ? 'failed' : 'ready',
            ]);
        } catch (\Throwable $e) {
            $design->update([
                'status' => 'failed',
                'content' => '[Error: ' . $e->getMessage() . ']',
            ]);
        }

        return response()->json(['data' => $this->transform($design->fresh())], 201);
    }

    public function update(Request $request, Design $design): JsonResponse
    {
        $this->authorizeOwnership($design);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_starred' => ['sometimes', 'boolean'],
        ]);

        $design->fill($validated);
        $design->save();

        return response()->json(['data' => $this->transform($design)]);
    }

    public function destroy(Design $design): JsonResponse
    {
        $this->authorizeOwnership($design);

        $design->delete();

        return response()->json(['deleted' => true]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function authorizeOwnership(Design $design): void
    {
        abort_unless($design->user_id === Auth::id(), 403);
    }

    /** Strip markdown code fences if the model wrapped the HTML. */
    private function extractHtml(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/```(?:html)?\s*(.*?)```/s', $raw, $m)) {
            return trim($m[1]);
        }
        return $raw;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Design $design): array
    {
        return [
            'id' => $design->id,
            'title' => $design->title,
            'type' => $design->type,
            'prompt' => $design->prompt,
            'content' => $design->content,
            'status' => $design->status,
            'is_starred' => (bool) $design->is_starred,
            'created_at' => optional($design->created_at)->toIso8601String(),
            'updated_at' => optional($design->updated_at)->toIso8601String(),
        ];
    }
}
