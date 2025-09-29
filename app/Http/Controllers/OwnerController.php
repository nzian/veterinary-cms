<?php
namespace App\Http\Controllers;

use App\Models\Owner;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    // Show all pet owners
    public function index()
    {
        $Owners = Owner::with('branch')->orderBy('own_name', 'asc')->paginate(10);
        return view('owner', ['owners' => $Owners]);
    }

    // Store new pet owner
    public function store(Request $request)
    {
        try {
            // Sanitize input
            $input = $request->only(['own_name', 'own_contactnum', 'own_location']);
            $input = array_map('strip_tags', $input);
            $input = array_map('trim', $input);

            // Validate
            $validated = $request->validate([
                'own_name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-.]+$/',
                'own_contactnum' => 'required|string|max:20|regex:/^[0-9+\-\s]+$/',
                'own_location' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-,.]+$/',
            ], [
                'own_name.required' => 'Owner name is required.',
                'own_name.regex' => 'Owner name can only contain letters, numbers, spaces, dots, or dashes.',
                'own_contactnum.required' => 'Contact number is required.',
                'own_contactnum.regex' => 'Contact number can only contain numbers, +, -, and spaces.',
                'own_location.required' => 'Location is required.',
                'own_location.regex' => 'Location can only contain letters, numbers, spaces, commas, dots, or dashes.',
            ]);

            Owner::create($input);

            return redirect()->back()->with('success', 'Pet Owner added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to add Pet Owner. Please check your input.');
        }
    }

    // Update pet owner
    public function update(Request $request, $id)
    {
        try {
            // Sanitize input
            $input = $request->only(['own_name', 'own_contactnum', 'own_location']);
            $input = array_map('strip_tags', $input);
            $input = array_map('trim', $input);

            // Validate
            $validated = $request->validate([
                'own_name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-.]+$/',
                'own_contactnum' => 'required|string|max:20|regex:/^[0-9+\-\s]+$/',
                'own_location' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-,.]+$/',
            ], [
                'own_name.required' => 'Owner name is required.',
                'own_name.regex' => 'Owner name can only contain letters, numbers, spaces, dots, or dashes.',
                'own_contactnum.required' => 'Contact number is required.',
                'own_contactnum.regex' => 'Contact number can only contain numbers, +, -, and spaces.',
                'own_location.required' => 'Location is required.',
                'own_location.regex' => 'Location can only contain letters, numbers, spaces, commas, dots, or dashes.',
            ]);

            $owner = Owner::findOrFail($id);
            $owner->update($input);

            return redirect()->back()->with('success', 'Pet owner updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Update unsuccessful. Please check your input.');
        }
    }

    // Delete pet owner
    public function destroy($id)
    {
        try {
            Owner::destroy($id);
            return redirect()->back()->with('success', 'Pet owner deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Delete unsuccessful. Please try again.');
        }
    }
}
