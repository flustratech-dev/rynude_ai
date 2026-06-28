<?php

namespace App\Services\AI\Quality;

use App\Services\AI\DTO\NormalizedOutput;
use App\Services\AI\Quality\DTO\QualityScore;

interface QualityEvaluatorInterface
{
    public function evaluate(NormalizedOutput $output, int $threshold = 85): QualityScore;
}
