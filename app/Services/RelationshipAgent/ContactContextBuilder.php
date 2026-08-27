<?php

namespace App\Services\RelationshipAgent;

use App\Models\Contact\Contact;

class ContactContextBuilder
{
    public function build(int $accountId, int $contactId): array
    {
        $contact = Contact::where('account_id', $accountId)
            ->with(['birthdate', 'relationships.ofContact.birthdate', 'relationships.relationshipType'])
            ->findOrFail($contactId);

        return [
            'contact' => [
                'ref' => 'contact:'.$contact->id,
                'source_label' => 'Contact profile · '.$contact->name,
                'id' => $contact->id,
                'name' => $contact->name,
                'nickname' => $contact->nickname,
                'description' => $contact->description,
                'job' => $contact->job,
                'company' => $contact->company,
                'birthday' => $contact->birthdate && $contact->birthdate->date
                    ? $contact->birthdate->toShortString()
                    : null,
            ],
            'relationships' => $contact->relationships->map(function ($relationship) {
                return [
                    'ref' => 'relationship:'.$relationship->id,
                    'source_label' => $relationship->ofContact->name.' · '.$relationship->relationshipType->name,
                    'type' => $relationship->relationshipType->name,
                    'name' => $relationship->ofContact->name,
                    'birthday' => $relationship->ofContact->birthdate && $relationship->ofContact->birthdate->date
                        ? $relationship->ofContact->birthdate->toShortString()
                        : null,
                ];
            })->values()->all(),
            'recent_activities' => $contact->activities()
                ->with('contacts')
                ->limit(config('relationship_agent.max_context_activities'))
                ->get()
                ->map(function ($activity) {
                    return [
                        'ref' => 'activity:'.$activity->id,
                        'source_label' => $activity->happened_at->toDateString().' · '.$activity->summary,
                        'date' => $activity->happened_at->toDateString(),
                        'summary' => $activity->summary,
                        'description' => $activity->description,
                        'participants' => $activity->contacts->pluck('name')->values()->all(),
                    ];
                })->all(),
            'recent_notes' => $contact->notes()
                ->latest()
                ->limit(config('relationship_agent.max_context_notes'))
                ->get()
                ->map(function ($note) {
                    return [
                        'ref' => 'note:'.$note->id,
                        'source_label' => $note->created_at->toDateString().' · Note',
                        'date' => $note->created_at->toDateString(),
                        'body' => $note->body,
                    ];
                })->all(),
            'open_tasks' => $contact->tasks()
                ->inProgress()
                ->latest()
                ->limit(config('relationship_agent.max_context_tasks'))
                ->get()
                ->map(function ($task) {
                    return [
                        'ref' => 'task:'.$task->id,
                        'source_label' => 'Task · '.$task->title,
                        'title' => $task->title,
                        'description' => $task->description,
                    ];
                })->all(),
            'active_reminders' => $contact->reminders()
                ->active()
                ->orderBy('initial_date')
                ->limit(config('relationship_agent.max_context_reminders'))
                ->get()
                ->map(function ($reminder) {
                    return [
                        'ref' => 'reminder:'.$reminder->id,
                        'source_label' => $reminder->initial_date->toDateString().' · '.$reminder->title,
                        'title' => $reminder->title,
                        'description' => $reminder->description,
                        'date' => $reminder->initial_date->toDateString(),
                    ];
                })->all(),
        ];
    }
}
