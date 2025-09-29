<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Branch;

class ReferralController extends Controller
{
    public function index()
    {
       $referrals = Referral::with([
    'appointment.pet.owner',
    'appointment.service',
    'appointment.user',
    'appointment',
    'refToBranch',
    'refByBranch'
])->get();

return view('referral', compact('referrals'));

    }

    
    public function store(Request $request)
    {
        $appointment = Appointment::find($request->appointment_id);
        $appointment->appoint_status = 'refer';
        $appointment->save();
        $request->validate([
            'ref_date' => 'required|date',
            'ref_description' => 'required|string',
            'ref_to' => 'required|exists:tbl_branch,branch_id',
            'appointment_id' => 'required|exists:tbl_appointment,appoint_id',
        ]);

        Referral::create([
            'ref_date' => $request->ref_date,
            'ref_description' => $request->ref_description,
            'ref_by' => auth()->user()->branch_id ?? 1, // assuming the current user's branch
            'ref_to' => $request->ref_to,
            'appointment_id' => $request->appointment_id,
        ]);

        return redirect()->route('appointments.index')->with('success', 'Referral submitted successfully.');
    }

    public function update(Request $request, $id)
    {
        $referral = Referral::findOrFail($id);

        $request->validate([
            'ref_date' => 'required|date',
            'ref_description' => 'required|string',
            'ref_to' => 'required|exists:tbl_user,user_id',
        ]);

        $referral->update($request->all());

        return redirect()->route('referral.update')->with('success', 'Referral updated successfully.');
    }

    public function destroy($id)
    {
        $referral = Referral::findOrFail($id);
        $referral->delete();

        return redirect()->back()->with('success', 'Pet deleted successfully!');
    }
}
