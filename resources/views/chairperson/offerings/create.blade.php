@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Add Offering</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('chairperson.offerings.store') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="subject_title">Subject Title</label>
        <input type="text" name="subject_title" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="subject_code">Offer Code</label>
        <input type="text" name="subject_code" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="teacher_name">Teacher Name</label>
        <input type="text" name="teacher_name" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Save</button>
</form>

</div>
@endsection
