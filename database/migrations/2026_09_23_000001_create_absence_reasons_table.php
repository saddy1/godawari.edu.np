<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('label', 150)->unique();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
        });

        // A starter list of reasons students commonly give — admins can add
        // more just by typing a new one when recording a remark; it's saved
        // here automatically and becomes a suggestion for everyone else too.
        $now = now();
        $reasons = [
            'Fever', 'Common Cold', 'Cough', 'Headache', 'Stomach Ache', 'Vomiting', 'Diarrhea',
            'Toothache', 'Body Ache', 'Injury / Accident', 'Fracture / Sprain', 'Skin Allergy',
            'Not Feeling Well', 'Menstrual Pain', "Doctor's Appointment", 'Hospital Visit',
            'Medical Checkup', 'Vaccination', 'Eye Checkup', 'Dental Checkup',
            'Family Function', 'Wedding in Family', 'Death in Family', 'Religious Ceremony',
            'Festival Celebration', 'Family Emergency', 'Guest at Home', 'Home Renovation',
            'Travel Out of Town', 'Went to Village / Home Town', 'Parent Out of Town', 'Guardian Unavailable',
            'School Bus Late', 'School Bus Missed', 'No Transportation', 'Vehicle Breakdown',
            'Heavy Rain', 'Flood / Landslide', 'Road Blocked', 'Bandh / Strike', 'Power Outage',
            'Weather Too Cold',
            'Personal Reason', 'Family Problem', 'Financial Problem', 'Fee Not Paid',
            'Books / Uniform Not Ready', 'Overslept', 'Helping at Home', "Sibling's Care",
            'Attending Coaching Class', 'Tuition Class Conflict', 'Exam Preparation Elsewhere', 'Sports Event Elsewhere',
        ];

        DB::table('absence_reasons')->insert(collect($reasons)->unique()->map(fn ($label) => [
            'label' => $label,
            'usage_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_reasons');
    }
};
