<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PetRepository;
use App\Models\Branch;
use App\Models\Owner;

class PetController extends Controller
{
    protected $petRepository;

    public function __construct()
    {
        $this->petRepository = new PetRepository();
    }

    public function index(Request $request)
    {
        try {
            $pets = $this->petRepository->getPaginatedPets($request);
            $branches = Branch::all();
            $owners = Owner::with('branch')->orderBy('own_name', 'asc')->paginate(10);

            return view('Pets', compact('owners', 'branches', 'pets'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load pets data.');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'pet_name' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
                'pet_weight' => 'required|numeric|min:0|max:200',
                'pet_species' => 'required|string|in:Dog,Cat',
                'pet_breed' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
                'pet_age' => [
                    'required',
                    'regex:/^[0-9]+\s?(month|months|year|years)(\s[0-9]+\s?(month|months))?$/i',
                ],
                'pet_gender' => 'required|in:Male,Female',
                'pet_temperature' => 'required|numeric|min:30|max:45',
                'pet_registration' => 'required|date',
                'own_id' => 'required|exists:tbl_own,own_id',
            ], [
                'pet_name.regex' => 'Pet name must only contain letters, numbers, spaces, or dashes.',
                'pet_breed.regex' => 'Breed must only contain letters, numbers, spaces, or dashes.',
                'pet_age.regex' => 'Age format must be like: "3 months", "1 year", or "1 year 2 months".',
                'pet_species.in' => 'Species must be Dog or Cat.',
                'pet_gender.in' => 'Gender must be Male or Female.',
                'pet_temperature.min' => 'Temperature must be realistic (minimum 30°C).',
                'pet_temperature.max' => 'Temperature must be realistic (maximum 45°C).',
                'own_id.exists' => 'Selected owner does not exist.',
                'pet_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            ]);

             if ($request->hasFile('pet_photo')) {
            $validated['pet_photo'] = $request->file('pet_photo')->store('pets', 'public');
        }

            $this->petRepository->create($validated);
            return back()->with('success', 'Pet saved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save pet. Please check your input.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'pet_name' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
                'pet_weight' => 'nullable|numeric|min:0|max:200',
                'pet_species' => 'required|string|in:Dog,Cat',
                'pet_breed' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-]+$/',
                'pet_age' => [
                    'required',
                    'regex:/^[0-9]+\s?(month|months|year|years)(\s[0-9]+\s?(month|months))?$/i',
                ],
                'pet_gender' => 'required|in:Male,Female',
                'pet_temperature' => 'nullable|numeric|min:30|max:45',
               'pet_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $pet = $this->petRepository->findById($id);

        // Handle photo update
        if ($request->hasFile('pet_photo')) {
            if ($pet->pet_photo) {
                Storage::disk('public')->delete($pet->pet_photo);
            }
            $validated['pet_photo'] = $request->file('pet_photo')->store('pets', 'public');
        }

        // Optional: handle photo removal checkbox
        if ($request->remove_photo == '1' && $pet->pet_photo) {
            Storage::disk('public')->delete($pet->pet_photo);
            $validated['pet_photo'] = null;
        }

            $this->petRepository->update($id, $validated);
            return back()->with('success', 'Pet updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update unsuccessful. Please check your input.');
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = $this->petRepository->delete($id);
            
            if ($deleted) {
                return back()->with('success', 'Pet deleted successfully!');
            }
            
            return back()->with('error', 'Pet not found.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete unsuccessful. Please try again.');
        }
    }
}