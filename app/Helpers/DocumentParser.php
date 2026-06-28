<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class DocumentParser
{
    public static function parseText($filePath, $mimeType = null, $fileName = null)
    {
        // Attachments are uploaded via `->store('attachments', 'public')`, so the
        // relative path is anchored to the `public` disk (storage/app/public/...),
        // not the default `local` disk (storage/app/private/...). Resolving with
        // Storage::path() — which uses the default disk — used to point at a
        // non-existent file and silently return "", so PDFs reached the model
        // as `[Attachment: name.pdf]` with no body and got ignored.
        $absolutePath = Storage::disk('public')->path($filePath);
        if (!file_exists($absolutePath)) {
            $fallback = Storage::path($filePath);
            if (file_exists($fallback)) {
                $absolutePath = $fallback;
            } else {
                return "";
            }
        }
        
        $mimeType = $mimeType ?? mime_content_type($absolutePath);
        $fileName = $fileName ?? basename($filePath);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($absolutePath);
                return $pdf->getText();
            } catch (\Exception $e) {
                return "[Error reading PDF: " . $e->getMessage() . "]";
            }
        } elseif ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || $extension === 'docx') {
            try {
                $zip = new \ZipArchive();
                $text = "";
                if ($zip->open($absolutePath) === true) {
                    if (($index = $zip->locateName('word/document.xml')) !== false) {
                        $contentXml = $zip->getFromIndex($index);
                        $text = strip_tags(str_replace('</w:p>', "\n", $contentXml));
                    }
                    $zip->close();
                }
                return trim($text);
            } catch (\Exception $e) {
                return "[Error reading DOCX: " . $e->getMessage() . "]";
            }
        }
        
        // Fallback to raw text read
        return Storage::get($filePath);
    }
}
