<?php

namespace App\Http\Controllers;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Models\Contact\Contact;
use App\Services\RelationshipAgent\AnalyzeInteraction;
use App\Services\RelationshipAgent\BuildContactBrief;
use App\Services\RelationshipAgent\SaveQuickRecord;

class RelationshipAgentController extends Controller
{
    public function index(): View
    {
        return view('assistant.quick-record')
            ->withAgentEnabled((bool) config('relationship_agent.enabled'));
    }

    public function analyze(Request $request, AnalyzeInteraction $analyzer): JsonResponse
    {
        $data = $request->validate([
            'contact_ids' => 'required|array|min:1|max:20',
            'contact_ids.*' => 'required|integer',
            'text' => 'required|string|max:1000000',
            'happened_at' => 'required|date_format:Y-m-d',
        ]);

        try {
            $proposal = $analyzer->execute(
                $request->user()->account_id,
                array_values(array_unique($data['contact_ids'])),
                $data['text'],
                $data['happened_at']
            );

            return response()->json(['data' => $proposal]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }

    public function store(Request $request, SaveQuickRecord $saveQuickRecord): JsonResponse
    {
        $result = $saveQuickRecord->execute($request->user()->account_id, $request->all());

        return response()->json(['data' => $result], 201);
    }

    public function brief(Request $request, Contact $contact, BuildContactBrief $briefBuilder): JsonResponse
    {
        abort_unless($contact->account_id === $request->user()->account_id, 404);

        try {
            return response()->json([
                'data' => $briefBuilder->execute($request->user()->account_id, $contact->id),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }
}
