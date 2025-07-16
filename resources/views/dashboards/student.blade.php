@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 900px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1 text-center" style="font-size:2.5rem; margin-bottom:0.1rem;">Student Dashboard</h1>
            <div class="text-muted text-center" style="font-size:1.1rem; margin-bottom:0;">Access your project, group, proposal, and milestones</div>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
            <a href="{{ route('student.project') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">Project Submissions</a>
            <a href="{{ route('student.group') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">Group</a>
            <a href="{{ route('student.proposal') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">Proposal & Endorsement</a>
            <a href="{{ route('student.milestones') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">Milestones</a>
        </div>
    </div>
</div>
@endsection 