<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Visit;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $activityTabs = [
            'all'         => ['label' => 'All',                    'icon' => '📋'],
            'checkup'     => ['label' => 'Check-up / Consultation', 'icon' => '🩺'],
            'vaccination' => ['label' => 'Vaccination',            'icon' => '💉'],
            'deworming'   => ['label' => 'Deworming',              'icon' => '🪱'],
            'grooming'    => ['label' => 'Grooming',               'icon' => '🧼'],
            'diagnostic'  => ['label' => 'Laboratory / Diagnostics','icon' => '🧪'],
            'surgical'    => ['label' => 'Surgical Services',      'icon' => '🗡️'],
            'boarding'    => ['label' => 'Boarding',               'icon' => '🏨'],
            'emergency'   => ['label' => 'Emergency',              'icon' => '🚨'],
        ];

        $activeKey = $request->query('tab', 'all');
        if (!array_key_exists($activeKey, $activityTabs)) {
            $activeKey = 'all';
        }

        $query = \App\Models\Visit::with(['pet.owner', 'user'])->orderBy('visit_date', 'desc');
        if ($activeKey !== 'all') {
            $query->where('service_type', $activeKey);
        }
        $visits = $query->get();

        return view('activities_tablist', compact('activityTabs', 'activeKey', 'visits'));
    }

    public function attendVisit($id, Request $request)
    {
        $visit = Visit::with(['pet.owner', 'user'])->findOrFail($id);

        $activityTabs = [
            'checkup'     => ['label' => 'Check-up / Consultation', 'icon' => '🩺'],
            'vaccination' => ['label' => 'Vaccination',            'icon' => '💉'],
            'deworming'   => ['label' => 'Deworming',              'icon' => '🪱'],
            'grooming'    => ['label' => 'Grooming',               'icon' => '🧼'],
            'diagnostic'  => ['label' => 'Laboratory / Diagnostics','icon' => '🧪'],
            'surgical'    => ['label' => 'Surgical Services',      'icon' => '🗡️'],
            'boarding'    => ['label' => 'Boarding',               'icon' => '🏨'],
            'emergency'   => ['label' => 'Emergency',              'icon' => '🚨'],
        ];

        // Always use the visit's service_type as the active tab
        $activeKey = $visit->service_type;
        if (!array_key_exists($activeKey, $activityTabs)) {
            $activeKey = 'checkup';
        }

        return view('activities', compact('visit', 'activityTabs', 'activeKey'));
    }

    public function handleActivitySave($visitId, $activityKey, Request $request)
    {
        $mmc = app(MedicalManagementController::class);

        switch ($activityKey) {
            case 'checkup':
                return $mmc->saveConsultation($request, $visitId);
            case 'vaccination':
                return $mmc->saveVaccination($request, $visitId);
            case 'deworming':
                return $mmc->saveDeworming($request, $visitId);
            case 'grooming':
                return $mmc->saveGrooming($request, $visitId);
            case 'boarding':
                return $mmc->saveBoarding($request, $visitId);
            case 'diagnostic':
                return $mmc->saveDiagnostic($request, $visitId);
            case 'surgical':
                return $mmc->saveSurgical($request, $visitId);
            case 'emergency':
                return $mmc->saveEmergency($request, $visitId);
            default:
                return redirect()
                    ->route('activities.attend', ['id' => $visitId, 'tab' => 'checkup'])
                    ->with('error', 'Unknown activity tab.');
        }
    }
}
