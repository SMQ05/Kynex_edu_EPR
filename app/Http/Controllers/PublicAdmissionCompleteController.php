<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\GuardianType;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentApplication;
use App\Models\Tenant\StudentGuardian;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicAdmissionCompleteController extends Controller
{
    public function show(string $token)
    {
        $application = StudentApplication::where('public_token', $token)
            ->where('status', ApplicationStatus::Admitted->value)
            ->firstOrFail();

        $student = Student::findOrFail($application->student_id);

        $father = $student->guardians()
            ->where('guardian_type', GuardianType::Father->value)
            ->first();

        $mother = $student->guardians()
            ->where('guardian_type', GuardianType::Mother->value)
            ->first();

        return view('public.admission-complete', [
            'tenant'      => tenancy()->initialized ? tenant() : null,
            'application' => $application,
            'student'     => $student,
            'father'      => $father,
            'mother'      => $mother,
            'completed'   => filled($student->profile_completed_at),
        ]);
    }

    public function submit(string $token, Request $request)
    {
        $application = StudentApplication::where('public_token', $token)
            ->where('status', ApplicationStatus::Admitted->value)
            ->firstOrFail();

        $student = Student::findOrFail($application->student_id);

        $validated = $request->validate([
            'blood_group'          => ['required', Rule::in(['A+','A-','B+','B-','AB+','AB-','O+','O-'])],
            'religion'             => 'required|string|max:100',
            'nationality'          => 'required|string|max:100',
            'cnic_bform'           => 'required|string|max:20',

            'father_name'          => 'required|string|max:255',
            'father_cnic'          => 'required|string|max:20',
            'father_phone'         => 'required|string|max:20',
            'father_email'         => 'nullable|email|max:255',
            'father_occupation'    => 'nullable|string|max:150',
            'father_whatsapp'      => 'nullable|string|max:20',

            'mother_name'          => 'nullable|string|max:255',
            'mother_cnic'          => 'nullable|string|max:20',
            'mother_phone'         => 'nullable|string|max:20',

            'emergency_name'       => 'required|string|max:255',
            'emergency_phone'      => 'required|string|max:20',
            'emergency_relation'   => 'required|string|max:100',

            'medical_notes'        => 'nullable|string|max:1000',
            'special_needs_notes'  => 'nullable|string|max:1000',
        ]);

        // Update student personal info.
        $student->update([
            'blood_group'          => $validated['blood_group'],
            'religion'             => $validated['religion'],
            'nationality'          => $validated['nationality'],
            'medical_notes'        => $validated['medical_notes'] ?? null,
            'special_needs_notes'  => $validated['special_needs_notes'] ?? null,
            'profile_completed_at' => now(),
        ]);

        // Upsert father guardian.
        $fatherData = [
            'student_id'         => $student->id,
            'guardian_type'      => GuardianType::Father->value,
            'name'               => $validated['father_name'],
            'relationship'       => 'Father',
            'cnic'               => $validated['father_cnic'],
            'phone'              => $validated['father_phone'],
            'email'              => $validated['father_email'] ?? null,
            'occupation'         => $validated['father_occupation'] ?? null,
            'whatsapp'           => $validated['father_whatsapp'] ?? null,
            'is_primary_contact' => true,
            'can_access_portal'  => filled($validated['father_email'] ?? null),
        ];

        $father = StudentGuardian::where('student_id', $student->id)
            ->where('guardian_type', GuardianType::Father->value)
            ->first();

        if ($father) {
            $father->update($fatherData);
        } else {
            StudentGuardian::create($fatherData);
        }

        // Upsert mother guardian if provided.
        if (filled($validated['mother_name'] ?? null)) {
            $motherData = [
                'student_id'         => $student->id,
                'guardian_type'      => GuardianType::Mother->value,
                'name'               => $validated['mother_name'],
                'relationship'       => 'Mother',
                'cnic'               => $validated['mother_cnic'] ?? null,
                'phone'              => $validated['mother_phone'] ?? '0000000000',
                'is_primary_contact' => false,
                'can_access_portal'  => false,
            ];

            $mother = StudentGuardian::where('student_id', $student->id)
                ->where('guardian_type', GuardianType::Mother->value)
                ->first();

            if ($mother) {
                $mother->update($motherData);
            } else {
                StudentGuardian::create($motherData);
            }
        }

        // Store emergency contact as a generic guardian row.
        $emergency = StudentGuardian::where('student_id', $student->id)
            ->where('guardian_type', GuardianType::Guardian->value)
            ->where('relationship', 'Emergency Contact')
            ->first();

        $emergencyData = [
            'student_id'         => $student->id,
            'guardian_type'      => GuardianType::Guardian->value,
            'name'               => $validated['emergency_name'],
            'relationship'       => 'Emergency Contact',
            'phone'              => $validated['emergency_phone'],
            'is_primary_contact' => false,
            'can_access_portal'  => false,
        ];

        if ($emergency) {
            $emergency->update($emergencyData);
        } else {
            StudentGuardian::create($emergencyData);
        }

        return redirect()->route('public.admission.complete', ['token' => $token])
            ->with('profile_saved', true);
    }
}
