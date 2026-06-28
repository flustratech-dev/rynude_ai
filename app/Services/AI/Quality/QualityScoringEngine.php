<?php

namespace App\Services\AI\Quality;

use App\Services\AI\DTO\NormalizedOutput;
use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\ModelAdapter;
use App\Services\AI\Normalization\OutputNormalizer;
use App\Services\AI\Quality\DTO\QualityScore;

class QualityScoringEngine implements QualityEvaluatorInterface
{
    public function __construct(
        private ModelAdapter $adapter,
        private OutputNormalizer $normalizer
    ) {}

    public function evaluate(NormalizedOutput $output, int $threshold = 85): QualityScore
    {
        $prompt = <<<MD
You are a Quality Evaluator for RYNUDE V5.
Your task is to evaluate the following output based on Accuracy, Completeness, Consistency, and Formatting.
Score each category from 0 to 100.
Provide improvement feedback.

Output to evaluate:
```
{$output->visibleText}
```

You MUST output your evaluation in a JSON artifact.
Example:
<antigravity-artifact identifier="evaluation" type="application/json" language="json" title="Evaluation">
{
    "accuracyScore": 90,
    "completenessScore": 85,
    "consistencyScore": 95,
    "formattingScore": 100,
    "feedback": "Your feedback here."
}
</antigravity-artifact>
MD;

        $request = new NormalizedRequest(
            messages: [['role' => 'user', 'content' => $prompt]],
            systemPrompt: "You are a strict quality scoring engine. Evaluate objectively and rigorously."
        );

        $stream = $this->adapter->streamCompletion($request);
        $rawText = '';
        foreach ($stream as $event) {
            if ($event->type === 'text') {
                $rawText .= $event->payload['text'];
            }
        }

        $evalOutput = $this->normalizer->normalize($rawText);

        $data = [];
        if ($evalOutput->artifact) {
            $data = json_decode($evalOutput->artifact->content, true) ?? [];
        } else {
            // Attempt to parse JSON from the visible text directly if artifact extraction failed
            preg_match('/\{.*\}/s', $evalOutput->visibleText, $matches);
            if (!empty($matches)) {
                $data = json_decode($matches[0], true) ?? [];
            }
        }

        $accuracy = (int) ($data['accuracyScore'] ?? 0);
        $completeness = (int) ($data['completenessScore'] ?? 0);
        $consistency = (int) ($data['consistencyScore'] ?? 0);
        $formatting = (int) ($data['formattingScore'] ?? 0);
        $feedback = $data['feedback'] ?? 'No feedback provided.';

        // Weighted average (currently equal weights as per requirements)
        $overall = (int) round(($accuracy + $completeness + $consistency + $formatting) / 4);

        $status = $overall >= $threshold ? 'PASSED' : 'REQUIRES_IMPROVEMENT';

        return new QualityScore(
            overallScore: $overall,
            accuracyScore: $accuracy,
            completenessScore: $completeness,
            consistencyScore: $consistency,
            formattingScore: $formatting,
            feedback: $feedback,
            status: $status
        );
    }
}
