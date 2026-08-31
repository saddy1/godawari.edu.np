<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admission; 
use App\Models\Setting;
use Illuminate\Validation\Rule;

class AdmissionsController extends Controller
{
    private const PROGRAMS = ['B.Sc. CSIT', 'BBS'];

    public function index()
    {
        $settings = [
            'academic_year' => Setting::get('admission_year', '2082 – 2083 B.S.'),
            'phone' => Setting::get('school_phone', '+977-123456789'),
            'email' => Setting::get('school_email', 'info@godawari.edu.np'),
            'office_hours' => Setting::get('office_hours', 'Mon-Fri 9:00 AM - 5:00 PM'),
        ];

        $programs = self::PROGRAMS;

        return view('pages.admissions', compact('settings', 'programs'));
    }

   public function storeAdmission(Request $request)
    {
        // Honeypot field is invisible to real visitors; only bots fill it in.
        // A submission faster than 3 seconds after the form rendered is also
        // almost certainly scripted. Pretend success either way.
        $renderedAt = (int) $request->input('form_rendered_at');
        if (filled($request->input('website')) || ($renderedAt && (time() - $renderedAt) < 3)) {
            return back()->with('success', 'Your admission inquiry has been submitted successfully! Our team will contact you shortly.');
        }

        if ($request->session()->get('admission_submitted')) {
            return back()->with('success', 'You have already submitted an admission inquiry. Our team will contact you shortly.');
        }

        // 1. Validate the user's input
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|string|in:Male,Female,Other',
            'applied_grade' => ['required', 'string', Rule::in(self::PROGRAMS)],
            'guardian_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'previous_school' => 'nullable|string|max:255',
        ]);

        // 2. Save exactly what was submitted to the database
        Admission::create($validated);

        $request->session()->put('admission_submitted', true);

        // 3. Send them back to the form with a success message
        return back()->with('success', 'Your admission inquiry has been submitted successfully! Our team will contact you shortly.');
    }
}
