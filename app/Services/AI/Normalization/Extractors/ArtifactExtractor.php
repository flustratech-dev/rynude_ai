<?php

namespace App\Services\AI\Normalization\Extractors;

use App\Services\AI\DTO\ArtifactDto;

class ArtifactExtractor
{
    public function extract(string $text): array
    {
        $pattern = '/<antigravity-artifact(?:[^>]*)>([\s\S]*?)<\/antigravity-artifact>/i';
        
        if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            $fullMatch = $matches[0][0];
            $content = $matches[1][0];
            
            // Extract attributes from the opening tag
            $openingTagPattern = '/<antigravity-artifact([^>]*)>/i';
            preg_match($openingTagPattern, $fullMatch, $tagMatches);
            $attributesString = $tagMatches[1] ?? '';
            
            $identifier = $this->getAttribute('identifier', $attributesString) ?? 'default';
            $type = $this->getAttribute('type', $attributesString) ?? 'text/plain';
            $language = $this->getAttribute('language', $attributesString) ?? 'text';
            $title = $this->getAttribute('title', $attributesString) ?? 'Artifact';
            
            $artifact = new ArtifactDto(
                identifier: $identifier,
                type: $type,
                language: $language,
                title: $title,
                content: trim($content)
            );
            
            $cleanText = str_replace($fullMatch, '', $text);
            
            return ['artifact' => $artifact, 'cleanText' => trim($cleanText)];
        }
        
        return ['artifact' => null, 'cleanText' => $text];
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
