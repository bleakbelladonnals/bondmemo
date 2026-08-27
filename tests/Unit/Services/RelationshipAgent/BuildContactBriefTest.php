<?php

namespace Tests\Unit\Services\RelationshipAgent;

use Mockery;
use Tests\TestCase;
use App\Interfaces\RelationshipAgentClient;
use App\Services\RelationshipAgent\BuildContactBrief;
use App\Services\RelationshipAgent\ContactContextBuilder;

class BuildContactBriefTest extends TestCase
{
    /** @test */
    public function it_keeps_only_items_that_cite_supplied_records()
    {
        $context = [
            'contact' => [
                'ref' => 'contact:7',
                'source_label' => 'Contact profile · Mei',
                'id' => 7,
                'name' => 'Mei',
            ],
            'relationships' => [],
            'recent_activities' => [[
                'ref' => 'activity:11',
                'source_label' => '2026-08-20 · Dinner',
                'summary' => 'Dinner',
            ]],
            'recent_notes' => [],
            'open_tasks' => [],
            'active_reminders' => [],
        ];

        $contextBuilder = Mockery::mock(ContactContextBuilder::class);
        $contextBuilder->shouldReceive('build')->once()->with(3, 7)->andReturn($context);

        $client = Mockery::mock(RelationshipAgentClient::class);
        $client->shouldReceive('complete')->once()->andReturn([
            'overview' => [
                'text' => 'Mei is a saved contact.',
                'sources' => ['contact:7'],
            ],
            'recent_events' => [[
                'text' => 'You had dinner.',
                'sources' => ['activity:11'],
            ], [
                'text' => 'An invented event.',
                'sources' => ['activity:999'],
            ]],
            'commitments' => [],
            'upcoming' => [],
            'relationship_context' => [],
            'conversation_starters' => [],
            'unrequested_model_field' => 'must not leave the service boundary',
        ]);

        $result = (new BuildContactBrief($contextBuilder, $client))->execute(3, 7);

        $this->assertSame('Contact profile · Mei', $result['overview']['sources'][0]['label']);
        $this->assertCount(1, $result['recent_events']);
        $this->assertSame('activity:11', $result['recent_events'][0]['sources'][0]['ref']);
        $this->assertArrayNotHasKey('unrequested_model_field', $result);
    }
}
