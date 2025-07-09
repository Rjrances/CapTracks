@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Welcome, {{ auth()->user()->name }}</h2>
    <div class="card">
        <div class="card-header">Chairperson Dashboard</div>
        <div class="card-body">
            <p>You are logged in as a <strong>Chairperson</strong>.</p>
            <ul>
                <li>Oversee panel assignments</li>
                <li>Monitor proposal evaluations</li>
                <li>Generate and view reports</li>
            </ul>
        </div>
    </div>
</div>
@endsection
