<?php

namespace App\Services\AI\Concerns;

/**
 * Shared attachment → content-part resolution for all LLM providers.
 *
 * Providers used to duplicate this logic and drift apart: Google/Mistral read a
 * legacy singular `attachment` key the controllers never send (so those models
 * silently lost every upload), and no provider handled plain-text files. Each
 * provider maps the normalized parts returned here onto its own wire format.
 */
trait ResolvesAttachments
{
    /**
     * Normalize message attachments into provider-agnostic parts.
     *
     * Documents go through DocumentRagService: content within $textBudget is
     * injected whole, anything larger is chunked + BM25-retrieved against
     * $ragQuery so only the most relevant excerpts reach the model. Both paths
     * append grounding instructions (answer ONLY from the document, admit when
     * the answer isn't there) — this is what keeps small local models from
     * hallucinating document contents.
     *
     * @param array  $attachments Each: ['file_path' => string, 'file_type' => ?string, 'file_name' => ?string]
     * @param string $ragQuery    The user's question — retrieval key for large documents.
     * @param int    $textBudget  Max chars of document content to inject per attachment.
     * @return array<int, array{kind: 'image', mime: string, base64: string}|array{kind: 'text', text: string}>
     */
    /**
     * The latest user message text in a normalized message array — used as the
     * RAG retrieval query so follow-up questions about an earlier attachment
     * still retrieve against what the user is asking NOW.
     */
    protected function ragQueryFrom(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'user') {
                continue;
            }
            $content = $messages[$i]['content'] ?? '';
            if (is_array($content)) {
                $content = implode(' ', array_map(
                    fn ($p) => is_array($p) ? ($p['text'] ?? '') : (string) $p,
                    $content
                ));
            }
            if (trim((string) $content) !== '') {
                return (string) $content;
            }
        }
        return '';
    }

    protected function resolveAttachmentParts(array $attachments, string $ragQuery = '', int $textBudget = 48_000): array
    {
        // Absolute cap on parsed text per attachment (pre-RAG); retrieval then
        // narrows it down to $textBudget.
        $textLimit = 400_000;
        $rag = app(\App\Services\DocumentRagService::class);
        $parts = [];

        foreach ($attachments as $att) {
            $relPath = $att['file_path'] ?? null;
            if (!$relPath) {
                continue;
            }
            $absPath = storage_path('app/public/' . $relPath);
            if (!file_exists($absPath)) {
                continue;
            }

            $mime = $att['file_type'] ?? ((string) @mime_content_type($absPath) ?: '');
            $name = $att['file_name'] ?? basename($relPath);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (str_starts_with($mime, 'image/')) {
                $img = \App\Helpers\ImageHelper::resizeAndEncode($absPath, $mime, 4000);
                $parts[] = ['kind' => 'image', 'mime' => $img['mime_type'], 'base64' => $img['data']];
                continue;
            }

            $isDocument = $mime === 'application/pdf'
                || $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                || in_array($ext, ['pdf', 'docx'], true);
            $isTextLike = str_starts_with($mime, 'text/')
                || in_array($mime, ['application/json', 'application/xml', 'application/x-yaml'], true)
                || in_array($ext, ['txt', 'csv', 'md', 'json', 'log', 'xml', 'html', 'yml', 'yaml'], true);

            if (!$isDocument && !$isTextLike) {
                continue;
            }

            // DocumentParser resolves the relative path against the public disk itself.
            $text = $isDocument
                ? (string) \App\Helpers\DocumentParser::parseText($relPath, $mime, $name)
                : (string) file_get_contents($absPath, false, null, 0, $textLimit + 1);

            $text = trim($text);
            if ($text === '') {
                continue;
            }
            if (strlen($text) > $textLimit) {
                $text = substr($text, 0, $textLimit);
            }

            $block = $rag->buildDocumentBlock($text, $name, $ragQuery, $textBudget);
            if ($block === '') {
                continue;
            }

            $parts[] = ['kind' => 'text', 'text' => $block];
        }

        return $parts;
    }
}
