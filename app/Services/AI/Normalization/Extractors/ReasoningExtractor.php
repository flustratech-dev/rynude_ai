<?php

namespace App\Services\AI\Normalization\Extractors;

use App\Services\AI\DTO\ReasoningTrace;

class ReasoningExtractor
{
    public function extract(string $text): array
    {
        $pattern = '/<thinking>([\s\S]*?)<\/thinking>/i';
        
        if (preg_match($pattern, $text, $matches)) {
            $fullMatch = $matches[0];
            $content = trim($matches[1]);
            
            $reasoning = new ReasoningTrace(text: $content);
            $cleanText = str_replace($fullMatch, '', $text);
            
            return ['reasoning' => $reasoning, 'cleanText' => trim($cleanText)];
        }
        
        return ['reasoning' => null, 'cleanText' => $text];
    }
}
