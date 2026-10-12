<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Project;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    /**
     * Module 4 — Procurement Officer registers the awarded contract.
     */
    public function store(Request $request, Project $project)
    {
        abort_unless($request->user()->hasPermission('contracts.manage'), 403);
        abort_if($project->contract()->exists(), 422, 'This project already has a contract. Record a variation instead.');

        $data = $request->validate([
            'contract_number' => 'required|string|unique:contracts,contract_number',
            'award_date' => 'required|date',
            'contract_value' => 'required|numeric|min:0',
            'funding_source_id' => 'nullable|exists:funding_sources,id',
            'retention_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $project->contract()->create($data);

        return back()->with('status', 'Contract registered.');
    }

    /**
     * Variation order or extension of time.
     */
    public function storeVariation(Request $request, Contract $contract)
    {
        abort_unless($request->user()->hasPermission('contracts.manage'), 403);

        $data = $request->validate([
            'type' => 'required|in:variation_order,extension_of_time',
            'amount' => 'required_if:type,variation_order|nullable|numeric',
            'extra_days' => 'required_if:type,extension_of_time|nullable|integer|min:1',
            'reason' => 'required|string',
            'date' => 'required|date',
        ]);

        $contract->variations()->create($data);

        if ($data['type'] === 'variation_order' && $data['amount']) {
            $contract->update([
                'revised_value' => ($contract->revised_value ?? $contract->contract_value) + $data['amount'],
            ]);
        }

        if ($data['type'] === 'extension_of_time' && $data['extra_days']) {
            $contract->project->update([
                'expected_completion_date' => $contract->project->expected_completion_date?->addDays($data['extra_days']),
            ]);
        }

        return back()->with('status', 'Variation recorded.');
    }
}
