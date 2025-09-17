@extends('layouts.coordinator')
@section('title', 'Class List')
@section('content')
<div class="d-flex justify-content-center align-items-start" style="min-height: 80vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm p-5 w-100" style="max-width: 950px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1" style="font-size:2.2rem; margin-bottom:0.1rem;">Class List</h1>
            <div class="text-muted" style="font-size:1.1rem; margin-bottom:0;">View and manage students by semester</div>
        </div>
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <form method="GET" action="{{ route('coordinator.classlist.index') }}" class="d-flex align-items-center gap-2">
                <label for="semester" class="fw-semibold me-2 mb-0">Semester:</label>
                <select name="semester" id="semester" class="form-select rounded-pill" style="max-width: 200px;" onchange="this.form.submit()">
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester }}" {{ $selectedSemester == $semester ? 'selected' : '' }}>
                            {{ $semester }}
                        </option>
                    @endforeach
                </select>
                <input type="text" name="search" class="form-control rounded-pill" placeholder="Search students..." style="max-width: 220px;" value="{{ request('search') }}">
                <button class="btn btn-primary rounded-pill px-4" type="submit">Search</button>
            </form>
        </div>
        @if ($selectedSemester)
            <div class="mb-3 fw-semibold" style="font-size:1.1rem;">Students Enrolled in <span class="text-primary">{{ $selectedSemester }}</span></div>
            @if ($students->isEmpty())
                <div class="text-center text-muted">No students enrolled in this semester.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0 bg-white rounded-3" style="overflow:hidden;">
                        <thead class="bg-light border-bottom">
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Course</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="fw-semibold">{{ $student->student_id }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td class="text-lowercase">{{ $student->email }}</td>
                                    <td>{{ $student->course }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 pt-3 border-0 flex justify-center w-100 d-flex justify-content-center">
                    {{ $students->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
