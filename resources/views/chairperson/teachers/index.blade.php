@extends('layouts.chairperson')

@section('content')
    <div class="container">
        <h2 class="mb-4">Teachers List</h2>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        <tr>
                            <td>{{ $teacher->name }}</td>
                            <td>{{ $teacher->email }}</td>
                            <td>{{ ucfirst($teacher->role) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No teachers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
