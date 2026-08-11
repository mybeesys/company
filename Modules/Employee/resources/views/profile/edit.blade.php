@extends('layouts.app')

@section('title', __('employee::general.profile_page_title'))

@section('css')
    <style>
        .profile-page .profile-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 6px 22px rgba(62, 57, 107, 0.08);
        }

        .profile-page .profile-hero {
            background: linear-gradient(135deg, #fffef0 0%, #ffffff 55%, #f8f9fc 100%);
            border: 1px solid #eef1f7;
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.5rem;
        }

        .profile-password-toggle {
            position: absolute;
            top: 50%;
            inset-inline-end: 0.75rem;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #7e8299;
            padding: 0;
            line-height: 1;
        }

        .profile-password-field {
            position: relative;
        }

        .profile-password-field .form-control {
            padding-inline-end: 2.5rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-5 profile-page">
        <div class="profile-hero">
            <h1 class="fs-2 fw-bold mb-1">@lang('employee::general.profile_page_title')</h1>
            <p class="text-muted mb-0">@lang('employee::general.profile_page_subtitle')</p>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="form">
            @csrf
            @method('PATCH')

            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="card profile-card">
                        <div class="card-body text-center p-6">
                            <x-form.form-card :title="__('employee::fields.employee_image')" bodyClass="text-center p-0 border-0 shadow-none">
                                <x-form.image-input :errors="$errors" name="image" image="{{ $employee->image }}" />
                                <div class="text-muted fs-7 mt-2">@lang('employee::general.image_hint')</div>
                            </x-form.form-card>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card profile-card mb-5">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold">@lang('employee::general.profile_details_section')</h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <x-form.input-div class="mb-0">
                                        <x-form.input required :errors="$errors" name="name" :label="__('employee::fields.name')"
                                            value="{{ old('name', $employee->name) }}" />
                                    </x-form.input-div>
                                </div>
                                <div class="col-md-6">
                                    <x-form.input-div class="mb-0">
                                        <x-form.input required :errors="$errors" name="name_en" :label="__('employee::fields.name_en')"
                                            value="{{ old('name_en', $employee->name_en) }}" />
                                    </x-form.input-div>
                                </div>
                                <div class="col-md-6">
                                    <x-form.input-div class="mb-0">
                                        <x-form.input required type="email" :errors="$errors" name="email" :label="__('employee::fields.email')"
                                            value="{{ old('email', $employee->email) }}" />
                                    </x-form.input-div>
                                </div>
                                <div class="col-md-6">
                                    <x-form.input-div class="mb-0">
                                        <x-form.input :errors="$errors" name="phone_number" :label="__('employee::fields.phone_number')"
                                            value="{{ old('phone_number', $employee->phone_number) }}" />
                                    </x-form.input-div>
                                </div>
                                @if ($employee->ems_access)
                                    <div class="col-md-6">
                                        <x-form.input-div class="mb-0">
                                            <x-form.input :errors="$errors" name="user_name" :label="__('employee::fields.user_name')"
                                                value="{{ old('user_name', $employee->user_name) }}" />
                                        </x-form.input-div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card profile-card">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold">@lang('employee::general.profile_password_section')</h3>
                        </div>
                        <div class="card-body pt-0">
                            <p class="text-muted fs-7 mb-5">@lang('employee::general.profile_password_hint')</p>
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <x-form.input-div class="mb-0">
                                        <label class="form-label" for="current_password">{{ __('employee::fields.current_password') }}</label>
                                        <div class="profile-password-field">
                                            <input type="password" name="current_password" id="current_password"
                                                class="form-control form-control-solid @error('current_password') is-invalid @enderror"
                                                autocomplete="current-password" />
                                            <button type="button" class="profile-password-toggle" data-target="current_password"
                                                aria-label="@lang('employee::general.show_password')">
                                                <i class="ki-outline ki-eye fs-5"></i>
                                            </button>
                                        </div>
                                        @error('current_password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </x-form.input-div>
                                </div>
                                <div class="col-md-6">
                                    <x-form.input-div class="mb-0">
                                        <label class="form-label" for="password">{{ __('employee::fields.new_password') }}</label>
                                        <div class="profile-password-field">
                                            <input type="password" name="password" id="password"
                                                class="form-control form-control-solid @error('password') is-invalid @enderror"
                                                autocomplete="new-password" />
                                            <button type="button" class="profile-password-toggle" data-target="password"
                                                aria-label="@lang('employee::general.show_password')">
                                                <i class="ki-outline ki-eye fs-5"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </x-form.input-div>
                                </div>
                                <div class="col-md-6">
                                    <x-form.input-div class="mb-0">
                                        <label class="form-label" for="password_confirmation">{{ __('employee::fields.password_confirmation') }}</label>
                                        <div class="profile-password-field">
                                            <input type="password" name="password_confirmation" id="password_confirmation"
                                                class="form-control form-control-solid @error('password_confirmation') is-invalid @enderror"
                                                autocomplete="new-password" />
                                            <button type="button" class="profile-password-toggle" data-target="password_confirmation"
                                                aria-label="@lang('employee::general.show_password')">
                                                <i class="ki-outline ki-eye fs-5"></i>
                                            </button>
                                        </div>
                                        @error('password_confirmation')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </x-form.input-div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-5">
                        <a href="{{ route('dashboard') }}" class="btn btn-light">@lang('messages.cancel')</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-outline ki-check fs-4 me-1"></i>
                            @lang('messages.save')
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script src="{{ url('js/general.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            handleImageInput('imageInput', 'image');

            document.querySelectorAll('.profile-password-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const input = document.getElementById(btn.dataset.target);
                    if (!input) {
                        return;
                    }
                    const isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    btn.querySelector('i').classList.toggle('ki-eye', !isPassword);
                    btn.querySelector('i').classList.toggle('ki-eye-slash', isPassword);
                });
            });
        });
    </script>
@endsection
