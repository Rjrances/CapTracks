@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">View Schedules</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Offering</th>
                <th>Date</th>
                <th>Time</th>
                <th>Room</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schedules as $schedule)
                <tr>
                    <td>{{ $schedule->offering->name }}</td>
                    <td>{{ $schedule->date }}</td>
                    <td>{{ $schedule->time }}</td>
                    <td>{{ $schedule->room }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
