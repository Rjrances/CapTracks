@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Welcome, {{ auth()->user()->name }}</h2>
    <div class="card">
        <div class="card-header">Student Dashboard</div>
        <div class="card-body">
            <p>You are logged in as a <strong>Student</strong>.</p>
            <ul>
                <li>View project requirements</li>
                <li>Submit your documents</li>
                <li>Track your progress</li>
            </ul>
        </div>
    </div>
</div>
@endsection
