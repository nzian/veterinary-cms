<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

   protected function authenticated(Request $request, $user)
{
    // 🕒 Update last login timestamp
    $user->last_login_at = now();
    $user->save();

    // 🧑‍💼 If the user is Super Admin
    if ($user->user_role === 'superadmin') {
        return redirect()->route('superadmin.dashboard');
    }

    // 🏢 For branch users
    return redirect()->route('dashboard-index');
}


   public function login(Request $request)
{
    $request->validate([
        'user_email' => 'required|email',
        'user_password' => 'required|string',
    ]);

    \Log::info('=== LOGIN ATTEMPT ===');
    \Log::info('Email: ' . $request->user_email);
    \Log::info('Password: ' . $request->user_password);

    // Check if user exists
    $user = \App\Models\User::where('user_email', $request->user_email)->first();
    
    if (!$user) {
        \Log::info('USER NOT FOUND');
        return back()->withErrors(['user_email' => 'User not found'])->withInput();
    }

    \Log::info('User found: ' . $user->user_name);
    \Log::info('User status: ' . $user->user_status);
    \Log::info('Stored hash: ' . $user->user_password);
    
    // Test password
    $passwordCheck = \Illuminate\Support\Facades\Hash::check($request->user_password, $user->user_password);
    \Log::info('Password check result: ' . ($passwordCheck ? 'SUCCESS' : 'FAILED'));

    if ($passwordCheck) {
        \Log::info('LOGIN SUCCESS');
        auth()->login($user);
        return redirect('/dashboard');
    }

    \Log::info('LOGIN FAILED');
    return back()->withErrors(['user_email' => 'Invalid credentials'])->withInput();
}
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    
}
