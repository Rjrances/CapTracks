@extends('layouts.coordinator')

@section('title', 'Class List')

@section('content')
<div class="max-w-6xl mx-auto p-6 rounded text-gray-900">
    <h1 class="text-4xl font-semibold mb-10 text-center border-b pb-5">Class List by Semester</h1>

    <form method="GET" action="{{ route('coordinator.classlist.index') }}" class="mb-10 max-w-xs mx-auto">
        <label for="semester" class="block font-semibold mb-3 text-gray-700 text-lg">Select Semester:</label>
        <select 
            name="semester" 
            id="semester" 
            class="w-full border border-gray-300 rounded-md px-5 py-3 text-gray-700 text-lg focus:outline-none focus:ring-2 focus:ring-blue-600 transition"
            onchange="this.form.submit()"
        >
            <option value="">-- Choose Semester --</option>
            @foreach ($semesters as $semester)
                <option value="{{ $semester }}" {{ $selectedSemester == $semester ? 'selected' : '' }}>
                    {{ $semester }}
                </option>
            @endforeach
        </select>
    </form>

    @if ($selectedSemester)
        <h2 class="text-3xl font-semibold mb-8 text-center text-gray-900 tracking-tight">
            Students Enrolled in {{ $selectedSemester }}
        </h2>

        @if ($students->isEmpty())
            <p class="text-center text-gray-600 text-lg italic">No students enrolled in this semester.</p>
        @else
            <div class="overflow-x-auto rounded-lg shadow border border-gray-200 bg-white">
                <table class="min-w-full text-left text-gray-700" style="table-layout: fixed; width: 100%;">
                    <thead class="bg-gray-100 border-b border-gray-300 uppercase text-sm font-semibold tracking-wide">
                        <tr>
                            <th class="px-6 py-4 w-1/4">Student ID</th>
                            <th class="px-6 py-4 w-1/4">Name</th>
                            <th class="px-6 py-4 w-1/4">Email</th>
                            <th class="px-6 py-4 w-1/4">Course</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr class="hover:bg-gray-50 border-b border-gray-200">
                                <td class="px-6 py-4 font-medium whitespace-nowrap" style="overflow-wrap: break-word;">
                                    {{ $student->student_id }}
                                </td>
                                <td class="px-6 py-4" style="overflow-wrap: break-word;">
                                    {{ $student->name }}
                                </td>
                                <td class="px-6 py-4 lowercase" style="overflow-wrap: break-word;">
                                    {{ $student->email }}
                                </td>
                                <td class="px-6 py-4" style="overflow-wrap: break-word;">
                                    {{ $student->course }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links --}}
            <div class="mt-8 flex justify-center">
                {{ $students->withQueryString()->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
