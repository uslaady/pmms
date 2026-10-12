<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Director of Finance records a payment certificate (Module 10).
     */
    public function store(Request $request, Contract $contract)
    {
        abort_unless($request->user()->hasPermission('payments.record'), 403);

        $data = $request->validate([
            'certificate_number' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'retention_held' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        $data['contract_id'] = $contract->id;
        $data['recorded_by'] = $request->user()->id;
        $data['status'] = 'pending';

        $payment = Payment::create($data);
        app(\App\Services\NotificationService::class)->paymentRecorded($payment);

        return back()->with('status', 'Payment recorded and sent to the Permanent Secretary for approval.');
    }

    /**
     * Permanent Secretary / Director of Finance approves a pending payment.
     */
    public function approve(Request $request, Payment $payment)
    {
        abort_unless($request->user()->hasPermission('payments.approve'), 403);
        abort_unless($payment->status === 'pending', 422, 'Only pending payments can be approved.');

        $payment->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        app(\App\Services\NotificationService::class)->paymentApproved($payment);

        return back()->with('status', 'Payment approved. Once the funds are actually disbursed, mark it as Paid.');
    }

    /**
     * Final step: after the approved certificate is actually disbursed
     * (cheque issued / transfer made), Finance marks it Paid. Only 'paid'
     * amounts count toward Total Paid and Outstanding calculations.
     */
    public function markPaid(Request $request, Payment $payment)
    {
        abort_unless($request->user()->hasPermission('payments.record'), 403);
        abort_unless($payment->status === 'approved', 422, 'Only approved payments can be marked as paid.');

        $payment->update(['status' => 'paid']);

        return back()->with('status', 'Payment marked as paid.');
    }
}
