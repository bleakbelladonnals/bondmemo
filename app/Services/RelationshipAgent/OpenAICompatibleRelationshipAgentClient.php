<?php

namespace App\Services\RelationshipAgent;

use Throwable;
use RuntimeException;
use GuzzleHttp\Client;
use JsonException;
use App\Interfaces\RelationshipAgentClient;

class OpenAICompatibleRelationshipAgentClient implements RelationshipAgentClient
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function complete(array $messages): array
    {
        $this->assertConfigured();

        $request = [
            'model' => config('relationship_agent.model'),
            'messages' => $messages,
            'temperature' => 0.1,
        ];

        if (config('relationship_agent.json_mode')) {
            $request['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = $this->client->post(config('relationship_agent.endpoint'), [
                'connect_timeout' => 5,
                'timeout' => config('relationship_agent.timeout'),
                'headers' => [
                    'Authorization' => 'Bearer '.config('relationship_agent.api_key'),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $request,
            ]);

            $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $content = data_get($payload, 'choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                throw new RuntimeException('The model returned an empty response.');
            }

            return $this->decodeContent($content);
        } catch (Throwable $e) {
            if ($e instanceof RuntimeException && ! $e instanceof JsonException) {
                throw $e;
            }

            // Do not attach provider responses: they may echo sensitive context.
            throw new RuntimeException('The relationship assistant is temporarily unavailable.');
        }
    }

    private function assertConfigured(): void
    {
        if (! config('relationship_agent.enabled')) {
            throw new RuntimeException('The relationship assistant is disabled.');
        }

        if (! config('relationship_agent.endpoint')
            || ! config('relationship_agent.api_key')
            || ! config('relationship_agent.model')) {
            throw new RuntimeException('The relationship assistant is not configured.');
        }
    }

    private function decodeContent(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content);

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('The model returned invalid structured data.');
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('The model returned invalid structured data.');
        }

        return $decoded;
    }
}
