<?php

namespace Tests\Unit\Services\RelationshipAgent;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use App\Services\RelationshipAgent\OpenAICompatibleRelationshipAgentClient;

class OpenAICompatibleRelationshipAgentClientTest extends TestCase
{
    /** @test */
    public function it_decodes_structured_model_output()
    {
        config()->set('relationship_agent.enabled', true);
        config()->set('relationship_agent.endpoint', 'https://model.example.test/v1/chat/completions');
        config()->set('relationship_agent.api_key', 'test-only-key');
        config()->set('relationship_agent.model', 'test-model');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'choices' => [[
                    'message' => [
                        'content' => '```json'.PHP_EOL.'{"summary":"Dinner together","tasks":[],"reminders":[]}'.PHP_EOL.'```',
                    ],
                ]],
            ])),
        ]);
        $client = new OpenAICompatibleRelationshipAgentClient(
            new Client(['handler' => HandlerStack::create($mock)])
        );

        $result = $client->complete([['role' => 'user', 'content' => 'private record']]);

        $this->assertSame('Dinner together', $result['summary']);
        $this->assertSame([], $result['tasks']);
        $this->assertSame([], $result['reminders']);
    }

    /** @test */
    public function it_fails_closed_when_disabled()
    {
        config()->set('relationship_agent.enabled', false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The relationship assistant is disabled.');

        app(OpenAICompatibleRelationshipAgentClient::class)->complete([]);
    }
}
