@extends('card.student-portal.layout')
@section('title', 'Attendance')

@section('content')
@include('partials.attendance-year-calendar', [
    'yearFormAction' => route('student.attendance'),
])
@endsection
