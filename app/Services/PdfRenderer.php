<?php

namespace App\Services;

use App\Services\Concerns\BuildsDocumentContent;
use Mpdf\Mpdf;

/**
 * Renders a document artifact (markdown, optionally with YAML front-matter) into a
 * polished PDF using mPDF. Supports several layout modes driven by front-matter:
 *
 *   mode: skripsi | laporan   → full academic layout (cover page, auto Table of
 *                               Contents, Roman→Arabic page numbering, 4-3-3-3 cm
 *                               margins, Times-like 12pt, justified).
 *   mode: jurnal              → 2-column journal article layout.
 *   mode: document (default)  → clean general document (2.5 cm margins, simple
 *                               centered page numbers, no cover/TOC).
 *
 * Shared logic (markdown→HTML, front-matter parsing, font/margin/spacing
 * resolution, image path rewriting) lives in {@see BuildsDocumentContent} so
 * the DOCX renderer produces a matching layout from the same artifact.
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
        $mode = in_array($mode, ['skripsi', 'laporan', 'jurnal', 'document'], true) ? $mode : 'document';

        $html = $this->resolveImages($html);

        return $this->buildPdf($title, $html, $meta, $mode);
    }

    /**
     * Build an HTML preview string mimicking the PDF document layout.
     * Can be embedded in an iframe via srcdoc.
     */
    public function renderHtml(array $artifact, ?string $modeOverride = null): string
    {
        $title = $artifact['title'] ?? 'Document';
        $raw = (string) ($artifact['content'] ?? '');
        $language = strtolower($artifact['language'] ?? '');
        $type = $artifact['type'] ?? 'text';

        if (($type === 'code' && ! in_array($language, ['markdown', 'md', ''], true)) && $language !== 'html') {
            return '<pre>' . htmlspecialchars($raw) . '</pre>';
        }

        if ($language === 'html') {
            return $this->buildHtml($title, $raw, $this->metaDefaults('document'), 'document');
        }

        [$html, $meta] = $this->markdownToHtml($raw);

        $mode = $modeOverride ?: ($meta['mode'] ?? 'document');
        $mode = in_array($mode, ['skripsi', 'laporan', 'jurnal', 'document'], true) ? $mode : 'document';

        $html = $this->resolveImages($html);

        return $this->buildHtml($title, $html, $meta, $mode);
    }

    /**
     * Assemble the final PDF for a document/skripsi/laporan/jurnal layout.
     */
    private function buildPdf(string $title, string $bodyHtml, array $meta, string $mode): string
    {
        $academic = in_array($mode, ['skripsi', 'laporan'], true);
        $isJurnal = $mode === 'jurnal';
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
        // Auto-shrink any table wider than the printable area so nothing spills off
        // the page edge (wide data tables in a skripsi/laporan still fit).
        $mpdf->shrink_tables_to_fit = 1;

        $css = $this->css($academic, $fmt, $mode);
        $pageNum = $this->pageNumberMarkup($fmt['pageNumber']);

        if ($academic) {
            // Collect TOC + bookmarks automatically from headings.
            $mpdf->h2toc = ['H1' => 0, 'H2' => 1, 'H3' => 2];
            $mpdf->h2bookmarks = ['H1' => 0, 'H2' => 1, 'H3' => 2];

            // Split the document into the three academic regions so the front matter
            // sits in standard skripsi order with Roman page numbers while the
            // chapters restart at Arabic 1:
            //   pengesahan → BEFORE the DAFTAR ISI (Roman i…)
            //   mid        → ABSTRAK/ABSTRACT, AFTER the DAFTAR ISI (Roman, listed in TOC)
            //   chapters   → BAB I onward + DAFTAR PUSTAKA/LAMPIRAN (Arabic, restart at 1)
            [$pengesahanHtml, $midHtml, $chaptersHtml] = $this->splitAcademicBody($bodyHtml);

            // Lembar Pengesahan/Persetujuan signature tables render without borders.
            $signTables = function (string $html): string {
                return preg_replace_callback('/<table\b[^>]*>(.*?)<\/table>/is', function ($m) {
                    if (preg_match('/(?:Pembimbing|Penguji|Menyetujui|Mengetahui|NIP\.|NIDN\.)/i', $m[1])) {
                        return '<table class="no-border">' . $m[1] . '</table>';
                    }
                    return $m[0];
                }, $html) ?? $html;
            };
            $pengesahanHtml = $signTables($pengesahanHtml);
            $chaptersHtml = $signTables($chaptersHtml);

            // Each h1 starts on a fresh page; strip the single leading break so the
            // first item shares the page it opens on (no blank leading page).
            $pageBreakH1 = function (string $html): string {
                $html = preg_replace('/(?<!^)\s*<h1\b/i', '<pagebreak />$0', $html) ?? $html;
                return preg_replace('/^\s*<pagebreak \/>/', '', $html) ?? $html;
            };
            $midHtml = $pageBreakH1($midHtml);
            $chaptersHtml = $pageBreakH1($chaptersHtml);

            $hasPengesahan = trim(strip_tags($pengesahanHtml)) !== '';
            $hasMid = trim(strip_tags($midHtml)) !== '';

            $mpdf->WriteHTML('<style>' . $css . '</style>');

            // 1) Cover — unnumbered (written before any footer is set).
            $mpdf->WriteHTML($this->coverHtml($title, $meta));

            // 2) Halaman Pengesahan — placed BEFORE the DAFTAR ISI and left
            //    unnumbered, matching the common skripsi convention where the cover
            //    and pengesahan carry no page number and Roman numbering starts at
            //    the DAFTAR ISI. (mPDF numbers auto-TOC pages separately, so keeping
            //    pengesahan unnumbered is what yields a clean, contiguous sequence.)
            if ($hasPengesahan) {
                $mpdf->WriteHTML('<pagebreak />');
                $mpdf->WriteHTML('<div class="body">' . $pengesahanHtml . '</div>');
            }

            // 3) DAFTAR ISI starts the Roman sequence at i. With mid matter
            //    (ABSTRAK/ABSTRACT) the numbering continues Roman after the TOC;
            //    without it, the chapters follow immediately so reset to Arabic 1 here.
            $tocPre = htmlspecialchars('<div class="toc-title">DAFTAR ISI</div>', ENT_QUOTES);
            $tocAttrs = 'toc-preHTML="' . $tocPre . '" links="on" toc-resetpagenum="1" toc-pagenumstyle="i"';
            $tocAttrs .= $hasMid ? ' pagenumstyle="i"' : ' resetpagenum="1" pagenumstyle="1"';
            $mpdf->WriteHTML('<tocpagebreak ' . $tocAttrs . ' />');

            // Footer applied AFTER the TOC tag so the cover and pengesahan stay
            // unnumbered while the TOC (filled at output time) and the rest get numbers.
            $this->applyPageNumber($mpdf, $pageNum);

            // 4) ABSTRAK/ABSTRACT (Roman ii…, listed in TOC), then reset to Arabic.
            if ($hasMid) {
                $mpdf->WriteHTML('<div class="body">' . $midHtml . '</div>');
                $mpdf->WriteHTML('<pagebreak resetpagenum="1" pagenumstyle="1" />');
            }

            // 5) BAB I onward (Arabic page 1).
            $mpdf->WriteHTML('<div class="body">' . $chaptersHtml . '</div>');
        } elseif ($isJurnal) {
            // Jurnal/artikel mode: 2-column layout, no cover, no TOC.
            $this->applyPageNumber($mpdf, $pageNum);
            $mpdf->WriteHTML('<style>' . $css . '</style>');
            $mpdf->SetColumns(2, '', 5);
            $mpdf->WriteHTML('<div class="body">' . $bodyHtml . '</div>');
            $mpdf->SetColumns(0);
        } else {
            $this->applyPageNumber($mpdf, $pageNum);
            $mpdf->WriteHTML('<style>' . $css . '</style>');
            $mpdf->WriteHTML('<div class="body">' . $bodyHtml . '</div>');
        }

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function buildHtml(string $title, string $bodyHtml, array $meta, string $mode): string
    {
        $academic = in_array($mode, ['skripsi', 'laporan'], true);
        $fmt = $this->resolveFormat($meta, $academic);
        $css = $this->css($academic, $fmt, $mode);

        $docHtml = '';
        if ($academic) {
            $docHtml .= $this->coverHtml($title, $meta);
            // Replace <pagebreak /> with a visual page break div
            $bodyHtml = str_replace('<pagebreak />', '<div style="page-break-after: always; margin: 2cm 0; border-bottom: 1px dashed #ccc;"></div>', $bodyHtml);
        }

        $docHtml .= '<div class="body">' . $bodyHtml . '</div>';

        $mt = $fmt['margins'][0] ?? 25;
        $mr = $fmt['margins'][1] ?? 25;
        $mb = $fmt['margins'][2] ?? 25;
        $ml = $fmt['margins'][3] ?? 25;

        // Wrap it in a full HTML structure with a white paper container
        $fullHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>';
        $fullHtml .= '<style>
            body { background: transparent; margin: 0; padding: 2rem; display: flex; justify-content: center; }
            .paper {
                background: white;
                width: 210mm;
                min-height: 297mm;
                padding: ' . $mt . 'mm ' . $mr . 'mm ' . $mb . 'mm ' . $ml . 'mm;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
                box-sizing: border-box;
                overflow: hidden;
            }
            @media (max-width: 768px) {
                body { padding: 0; display: block; }
                .paper { width: 100%; min-height: 100vh; margin: 0; box-shadow: none; }
            }
            ' . $css . '
        </style>';

        $fullHtml .= '</head><body><div class="paper">' . $docHtml . '</div></body></html>';

        return $fullHtml;
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

    private function css(bool $academic, array $fmt, string $mode = 'document'): string
    {
        $font = $fmt['font'];
        $size = $fmt['fontSize'];
        $lh = $fmt['lineSpacing'];
        $align = $fmt['align'];
        // Heading sizes scale relative to the chosen body size.
        $h1 = $academic ? 16 : ($size + 6);
        $h2 = $academic ? 14 : ($size + 2);
        $h3 = $academic ? 12 : ($size + 0.5);

        $base = <<<CSS
        body { font-family: {$font}; font-size: {$size}pt; line-height: {$lh}; text-align: {$align}; color: #000; word-wrap: break-word; overflow-wrap: break-word; }
        /* Keep all content inside the page: wrap long words/URLs instead of overflowing. */
        h1, h2, h3, h4, h5, p, li, blockquote, figcaption, td, th { overflow-wrap: break-word; word-wrap: break-word; }
        .pf { font-family: {$font}; font-size: 11pt; color: #000; }
        h1, h2, h3, h4, h5 { font-family: {$font}; font-weight: bold; color: #000; }
        a { color: #000; text-decoration: none; word-break: break-all; }
        ul, ol { margin: 0 0 6pt 0; }
        li { margin-bottom: 2pt; }
        blockquote { border-left: none; padding-left: 1.27cm; color: #000; font-style: normal; margin: 8pt 0; line-height: 1.0; }
        table { width: 100%; border-collapse: collapse; margin: 12pt 0; font-size: 10pt; overflow: wrap; }
        th, td { border: 0.6pt solid #000; padding: 5pt 7pt; text-align: left; vertical-align: top; overflow-wrap: break-word; word-break: break-word; }
        th { background: #efefef; font-weight: bold; }
        table.no-border, table.no-border th, table.no-border td { border: none !important; background: transparent !important; }
        table.no-border th, table.no-border td { text-align: center; vertical-align: bottom; }
        table.no-border p { text-indent: 0 !important; }
        img, svg { max-width: 100%; height: auto; }
        figure { text-align: center; margin: 12pt 0; }
        figure svg, figure img { max-width: 100%; height: auto; }
        figcaption { font-size: 10pt; text-align: center; margin-top: 4pt; }
        .table-caption { font-size: 10pt; text-align: center; margin-bottom: 4pt; font-weight: bold; }
        .daftar-pustaka p { text-indent: 0 !important; margin: 0 0 6pt 0; line-height: 1.0; }
        .daftar-pustaka ol { list-style: none; padding: 0; margin: 0; }
        .daftar-pustaka li { margin-bottom: 8pt; line-height: 1.0; text-indent: -1.27cm; padding-left: 1.27cm; }
        pre { background: #f4f4f4; padding: 8pt; border: 0.4pt solid #ddd; font-family: dejavusansmono; font-size: 9.5pt; white-space: pre-wrap; word-wrap: break-word; }
        code { font-family: dejavusansmono; font-size: 10pt; }
        CSS;

        if ($academic) {
            $base .= <<<CSS
            p { text-indent: 1.27cm; margin: 0; }
            li p, td p, blockquote p, figcaption { text-indent: 0; }
            h1 { font-size: {$h1}pt; text-align: center; text-transform: uppercase; margin: 0 0 18pt 0; }
            h2 { font-size: {$h2}pt; text-align: left; margin: 16pt 0 8pt 0; }
            h3 { font-size: {$h3}pt; text-align: left; margin: 12pt 0 6pt 0; }
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

        if ($mode === 'jurnal') {
            $base .= <<<CSS
            body { font-size: 10pt; line-height: 1.0; }
            p { text-indent: 0; margin: 0 0 4pt 0; }
            h1 { font-size: 12pt; text-align: center; text-transform: uppercase; margin: 0 0 8pt 0; }
            h2 { font-size: 11pt; text-align: left; margin: 10pt 0 4pt 0; }
            h3 { font-size: 10pt; text-align: left; margin: 8pt 0 3pt 0; }
            figcaption { font-size: 8pt; }
            .table-caption { font-size: 8pt; }
            table { font-size: 8pt; }
            .daftar-pustaka li { font-size: 8pt; }
            CSS;
        }

        return $base;
    }

    /**
     * Strip a leading YAML front-matter block so on-screen markdown previews don't
     * render the raw metadata. Kept as a public static helper for backward
     * compatibility — views and components reference it via this class path.
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
