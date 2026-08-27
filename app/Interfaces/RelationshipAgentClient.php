<?php

namespace App\Interfaces;

interface RelationshipAgentClient
{
    /**
     * Return the decoded JSON object produced by the configured model.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function complete(array $messages): array;
}
