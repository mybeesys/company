@extends('layouts.app')
@section('title', __('franchise::lang.franchise'))

@section('content')
    <div class="container-fluid py-3">
        @include('franchise::partials.hub-tabs')

        @include('franchise::companies.partials.list')
    </div>
@endsection

@section('script')
    @include('franchise::companies.partials.list-scripts')
@endsection
