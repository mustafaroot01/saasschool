<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // For SPA we can just return success or create a token if we use token routing
            // The Vue component currently does: response.data.token || 'simulated-token-if-cookie-only'
            $token = $request->user()->createToken('admin-token')->plainTextToken;

            \App\Models\ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'تسجيل دخول',
                'description' => json_encode(['email' => $request->email]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'message' => 'Logged in successfully',
                'user' => $request->user(),
                'token' => $token
            ]);
        }

        return response()->json([
            'message' => 'The provided credentials do not match our records.'
        ], 422);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
