<?php

namespace App\Services\AI\Normalization\Extractors;

use App\Services\AI\DTO\CitationDto;

class CitationExtractor
{
    public function extract(string $text): array
    {
        $pattern = '/<cite(?:[^>]*)>([\s\S]*?)<\/cite>/i';
        $citations = [];
        $cleanText = $text;
        
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullMatch = $match[0];
                $content = $match[1];
                
                $openingTagPattern = '/<cite([^>]*)>/i';
                preg_match($openingTagPattern, $fullMatch, $tagMatches);
                $attributesString = $tagMatches[1] ?? '';
                
                $url = $this->getAttribute('url', $attributesString);
                $title = $this->getAttribute('title', $attributesString);
                
                $citations[] = new CitationDto(
                    text: trim($content),
                    url: $url,
                    title: $title
                );
                
                $cleanText = str_replace($fullMatch, trim($content), $cleanText);
            }
        }
        
        return ['citations' => $citations, 'cleanText' => trim($cleanText)];
    }
    
    private function getAttribute(string $name, string $attributesString): ?string
    {
        $pattern = '/' . $name . '="([^"]*)"/i';
        if (preg_match($pattern, $attributesString, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
