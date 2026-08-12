<?php

namespace App\Http\Controllers\Card;

use App\Models\Card\Student;
use Illuminate\Http\Request;

class StudentVerificationController extends Controller
{
    public function show(Student $student): \Illuminate\View\View
    {
        return view('card.students.verify', compact('student'));
    }
}
