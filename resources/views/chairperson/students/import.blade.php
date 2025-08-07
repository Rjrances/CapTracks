@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4 text-center">📥 Import Student List</h2>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Card --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('chairperson.upload-students') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Select Excel/CSV File</label>
                    <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                </div>
                <button type="submit" class="btn btn-primary w-100">Upload & Import</button>
            </form>

            {{-- Format Information --}}
            <div class="mt-3">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-1"></i>Excel Format Required
                    </h6>
                    <p class="mb-2">Your Excel file should have these columns:</p>
                    <ul class="mb-0">
                        <li><strong>student_id</strong> - Student ID number</li>
                        <li><strong>name</strong> - Full name</li>
                        <li><strong>email</strong> - Email address</li>
                        <li><strong>semester</strong> - Current semester</li>
                        <li><strong>course</strong> - Course/Program</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
