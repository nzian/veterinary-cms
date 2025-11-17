<?php

namespace App\Http\Controllers;

use App\Models\GroomingAgreement;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $dataUrl = urldecode($validated['signature_data']);
        if (!str_starts_with($dataUrl, 'data:image/png;base64,')) {
            return back()->with('error', 'Invalid signature format.');
        }

        $base64Payload = substr($dataUrl, strlen('data:image/png;base64,'));
        $base64Payload = str_replace(' ', '+', $base64Payload);
        $png = base64_decode($base64Payload, true);

        if ($png === false) {
            return back()->with('error', 'Unable to read the signature. Please sign again.');
        }

        $directory = 'signatures/' . date('Y/m');
        Storage::disk('public')->makeDirectory($directory);
        $fileName = $directory . '/' . Str::uuid() . '.png';

        if (!Storage::disk('public')->put($fileName, $png)) {
            return back()->with('error', 'Failed to store the signature. Please try again.');
        }

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

        // Update workflow status to 'Agreement Signed'
        $visit->workflow_status = 'Agreement Signed';
        $visit->save();

        return back()->with('success', 'Grooming agreement signed.');
    }

    public function print($visitId)
    {
        $visit = Visit::with(['pet.owner', 'groomingAgreement'])->findOrFail($visitId);

        if (!$visit->groomingAgreement) {
            return redirect()
                ->route('medical.visits.perform', ['id' => $visitId, 'type' => 'grooming'])
                ->with('error', 'No signed agreement found for this visit.');
        }

        $agreement = $visit->groomingAgreement;
        $signatureUrl = $agreement->signature_path
            ? Storage::disk('public')->url($agreement->signature_path)
            : null;

        return view('visits.print-grooming-agreement', [
            'visit' => $visit,
            'agreement' => $agreement,
            'signatureUrl' => $signatureUrl,
            'generatedAt' => now(),
        ]);
    }
}
