@extends('layouts.chairperson')

@section('content')
    <div class="text-center">
        <h1 class="mb-4">Welcome, {{ auth()->user()->name }}</h1>
        <p class="lead">Chairperson Dashboard</p>

        <div class="row mt-5">
            {{-- Manage Offerings --}}
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">Manage Offerings</h5>
                        <a href="{{ route('chairperson.offerings') }}" class="btn btn-primary">Go</a>
                    </div>
                </div>
            </div>

            {{-- View Teachers --}}
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">View Teachers</h5>
                        <a href="{{ route('chairperson.teachers') }}" class="btn btn-primary">Go</a>
                    </div>
                </div>
            </div>

            {{-- View Schedules --}}
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">View Schedules</h5>
                        <a href="{{ route('chairperson.schedules') }}" class="btn btn-primary">Go</a>
                    </div>
                </div>
            </div>

            {{-- Import Students --}}
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">Import Students</h5>
                        <a href="{{ route('chairperson.upload-form') }}" class="btn btn-primary">Import</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
