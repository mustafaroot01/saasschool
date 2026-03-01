<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            static::logAction($model, 'إنشاء ' . static::getModelName(), null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            $original = array_intersect_key($model->getOriginal(), $changes);
            
            // Only log if there are actual changes
            if (!empty($original)) {
                static::logAction($model, 'تعديل ' . static::getModelName(), $original, $changes);
            }
        });

        static::deleted(function ($model) {
            static::logAction($model, 'حذف ' . static::getModelName(), $model->getAttributes(), null);
        });
    }

    protected static function logAction($model, $action, $oldValues = null, $newValues = null)
    {
        $userId = Auth::id() ?? request()->user()?->id ?? null;
        
        // Exclude some fields from logging like updated_at
        if (is_array($oldValues)) unset($oldValues['updated_at']);
        if (is_array($newValues)) unset($newValues['updated_at']);

        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => static::getModelName() . ' (ID: ' . $model->id . ') تم التعديل عليه عبر النظام.',
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    protected static function getModelName()
    {
        $basename = class_basename(static::class);
        $names = [
            'Tenant' => 'مدرسة',
            'Plan' => 'خطة',
            'User' => 'مستخدم',
            'Subscription' => 'اشتراك',
            'Setting' => 'إعدادات',
        ];
        
        return $names[$basename] ?? $basename;
    }
}
