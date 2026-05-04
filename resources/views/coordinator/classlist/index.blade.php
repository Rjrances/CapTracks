@extends('layouts.coordinator')
@section('title', 'Class List')
@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4" style="margin-bottom: 1.2rem !important;">
            <div>
                <h1 class="fw-bold mb-1" style="font-size:2.2rem; margin-bottom:0.1rem;">Class List</h1>
                <div class="text-muted" style="font-size:1.1rem; margin-bottom:0;">View and manage students in your coordinated offerings</div>
            </div>
            <a href="{{ route('coordinator.classlist.import') }}" class="btn btn-outline-primary">
                <i class="fas fa-file-upload me-1"></i>Import students
            </a>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($activeTerm)
            <div class="mb-4">
                <div class="alert alert-info">
                    <i class="fas fa-calendar me-2"></i>
                    Showing students from your coordinated offerings for: <strong>{{ $activeTerm->semester }}</strong>
                    <div class="small mt-2 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Coordinated offerings: <strong>{{ $coordinatedOfferingCount ?? 0 }}</strong> | Coordinated students: <strong>{{ $coordinatedStudentCount ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Students</h5>
                        <h3 class="mb-0">{{ $totalSemesterStudents ?? 0 }}</h3>
                        <small>active semester</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Grouped Students</h5>
                        <h3 class="mb-0">{{ $groupedStudentCount ?? 0 }}</h3>
                        <small>assigned to groups</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Ungrouped Students</h5>
                        <h3 class="mb-0">{{ $ungroupedStudentCount ?? 0 }}</h3>
                        <small>needs grouping</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Offerings</h5>
                        <h3 class="mb-0">{{ $coordinatedOfferingCount ?? 0 }}</h3>
                        <small>coordinated</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <form method="GET" action="{{ route('coordinator.classlist.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="name" class="form-label">Filter by Name:</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter student name..." value="{{ request('name') }}">
                </div>
                <div class="col-md-2">
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
                    <label for="offering" class="form-label">Filter by Offering:</label>
                    <select name="offering" id="offering" class="form-select">
                        <option value="">All Coordinated Offerings</option>
                        @foreach($coordinatedOfferings as $offering)
                            <option value="{{ $offering->id }}" {{ (string) request('offering') === (string) $offering->id ? 'selected' : '' }}>
                                {{ $offering->subject_code }} - {{ $offering->subject_title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="search" class="form-label">General Search:</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search by ID, email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
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
            <div class="mb-3 fw-semibold" style="font-size:1.1rem;">Students in Your Coordinated Offerings <span class="text-primary">({{ $activeTerm->semester }})</span></div>
            @if ($students->isEmpty())
                <div class="text-center text-muted">No students found in your coordinated offerings for this semester.</div>
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
