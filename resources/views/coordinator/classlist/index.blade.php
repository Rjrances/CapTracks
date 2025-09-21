@extends('layouts.coordinator')
@section('title', 'Class List')
@section('content')
<div class="d-flex justify-content-center align-items-start" style="min-height: 80vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm p-5 w-100" style="max-width: 950px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1" style="font-size:2.2rem; margin-bottom:0.1rem;">Class List</h1>
            <div class="text-muted" style="font-size:1.1rem; margin-bottom:0;">View and manage students by semester</div>
        </div>
        @if($activeTerm)
            <div class="mb-4">
                <div class="alert alert-info">
                    <i class="fas fa-calendar me-2"></i>
                    Showing students for: <strong>{{ $activeTerm->semester }}</strong>
                </div>
            </div>
        @endif
        
        <div class="mb-4">
            <form method="GET" action="{{ route('coordinator.classlist.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="name" class="form-label">Filter by Name:</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter student name..." value="{{ request('name') }}">
                </div>
                <div class="col-md-3">
                    <label for="course" class="form-label">Filter by Course:</label>
                    <select name="course" id="course" class="form-select">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course }}" {{ request('course') == $course ? 'selected' : '' }}>
                                {{ $course }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">General Search:</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search by ID, email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('coordinator.classlist.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        @if ($activeTerm)
            <div class="mb-3 fw-semibold" style="font-size:1.1rem;">Students Enrolled in <span class="text-primary">{{ $activeTerm->semester }}</span></div>
            @if ($students->isEmpty())
                <div class="text-center text-muted">No students enrolled in this semester.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0 bg-white rounded-3" style="overflow:hidden;">
                        <thead class="bg-light border-bottom">
                            <tr>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'student_id', 'direction' => request('sort') == 'student_id' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Student ID
                                        @if(request('sort') == 'student_id')
                                            @if(request('direction') == 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        @else
                                            <i class="fas fa-sort text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Name
                                        @if(request('sort') == 'name')
                                            @if(request('direction') == 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        @else
                                            <i class="fas fa-sort text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'email', 'direction' => request('sort') == 'email' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Email
                                        @if(request('sort') == 'email')
                                            @if(request('direction') == 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        @else
                                            <i class="fas fa-sort text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'course', 'direction' => request('sort') == 'course' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Course
                                        @if(request('sort') == 'course')
                                            @if(request('direction') == 'asc')
                                                <i class="fas fa-sort-up"></i>
                                            @else
                                                <i class="fas fa-sort-down"></i>
                                            @endif
                                        @else
                                            <i class="fas fa-sort text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Offer Codes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td class="fw-semibold">{{ $student->student_id }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td class="text-lowercase">{{ $student->email }}</td>
                                    <td>{{ $student->course }}</td>
                                    <td>
                                        @if($student->offerings && $student->offerings->count() > 0)
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($student->offerings as $offering)
                                                    <span class="badge bg-primary">{{ $offering->offer_code }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small">No enrollments</span>
                                        @endif
                                    </td>
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
