<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Owner;

class BranchController extends Controller
{
    public function switch($id)
    {
        // Optionally store selected branch in session or redirect as needed
        session(['active_branch_id' => $id]);

        // Redirect to dashboard or reload current view
        return redirect()->route('dashboard-index');
    }

    public function view()
    {
        $branches = Branch::all();
        return view('branches', compact('branches'));


    }
    public function index(Request $request)
{
     $branches = Branch::paginate(10);
        return view('branches', compact('branches'));
    }

//sanitize


    public function store(Request $request)
    {
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

        // AJAX or normal form
        if ($request->ajax()) {
            return response()->json(['success' => true, 'branch' => $branch]);
        }

        return redirect()->back()->with('success', 'Branch added successfully.');
    }

    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        $branches = Branch::all();

        return view('branches', compact('branches', 'branch'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'contact' => 'required|string',
        ]);

        $branch = Branch::findOrFail($id);
        $branch->branch_name = $request->name;
        $branch->branch_address = $request->address;
        $branch->branch_contactNum = $request->contact;
        $branch->save();

        return redirect()->back()->with('success', 'Branch updated successfully.');
    }

    public function destroy($id)
    {
        Branch::destroy($id);
        return redirect()->back()->with('success', 'Branch deleted successfully!');
    }


}

