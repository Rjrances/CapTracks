@extends('layouts.chairperson')

@section('content')
    <div class="text-center">
        <h1 class="mb-4">Welcome, {{ auth()->user()->name }}</h1>
        <p class="lead">Chairperson Dashboard</p>

        <div class="row mt-5">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">Manage Offerings</h5>
                        <a href="{{ route('chairperson.offerings') }}" class="btn btn-primary">Go</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">View Teachers</h5>
                        <a href="{{ route('chairperson.teachers') }}" class="btn btn-primary">Go</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">View Schedules</h5>
                        <a href="{{ route('chairperson.schedules') }}" class="btn btn-primary">Go</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
