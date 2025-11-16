<?php

namespace App\Http\Controllers;

use App\Models\ReferralCompany;
use Illuminate\Http\Request;

class ReferralCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
           try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'contact_number' => 'nullable|string|max:15',
                'address' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string',
                'contact_person' => 'nullable|string|max:255',
                'contact_person_number' => 'nullable|string|max:15',
                'branch_id' => 'required|exists:tbl_branch,branch_id',

            ]);

            $referralCompany = ReferralCompany::create([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'contact_number' => $validated['contact_number'],
                'website' => $validated['website'] ?? null,
                'email' => $validated['email'] ?? null,
                'description' => $validated['description'] ?? null,
                'contact_person' => $validated['contact_person'],
                'contact_person_number' => $validated['contact_person_number'] ?? null,
                'branch_id' => $validated['branch_id'],
                'is_active' => true,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Referral Company added successfully.',
                    'referralCompany' => $referralCompany
                ]);
            }

            return redirect()->back()->with('success', 'Referral Company added successfully.')->with('active_tab', 'referral_company');
            
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
            \Log::error('Referral company creation failed: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create referral company: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to create referral company.')->withInput();
        }
            
        
    }

    /**
     * Display the specified resource.
     */
    public function show(ReferralCompany $referralCompany)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReferralCompany $referralCompany)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReferralCompany $referralCompany)
    {
        //
           $validated = $request->validate([
                'name' => 'required|string|max:255',
                'contact_number' => 'required|string|max:15',
                'address' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'website' => 'nullable|url|max:255',
                'description' => 'nullable|string',
                'contact_person' => 'required|string|max:255',
                'contact_person_number' => 'nullable|string|max:15',
                'branch_id' => 'required|exists:tbl_branch,branch_id',

            ]);
            $referralCompany->update($validated);
             return redirect()->back()->with('success', 'Referral Company updated successfully.')->with('active_tab', 'referral_company');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReferralCompany $referralCompany)
    {
        //
        $referralCompany->delete(); 
        return redirect()->back()->with('success', 'Referral Company deleted successfully!')->with('active_tab', 'referral_company');
    }
}
