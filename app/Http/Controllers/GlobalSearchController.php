<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pet;
use App\Models\Owner;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\Service;

class GlobalSearchController extends Controller
{
    public function redirect(Request $request)
    {
        $query = $request->input('search');

        // Check Pets first
        $pet = Pet::where('pet_name', 'like', "%{$query}%")
                  ->orWhere('pet_species', 'like', "%{$query}%")
                  ->first();
        if ($pet) {
            return redirect()->route('pets-index', ['search' => $query]);
        }

        // Then Owners
        $owner = Owner::where('own_name', 'like', "%{$query}%")
                      ->orWhere('own_contactnum', 'like', "%{$query}%")
                      ->first();
        if ($owner) {
            return redirect()->route('owners-index', ['search' => $query]);
        }

        // Then Appointments
        $appointment = Appointment::where('appoint_description', 'like', "%{$query}%")
                                  ->orWhere('appoint_status', 'like', "%{$query}%")
                                  ->first();
        if ($appointment) {
            return redirect()->route('appointments-index', ['search' => $query]);
        }

        // Products
        $product = Product::where('prod_name', 'like', "%{$query}%")
                          ->orWhere('prod_description', 'like', "%{$query}%")
                          ->first();
        if ($product) {
            return redirect()->route('prodservequip.index', ['search' => $query]);
        }

        // Services
        $service = Service::where('serv_name', 'like', "%{$query}%")
                          ->orWhere('serv_description', 'like', "%{$query}%")
                          ->first();
        if ($service) {
            return redirect()->route('services-index', ['search' => $query]);
        }

        // Default → if no match
        return back()->with('error', 'No results found for "' . $query . '"');
    }
}
