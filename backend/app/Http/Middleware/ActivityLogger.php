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
                \App\Models\ActivityLog::create([
                    'user_id' => $request->user() ? $request->user()->id : null,
                    'action' => $action,
                    'description' => json_encode($request->except(['password', 'password_confirmation', 'logo'])),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
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

        return null; // Ignore other routes like login
    }
}
