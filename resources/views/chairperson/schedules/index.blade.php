@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Schedules</h2>
    <a href="{{ route('chairperson.schedules.create') }}" class="btn btn-success mb-3">Add New Schedule</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($schedules->isEmpty())
        <div class="alert alert-info">No schedules available.</div>
    @else
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Room</th>
                    <th>Offering</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->student->name ?? 'N/A' }}</td>
                        <td class="text-capitalize">{{ $schedule->type }}</td>
                        <td>{{ $schedule->date }}</td>
                        <td>{{ $schedule->time }}</td>
                        <td>{{ $schedule->room }}</td>
                        <td>{{ $schedule->offering->subject_title ?? 'N/A' }}</td>
                        <td>{{ $schedule->remarks }}</td>
                        <td>
                            <a href="{{ route('chairperson.schedules.edit', $schedule->id) }}" class="btn btn-primary btn-sm">Edit</a>
                            <form action="{{ route('chairperson.schedules.destroy', $schedule->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('Are you sure?')" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
