<?php
namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Branch;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('branch')->get();
        $branches = Branch::all();

        return view('service', compact('services', 'branches'));
    }
//sanitize
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serv_name' => 'required|string|max:255',
            'serv_type' => 'required|string|max:255',
            'serv_description' => 'required|string|max:255',
            'serv_price' => 'required|numeric',
            'branch_id' => 'required|exists:tbl_branch,branch_id',
        ]);

        Service::create($validated);

        return redirect()->back()->with('success', 'Service added successfully!');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'serv_name' => 'required|string|max:255',
            'serv_description' => 'nullable|string',
            'serv_type' => 'required|string|max:255',
            'serv_price' => 'required|numeric|min:0',
            'branch_id' => 'required|exists:tbl_branch,branch_id',
        ]);

        $service = Service::findOrFail($id);
        $service->update($validated);

        return redirect()->back()->with('success', 'Service updated successfully.');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }

    
}
