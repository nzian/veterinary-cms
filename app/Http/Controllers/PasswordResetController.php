<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class PasswordResetController extends Controller
{
    // Show the reset password form
    public function showResetForm()
    {
        return view('reset-password');
    }

    // Handle password update
    public function update(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:tbl_user,user_email',
            'password' => 'required|string|min:6|confirmed',
        ]);

       $user = User::where('user_email', $request->email)->first();
       $user->user_password = Hash::make($request->password);
        $user->save();

        return redirect()->route('login')->with('status', 'Password successfully updated. You can now login.');
    }
}
