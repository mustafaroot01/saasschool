<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Plan::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'max_students' => 'required|integer',
            'max_teachers' => 'required|integer',
            'max_admins' => 'required|integer',
            'storage_limit_mb' => 'required|integer',
            'notifications_limit' => 'required|integer',
            'price' => 'required|numeric',
            'duration_months' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        $plan = \App\Models\Plan::create($validated);
        return response()->json(['message' => 'Plan created successfully', 'plan' => $plan], 201);
    }

    public function show(string $id)
    {
        return response()->json(\App\Models\Plan::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $plan = \App\Models\Plan::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'max_students' => 'sometimes|integer',
            'max_teachers' => 'sometimes|integer',
            'max_admins' => 'sometimes|integer',
            'storage_limit_mb' => 'sometimes|integer',
            'notifications_limit' => 'sometimes|integer',
            'price' => 'sometimes|numeric',
            'duration_months' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $plan->update($validated);
        return response()->json(['message' => 'Plan updated successfully', 'plan' => $plan]);
    }

    public function destroy(string $id)
    {
        $plan = \App\Models\Plan::findOrFail($id);
        $plan->delete();
        return response()->json(['message' => 'Plan deleted successfully.']);
    }
}
