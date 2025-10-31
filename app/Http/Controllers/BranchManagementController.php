<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Branch;
use App\Models\User;

class BranchManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        try {
            $branches = Branch::with('users')->get();
            
            $users = User::with('branch')->get();
            
            return view('branchManagement', compact('branches', 'users'));
        } catch (\Exception $e) {
            dd('Error in controller: ' . $e->getMessage());
        }
    }

    /**
     * Switch to a specific branch (UPDATED METHOD)
     */
    public function switchBranch($id)
    {
        $user = auth()->user();
        
        // Verify user is super admin
        if (strtolower(trim($user->user_role)) !== 'superadmin') {
            return redirect()->back()->with('error', 'Unauthorized access');
        }
        
        // Verify branch exists
        $branch = Branch::findOrFail($id);
        
        // Store the active branch in session with all required keys
        session([
            'active_branch_id' => $branch->branch_id,
            'active_branch_name' => $branch->branch_name,
            'branch_mode' => 'active' // This flag is important!
        ]);
        
        return redirect()->route('dashboard-index')
            ->with('success', "Switched to {$branch->branch_name}");
    }
    
    /**
     * Clear branch selection and return to super admin view (NEW METHOD)
     */
    public function clearBranch()
    {
        session()->forget(['active_branch_id', 'active_branch_name', 'branch_mode']);
        
        return redirect()->route('dashboard-index')
            ->with('success', 'Returned to Super Admin view');
    }

    // ... rest of your existing methods (storeBranch, updateBranch, etc.)
    
    public function storeBranch(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'contact' => 'required|string|max:20',
            ]);

            $branch = Branch::create([
                'branch_name' => $validated['name'],
                'branch_address' => $validated['address'],
                'branch_contactNum' => $validated['contact'],
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Branch added successfully.',
                    'branch' => $branch
                ]);
            }

            return redirect()->back()->with('success', 'Branch added successfully.')->with('active_tab', 'branch');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            \Log::error('Branch creation failed: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create branch: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to create branch.')->withInput();
        }
    }

    public function updateBranch(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'contact' => 'required|string',
        ]);

        $branch = Branch::findOrFail($id);
        $branch->update([
            'branch_name' => $request->name,
            'branch_address' => $request->address,
            'branch_contactNum' => $request->contact,
        ]);

       return redirect()->back()->with('success', 'Branch updated successfully.')->with('active_tab', 'branch');
    }

    public function destroyBranch($id)
    {
        Branch::destroy($id);
       return redirect()->back()->with('success', 'Branch deleted successfully!')->with('active_tab', 'branch');
    }

    // User Methods
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:tbl_user,user_email',
            'user_contactNum' => 'required|string|max:20',
            'user_password' => 'required|string|min:6|confirmed', 
            'user_role' => 'required|string|in:veterinarian,receptionist',
            'branch_id' => 'required|exists:tbl_branch,branch_id',
            'user_licenseNum' => 'nullable|string|max:100|required_if:user_role,veterinarian',
        ]);

        User::create([
            'user_name' => $validated['user_name'],
            'user_email' => $validated['user_email'],
            'user_contactNum' => $validated['user_contactNum'],
            'user_password' => $validated['user_password'],
            'user_role' => $validated['user_role'],
            'branch_id' => $validated['branch_id'],
            'user_licenseNum' => $validated['user_licenseNum'] ?? null,
        ]);

        return redirect()->back()->with('success', 'User added successfully.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:tbl_user,user_email,' . $id . ',user_id',
            'user_contactNum' => 'required|string|max:20',
            'user_password' => 'nullable|string|min:6|confirmed',
            'user_role' => 'required|string|in:veterinarian,receptionist',
            'branch_id' => 'required|exists:tbl_branch,branch_id',
            'user_licenseNum' => 'nullable|string|max:100|required_if:user_role,veterinarian',
        ]);

        $updateData = [
            'user_name' => $validated['user_name'],
            'user_email' => $validated['user_email'],
            'user_contactNum' => $validated['user_contactNum'],
            'user_role' => $validated['user_role'],
            'branch_id' => $validated['branch_id'],
            'user_licenseNum' => $validated['user_licenseNum'] ?? null,
        ];

        if ($request->filled('user_password')) {
            $updateData['user_password'] = bcrypt($validated['user_password']);
        }

        $user->update($updateData);

        return redirect()->back()->with('success', 'User updated successfully.')->with('active_tab', 'user');
    }

    public function destroyUser($id)
    {
        User::destroy($id);
       return redirect()->back()->with('success', 'User deleted successfully.')->with('active_tab', 'user');
}

    public function getCompleteData($id)
    {
        try {
            $branch = \App\Models\Branch::findOrFail($id);
            
            // Filter by branch (requires branch_id columns in your tables)
            $products = \App\Models\Product::where('branch_id', $id)->get();
            $services = \App\Models\Service::where('branch_id', $id)->get();
            $equipment = \App\Models\Equipment::where('branch_id', $id)->get();
            
            return response()->json([
                'products' => $products,
                'services' => $services,
                'equipment' => $equipment,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch branch data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // New method to add user from branch tab
    public function addUserToBranch(Request $request)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:tbl_user,user_email',
            'user_contactNum' => 'required|string|max:20',
            'user_password' => 'required|string|min:6|confirmed', 
            'user_role' => 'required|string|in:veterinarian,receptionist',
            'branch_id' => 'required|exists:tbl_branch,branch_id',
            'user_licenseNum' => 'nullable|string|max:100|required_if:user_role,veterinarian',
        ]);

        $user = User::create([
            'user_name' => $validated['user_name'],
            'user_email' => $validated['user_email'],
            'user_contactNum' => $validated['user_contactNum'],
            'user_password' => $validated['user_password'],
            'user_role' => $validated['user_role'],
            'branch_id' => $validated['branch_id'],
            'user_licenseNum' => $validated['user_licenseNum'] ?? null,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User added to branch successfully.',
                'user' => $user->load('branch')
            ]);
        }

        return redirect()->back()->with('success', 'User added to branch successfully.')->with('active_tab', 'user');
    }
}