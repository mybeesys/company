@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees_working_hours'))

@section('content')
<x-cards.card>

</x-cards.card>
@endsection

@section('script')
@parent
<script src="{{ url('/js/table.js') }}"></script>
<script type="text/javascript" src="vfs_fonts.js"></script>
<script>

</script>
@endsection