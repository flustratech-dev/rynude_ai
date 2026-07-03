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
     * @param array $attachments Each: ['file_path' => string, 'file_type' => ?string, 'file_name' => ?string]
     * @return array<int, array{kind: 'image', mime: string, base64: string}|array{kind: 'text', text: string}>
     */
    protected function resolveAttachmentParts(array $attachments): array
    {
        // Cap extracted text per attachment so one big file can't blow the context window.
        $textLimit = 100_000;
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
                $text = substr($text, 0, $textLimit) . "\n[... isi file dipotong karena terlalu panjang ...]";
            }

            $parts[] = [
                'kind' => 'text',
                'text' => "\n\n[Isi Dokumen lampiran: {$name}]\n{$text}\n[Akhir Isi Dokumen]",
            ];
        }

        return $parts;
    }
}
