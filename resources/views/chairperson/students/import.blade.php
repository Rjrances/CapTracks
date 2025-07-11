@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Import Student List</h2>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Upload Form --}}
    <form action="{{ route('chairperson.upload-students') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="file" class="form-label">Upload Excel/CSV File</label>
            <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
        </div>
        <button type="submit" class="btn btn-primary">Import Students</button>
    </form>

    {{-- Optional: Sample Template Download --}}
    <div class="mt-3">
        <a href="{{ asset('samples/student_template.xlsx') }}" class="btn btn-outline-secondary">Download Sample Template</a>
    </div>
</div>
@endsection
