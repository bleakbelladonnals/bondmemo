<?php

namespace Tests\Unit\Services\RelationshipAgent;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\RelationshipAgent\SaveQuickRecord;

class SaveQuickRecordTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_saves_the_original_record_to_one_multi_contact_activity_and_confirmed_items()
    {
        $account = factory(Account::class)->create();
        $contacts = factory(Contact::class, 2)->create(['account_id' => $account->id]);

        $result = app(SaveQuickRecord::class)->execute($account->id, [
            'contact_ids' => $contacts->pluck('id')->all(),
            'text' => 'Dinner together. I promised to send the moving company details.',
            'summary' => 'Family dinner and moving plans',
            'happened_at' => '2026-08-27',
            'tasks' => [[
                'title' => 'Send the moving company details',
                'description' => null,
                'contact_id' => $contacts->first()->id,
            ]],
            'reminders' => [[
                'title' => 'Ask how the move is going',
                'description' => null,
                'contact_id' => $contacts->first()->id,
                'date' => '2026-09-03',
            ]],
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $result['activity_id'],
            'account_id' => $account->id,
            'summary' => 'Family dinner and moving plans',
            'description' => 'Dinner together. I promised to send the moving company details.',
        ]);
        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('activity_contact', [
                'activity_id' => $result['activity_id'],
                'contact_id' => $contact->id,
                'account_id' => $account->id,
            ]);
        }
        $this->assertDatabaseHas('tasks', [
            'id' => $result['task_ids'][0],
            'contact_id' => $contacts->first()->id,
            'title' => 'Send the moving company details',
        ]);
        $this->assertDatabaseHas('reminders', [
            'id' => $result['reminder_ids'][0],
            'contact_id' => $contacts->first()->id,
            'title' => 'Ask how the move is going',
        ]);
    }
}
