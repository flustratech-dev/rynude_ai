<?php

namespace App\Services;

use App\Services\Concerns\BuildsDocumentContent;
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
    use BuildsDocumentContent;

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
        // Keep the chosen font: do NOT auto-substitute fonts by detected language —
        // that silently replaced the body with DejaVu Serif instead of Times-like
        // FreeSerif. The Free* families already cover Latin + Greek + maths.
        $mpdf->autoScriptToLang = false;
        $mpdf->autoLangToFont = false;

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

    private function css(bool $academic, array $fmt): string
    {
        $font = $fmt['font'];
        $size = $fmt['fontSize'];
        $lh = $fmt['lineSpacing'];
        $align = $fmt['align'];
        // Heading sizes scale relative to the chosen body size.
        $h1 = $academic ? ($size + 2) : ($size + 6);
        $h2 = $academic ? $size : ($size + 2);
        $h3 = $academic ? $size : ($size + 0.5);

        $base = <<<CSS
        body { font-family: {$font}; font-size: {$size}pt; line-height: {$lh}; text-align: {$align}; color: #000; }
        .pf { font-family: {$font}; font-size: 11pt; color: #000; }
        h1, h2, h3, h4, h5 { font-family: {$font}; font-weight: bold; color: #000; }
        a { color: #000; text-decoration: none; }
        ul, ol { margin: 0 0 6pt 0; }
        li { margin-bottom: 2pt; }
        blockquote { border-left: 3px solid #999; padding-left: 10pt; color: #333; font-style: italic; margin: 8pt 0; }
        table { width: 100%; border-collapse: collapse; margin: 12pt 0; font-size: {$h3}pt; }
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
            $base .= <<<CSS
            p { text-indent: 1.27cm; margin: 0; }
            li p, td p, blockquote p, figcaption { text-indent: 0; }
            h1 { font-size: {$h1}pt; text-align: center; text-transform: uppercase; margin: 0 0 18pt 0; }
            h2 { font-size: {$h2}pt; margin: 16pt 0 8pt 0; }
            h3 { font-size: {$h3}pt; margin: 12pt 0 6pt 0; }
            .cover { text-align: center; }
            .cover-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; line-height: 1.5; margin-top: 1cm; }
            .cover-logo { margin: 1cm 0; }
            .cover-meta { margin-top: 1.5cm; font-size: 12pt; }
            .cover-line { margin: 3pt 0; }
            .cover-bottom { margin-top: 2cm; font-size: 14pt; font-weight: bold; line-height: 1.5; }
            .cover-inst { margin: 2pt 0; text-transform: uppercase; }
            .toc-title { text-align: center; font-size: 14pt; font-weight: bold; text-transform: uppercase; margin-bottom: 18pt; }
            .mpdf_toc, .mpdf_toc_a, .mpdf_toc_level_0, .mpdf_toc_level_1, .mpdf_toc_level_2,
            .mpdf_toc_t_level_0, .mpdf_toc_t_level_1, .mpdf_toc_t_level_2,
            .mpdf_toc_p_level_0, .mpdf_toc_p_level_1, .mpdf_toc_p_level_2 { font-family: {$font}; font-size: {$size}pt; }
            CSS;
        } else {
            $base .= <<<CSS
            p { margin: 0 0 6pt 0; }
            h1 { font-size: {$h1}pt; margin: 0 0 10pt 0; }
            h2 { font-size: {$h2}pt; margin: 14pt 0 6pt 0; }
            h3 { font-size: {$h3}pt; margin: 10pt 0 5pt 0; }
            CSS;
        }

        return $base;
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
