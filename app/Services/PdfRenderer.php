<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Mpdf\Mpdf;

/**
 * Renders a document artifact (markdown, optionally with YAML front-matter) into a
 * polished PDF using mPDF. Supports two layout modes driven by front-matter:
 *
 *   mode: skripsi | laporan   → full academic layout (cover page, auto Table of
 *                               Contents, Roman→Arabic page numbering, 4-3-3-3 cm
 *                               margins, Times New Roman 12pt, justified).
 *   mode: document (default)  → clean general document (2.5 cm margins, simple
 *                               centered page numbers, no cover/TOC).
 *
 * GFM tables, raw inline <svg> diagrams and embedded images (local uploads or URLs)
 * are all supported.
 */
class PdfRenderer
{
    /**
     * Build a PDF binary string from an artifact array.
     *
     * @param array $artifact Expects keys: title, content, language, type
     */
    public function render(array $artifact, ?string $modeOverride = null): string
    {
        $title = $artifact['title'] ?? 'Document';
        $raw = (string) ($artifact['content'] ?? '');
        $language = strtolower($artifact['language'] ?? '');
        $type = $artifact['type'] ?? 'text';

        // Pure code artifacts: render as a monospaced listing, never as a document.
        if (($type === 'code' && ! in_array($language, ['markdown', 'md', ''], true)) && $language !== 'html') {
            return $this->renderCode($title, $raw);
        }

        // HTML artifacts are passed through directly (already markup).
        if ($language === 'html') {
            return $this->buildPdf($title, $raw, $this->metaDefaults('document'), 'document');
        }

        // Markdown / text document path -----------------------------------
        [$html, $meta] = $this->markdownToHtml($raw);

        $mode = $modeOverride ?: ($meta['mode'] ?? 'document');
        $mode = in_array($mode, ['skripsi', 'laporan', 'document'], true) ? $mode : 'document';

        $html = $this->resolveImages($html);

        return $this->buildPdf($title, $html, $meta, $mode);
    }

    /**
     * Convert markdown (with optional front-matter) to HTML body + parsed meta.
     *
     * @return array{0:string,1:array}
     */
    private function markdownToHtml(string $markdown): array
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

    private function metaDefaults(string $mode, array $meta = []): array
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
     * number position) without exposing mPDF/CSS to arbitrary input.
     *
     * @return array{font:string,fontSize:float,lineSpacing:float,align:string,margins:array{0:float,1:float,2:float,3:float},pageNumber:array{loc:string,align:string}}
     */
    private function resolveFormat(array $meta, bool $academic): array
    {
        // Font name → mPDF font family (core fonts + bundled DejaVu).
        $fontMap = [
            'times' => 'times', 'times new roman' => 'times', 'serif' => 'times',
            'georgia' => 'times', 'cambria' => 'times',
            'arial' => 'helvetica', 'helvetica' => 'helvetica', 'calibri' => 'helvetica',
            'sans' => 'helvetica', 'sans-serif' => 'helvetica', 'verdana' => 'helvetica',
            'tahoma' => 'helvetica', 'segoe ui' => 'helvetica',
            'courier' => 'courier', 'courier new' => 'courier', 'consolas' => 'courier',
            'monospace' => 'courier', 'mono' => 'courier',
        ];
        $fontKey = strtolower(trim((string) ($meta['font'] ?? '')));
        $font = $fontMap[$fontKey] ?? 'times';

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
     * filesystem paths that mPDF can read directly. Leaves remote URLs and data-URIs
     * untouched (mPDF fetches those when allowed).
     */
    private function resolveImages(string $html): string
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

    private function resolveImagePath(string $src): ?string
    {
        // data: URIs and remote URLs are handled by mPDF natively.
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

    private function fromPublicDisk(string $relative): ?string
    {
        try {
            $path = Storage::disk('public')->path($relative);
        } catch (\Throwable $e) {
            return null;
        }
        return is_file($path) ? $path : null;
    }

    /**
     * Assemble the final PDF for a document/skripsi/laporan layout.
     */
    private function buildPdf(string $title, string $bodyHtml, array $meta, string $mode): string
    {
        $academic = in_array($mode, ['skripsi', 'laporan'], true);
        $fmt = $this->resolveFormat($meta, $academic);
        [$mt, $mr, $mb, $ml] = $fmt['margins'];

        $config = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => $fmt['font'],
            'default_font_size' => $fmt['fontSize'],
            'tempDir' => $this->tempDir(),
            'margin_top' => $mt,
            'margin_right' => $mr,
            'margin_bottom' => $mb,
            'margin_left' => $ml,
            'margin_header' => 10,
            'margin_footer' => 12,
        ];

        $mpdf = new Mpdf($config);
        $mpdf->SetTitle($title);
        $mpdf->showImageErrors = false;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $css = $this->css($academic, $fmt);
        $pageNum = $this->pageNumberMarkup($fmt['pageNumber']);

        if ($academic) {
            // Collect TOC + bookmarks automatically from headings.
            $mpdf->h2toc = ['H1' => 0, 'H2' => 1, 'H3' => 2];
            $mpdf->h2bookmarks = ['H1' => 0, 'H2' => 1, 'H3' => 2];

            // Each BAB (h1) starts on a fresh page, but the FIRST one flows onto the
            // body's opening page (avoids a blank leading page).
            $body = preg_replace('/(?<!^)\s*<h1\b/i', '<pagebreak />$0', $bodyHtml);
            // Above also matches the first h1 if it isn't at the very start; strip the
            // single leading page break so the first chapter shares the body's first page.
            $body = preg_replace('/^\s*<pagebreak \/>/', '', $body);

            $mpdf->WriteHTML('<style>' . $css . '</style>');

            // 1) Cover page — written before any footer is set, so it carries no number.
            $mpdf->WriteHTML($this->coverHtml($title, $meta));

            // 2) Auto Table of Contents on a Roman-numbered page (i, ii, …); the body
            //    that follows restarts with Arabic numerals (1, 2, …).
            $tocPre = htmlspecialchars('<div class="toc-title">DAFTAR ISI</div>', ENT_QUOTES);
            $mpdf->WriteHTML(
                '<tocpagebreak toc-preHTML="' . $tocPre . '" '
                . 'toc-resetpagenum="1" toc-pagenumstyle="i" '
                . 'resetpagenum="1" pagenumstyle="1" links="on" />'
            );

            // 3) Process Lembar Persetujuan / Pengesahan signature tables
            // Make tables in signature pages borderless automatically
            $body = preg_replace_callback('/<table\b[^>]*>(.*?)<\/table>/is', function($m) {
                $content = $m[1];
                if (preg_match('/(?:Pembimbing|Penguji|Menyetujui|NIP\.|NIDN\.)/i', $content)) {
                    return '<table class="no-border">' . $content . '</table>';
                }
                return $m[0];
            }, $body);

            // 4) Page number set AFTER the TOC tag so the cover stays unnumbered while
            //    the TOC page and body both get numbered at the requested position.
            $this->applyPageNumber($mpdf, $pageNum);
            $mpdf->WriteHTML('<div class="body">' . $body . '</div>');
        } else {
            $this->applyPageNumber($mpdf, $pageNum);
            $mpdf->WriteHTML('<style>' . $css . '</style>');
            $mpdf->WriteHTML('<div class="body">' . $bodyHtml . '</div>');
        }

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Build the page-number markup (or null when suppressed) for the resolved position.
     *
     * @return array{loc:string,html:string}|null
     */
    private function pageNumberMarkup(array $pos): ?array
    {
        if (($pos['loc'] ?? '') === '') {
            return null;
        }
        $align = in_array($pos['align'], ['left', 'right', 'center'], true) ? $pos['align'] : 'center';
        return [
            'loc' => $pos['loc'], // 'F' footer or 'H' header
            'html' => '<div class="pf" style="text-align:' . $align . ';">{PAGENO}</div>',
        ];
    }

    private function applyPageNumber(Mpdf $mpdf, ?array $pageNum): void
    {
        if ($pageNum === null) {
            return;
        }
        if ($pageNum['loc'] === 'H') {
            $mpdf->SetHTMLHeader($pageNum['html']);
        } else {
            $mpdf->SetHTMLFooter($pageNum['html']);
        }
    }

    private function renderCode(string $title, string $code): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $this->tempDir(),
            'default_font' => 'dejavusansmono',
            'default_font_size' => 9,
        ]);
        $mpdf->SetTitle($title);
        $mpdf->SetHTMLFooter('<div class="pf" style="text-align:center;font-size:9pt;">{PAGENO}</div>');
        $safe = htmlspecialchars($code);
        $mpdf->WriteHTML(
            '<style>pre{white-space:pre-wrap;word-wrap:break-word;font-family:dejavusansmono;'
            . 'font-size:9pt;line-height:1.4;}</style><pre>' . $safe . '</pre>'
        );
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function coverHtml(string $title, array $meta): string
    {
        $judul = e($meta['judul'] ?: $title);

        $skripsi_text = '';
        if (($meta['mode'] ?? '') === 'skripsi') {
            $skripsi_text = '<div style="margin-top: 1.5cm; font-size: 14pt; font-weight: bold; text-transform: uppercase;">SKRIPSI</div>';
            $skripsi_text .= '<div style="margin-top: 1cm; font-size: 11pt; line-height: 1.5;">Diajukan sebagai salah satu syarat untuk memperoleh<br>gelar Sarjana pada ' . e($meta['universitas'] ?? 'Universitas') . '</div>';
        } elseif (($meta['mode'] ?? '') === 'laporan') {
            $skripsi_text = '<div style="margin-top: 1.5cm; font-size: 14pt; font-weight: bold; text-transform: uppercase;">LAPORAN</div>';
        }

        $logo = '';
        if ($meta['logo']) {
            $resolved = $this->resolveImagePath((string) $meta['logo']);
            if ($resolved) {
                $logo = '<div class="cover-logo"><img src="' . e($resolved) . '" style="height:4.5cm; width:auto;" /></div>';
            }
        } else {
            // Spacer if no logo is provided
            $logo = '<div class="cover-logo" style="height:4.5cm;"></div>';
        }

        $meta_block = '<div style="margin-top: 1cm; font-size: 12pt;">Disusun Oleh:</div>';
        $meta_block .= '<div style="margin-top: 0.5cm; font-weight: bold; font-size: 12pt; text-transform: uppercase;">' . e($meta['penulis']) . '</div>';
        $meta_block .= '<div style="margin-top: 0.2cm; font-weight: bold; font-size: 12pt;">NIM. ' . e($meta['nim']) . '</div>';

        $inst = collect([
            $meta['prodi'] ? 'PROGRAM STUDI ' . strtoupper($meta['prodi']) : null,
            $meta['fakultas'] ? strtoupper($meta['fakultas']) : null,
            $meta['universitas'] ? strtoupper($meta['universitas']) : null,
            $meta['kota'] ? strtoupper($meta['kota']) : null,
            $meta['tahun'],
        ])->filter()->map(fn ($v) => '<div class="cover-inst">' . e($v) . '</div>')->implode('');

        return '<div class="cover">'
            . '<div class="cover-title">' . $judul . '</div>'
            . $skripsi_text
            . $logo
            . '<div class="cover-meta">' . $meta_block . '</div>'
            . '<div class="cover-bottom">' . $inst . '</div>'
            . '</div>';
    }

    private function css(bool $academic): string
    {
        $base = <<<'CSS'
        body { font-family: times; font-size: 12pt; color: #000; }
        .pf { text-align: center; font-family: times; font-size: 11pt; color: #000; }
        h1, h2, h3, h4, h5 { font-family: times; font-weight: bold; color: #000; }
        p { margin: 0 0 6pt 0; }
        a { color: #000; text-decoration: none; }
        ul, ol { margin: 0 0 6pt 0; }
        li { margin-bottom: 2pt; }
        blockquote { border-left: 3px solid #999; padding-left: 10pt; color: #333; font-style: italic; margin: 8pt 0; }
        table { width: 100%; border-collapse: collapse; margin: 12pt 0; font-size: 11pt; }
        th, td { border: 0.6pt solid #000; padding: 5pt 7pt; text-align: left; vertical-align: top; }
        th { background: #efefef; font-weight: bold; }
        table.no-border, table.no-border th, table.no-border td { border: none !important; background: transparent !important; }
        table.no-border th, table.no-border td { text-align: center; vertical-align: bottom; }
        table.no-border p { text-indent: 0 !important; }
        img, svg { max-width: 100%; }
        figure { text-align: center; margin: 12pt 0; }
        figcaption { font-size: 10pt; font-style: italic; margin-top: 4pt; }
        pre { background: #f4f4f4; padding: 8pt; border: 0.4pt solid #ddd; font-family: dejavusansmono; font-size: 9.5pt; white-space: pre-wrap; word-wrap: break-word; }
        code { font-family: dejavusansmono; font-size: 10pt; }
        CSS;

        if ($academic) {
            $base .= <<<'CSS'
            body { line-height: 1.5; text-align: justify; }
            p { text-indent: 1.27cm; margin: 0; text-align: justify; }
            li p, td p, blockquote p, figcaption { text-indent: 0; }
            h1 { font-size: 14pt; text-align: center; text-transform: uppercase; margin: 0 0 18pt 0; }
            h2 { font-size: 12pt; margin: 16pt 0 8pt 0; }
            h3 { font-size: 12pt; margin: 12pt 0 6pt 0; }
            .cover { text-align: center; }
            .cover-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; line-height: 1.5; margin-top: 1cm; }
            .cover-logo { margin: 1cm 0; }
            .cover-meta { margin-top: 1.5cm; font-size: 12pt; }
            .cover-line { margin: 3pt 0; }
            .cover-bottom { margin-top: 2cm; font-size: 14pt; font-weight: bold; line-height: 1.5; }
            .cover-inst { margin: 2pt 0; text-transform: uppercase; }
            .toc-title { text-align: center; font-size: 14pt; font-weight: bold; text-transform: uppercase; margin-bottom: 18pt; }
            CSS;
        } else {
            $base .= <<<'CSS'
            body { line-height: 1.45; }
            h1 { font-size: 18pt; margin: 0 0 10pt 0; }
            h2 { font-size: 14pt; margin: 14pt 0 6pt 0; }
            h3 { font-size: 12.5pt; margin: 10pt 0 5pt 0; }
            CSS;
        }

        return $base;
    }

    /**
     * Strip a leading YAML front-matter block so on-screen markdown previews don't
     * render the raw metadata. Used by the artifact preview blades.
     */
    public static function stripFrontMatter(string $content): string
    {
        return preg_replace('/^(?:\xEF\xBB\xBF)?\s*---\r?\n.*?\r?\n---[ \t]*\r?\n/s', '', $content, 1) ?? $content;
    }

    private function tempDir(): string
    {
        $dir = storage_path('app/mpdf-tmp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }
}
