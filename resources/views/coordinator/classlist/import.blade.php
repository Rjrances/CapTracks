@extends('layouts.coordinator')

@section('title', 'Import students')

@section('content')
<div class="container-fluid py-4" style="max-width: 720px;">
    <div class="mb-4">
        <a href="{{ route('coordinator.classlist.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i>Back to class list
        </a>
        <h1 class="fw-bold mb-1">Import students</h1>
        <p class="text-muted mb-0">Upload the same CSV format used by the chairperson bulk import. Choose your offering so enrollment stays scoped to classes you coordinate.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($offerings->isEmpty())
        <div class="alert alert-warning">
            You have no offerings assigned as coordinator. Imports must target an offering you coordinate.
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('coordinator.classlist.import.store') }}" method="POST" enctype="multipart/form-data" id="coord-import-form">
                    @csrf
                    <div class="mb-3">
                        <label for="offering_id" class="form-label">Offering</label>
                        <select name="offering_id" id="offering_id" class="form-select" required>
                            @foreach($offerings as $off)
                                <option value="{{ $off->id }}" {{ (string) ($selectedOfferingId ?? '') === (string) $off->id ? 'selected' : '' }}>
                                    {{ $off->subject_code }} — {{ $off->subject_title }}
                                    @if($off->academicTerm)
                                        ({{ $off->academicTerm->semester }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Students are imported and enrolled according to the CSV (offer_code column). The offering above scopes this action to your coordinated classes.</div>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">CSV file</label>
                        <input type="file" name="file" id="file" class="form-control" required accept=".csv">
                        <div class="form-text">Maximum 10 MB. <a href="/student_import_template_final.csv" download>Download template</a></div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i>Upload &amp; import
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
