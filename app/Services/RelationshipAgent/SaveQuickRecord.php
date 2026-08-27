<?php

namespace App\Services\RelationshipAgent;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Contact\Contact;
use App\Services\Task\CreateTask;
use App\Services\Contact\Reminder\CreateReminder;
use App\Services\Account\Activity\Activity\CreateActivity;

class SaveQuickRecord
{
    public function execute(int $accountId, array $data): array
    {
        Validator::make($data, [
            'contact_ids' => 'required|array|min:1|max:20',
            'contact_ids.*' => 'required|integer',
            'text' => 'required|string|max:1000000',
            'summary' => 'required|string|max:255',
            'happened_at' => 'required|date_format:Y-m-d',
            'tasks' => 'present|array|max:10',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string|max:2000',
            'tasks.*.contact_id' => ['nullable', 'integer', Rule::in($data['contact_ids'] ?? [])],
            'reminders' => 'present|array|max:10',
            'reminders.*.title' => 'required|string|max:100000',
            'reminders.*.description' => 'nullable|string|max:2000',
            'reminders.*.contact_id' => ['required', 'integer', Rule::in($data['contact_ids'] ?? [])],
            'reminders.*.date' => 'required|date_format:Y-m-d',
        ])->validate();

        $contacts = Contact::where('account_id', $accountId)
            ->whereIn('id', $data['contact_ids'])
            ->get();
        abort_if($contacts->count() !== count(array_unique($data['contact_ids'])), 404);

        return DB::transaction(function () use ($accountId, $data) {
            $activity = app(CreateActivity::class)->execute([
                'account_id' => $accountId,
                'activity_type_id' => null,
                'summary' => $data['summary'],
                'description' => $data['text'],
                'happened_at' => $data['happened_at'],
                'emotions' => [],
                'contacts' => array_values(array_unique($data['contact_ids'])),
            ]);

            $tasks = collect($data['tasks'])->map(function ($task) use ($accountId) {
                return app(CreateTask::class)->execute([
                    'account_id' => $accountId,
                    'contact_id' => $task['contact_id'] ?? null,
                    'title' => $task['title'],
                    'description' => $task['description'] ?? null,
                ]);
            });

            $reminders = collect($data['reminders'])->map(function ($reminder) use ($accountId) {
                return app(CreateReminder::class)->execute([
                    'account_id' => $accountId,
                    'contact_id' => $reminder['contact_id'],
                    'initial_date' => $reminder['date'],
                    'frequency_type' => 'one_time',
                    'frequency_number' => 1,
                    'title' => $reminder['title'],
                    'description' => $reminder['description'] ?? null,
                ]);
            });

            return [
                'activity_id' => $activity->id,
                'task_ids' => $tasks->pluck('id')->all(),
                'reminder_ids' => $reminders->pluck('id')->all(),
            ];
        });
    }
}
