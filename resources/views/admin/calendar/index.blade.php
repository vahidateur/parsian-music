@extends('layouts.dashboard')

@section('title', __('admin.weekly_calendar'))
@section('breadcrumb'){{ __('admin.weekly_calendar') }}@endsection

@section('content')
{{-- Admin calendar page composition. Data is supplied by CalendarController. --}}
<h1 id="calendar-page-title" class="calendar-page__title">{{ __('admin.weekly_calendar') }}</h1>

<x-calendar-layout
    :teachers="$teachers"
    :students="$students"
    :rooms="$rooms"
    :instruments="$instruments"
    :events-url="route('admin.calendar.events')"
/>

@vite('resources/js/calendar/calendar-app.js')
@endsection
