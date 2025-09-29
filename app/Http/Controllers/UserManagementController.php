<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index()
    {
        $branches = Branch::all(); 
        $users = User::paginate(10);
        return view('userManagement', compact('users', 'branches'));
    }
//sanitize
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:tbl_user,user_email',
            'user_password' => 'required|string|min:6|confirmed', 
            'user_role' => 'required|string',
            'branch_id' => 'required|exists:tbl_branch,branch_id',
        ]);

        User::create([
            'user_name' => $validated['user_name'],
            'user_email' => $validated['user_email'],
            'user_password' => bcrypt($validated['user_password']), // ✅ hash password
            'user_role' => $validated['user_role'],
            'branch_id' => $validated['branch_id'],
        ]);

        return redirect()->back()->with('success', 'User added successfully.');
    }

    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $request->validate([
        'user_name' => 'required|string|max:255',
        'user_email' => 'required|email|unique:tbl_user,user_email,' . $id . ',user_id',
        'user_role' => 'required|string',
    ]);

    $data = $request->only(['user_name', 'user_email', 'user_role']);
    if ($request->filled('password')) {
        $data['password'] = bcrypt($request->password);
    }

    $user->update($data);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        User::destroy($id);
        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
