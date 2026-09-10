@extends('layouts.app')

@section('title', 'Report received — '.config('app.name'))

@section('content')
<section class="container py-5" style="max-width: 560px;">
    <div class="bg-white rounded-4 shadow-sm p-4 p-md-5 text-center">
        <div class="badge text-bg-success mb-3">Submitted</div>
        <h1 class="h4 fw-bold">Thank you</h1>
        <p class="text-secondary">Your report <strong>{{ $report->report_no }}</strong> was sent to the district office.</p>
        <a href="{{ route('home') }}" class="btn btn-rflms">Back to home</a>
    </div>
</section>
@endsection
