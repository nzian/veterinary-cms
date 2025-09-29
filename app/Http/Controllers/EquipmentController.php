<?php
namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Branch;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipments = Equipment::with('branch')->get();
        $branches = Branch::all();
        return view('inventory', compact('equipments', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equip_name' => 'required|string|max:255',
            'equip_category' => 'nullable|string|max:255',
            'equip_description' => 'required|string|max:1000',
            'equip_price' => 'required|numeric|min:0',
            'equip_stocks' => 'required|integer|min:0',
            'equip_reorderlevel' => 'nullable|integer|min:0',
            'branch_id' => 'required|exists:tbl_branch,branch_id',
            'equip_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('equip_image')) {
            $validated['equip_image'] = $request->file('equip_image')->store('equipment', 'public');
        }

        Equipment::create($validated);

        return redirect()->back()->with('success', 'Equipment added successfully!');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'equip_name' => 'required|string|max:255',
            'equip_category' => 'nullable|string|max:255',
            'equip_description' => 'required|string|max:1000',
            'equip_price' => 'required|numeric|min:0',
            'equip_stocks' => 'required|integer|min:0',
            'equip_reorderlevel' => 'nullable|integer|min:0',
            'branch_id' => 'required|exists:tbl_branch,branch_id',
            'equip_image' => 'nullable|image|max:2048',
        ]);

        $equipment = Equipment::findOrFail($id);
        if ($request->hasFile('equip_image')) {
            $validated['equip_image'] = $request->file('equip_image')->store('equipment', 'public');
        }

        $equipment->update($validated);
        return redirect()->back()->with('success', 'Equipment updated successfully!');
    }

    public function updateInventory(Request $request)
    {
        $validated = $request->validate([
            'equip_id' => 'required|exists:tbl_equipment,equip_id',
            'equip_damaged' => 'nullable|integer|min:0',
            'equip_pullout' => 'nullable|integer|min:0',
            'equip_expiry' => 'nullable|date',
        ]);

        $equipment = Equipment::findOrFail($validated['equip_id']);
        $equipment->update($validated);

        return redirect()->back()->with('success', 'Equipment inventory updated successfully!');
    }

    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->delete();

        return redirect()->back()->with('success', 'Equipment deleted successfully.');
    }
}
