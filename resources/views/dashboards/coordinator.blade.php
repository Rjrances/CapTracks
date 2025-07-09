@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Welcome, {{ auth()->user()->name }}</h2>
    <div class="card">
        <div class="card-header">Coordinator Dashboard</div>
        <div class="card-body">
            <p>You are logged in as a <strong>Coordinator</strong>.</p>
            <ul>
                <li>Manage student submissions</li>
                <li>Assign advisers or panelists</li>
                <li>Review and approve proposals</li>
            </ul>
        </div>
    </div>
</div>
@endsection
