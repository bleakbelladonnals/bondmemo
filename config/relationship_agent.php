<?php

return [
    'enabled' => (bool) env('RELATIONSHIP_AGENT_ENABLED', false),
    'endpoint' => env('RELATIONSHIP_AGENT_ENDPOINT'),
    'api_key' => env('RELATIONSHIP_AGENT_API_KEY'),
    'model' => env('RELATIONSHIP_AGENT_MODEL'),
    'timeout' => (int) env('RELATIONSHIP_AGENT_TIMEOUT', 30),
    'json_mode' => (bool) env('RELATIONSHIP_AGENT_JSON_MODE', true),
    'max_context_activities' => (int) env('RELATIONSHIP_AGENT_MAX_ACTIVITIES', 10),
    'max_context_notes' => (int) env('RELATIONSHIP_AGENT_MAX_NOTES', 8),
    'max_context_tasks' => (int) env('RELATIONSHIP_AGENT_MAX_TASKS', 12),
    'max_context_reminders' => (int) env('RELATIONSHIP_AGENT_MAX_REMINDERS', 12),
    'requests_per_minute' => (int) env('RELATIONSHIP_AGENT_RATE_LIMIT', 10),
];
