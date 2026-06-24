<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Storage;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Shared document-building logic used by both the PDF (mPDF) and DOCX (PHPWord)
 * renderers. Keeping markdown parsing, front-matter handling, formatting
 * resolution and image rewriting in one place guarantees that the two output
 * formats stay in sync — a DOCX is meant to mirror the PDF exactly.
 */
trait BuildsDocumentContent
{
    /**
     * Convert markdown (with optional front-matter) to HTML body + parsed meta.
     *
     * @return array{0:string,1:array}
     */
    protected function markdownToHtml(string $markdown): array
    {
        $environment = new Environment([
            'html_input' => 'allow',          // keep author/AI inline <svg> and <img>
            'allow_unsafe_links' => false,
            'renderer' => ['block_separator' => "\n"],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FrontMatterExtension());

        $converter = new MarkdownConverter($environment);

        // Auto-fix unquoted YAML values that contain colons (which breaks the YAML parser)
        if (preg_match('/^---\r?\n(.*?)\r?\n---/s', $markdown, $matches)) {
            $frontMatter = $matches[1];
            // If a line is like "key: some: value", replace it with "key: 'some: value'"
            $fixedFrontMatter = preg_replace('/^([a-zA-Z0-9_-]+):\s*([^"\'].*?:.*?)$/m', '$1: \'$2\'', $frontMatter);
            if ($frontMatter !== $fixedFrontMatter) {
                $markdown = "---\n" . $fixedFrontMatter . "\n---" . substr($markdown, strlen($matches[0]));
            }
        }

        try {
            $result = $converter->convert($markdown);
        } catch (\Exception $e) {
            // Fallback if YAML parsing still fails completely: strip front matter and parse as plain markdown
            $markdown = preg_replace('/^---\r?\n.*?\r?\n---\r?\n/s', '', $markdown);
            $environment = new Environment([
                'html_input' => 'allow',
                'allow_unsafe_links' => false,
                'renderer' => ['block_separator' => "\n"],
            ]);
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new GithubFlavoredMarkdownExtension());
            $converter = new MarkdownConverter($environment);
            $result = $converter->convert($markdown);
        }

        $meta = [];
        if ($result instanceof \League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter) {
            $fm = $result->getFrontMatter();
            if (is_array($fm)) {
                $meta = $fm;
            }
        }

        return [(string) $result->getContent(), $this->metaDefaults($meta['mode'] ?? 'document', $meta)];
    }

    protected function metaDefaults(string $mode, array $meta = []): array
    {
        $normalizedMeta = [];
        foreach ($meta as $key => $value) {
            $normalizedMeta[$key] = is_array($value) ? implode(', ', $value) : $value;
        }

        return array_merge([
            'mode' => $mode,
            'judul' => null,
            'penulis' => null,
            'nim' => null,
            'prodi' => null,
            'fakultas' => null,
            'universitas' => null,
            'kota' => null,
            'tahun' => null,
            'pembimbing' => null,
            'logo' => null,
            // Optional format overrides (used to mimic an uploaded template).
            'font' => null,
            'font_size' => null,
            'line_spacing' => null,
            'align' => null,
            'margin_top' => null,
            'margin_right' => null,
            'margin_bottom' => null,
            'margin_left' => null,
            'page_number' => null,
        ], $normalizedMeta);
    }

    /**
     * Normalise the optional formatting overrides from front-matter into safe,
     * whitelisted values, falling back to per-mode defaults. Lets the AI replicate
     * the look of a template the user uploaded (font, size, spacing, margins, page
     * number position) without exposing the renderer internals to arbitrary input.
     *
     * @return array{font:string,fontSize:float,lineSpacing:float,align:string,margins:array{0:float,1:float,2:float,3:float},pageNumber:array{loc:string,align:string}}
     */
    protected function resolveFormat(array $meta, bool $academic): array
    {
        // Font name → mPDF font family (core fonts + bundled DejaVu).
        $fontMap = [
            // GNU FreeFont (bundled with mPDF): Times/Helvetica/Courier-metric serif,
            // sans and mono with FULL Unicode (Greek, math, accents). mPDF's core
            // fonts ('times' etc.) corrupt Greek/maths in UTF-8 mode, so we map to the
            // Free* families which look like Times New Roman / Arial but render every
            // glyph an academic report needs.
            'times' => 'freeserif', 'times new roman' => 'freeserif', 'serif' => 'freeserif',
            'georgia' => 'freeserif', 'cambria' => 'freeserif',
            'arial' => 'freesans', 'helvetica' => 'freesans', 'calibri' => 'freesans',
            'sans' => 'freesans', 'sans-serif' => 'freesans', 'verdana' => 'freesans',
            'tahoma' => 'freesans', 'segoe ui' => 'freesans',
            'courier' => 'freemono', 'courier new' => 'freemono', 'consolas' => 'freemono',
            'monospace' => 'freemono', 'mono' => 'freemono',
        ];
        $fontKey = strtolower(trim((string) ($meta['font'] ?? '')));
        $font = $fontMap[$fontKey] ?? 'freeserif';

        $fontSize = (float) ($meta['font_size'] ?? 12);
        if ($fontSize < 8 || $fontSize > 20) {
            $fontSize = 12;
        }

        $spacingMap = ['1' => 1.0, '1.0' => 1.0, '1.15' => 1.15, '1.5' => 1.5, '2' => 2.0, '2.0' => 2.0, 'single' => 1.0, 'double' => 2.0];
        $spacingKey = strtolower(trim((string) ($meta['line_spacing'] ?? '')));
        $lineSpacing = $spacingMap[$spacingKey] ?? ($academic ? 1.5 : 1.45);

        $align = strtolower(trim((string) ($meta['align'] ?? '')));
        $align = in_array($align, ['justify', 'left'], true) ? $align : ($academic ? 'justify' : 'left');

        // Margins in mm; defaults follow the chosen mode (skripsi = 4-3-3-3 cm).
        $defT = $academic ? 30 : 25;
        $defR = $academic ? 30 : 25;
        $defB = $academic ? 30 : 25;
        $defL = $academic ? 40 : 25;
        $cm = function ($v, $default) {
            if ($v === null || $v === '') {
                return (float) $default;
            }
            $mm = (float) $v * 10; // front-matter margins are given in cm
            return ($mm >= 5 && $mm <= 80) ? $mm : (float) $default;
        };
        $margins = [
            $cm($meta['margin_top'] ?? null, $defT),
            $cm($meta['margin_right'] ?? null, $defR),
            $cm($meta['margin_bottom'] ?? null, $defB),
            $cm($meta['margin_left'] ?? null, $defL),
        ];

        $posMap = [
            'bottom-center' => ['F', 'center'], 'bottom-centre' => ['F', 'center'], 'bottom' => ['F', 'center'],
            'bottom-right' => ['F', 'right'], 'bottom-left' => ['F', 'left'],
            'top-center' => ['H', 'center'], 'top-centre' => ['H', 'center'], 'top' => ['H', 'center'],
            'top-right' => ['H', 'right'], 'top-left' => ['H', 'left'],
            'none' => ['', ''], 'off' => ['', ''],
        ];
        $posKey = strtolower(trim((string) ($meta['page_number'] ?? '')));
        [$loc, $palign] = $posMap[$posKey] ?? ['F', 'center'];

        return [
            'font' => $font,
            'fontSize' => $fontSize,
            'lineSpacing' => $lineSpacing,
            'align' => $align,
            'margins' => $margins,
            'pageNumber' => ['loc' => $loc, 'align' => $palign],
        ];
    }

    /**
     * Rewrite <img src> values that point at local storage / app URLs into absolute
     * filesystem paths that the renderer can read directly. Leaves remote URLs and
     * data-URIs untouched.
     */
    protected function resolveImages(string $html): string
    {
        return preg_replace_callback('/<img\b[^>]*\bsrc=("|\')(.*?)\1/i', function ($m) {
            $quote = $m[1];
            $src = html_entity_decode($m[2]);
            $resolved = $this->resolveImagePath($src);
            if ($resolved === null) {
                return $m[0];
            }
            return str_replace($m[2], htmlspecialchars($resolved, ENT_QUOTES), $m[0]);
        }, $html) ?? $html;
    }

    protected function resolveImagePath(string $src): ?string
    {
        // data: URIs and remote URLs are handled by the renderer natively.
        if (str_starts_with($src, 'data:') || preg_match('#^https?://#i', $src)) {
            // Strip the app's own host so local uploads resolve from disk instead of HTTP.
            $appUrl = rtrim((string) config('app.url'), '/');
            if ($appUrl && str_starts_with($src, $appUrl . '/storage/')) {
                $src = substr($src, strlen($appUrl . '/storage/'));
                return $this->fromPublicDisk($src);
            }
            return null; // genuine remote image — leave as-is
        }

        // /storage/... (public symlink) → public disk
        if (str_starts_with($src, '/storage/')) {
            return $this->fromPublicDisk(ltrim(substr($src, strlen('/storage/')), '/'));
        }
        if (str_starts_with($src, 'storage/')) {
            return $this->fromPublicDisk(substr($src, strlen('storage/')));
        }

        // Bare relative path: try the public disk (where chat attachments live).
        $fromPublic = $this->fromPublicDisk(ltrim($src, '/'));
        if ($fromPublic !== null) {
            return $fromPublic;
        }

        // Absolute filesystem path already.
        return is_file($src) ? $src : null;
    }

    protected function fromPublicDisk(string $relative): ?string
    {
        try {
            $path = Storage::disk('public')->path($relative);
        } catch (\Throwable $e) {
            return null;
        }
        return is_file($path) ? $path : null;
    }

    /**
     * Strip a leading YAML front-matter block so on-screen markdown previews don't
     * render the raw metadata. Used by the artifact preview blades and the .md export.
     */
    public static function stripFrontMatter(string $content): string
    {
        return preg_replace('/^(?:\xEF\xBB\xBF)?\s*---\r?\n.*?\r?\n---[ \t]*\r?\n/s', '', $content, 1) ?? $content;
    }
}
