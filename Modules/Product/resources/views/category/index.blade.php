@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/js/app.jsx')
    <div class="card mb-5 mb-xl-8">
											<!--begin::Header-->
	   <div class="card-header border-0 pt-5">
												<h3 class="card-title align-items-start flex-column">
													<span class="card-label fw-bold fs-3 mb-1">Category List</span>
													<span class="text-muted mt-1 fw-semibold fs-7">Product List</span>
												</h3>
												<div class="card-toolbar">
												<div class="d-flex align-items-center gap-2 gap-lg-3">
											<a href="#" class="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold" data-bs-toggle="modal" data-bs-target="#kt_modal_view_users">Add</a>
										</div>
											</div>
										</div>
										<!--end::Header-->
										<!--begin::Body-->
										<div class="card-body">
									
      <div id="react-root" data-user="{{ json_encode($tree) }}"></div>

</div>

    </div>
   
@endsection
