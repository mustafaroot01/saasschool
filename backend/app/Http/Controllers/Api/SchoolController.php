<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Tenant::with('domains')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id'            => 'required|string|unique:tenants,id',
            'name'          => 'required|string',
            'plan_id'       => 'nullable|exists:plans,id',
            'domain'        => 'required|string|unique:domains,domain',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'logo'          => 'nullable|image|max:2048',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        if (empty($validated['plan_id'])) {
            $plan = \App\Models\Plan::firstOrCreate(
                ['name' => 'باقة تجريبية'],
                [
                    'max_students' => 500,
                    'max_teachers' => 3,
                    'max_admins' => 5,
                    'storage_limit_mb' => 2048,
                    'notifications_limit' => 20,
                    'price' => 0,
                    'duration_months' => 0,
                    'is_active' => true,
                ]
            );
            $subscriptionEndDate = now()->addDays(7);
        } else {
            $plan = \App\Models\Plan::findOrFail($validated['plan_id']);
            $subscriptionEndDate = now()->addMonths($plan->duration_months);
        }

        $tenant = \App\Models\Tenant::create([
            'id'            => $validated['id'],
            'name'          => $validated['name'],
            'plan_id'       => $plan->id,
            'subscription_end_date' => $subscriptionEndDate,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'logo'          => $logoPath,
        ]);

        \App\Models\Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'start_date' => now(),
            'end_date' => $subscriptionEndDate,
            'status' => 'active',
            'amount' => $plan->price,
            'currency' => 'IQD',
            'invoice_number' => 'INV-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'payment_status' => 'paid',
        ]);

        $tenant->domains()->create([
            'domain' => $validated['domain']
        ]);

        $password = \Illuminate\Support\Str::random(10);
        $email = $validated['contact_email'] ?? 'admin@' . $validated['domain'] . '.localhost';

        $tenant->run(function () use ($tenant, $email, $password) {
            \App\Models\User::create([
                'name' => 'مدير النظام - ' . $tenant->name,
                'email' => $email,
                'password' => bcrypt($password),
                // 'email_verified_at' => now(), // User model might not have this in mass assignment
            ]);
        });

        return response()->json([
            'message' => 'School created successfully', 
            'tenant' => $tenant->load('domains'),
            'admin_email' => $email,
            'admin_password' => $password
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(\App\Models\Tenant::with('domains')->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $tenant = \App\Models\Tenant::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'sometimes|string',
            'plan_id'       => 'sometimes|exists:plans,id',
            'status'        => 'sometimes|in:active,suspended',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'logo'          => 'nullable|image|max:2048',
            'primary_color' => 'sometimes|string|max:7',
            'secondary_color' => 'sometimes|string|max:7',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($tenant->logo) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $tenant->update($validated);

        if ($request->has('domain')) {
            $domainRecord = $tenant->domains()->first();
            $request->validate([
                'domain' => 'string|unique:domains,domain,' . ($domainRecord ? $domainRecord->id : 'NULL')
            ]);

            if ($domainRecord) {
                $domainRecord->update(['domain' => $request->domain]);
            } else {
                $tenant->domains()->create(['domain' => $request->domain]);
            }
        }

        return response()->json(['message' => 'School updated successfully', 'tenant' => $tenant->load('domains')]);
    }

    public function destroy(string $id)
    {
        $tenant = \App\Models\Tenant::findOrFail($id);
        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }
        $tenant->delete();
        return response()->json(['message' => 'School deleted successfully.']);
    }

    public function resetPassword(string $id)
    {
        $tenant = \App\Models\Tenant::findOrFail($id);
        $newPassword = \Illuminate\Support\Str::random(10);
        
        $tenant->run(function () use ($newPassword) {
            $admin = \App\Models\User::first();
            if ($admin) {
                $admin->password = bcrypt($newPassword);
                $admin->save();
            }
        });

        return response()->json([
            'message' => 'Password reset successfully',
            'password' => $newPassword
        ]);
    }

    public function calculateStorageUsage(string $id)
    {
        $tenant = \App\Models\Tenant::with('plan')->findOrFail($id);
        
        $dbName = config('tenancy.database.prefix') . $tenant->id;
        
        $sizeQuery = \Illuminate\Support\Facades\DB::select("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' 
            FROM information_schema.TABLES 
            WHERE table_schema = ?
        ", [$dbName]);

        $usedMb = $sizeQuery[0]->size_mb ?? 0;
        $limitMb = $tenant->plan ? $tenant->plan->storage_limit_mb : 0;
        
        return response()->json([
            'used_mb' => (float) $usedMb,
            'limit_mb' => (float) $limitMb,
            'percentage' => $limitMb > 0 ? min(100, round(($usedMb / $limitMb) * 100, 2)) : 0
        ]);
    }

    public function impersonate(string $id)
    {
        $tenant = \App\Models\Tenant::with('domains')->findOrFail($id);
        $tokenStr = null;
        
        $tenant->run(function () use (&$tokenStr) {
            $admin = \App\Models\User::first();
            if ($admin) {
                // Delete old impersonation tokens to keep DB clean
                $admin->tokens()->where('name', 'impersonation')->delete();
                $tokenStr = $admin->createToken('impersonation')->plainTextToken;
            }
        });

        if (!$tokenStr) {
            return response()->json(['message' => 'Admin user not found in tenant database.'], 404);
        }

        $domain = $tenant->domains->first()->domain ?? $tenant->id;
        $url = "http://{$domain}:5173/auth/impersonate?token={$tokenStr}";

        return response()->json(['url' => $url]);
    }

    public function listBackups(string $id)
    {
        $tenant = \App\Models\Tenant::findOrFail($id);
        $path = 'backups/' . $tenant->id;
        
        $files = Storage::disk('local')->files($path);
        
        $backups = collect($files)->map(function ($file) {
            return [
                'name' => basename($file),
                'size' => Storage::disk('local')->size($file),
                'date' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file))
            ];
        })->sortByDesc('date')->values();

        return response()->json($backups);
    }

    public function createBackup(string $id)
    {
        $tenant = \App\Models\Tenant::findOrFail($id);
        $dbName = config('tenancy.database.prefix') . $tenant->id;
        
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        
        $filename = 'backup_' . date('Y_m_d_H_i_s') . '.sql';
        $relativeDir = 'backups/' . $tenant->id;
        
        // Ensure directory exists via Disk
        if (!Storage::disk('local')->exists($relativeDir)) {
            Storage::disk('local')->makeDirectory($relativeDir);
        }
        
        $fullPath = Storage::disk('local')->path($relativeDir . '/' . $filename);
        
        $passwordPart = $password ? "--password=" . escapeshellarg($password) : "";
        $command = "mysqldump --user=" . escapeshellarg($username) . " {$passwordPart} --host=" . escapeshellarg($host) . " " . escapeshellarg($dbName) . " > " . escapeshellarg($fullPath);
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            return response()->json(['error' => 'تعذر إنشاء نسخة احتياطية. يرجى التأكد من توفر مسار mysqldump.'], 500);
        }
        
        return response()->json(['message' => 'تم إنشاء النسخة الاحتياطية بنجاح', 'filename' => $filename]);
    }

    public function allBackups()
    {
        $tenants = \App\Models\Tenant::all();
        $allBackups = collect();

        foreach ($tenants as $tenant) {
            $path = 'backups/' . $tenant->id;
            if (Storage::disk('local')->exists($path)) {
                $files = Storage::disk('local')->files($path);
                foreach ($files as $file) {
                    $allBackups->push([
                        'school_name' => $tenant->name,
                        'school_id' => $tenant->id,
                        'name' => basename($file),
                        'size' => Storage::disk('local')->size($file),
                        'date' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file))
                    ]);
                }
            }
        }

        return response()->json($allBackups->sortByDesc('date')->values());
    }

    public function deleteAllBackups()
    {
        try {
            if (\Storage::disk('local')->exists('backups')) {
                \Storage::disk('local')->deleteDirectory('backups');
            }
            return response()->json(['message' => 'All backups deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete backups', 'message' => $e->getMessage()], 500);
        }
    }

    public function downloadBackup(string $id, string $filename)
    {
        $tenant = \App\Models\Tenant::findOrFail($id);
        $path = 'backups/' . $tenant->id . '/' . basename($filename);
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Backup not found.');
        }
        
        return Storage::disk('local')->download($path);
    }
}
