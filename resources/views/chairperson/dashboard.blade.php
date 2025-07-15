@extends('layouts.chairperson')

@section('content')
    <div class="text-center">
        <h1 class="mb-4">Welcome, {{ auth()->user()->name }}</h1>
        <p class="lead">Chairperson Dashboard</p>

        <div class="row mt-5 justify-content-center">
            {{-- Manage Offerings --}}
            <div class="col-md-2 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title">Manage Offerings</h5>
                        <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-primary mt-3">Go</a>
                    </div>
                </div>
            </div>

            {{-- View Teachers --}}
            <div class="col-md-2 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title">View Teachers</h5>
                        <a href="{{ route('chairperson.teachers.index') }}" class="btn btn-primary mt-3">Go</a>
                    </div>
                </div>
            </div>

            {{-- View Schedules --}}
            <div class="col-md-2 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title">View Schedules</h5>
                        <a href="{{ route('chairperson.schedules') }}" class="btn btn-primary mt-3">Go</a>
                    </div>
                </div>
            </div>

            {{-- Import Students --}}
            <div class="col-md-2 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title">Import Students</h5>
                        <a href="{{ route('chairperson.upload-form') }}" class="btn btn-primary mt-3">Import</a>
                    </div>
                </div>
            </div>

            {{-- Manage Roles --}}
            <div class="col-md-2 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title">Manage Roles</h5>
                        <a href="{{ url('/chairperson/manage-roles') }}" class="btn btn-primary mt-3">Manage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
