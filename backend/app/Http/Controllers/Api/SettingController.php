<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return Setting::all()->pluck('value', 'key');
    }

    public function update(Request $request)
    {
        $allowedKeys = [
            'site_name', 'site_email', 'site_phone', 'site_address',
            'currency', 'timezone', 'tax_number', 'invoice_terms',
            'logo', 'favicon', 'primary_color', 'secondary_color',
        ];

        $settings = $request->only($allowedKeys);

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

}
