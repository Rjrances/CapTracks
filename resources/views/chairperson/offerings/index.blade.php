@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Manage Offerings</h2>
    <a href="{{ route('offerings.create') }}" class="btn btn-success mb-3">Add Offering</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Offering Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($offerings as $offering)
                <tr>
                    <td>{{ $offering->name }}</td>
                    <td>{{ $offering->description }}</td>
                    <td>
                        <a href="{{ route('offerings.edit', $offering->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        <form action="{{ route('offerings.destroy', $offering->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this offering?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
