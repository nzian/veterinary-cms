<?php

namespace App\Http\Controllers;

use App\Models\GroomingAgreement;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class GroomingAgreementController extends Controller
{
    public function store(Request $request, $visitId)
    {
        $visit = Visit::with(['pet.owner'])->findOrFail($visitId);

        $validated = $request->validate([
            'signer_name' => 'nullable|string|max:255',
            'signature_data' => 'required|string', // data URL
            'checkbox_acknowledge' => 'accepted',
            'color_markings' => 'nullable|string|max:255',
            'history_before' => 'nullable|string',
            'history_after' => 'nullable|string',
        ]);

        if ($visit->groomingAgreement) {
            return back()->with('error', 'Agreement already signed for this visit.');
        }

        $dataUrl = $validated['signature_data'];
        if (!str_starts_with($dataUrl, 'data:image/png;base64,')) {
            return back()->with('error', 'Invalid signature format.');
        }

        $png = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')));
        $fileName = 'signatures/'.date('Y/m/').Str::uuid().'.png';
        Storage::disk('public')->put($fileName, $png);

        $agreement = GroomingAgreement::create([
            'visit_id' => $visit->getKey(),
            'owner_id' => optional($visit->pet->owner)->own_id,
            'pet_id' => $visit->pet_id,
            'signer_name' => $validated['signer_name'] ?? optional($visit->pet->owner)->own_name ?? 'Owner/Representative',
            'signature_path' => $fileName,
            'color_markings' => $validated['color_markings'] ?? null,
            'history_before' => $validated['history_before'] ?? null,
            'history_after' => $validated['history_after'] ?? null,
            'consent_text_version' => 'v1',
            'checkbox_acknowledge' => $request->boolean('checkbox_acknowledge'),
            'signed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 1000),
        ]);

        return back()->with('success', 'Grooming agreement signed.');
    }
}
