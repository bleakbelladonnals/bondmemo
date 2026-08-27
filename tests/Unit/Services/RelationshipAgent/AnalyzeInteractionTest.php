<?php

namespace Tests\Unit\Services\RelationshipAgent;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Interfaces\RelationshipAgentClient;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\RelationshipAgent\AnalyzeInteraction;

class AnalyzeInteractionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_accepts_only_structured_suggestions_for_selected_contacts()
    {
        $account = factory(Account::class)->create();
        $contacts = factory(Contact::class, 2)->create(['account_id' => $account->id]);
        $client = new class($contacts->first()->id) implements RelationshipAgentClient {
            private int $contactId;

            public function __construct(int $contactId)
            {
                $this->contactId = $contactId;
            }

            public function complete(array $messages): array
            {
                return [
                    'summary' => 'Family dinner and a promised follow-up',
                    'tasks' => [[
                        'title' => 'Send the moving company details',
                        'description' => 'This hidden description must not be persisted.',
                        'contact_id' => $this->contactId,
                    ]],
                    'reminders' => [],
                ];
            }
        };

        $result = (new AnalyzeInteraction($client))->execute(
            $account->id,
            $contacts->pluck('id')->all(),
            'We had dinner and I promised to send the details.',
            '2026-08-27'
        );

        $this->assertSame('Family dinner and a promised follow-up', $result['summary']);
        $this->assertSame($contacts->first()->id, $result['tasks'][0]['contact_id']);
        $this->assertNull($result['tasks'][0]['description']);
    }

    /** @test */
    public function it_rejects_a_model_suggestion_for_an_unselected_contact()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $otherContact = factory(Contact::class)->create(['account_id' => $account->id]);
        $client = new class($otherContact->id) implements RelationshipAgentClient {
            private int $contactId;

            public function __construct(int $contactId)
            {
                $this->contactId = $contactId;
            }

            public function complete(array $messages): array
            {
                return [
                    'summary' => 'A valid summary',
                    'tasks' => [],
                    'reminders' => [[
                        'title' => 'Follow up',
                        'description' => null,
                        'contact_id' => $this->contactId,
                        'date' => '2026-09-01',
                    ]],
                ];
            }
        };

        $this->expectException(ValidationException::class);

        (new AnalyzeInteraction($client))->execute(
            $account->id,
            [$contact->id],
            'We spoke today.',
            '2026-08-27'
        );
    }
}
