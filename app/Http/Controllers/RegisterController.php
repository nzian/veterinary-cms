<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:tbl_user,user_email',
            'user_password' => 'required|string|min:6|confirmed',
        ]);

        // Eloquent mass assignment with required NOT NULL fields
        User::create([
            'user_name'     => $request->user_name,
            'user_email'    => $request->user_email,
            'user_password' => $request->user_password, // password hashing can be handled in the model
            'user_role'     => 'superadmin',
            'user_status'   => 'active',
            'branch_id'     => null,
            'registered_by' => null,
        ]);

        return redirect()->route('login')->with('success', 'Super admin registered successfully.');
    }
}
