<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log on successful modifications
        if ($response->isSuccessful() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            
            $action = $this->determineAction($request);
            if ($action) {
                $user = $request->user();
                \App\Models\ActivityLog::create([
                    'user_id' => $user ? $user->id : null,
                    'action' => $action,
                    'description' => json_encode($request->except(['password', 'password_confirmation', 'logo', '_method'])),
                    'ip_address' => $request->ip() ?? '0.0.0.0',
                    'user_agent' => $request->userAgent() ?? 'unknown',
                ]);
            }
        }

        return $response;
    }

    private function determineAction(Request $request)
    {
        $path = $request->path();
        $method = $request->method();

        if (str_starts_with($path, 'api/schools')) {
            if ($method === 'POST') {
                if (str_ends_with($path, 'renew')) return 'تجديد اشتراك مدرسة';
                if (str_ends_with($path, 'reset-password')) return 'إعادة تعيين كلمة مرور مدرسة';
                if (str_ends_with($path, 'impersonate')) return 'دخول كمدير مدرسة';
                return 'إضافة مدرسة جديدة';
            }
            if ($method === 'PUT' || $method === 'PATCH') return 'تعديل بيانات مدرسة';
            if ($method === 'DELETE') return 'حذف مدرسة';
        }

        if (str_starts_with($path, 'api/plans')) {
            if ($method === 'POST') return 'إضافة باقة جديدة';
            if ($method === 'PUT' || $method === 'PATCH') return 'تعديل باقة';
            if ($method === 'DELETE') return 'حذف باقة';
        }

        if (str_starts_with($path, 'api/subscriptions')) {
            if ($method === 'POST' && str_ends_with($path, 'renew')) return 'تجديد اشتراك مدرسة';
            if ($method === 'PUT' || $method === 'PATCH') return 'تغيير حالة دفع اشتراك';
            if ($method === 'DELETE') return 'حذف سجل اشتراكات';
        }

        return null; // Ignore other routes like login
    }
}
