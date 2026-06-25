<?php

namespace Tests\Feature;

use App\Services\AI\AnthropicProvider;
use App\Services\AI\CostTracker;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProviderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CostTracker::reset();
    }

    /**
     * Helper to create a partial mock of AnthropicProvider with a mocked Guzzle client.
     */
    private function createProviderWithMockClient(MockHandler $mockHandler): AnthropicProvider
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $provider = $this->getMockBuilder(AnthropicProvider::class)
            ->onlyMethods(['getClient'])
            ->getMock();

        $provider->method('getClient')
            ->willReturn($client);

        return $provider;
    }

    public function test_model_code_mapping_and_successful_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], "data: {\"type\": \"message_start\", \"message\": {\"usage\": {\"input_tokens\": 100, \"cache_read_input_tokens\": 80, \"cache_creation_input_tokens\": 10}}}\n" .
                                   "data: {\"type\": \"content_block_delta\", \"delta\": {\"type\": \"text_delta\", \"text\": \"Hello from mock!\"}}\n" .
                                   "data: {\"type\": \"message_delta\", \"usage\": {\"output_tokens\": 20}}\n" .
                                   "data: [DONE]\n")
        ]);

        $provider = $this->createProviderWithMockClient($mock);

        $user = \App\Models\User::factory()->create(['anthropic_api_key' => 'fake-key']);
        $this->actingAs($user);

        $generator = $provider->streamResponse([
            ['role' => 'user', 'content' => 'Hi']
        ], 'claude-sonnet-4-6');

        $out = '';
        foreach ($generator as $chunk) {
            $out .= $chunk;
        }

        $this->assertEquals('Hello from mock!', $out);

        // Verify cost math:
        // regular input = 100 - 80 - 10 = 10
        // Sonnet base input = 3.0/1M, output = 15.0/1M
        // read rate = 3.0 * 0.10 = 0.3/1M
        // write rate = 3.0 * 1.25 = 3.75/1M
        // Cost = (10 * 3.0 + 80 * 0.3 + 10 * 3.75 + 20 * 15.0) / 1,000,000
        // Cost = (30 + 24 + 37.5 + 300) / 1,000,000 = 391.5 / 1,000,000 = 0.0003915 USD
        $summary = CostTracker::getSessionSummary();
        $this->assertEquals(0.00039, round($summary['cost'], 5));
    }

    public function test_retry_on_rate_limit_and_recovery(): void
    {
        $mock = new MockHandler([
            new Response(429, [], 'Rate limit exceeded'),
            new Response(200, [], "data: {\"type\": \"content_block_delta\", \"delta\": {\"type\": \"text_delta\", \"text\": \"Recovered content\"}}\n" .
                                   "data: [DONE]\n")
        ]);

        $provider = $this->createProviderWithMockClient($mock);

        $user = \App\Models\User::factory()->create(['anthropic_api_key' => 'fake-key']);
        $this->actingAs($user);

        $generator = $provider->streamResponse([
            ['role' => 'user', 'content' => 'Hi']
        ], 'claude-haiku-4-6');

        $out = '';
        foreach ($generator as $chunk) {
            $out .= $chunk;
        }

        $this->assertEquals('Recovered content', $out);
    }

    public function test_retry_on_server_error_and_failure_after_max_attempts(): void
    {
        $mock = new MockHandler([
            new Response(502, [], 'Bad Gateway'),
            new Response(502, [], 'Bad Gateway'),
            new Response(502, [], 'Bad Gateway'),
            new Response(502, [], 'Bad Gateway'),
        ]);

        $provider = $this->createProviderWithMockClient($mock);

        $user = \App\Models\User::factory()->create(['anthropic_api_key' => 'fake-key']);
        $this->actingAs($user);

        $generator = $provider->streamResponse([
            ['role' => 'user', 'content' => 'Hi']
        ], 'claude-haiku-4-6');

        $out = '';
        foreach ($generator as $chunk) {
            $out .= $chunk;
        }

        $this->assertStringContainsString('returned status code 502', $out);
    }
}
