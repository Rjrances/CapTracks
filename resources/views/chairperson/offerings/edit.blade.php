@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Edit Offering</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('chairperson.offerings.update', $offering->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="form-group">
        <label for="subject_title">Subject Title</label>
        <input type="text" name="subject_title" class="form-control" value="{{ old('subject_title', $offering->subject_title) }}" required>
    </div>

    <div class="form-group">
        <label for="subject_code">Offer Code</label>
        <input type="text" name="subject_code" class="form-control" value="{{ old('subject_code', $offering->subject_code) }}" required>
    </div>

    <div class="form-group">
        <label for="teacher_name">Teacher Name</label>
        <input type="text" name="teacher_name" class="form-control" value="{{ old('teacher_name', $offering->teacher_name) }}" required>
    </div>

    <button type="submit" class="btn btn-primary">Update</button>
</form>

</div>
@endsection
