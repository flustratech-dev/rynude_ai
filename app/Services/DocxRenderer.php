<?php

namespace App\Services;

use App\Services\Concerns\BuildsDocumentContent;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Renders a document artifact (markdown, optionally with YAML front-matter) into a
 * Word .docx using PHPWord. Mirrors {@see PdfRenderer} as closely as the Word layout
 * engine allows: it reuses the exact same markdown→HTML conversion, front-matter
 * parsing and formatting resolution (via {@see BuildsDocumentContent}) so a DOCX and
 * a PDF generated from the same artifact match in content, structure and formatting.
 *
 *   mode: skripsi | laporan   → cover page + Table of Contents + numbered body,
 *                               each BAB (h1) on a fresh page.
 *   mode: document (default)  → clean general document with simple page numbers.
 *
 * Engine limitations vs. the PDF (Word ≠ mPDF): inline <svg> diagrams are not
 * rendered, and front-matter page numbers are always Arabic (PHPWord cannot emit the
 * Roman `pgNumType w:fmt`). Headings, tables, lists, images, bold/italic, blockquotes
 * and code blocks all carry over.
 */
class DocxRenderer
{
    use BuildsDocumentContent;

    /** Internal (mPDF) font family → real Word font name. */
    private const FONT_MAP = [
        'freeserif' => 'Times New Roman',
        'freesans' => 'Arial',
        'freemono' => 'Courier New',
    ];

    private const MONO_FONT = 'Courier New';

    /**
     * Build a .docx binary string from an artifact array.
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
            return $this->buildDocx($title, $raw, $this->metaDefaults('document'), 'document');
        }

        // Markdown / text document path -----------------------------------
        [$html, $meta] = $this->markdownToHtml($raw);

        $mode = $modeOverride ?: ($meta['mode'] ?? 'document');
        $mode = in_array($mode, ['skripsi', 'laporan', 'document'], true) ? $mode : 'document';

        $html = $this->resolveImages($html);

        return $this->buildDocx($title, $html, $meta, $mode);
    }

    /**
     * Assemble the final DOCX for a document/skripsi/laporan layout.
     */
    private function buildDocx(string $title, string $bodyHtml, array $meta, string $mode): string
    {
        $academic = in_array($mode, ['skripsi', 'laporan'], true);
        $fmt = $this->resolveFormat($meta, $academic);
        $font = self::FONT_MAP[$fmt['font']] ?? 'Times New Roman';
        $size = $fmt['fontSize'];

        $phpWord = new PhpWord();
        $phpWord->getDocInfo()->setTitle($title);
        $phpWord->setDefaultFontName($font);
        $phpWord->setDefaultFontSize($size);
        $phpWord->setDefaultParagraphStyle([
            'alignment' => $this->wordAlign($fmt['align']),
            'lineHeight' => $fmt['lineSpacing'],
            'spaceBefore' => 0,
            'spaceAfter' => $academic ? 0 : 120, // 6pt, matching the PDF's document mode
        ]);

        // Black, chosen-font heading styles so Word doesn't fall back to its blue
        // built-in "Heading 1". Sizes mirror PdfRenderer::css().
        $h1 = $academic ? ($size + 2) : ($size + 6);
        $h2 = $academic ? $size : ($size + 2);
        $h3 = $academic ? $size : ($size + 0.5);
        $headingSizes = [1 => $h1, 2 => $h2, 3 => $h3, 4 => $size, 5 => $size, 6 => $size];
        foreach ($headingSizes as $depth => $hsize) {
            $phpWord->addTitleStyle(
                $depth,
                ['name' => $font, 'size' => $hsize, 'bold' => true, 'color' => '000000'],
                ['spaceBefore' => 160, 'spaceAfter' => 120, 'keepNext' => true]
            );
        }

        $bodyHtml = $this->stripSvg($bodyHtml);
        $pageNum = $fmt['pageNumber'];
        $margins = $this->marginTwips($fmt['margins']);

        if ($academic) {
            // 1) Cover page — its own section, carries no page number.
            $this->buildCover($phpWord, $title, $meta, $margins, $font, $size);

            // 2) Table of Contents — restarts page numbering. (Arabic: Word cannot
            //    emit Roman numerals via PHPWord; the field is populated on open.)
            $toc = $phpWord->addSection($this->sectionSettings($margins, 1));
            $this->applyPageNumber($toc, $pageNum, $font);
            $toc->addText('DAFTAR ISI', ['name' => $font, 'size' => $size + 2, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
            $toc->addTOC(['name' => $font, 'size' => $size], ['tabLeader' => \PhpOffice\PhpWord\Style\TOC::TAB_LEADER_DOT], 1, 3);

            // 3) Body — restarts numbering at 1; each BAB (h1) starts a fresh page.
            $body = $phpWord->addSection($this->sectionSettings($margins, 1));
            $this->applyPageNumber($body, $pageNum, $font);
            $this->addBodyWithChapterBreaks($body, $bodyHtml);
        } else {
            $section = $phpWord->addSection($this->sectionSettings($margins));
            $this->applyPageNumber($section, $pageNum, $font);
            Html::addHtml($section, $bodyHtml, false, false);
        }

        return $this->writeToString($phpWord);
    }

    /**
     * Section settings: A4, resolved margins, optional page-numbering restart.
     */
    private function sectionSettings(array $margins, ?int $pageNumberingStart = null): array
    {
        $settings = [
            'pageSizeW' => 11906, // A4 width in twips (210mm)
            'pageSizeH' => 16838, // A4 height in twips (297mm)
            'marginTop' => $margins[0],
            'marginRight' => $margins[1],
            'marginBottom' => $margins[2],
            'marginLeft' => $margins[3],
            'headerHeight' => 567,  // ~10mm
            'footerHeight' => 680,  // ~12mm
        ];
        if ($pageNumberingStart !== null) {
            $settings['pageNumberingStart'] = $pageNumberingStart;
        }

        return $settings;
    }

    /**
     * Add a {PAGE} field to the header or footer at the resolved position, or nothing
     * when page numbers are suppressed (page_number: none).
     */
    private function applyPageNumber(Section $section, array $pos, string $font): void
    {
        $loc = $pos['loc'] ?? 'F';
        if ($loc === '') {
            return;
        }
        $align = match ($pos['align']) {
            'left' => Jc::START,
            'right' => Jc::END,
            default => Jc::CENTER,
        };
        $container = $loc === 'H' ? $section->addHeader() : $section->addFooter();
        $container->addPreserveText('{PAGE}', ['name' => $font, 'size' => 11], ['alignment' => $align]);
    }

    /**
     * Render the academic body block-by-block: headings (h1–h3) become real Title
     * elements so they feed the Table of Contents, while everything else is rendered
     * via the HTML helper. A page break precedes every BAB (h1) except the first —
     * mirroring the <pagebreak> logic in PdfRenderer::buildPdf().
     */
    private function addBodyWithChapterBreaks(Section $section, string $bodyHtml): void
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root">' . $bodyHtml . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $dom->getElementById('__root');
        if (! $root) {
            Html::addHtml($section, $bodyHtml, false, false);
            return;
        }

        $seenChapter = false;
        foreach (iterator_to_array($root->childNodes) as $node) {
            if ($node->nodeType === XML_TEXT_NODE && trim($node->textContent) === '') {
                continue;
            }

            $tag = $node->nodeType === XML_ELEMENT_NODE ? strtolower($node->nodeName) : '';
            $depth = ['h1' => 1, 'h2' => 2, 'h3' => 3][$tag] ?? null;

            if ($depth !== null) {
                if ($depth === 1) {
                    if ($seenChapter) {
                        $section->addPageBreak();
                    }
                    $seenChapter = true;
                }
                $section->addTitle(trim($node->textContent), $depth);
                continue;
            }

            $html = $dom->saveHTML($node);
            if (trim((string) $html) !== '') {
                Html::addHtml($section, $html, false, false);
            }
        }
    }

    /**
     * Build the academic cover page (judul, SKRIPSI/LAPORAN block, logo, author,
     * institution). Mirrors PdfRenderer::coverHtml().
     */
    private function buildCover(PhpWord $phpWord, string $title, array $meta, array $margins, string $font, float $size): void
    {
        $section = $phpWord->addSection($this->sectionSettings($margins));
        $center = ['alignment' => Jc::CENTER];

        $judul = $meta['judul'] ?: $title;
        $section->addText(strtoupper($judul), ['name' => $font, 'size' => 14, 'bold' => true], array_merge($center, ['spaceBefore' => 567, 'spaceAfter' => 240]));

        if (($meta['mode'] ?? '') === 'skripsi') {
            $section->addText('SKRIPSI', ['name' => $font, 'size' => 14, 'bold' => true], array_merge($center, ['spaceBefore' => 480]));
            $section->addText('Diajukan sebagai salah satu syarat untuk memperoleh gelar Sarjana pada ' . ($meta['universitas'] ?? 'Universitas'), ['name' => $font, 'size' => 11], array_merge($center, ['spaceBefore' => 240]));
        } elseif (($meta['mode'] ?? '') === 'laporan') {
            $section->addText('LAPORAN', ['name' => $font, 'size' => 14, 'bold' => true], array_merge($center, ['spaceBefore' => 480]));
        }

        if (! empty($meta['logo'])) {
            $resolved = $this->resolveImagePath((string) $meta['logo']);
            if ($resolved) {
                $section->addImage($resolved, ['height' => 128, 'alignment' => Jc::CENTER, 'marginTop' => 240]);
            }
        }

        if (! empty($meta['penulis']) || ! empty($meta['nim'])) {
            $section->addText('Disusun Oleh:', ['name' => $font, 'size' => 12], array_merge($center, ['spaceBefore' => 480]));
            if (! empty($meta['penulis'])) {
                $section->addText(strtoupper((string) $meta['penulis']), ['name' => $font, 'size' => 12, 'bold' => true], array_merge($center, ['spaceBefore' => 120]));
            }
            if (! empty($meta['nim'])) {
                $section->addText('NIM. ' . $meta['nim'], ['name' => $font, 'size' => 12, 'bold' => true], $center);
            }
        }

        $inst = collect([
            $meta['prodi'] ? 'PROGRAM STUDI ' . strtoupper((string) $meta['prodi']) : null,
            $meta['fakultas'] ? strtoupper((string) $meta['fakultas']) : null,
            $meta['universitas'] ? strtoupper((string) $meta['universitas']) : null,
            $meta['kota'] ? strtoupper((string) $meta['kota']) : null,
            $meta['tahun'] ?: null,
        ])->filter();
        if ($inst->isNotEmpty()) {
            $first = true;
            foreach ($inst as $line) {
                $section->addText((string) $line, ['name' => $font, 'size' => 14, 'bold' => true], array_merge($center, $first ? ['spaceBefore' => 960] : []));
                $first = false;
            }
        }
    }

    /**
     * Render a pure code artifact as a monospaced listing — parallels
     * PdfRenderer::renderCode().
     */
    private function renderCode(string $title, string $code): string
    {
        $phpWord = new PhpWord();
        $phpWord->getDocInfo()->setTitle($title);
        $phpWord->setDefaultFontName(self::MONO_FONT);
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection($this->sectionSettings($this->marginTwips([25, 25, 25, 25])));
        $section->addFooter()->addPreserveText('{PAGE}', ['size' => 9], ['alignment' => Jc::CENTER]);

        $fontStyle = ['name' => self::MONO_FONT, 'size' => 9];
        $paraStyle = ['lineHeight' => 1.4, 'spaceAfter' => 0, 'spaceBefore' => 0];
        foreach (preg_split('/\r\n|\r|\n/', $code) as $line) {
            if ($line === '') {
                $section->addTextBreak();
                continue;
            }
            $section->addText(htmlspecialchars($line, ENT_QUOTES), $fontStyle, $paraStyle);
        }

        return $this->writeToString($phpWord);
    }

    private function wordAlign(string $align): string
    {
        return match ($align) {
            'justify' => Jc::BOTH,
            'right' => Jc::END,
            'center' => Jc::CENTER,
            default => Jc::START,
        };
    }

    /** Convert the resolved [top,right,bottom,left] margins (mm) to twips. */
    private function marginTwips(array $margins): array
    {
        return array_map(fn ($mm) => (int) round($mm * 56.6929), $margins);
    }

    /** Strip inline <svg> blocks the Word HTML parser can't render. */
    private function stripSvg(string $html): string
    {
        return preg_replace('/<svg\b[^>]*>.*?<\/svg>/is', '', $html) ?? $html;
    }

    private function writeToString(PhpWord $phpWord): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        try {
            IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
            return (string) file_get_contents($tmp);
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }
}
