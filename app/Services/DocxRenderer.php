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
        // Render mermaid diagrams to images (Kroki) for the downloadable DOCX only.
        $raw = $this->renderMermaidBlocks($raw);
        [$html, $meta] = $this->markdownToHtml($raw);

        $mode = $modeOverride ?: ($meta['mode'] ?? 'document');
        $mode = in_array($mode, ['skripsi', 'tesis', 'laporan', 'makalah', 'proposal', 'document'], true) ? $mode : 'document';

        $html = $this->resolveImages($html);

        return $this->buildDocx($title, $html, $meta, $mode);
    }

    /**
     * Assemble the final DOCX for a document/skripsi/laporan layout.
     */
    private function buildDocx(string $title, string $bodyHtml, array $meta, string $mode): string
    {
        $academic = in_array($mode, ['skripsi', 'tesis', 'laporan', 'makalah', 'proposal'], true);
        $fmt = $this->resolveFormat($meta, $academic);
        $font = self::FONT_MAP[$fmt['font']] ?? 'Times New Roman';
        $size = $fmt['fontSize'];

        // Tell Word to recalculate all fields (TOC, page numbers) the first time
        // the user opens the document — without this the TOC stays empty until
        // someone presses F9 / "Update Field". This is global PHPWord settings,
        // not per-document, but it is idempotent.
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        if (method_exists(\PhpOffice\PhpWord\Settings::class, 'setUpdateFields')) {
            \PhpOffice\PhpWord\Settings::setUpdateFields(true);
        }

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
        // built-in "Heading 1". Sizes mirror PdfRenderer::css(). Academic h1 is
        // ALL CAPS to match the PDF's text-transform:uppercase on chapter titles.
        $h1 = $academic ? ($size + 2) : ($size + 6);
        $h2 = $academic ? $size : ($size + 2);
        $h3 = $academic ? $size : ($size + 0.5);
        $headingSizes = [1 => $h1, 2 => $h2, 3 => $h3, 4 => $size, 5 => $size, 6 => $size];
        foreach ($headingSizes as $depth => $hsize) {
            $fontStyle = ['name' => $font, 'size' => $hsize, 'bold' => true, 'color' => '000000'];
            $paragraphStyle = ['spaceBefore' => 160, 'spaceAfter' => 120, 'keepNext' => true];
            if ($academic && $depth === 1) {
                $fontStyle['allCaps'] = true;
                $paragraphStyle['alignment'] = Jc::CENTER;
            }
            $phpWord->addTitleStyle($depth, $fontStyle, $paragraphStyle);
        }

        // Track SVG-derived PNGs so we can unlink them after the Word file is written.
        $tempPngs = [];
        $bodyHtml = $this->convertSvgToImages($bodyHtml, $tempPngs);
        $pageNum = $fmt['pageNumber'];
        $margins = $this->marginTwips($fmt['margins']);

        $sectionFormats = [];
        if ($academic) {
            // Standard skripsi order:
            //   COVER → HALAMAN PENGESAHAN → DAFTAR ISI → ABSTRAK/ABSTRACT → BAB I…
            // Split the body so the front matter lands before/after the TOC exactly
            // like the PDF. Page numbering: cover & pengesahan unnumbered, the TOC +
            // ABSTRAK are Roman (i, ii…), the chapters restart at Arabic 1.
            [$pengesahanHtml, $midHtml, $chaptersHtml] = $this->splitAcademicBody($bodyHtml);
            $hasPengesahan = trim(strip_tags($pengesahanHtml)) !== '';
            $hasMid = trim(strip_tags($midHtml)) !== '';

            $roman = [];
            $arabic = [];
            $idx = 0;

            // 1) Cover — own section, no page number.
            $this->buildCover($phpWord, $title, $meta, $margins, $font, $size);
            $idx++;

            // 2) Halaman Pengesahan — BEFORE the DAFTAR ISI, unnumbered. The heading
            //    is plain centered bold text (not a Title) so it is not pulled into
            //    the DAFTAR ISI with a misleading page number.
            if ($hasPengesahan) {
                $peng = $phpWord->addSection($this->sectionSettings($margins));
                $headingText = preg_match('/<h1\b[^>]*>(.*?)<\/h1>/is', $pengesahanHtml, $hm)
                    ? trim(html_entity_decode(strip_tags($hm[1]))) : 'HALAMAN PENGESAHAN';
                $rest = preg_replace('/^\s*<h1\b[^>]*>.*?<\/h1>/is', '', $pengesahanHtml, 1);
                $peng->addText(strtoupper($headingText), ['name' => $font, 'size' => $size + 2, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
                if (trim(strip_tags((string) $rest)) !== '') {
                    Html::addHtml($peng, $this->sanitizeHtmlForPhpWord((string) $rest), false, false);
                }
                $idx++;
            }

            // 3) DAFTAR ISI — Roman numbering starting at i (format applied as a
            //    post-process; PHPWord only emits w:start natively).
            $toc = $phpWord->addSection($this->sectionSettings($margins, 1));
            $this->applyPageNumber($toc, $pageNum, $font);
            $toc->addText('DAFTAR ISI', ['name' => $font, 'size' => $size + 2, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
            $toc->addTOC(['name' => $font, 'size' => $size], ['tabLeader' => \PhpOffice\PhpWord\Style\TOC::TAB_LEADER_DOT], 1, 3);
            $idx++;
            $roman[] = $idx;

            // 4) ABSTRAK / ABSTRACT — Roman, continues after the TOC, listed in TOC.
            if ($hasMid) {
                $mid = $phpWord->addSection($this->sectionSettings($margins));
                $this->applyPageNumber($mid, $pageNum, $font);
                $this->addBodyWithChapterBreaks($mid, $midHtml);
                $idx++;
                $roman[] = $idx;
            }

            // 5) BAB I onward — Arabic, restarts at 1; each BAB (h1) on a fresh page.
            $body = $phpWord->addSection($this->sectionSettings($margins, 1));
            $this->applyPageNumber($body, $pageNum, $font);
            $this->addBodyWithChapterBreaks($body, $chaptersHtml);
            $idx++;
            $arabic[] = $idx;

            foreach ($roman as $s) {
                $sectionFormats[$s] = 'lowerRoman';
            }
            foreach ($arabic as $s) {
                $sectionFormats[$s] = 'decimal';
            }
        } else {
            $section = $phpWord->addSection($this->sectionSettings($margins));
            $this->applyPageNumber($section, $pageNum, $font);
            Html::addHtml($section, $this->sanitizeHtmlForPhpWord($bodyHtml), false, false);
        }

        $output = $this->writeToString($phpWord);

        // Skripsi / laporan: rewrite the front-matter sections' page-number format
        // to Roman and the body to Arabic. PHPWord's section writer only emits
        // <w:pgNumType w:start="…"/> — never the format — so we post-process the XML.
        // $sectionFormats maps 1-indexed section → w:fmt, computed above to account
        // for the optional pengesahan / abstrak sections.
        if ($academic && ! empty($sectionFormats)) {
            $output = $this->applyRomanTocNumbering($output, $sectionFormats);
        }

        // Clean up rasterized SVG PNGs now that they're embedded in the .docx.
        foreach ($tempPngs as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return $output;
    }

    /**
     * Post-process a generated DOCX binary so the front-matter sections (DAFTAR ISI
     * and ABSTRAK) use Roman numerals (i, ii, iii…) and the body resets to Arabic.
     * This is the standard skripsi pagination convention and cannot be expressed
     * through PHPWord's public API as of v1.4.
     *
     * @param array<int,string> $sectionFormats 1-indexed section → w:fmt value
     */
    private function applyRomanTocNumbering(string $docxBinary, array $sectionFormats): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx-pp');
        if ($tmp === false) {
            return $docxBinary;
        }
        @file_put_contents($tmp, $docxBinary);

        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            return $docxBinary;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            @unlink($tmp);
            return $docxBinary;
        }

        // The sectPr elements appear in document order (1=cover, then pengesahan/
        // toc/abstrak/body as present); $sectionFormats maps each to its w:fmt.
        $modifiedXml = $this->rewritePgNumType($xml, $sectionFormats);

        if ($modifiedXml !== $xml) {
            $zip->deleteName('word/document.xml');
            $zip->addFromString('word/document.xml', $modifiedXml);
        }
        $zip->close();

        $patched = @file_get_contents($tmp);
        @unlink($tmp);
        return $patched !== false ? $patched : $docxBinary;
    }

    /**
     * Replace (or insert) `<w:pgNumType>` inside the Nth `<w:sectPr>` of a
     * document.xml. The numbering format is set to the requested `w:fmt`,
     * preserving any existing `w:start` value. Uses string parsing (no DOM)
     * because the namespace declarations make XPath finicky and a regex over
     * the well-defined sectPr block is robust enough.
     *
     * @param array<int,string> $sectionFormats 1-indexed section → fmt value
     */
    private function rewritePgNumType(string $xml, array $sectionFormats): string
    {
        if (! preg_match_all('/<w:sectPr\b[^>]*>.*?<\/w:sectPr>/s', $xml, $matches, PREG_OFFSET_CAPTURE)) {
            return $xml;
        }

        // Walk matches from the END so byte offsets stay valid as we splice.
        $sectionCount = count($matches[0]);
        for ($i = $sectionCount - 1; $i >= 0; $i--) {
            $sectionIndex = $i + 1; // 1-indexed
            if (! isset($sectionFormats[$sectionIndex])) {
                continue;
            }
            $fmt = $sectionFormats[$sectionIndex];
            $block = $matches[0][$i][0];
            $offset = $matches[0][$i][1];

            // Existing pgNumType: keep w:start (if any), set/override w:fmt.
            if (preg_match('/<w:pgNumType\b[^\/>]*\/>/', $block, $pm, PREG_OFFSET_CAPTURE)) {
                $oldTag = $pm[0][0];
                $start = '';
                if (preg_match('/w:start="[^"]*"/', $oldTag, $sm)) {
                    $start = ' ' . $sm[0];
                }
                $newTag = '<w:pgNumType w:fmt="' . $fmt . '"' . $start . '/>';
                $newBlock = str_replace($oldTag, $newTag, $block);
            } else {
                // No pgNumType — insert one just before </w:sectPr>. Omit w:start so
                // the section CONTINUES the running count (e.g. ABSTRAK following the
                // DAFTAR ISI becomes ii, iii — not a second i). Sections that must
                // restart are added with pageNumberingStart, so they already carry a
                // w:start handled by the branch above.
                $insert = '<w:pgNumType w:fmt="' . $fmt . '"/>';
                $newBlock = str_replace('</w:sectPr>', $insert . '</w:sectPr>', $block);
            }

            $xml = substr($xml, 0, $offset) . $newBlock . substr($xml, $offset + strlen($block));
        }

        return $xml;
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
     * Sanitize HTML to be valid XML for PHPWord's parser. Converts self-closing
     * tags like <br> to <br/>, ensures proper nesting, and removes malformed markup.
     */
    private function sanitizeHtmlForPhpWord(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Parse as HTML (permissive) then re-serialize as XHTML (strict)
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return $html;
        }

        // Save as XML (not HTML) to get proper self-closing tags
        $cleaned = '';
        foreach ($body->childNodes as $node) {
            $cleaned .= $dom->saveXML($node);
        }

        return $cleaned;
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
            Html::addHtml($section, $this->sanitizeHtmlForPhpWord($bodyHtml), false, false);
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
                Html::addHtml($section, $this->sanitizeHtmlForPhpWord($html), false, false);
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

        // Per-type banner + subtitle, mirroring PdfRenderer::coverHtml() (issue #2).
        $univ = $meta['universitas'] ?? 'Universitas';
        $banner = function (string $label, string $subtitle = '') use ($section, $font, $center) {
            $section->addText($label, ['name' => $font, 'size' => 14, 'bold' => true], array_merge($center, ['spaceBefore' => 480]));
            if ($subtitle !== '') {
                $section->addText($subtitle, ['name' => $font, 'size' => 11], array_merge($center, ['spaceBefore' => 240]));
            }
        };
        switch ($meta['mode'] ?? '') {
            case 'skripsi':
                $banner('SKRIPSI', 'Diajukan sebagai salah satu syarat untuk memperoleh gelar Sarjana pada ' . $univ);
                break;
            case 'tesis':
                $banner('TESIS', 'Diajukan sebagai salah satu syarat untuk memperoleh gelar Magister pada ' . $univ);
                break;
            case 'proposal':
                $banner('PROPOSAL PENELITIAN', 'Diajukan untuk memenuhi persyaratan penyusunan skripsi pada ' . $univ);
                break;
            case 'makalah':
                $banner('MAKALAH', ($meta['dosen'] ?? '') !== ''
                    ? 'Diajukan untuk memenuhi tugas mata kuliah — Dosen Pengampu: ' . $meta['dosen']
                    : 'Diajukan untuk memenuhi tugas mata kuliah pada ' . $univ);
                break;
            case 'laporan':
                $banner('LAPORAN');
                break;
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

    /**
     * Convert inline `<svg>…</svg>` blocks to `<img src="…png">` so PHPWord's
     * HTML parser can embed them. Tries Imagick first (PHP extension), then the
     * `rsvg-convert` CLI binary; if neither is available the block is replaced
     * with a styled placeholder so the user knows a diagram was present (and
     * not silently dropped, which was the old behaviour).
     *
     * Generated PNGs live in storage/app/docx-tmp and are cleaned up after the
     * Word document is written ({@see writeToString}).
     *
     * @param array<int,string> &$cleanupPaths Receives the temp paths so the
     *        caller can unlink them after the render is complete.
     */
    private function convertSvgToImages(string $html, array &$cleanupPaths): string
    {
        return preg_replace_callback('/<svg\b[^>]*>.*?<\/svg>/is', function ($m) use (&$cleanupPaths) {
            $svg = $m[0];
            // mPDF doesn't require an xmlns; the rasterizers DO.
            if (! preg_match('/\bxmlns\s*=/', $svg)) {
                $svg = preg_replace('/<svg\b/', '<svg xmlns="http://www.w3.org/2000/svg"', $svg, 1);
            }

            $pngPath = $this->rasterizeSvg($svg);
            if ($pngPath === null) {
                // Both rasterizers unavailable — emit a placeholder rather than
                // silently dropping the diagram (the old stripSvg behaviour).
                return '<p style="text-align:center;font-style:italic;color:#666;">[Diagram tidak dapat dirender di Word — tersedia di versi PDF]</p>';
            }
            $cleanupPaths[] = $pngPath;
            // PHPWord Html parser accepts absolute file paths in src.
            return '<p style="text-align:center;"><img src="' . htmlspecialchars($pngPath, ENT_QUOTES) . '" /></p>';
        }, $html) ?? $html;
    }

    /**
     * Rasterize a single SVG string to a PNG file. Returns the absolute path
     * or null if no rasterizer is available / conversion failed.
     */
    private function rasterizeSvg(string $svg): ?string
    {
        $dir = storage_path('app/docx-tmp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $base = $dir . DIRECTORY_SEPARATOR . 'svg-' . uniqid('', true);
        $svgPath = $base . '.svg';
        $pngPath = $base . '.png';
        if (@file_put_contents($svgPath, $svg) === false) {
            return null;
        }

        // 1) Imagick (PHP extension) — preferred, runs in-process.
        if (class_exists('Imagick')) {
            try {
                $im = new \Imagick();
                $im->setBackgroundColor(new \ImagickPixel('transparent'));
                $im->readImageBlob($svg);
                $im->setImageFormat('png32');
                // Reasonable raster size for an embedded figure (~600px wide).
                $width = $im->getImageWidth() ?: 600;
                if ($width < 600) {
                    $scale = 600 / $width;
                    $im->resizeImage((int) ($width * $scale), 0, \Imagick::FILTER_LANCZOS, 1);
                }
                $im->writeImage($pngPath);
                $im->destroy();
                @unlink($svgPath);
                return is_file($pngPath) ? $pngPath : null;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Imagick SVG→PNG failed: ' . $e->getMessage());
            }
        }

        // 2) rsvg-convert CLI — common on Linux servers.
        $rsvg = $this->findBinary('rsvg-convert');
        if ($rsvg !== null) {
            $cmd = escapeshellcmd($rsvg) . ' -f png -w 600 -o ' . escapeshellarg($pngPath) . ' ' . escapeshellarg($svgPath) . ' 2>&1';
            @exec($cmd, $out, $code);
            @unlink($svgPath);
            if ($code === 0 && is_file($pngPath)) {
                return $pngPath;
            }
            \Illuminate\Support\Facades\Log::warning('rsvg-convert failed (' . $code . '): ' . implode("\n", $out));
        }

        @unlink($svgPath);
        return null;
    }

    /** Locate an executable in PATH (cross-platform). */
    private function findBinary(string $name): ?string
    {
        $isWindows = strncasecmp(PHP_OS, 'WIN', 3) === 0;
        $command = $isWindows ? "where {$name}" : "command -v {$name}";
        @exec($command . ' 2>&1', $out, $code);
        if ($code !== 0 || empty($out)) {
            return null;
        }
        $path = trim($out[0]);
        return $path !== '' && is_file($path) ? $path : null;
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
