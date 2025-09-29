<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Models\Owner;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);

        $appointmentsQuery = Appointment::with(['pet.owner', 'services', 'user']);

        if ($perPage === 'all') {
            $appointments = $appointmentsQuery->get();
        } else {
            $appointments = $appointmentsQuery->paginate((int) $perPage);
        }

        $owners = Owner::with('pets')->get(); // Needed for add/edit modals
        $services = Service::all();
        $users = User::all();

        return view('appointments', compact('appointments', 'owners', 'services', 'users'));
    }

    private function generateBillingForAppointment($appointment)
{
    // Only generate billing if appointment has services
    if (!$appointment->services || $appointment->services->count() === 0) {
        return;
    }

    // Check if billing already exists for this appointment
    $existingBilling = \App\Models\Billing::where('appoint_id', $appointment->appoint_id)->first();
    if ($existingBilling) {
        return; // Don't create duplicate billing
    }

    \App\Models\Billing::create([
        'bill_date' => $appointment->appoint_date,
        'appoint_id' => $appointment->appoint_id,
        'bill_status' => 'Pending',
    ]);
}

    public function store(Request $request)
{
    $validated = $request->validate([
        'appoint_time'        => 'required',
        'appoint_date'        => 'required|date',
        'appoint_status'      => 'required',
        'pet_id'              => 'required|exists:tbl_pet,pet_id',
        'appoint_type'        => 'nullable|string',
        'appoint_description' => 'nullable|string',
        'services'            => 'array',
        'services.*'          => 'exists:tbl_serv,serv_id',
    ]);
    

    $validated['user_id'] = auth()->id() ?? $request->input('user_id');
    $services = $validated['services'] ?? [];
    unset($validated['services']);

    $appointment = Appointment::create($validated);

    if (!empty($services)) {
        $appointment->services()->sync($services);
        
        // ✅ AUTO-GENERATE BILLING when appointment has services
        $this->generateBillingForAppointment($appointment);
    }

    return redirect()->back()->with('success', 'Appointment added successfully');
}

    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'appoint_date' => 'required|date',
            'appoint_time' => 'required',
            'appoint_status' => 'required|string',
            'appoint_type' => 'required|string',
            'pet_id' => 'required|integer|exists:tbl_pet,pet_id',
            'appoint_description' => 'nullable|string',
            'services' => 'array',
            'services.*' => 'exists:tbl_serv,serv_id',
        ]);

        // update appointment
        $appointment->update($validated);

        // sync services if provided
        if ($request->has('services')) {
            $appointment->services()->sync($request->services);
        } else {
            // If no services provided, clear existing ones
            $appointment->services()->sync([]);
        }
        
        return redirect()->back()->with('success', 'Appointment updated successfully');
    }

    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->services()->detach();
        $appointment->delete();

        return back()->with('success', 'Appointment deleted.');
    }

    public function show($id)
    {
        $appointment = Appointment::with(['pet.owner', 'services', 'user'])->findOrFail($id);
        return view('appointments.show', compact('appointment'));
    }
}