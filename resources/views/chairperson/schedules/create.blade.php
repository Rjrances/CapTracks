@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Add New Schedule</h2>
    <form action="{{ route('chairperson.schedules.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="student_id" class="form-label">Student</label>
            <select name="student_id" id="student_id" class="form-select" required>
                <option value="">Select Student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select name="type" id="type" class="form-select" required>
                <option value="proposal">Proposal</option>
                <option value="final">Final Defense</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="date" class="form-label">Date</label>
            <input type="date" name="date" id="date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="time" class="form-label">Time</label>
            <input type="time" name="time" id="time" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="room" class="form-label">Room</label>
            <input type="text" name="room" id="room" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="offering_id" class="form-label">Offering (optional)</label>
            <select name="offering_id" id="offering_id" class="form-select">
                <option value="">None</option>
                @foreach($offerings as $offering)
                    <option value="{{ $offering->id }}">{{ $offering->subject_title }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="remarks" class="form-label">Remarks</label>
            <textarea name="remarks" id="remarks" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Create Schedule</button>
        <a href="{{ route('chairperson.schedules.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection 