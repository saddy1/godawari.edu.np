@extends('card.student-portal.layout')
@section('title', 'Feedback & Complaints')

@section('content')
@include('partials.complaints-panel', [
    'submitAction' => route('student.complaints.store'),
    'eyebrow' => 'Student Portal',
])
@endsection
