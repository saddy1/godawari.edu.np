@extends('teaching_learning.teacher-workspace.layout')
@section('title', 'Feedback & Complaints')
@section('content')
@include('partials.complaints-panel', [
    'submitAction' => route('admin.teacher.complaints.store'),
    'eyebrow' => 'Teacher Workspace',
])
@endsection
