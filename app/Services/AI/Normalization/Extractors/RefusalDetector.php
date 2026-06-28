<?php

namespace App\Services\AI\Normalization\Extractors;

class RefusalDetector
{
    private array $patterns = [
        "I cannot fulfill this request",
        "I can't fulfill this request",
        "I'm sorry, but I can't",
        "I am sorry, but I cannot",
        "I apologize, but I cannot",
        "As an AI language model, I cannot",
        "As an AI, I am unable",
        "I'm unable to assist",
        "I cannot assist with",
    ];

    public function detect(string $text): bool
    {
        foreach ($this->patterns as $pattern) {
            if (stripos($text, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}
