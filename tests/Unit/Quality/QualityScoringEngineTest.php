<?php

namespace Tests\Unit\Quality;

use App\Services\AI\DTO\ArtifactDto;
use App\Services\AI\DTO\NormalizedOutput;
use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\Events\NormalizedEvent;
use App\Services\AI\Normalization\ModelAdapter;
use App\Services\AI\Normalization\ModelCapability;
use App\Services\AI\Normalization\OutputNormalizer;
use App\Services\AI\Quality\QualityScoringEngine;
use PHPUnit\Framework\TestCase;

class QualityScoringEngineTest extends TestCase
{
    public function test_evaluates_output_and_calculates_scores_properly(): void
    {
        $mockAdapter = new class ('mock-model') extends ModelAdapter {
            public function capabilities(): ModelCapability
            {
                return new ModelCapability(supportsSystemPrompts: true, supportsToolUse: true, supportsExtendedThinking: false, isReasoningModel: false, supportsVision: false);
            }

            public function streamCompletion(NormalizedRequest $req): \Generator
            {
                $json = json_encode([
                    'accuracyScore' => 90,
                    'completenessScore' => 80,
                    'consistencyScore' => 90,
                    'formattingScore' => 100,
                    'feedback' => 'Good job!'
                ]);

                $response = "<antigravity-artifact identifier=\"eval\" type=\"application/json\" language=\"json\" title=\"Eval\">\n{$json}\n</antigravity-artifact>";
                yield NormalizedEvent::text($response);
            }
        };

        $mockNormalizer = $this->createMock(OutputNormalizer::class);
        
        $json = json_encode([
            'accuracyScore' => 90,
            'completenessScore' => 80,
            'consistencyScore' => 90,
            'formattingScore' => 100,
            'feedback' => 'Good job!'
        ]);
        
        $evalOutput = new NormalizedOutput(
            visibleText: '',
            artifact: new ArtifactDto('eval', 'application/json', 'json', 'Eval', $json)
        );

        $mockNormalizer->method('normalize')->willReturn($evalOutput);

        $engine = new QualityScoringEngine($mockAdapter, $mockNormalizer);

        $draftOutput = new NormalizedOutput(visibleText: 'This is the draft text.');

        $score = $engine->evaluate($draftOutput, 85);

        $this->assertSame(90, $score->accuracyScore);
        $this->assertSame(80, $score->completenessScore);
        $this->assertSame(90, $score->consistencyScore);
        $this->assertSame(100, $score->formattingScore);
        
        // (90 + 80 + 90 + 100) / 4 = 360 / 4 = 90
        $this->assertSame(90, $score->overallScore);
        $this->assertSame('PASSED', $score->status);
        $this->assertSame('Good job!', $score->feedback);
    }

    public function test_threshold_flags_requires_improvement(): void
    {
        $mockAdapter = new class ('mock-model') extends ModelAdapter {
            public function capabilities(): ModelCapability
            {
                return new ModelCapability(supportsSystemPrompts: true, supportsToolUse: true, supportsExtendedThinking: false, isReasoningModel: false, supportsVision: false);
            }

            public function streamCompletion(NormalizedRequest $req): \Generator
            {
                $json = json_encode([
                    'accuracyScore' => 50,
                    'completenessScore' => 50,
                    'consistencyScore' => 50,
                    'formattingScore' => 50,
                    'feedback' => 'Needs work.'
                ]);
                $response = "<antigravity-artifact identifier=\"eval\" type=\"application/json\" language=\"json\" title=\"Eval\">\n{$json}\n</antigravity-artifact>";
                yield NormalizedEvent::text($response);
            }
        };

        $mockNormalizer = $this->createMock(OutputNormalizer::class);
        $json = json_encode([
            'accuracyScore' => 50,
            'completenessScore' => 50,
            'consistencyScore' => 50,
            'formattingScore' => 50,
            'feedback' => 'Needs work.'
        ]);
        $evalOutput = new NormalizedOutput(
            visibleText: '',
            artifact: new ArtifactDto('eval', 'application/json', 'json', 'Eval', $json)
        );

        $mockNormalizer->method('normalize')->willReturn($evalOutput);

        $engine = new QualityScoringEngine($mockAdapter, $mockNormalizer);

        $draftOutput = new NormalizedOutput(visibleText: 'Draft text.');

        $score = $engine->evaluate($draftOutput, 80);

        $this->assertSame(50, $score->overallScore);
        $this->assertSame('REQUIRES_IMPROVEMENT', $score->status);
    }
}
