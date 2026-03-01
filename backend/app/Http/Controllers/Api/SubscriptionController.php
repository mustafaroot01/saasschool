<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Subscription::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'      => 'required|exists:tenants,id',
            'plan_id'        => 'required|exists:plans,id',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date',
            'status'         => 'in:active,expired,suspended,canceled',
            'amount'         => 'nullable|numeric|min:0',
            'payment_status' => 'in:paid,unpaid',
        ]);

        $validated['invoice_number'] = 'INV-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));
        $validated['currency']       = 'IQD';
        $validated['payment_status'] = $validated['payment_status'] ?? 'unpaid';
        $validated['amount']         = $validated['amount'] ?? 0;

        $subscription = \App\Models\Subscription::create($validated);
        return response()->json(['message' => 'Subscription created successfully', 'subscription' => $subscription], 201);
    }

    public function show(string $id)
    {
        return response()->json(\App\Models\Subscription::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $subscription = \App\Models\Subscription::findOrFail($id);
        
        $validated = $request->validate([
            'tenant_id' => 'sometimes|exists:tenants,id',
            'plan_id' => 'sometimes|exists:plans,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|nullable|date',
            'status' => 'sometimes|in:active,expired,suspended,canceled',
        ]);

        $subscription->update($validated);
        return response()->json(['message' => 'Subscription updated successfully', 'subscription' => $subscription]);
    }

    public function destroy(string $id)
    {
        $subscription = \App\Models\Subscription::findOrFail($id);
        $subscription->delete();
        return response()->json(['message' => 'Subscription deleted successfully.']);
    }

    public function renew(Request $request, string $id)
    {
        $request->validate([
            'subscription_end_date' => 'required|date',
            'amount' => 'nullable|numeric',
            'plan_id' => 'nullable|exists:plans,id',
            'payment_status' => 'required|in:paid,unpaid'
        ]);

        $tenant = \App\Models\Tenant::findOrFail($id);
        
        return \DB::transaction(function() use ($request, $tenant) {
            // Generate Invoice Number: INV-YYYYMMDD-RAND
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            // Update Tenant plan and dates FIRST to avoid deadlock with subscription foreign key
            $tenant->plan_id = $request->plan_id;
            $tenant->subscription_end_date = $request->subscription_end_date;
            $tenant->save();

            // Log the renewal in the subscriptions table
            $subscription = \App\Models\Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $request->plan_id,
                'start_date' => now(),
                'end_date' => $request->subscription_end_date,
                'status' => 'active',
                'amount' => $request->amount ?? 0,
                'currency' => 'IQD',
                'invoice_number' => $invoiceNumber,
                'payment_status' => $request->payment_status,
            ]);

            return response()->json([
                'message' => 'Subscription renewed successfully',
                'school' => $tenant->load('plan'),
                'subscription' => $subscription
            ]);
        });
    }

    public function allHistory()
    {
        try {
            $history = \App\Models\Subscription::with(['plan', 'tenant'])
                ->orderBy('created_at', 'desc')
                ->get();
                
            return response()->json($history);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch global history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function history(string $tenantId)
    {
        try {
            $history = \App\Models\Subscription::where('tenant_id', $tenantId)
                ->with('plan')
                ->orderBy('created_at', 'desc')
                ->get();
                
            return response()->json($history);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function resetHistory()
    {
        try {
            \DB::table('subscriptions')->truncate();
            return response()->json(['message' => 'Subscription history has been reset completely.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to reset history', 'message' => $e->getMessage()], 500);
        }
    }

    public function togglePaymentStatus(string $id)
    {
        try {
            $subscription = \App\Models\Subscription::findOrFail($id);
            $subscription->payment_status = $subscription->payment_status === 'paid' ? 'unpaid' : 'paid';
            $subscription->save();
            
            return response()->json(['message' => 'Payment status updated', 'subscription' => $subscription]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update status', 'message' => $e->getMessage()], 500);
        }
    }
}
