@props(['name', 'errors', 'image' => null, 'disabled' => false, 'class' => null])

<style>
    .image-input-placeholder {
        background-image: url('/assets/media/svg/files/blank-image.svg');
    }

    [data-bs-theme="dark"] .image-input-placeholder {
        background-image: url('/assets/media/svg/files/blank-image-dark.svg');
    }
</style>

<div id="imageInput" class="image-input image-input-empty image-input-outline mb-3 mx-auto text-center {{ $class }}"
    data-kt-image-input="true" style="max-width: 180px; position: relative;"
    data-initial-image="{{ $image ? asset('storage/tenant' . tenancy()->tenant->id . '/' . $image) : '' }}">
    <div class="image-input-wrapper w-150px h-150px mx-auto">
    </div>

    <!-- File Input -->
    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
        style="position: absolute; top: 10px; right: 10px;" data-kt-image-input-action="change" data-bs-toggle="tooltip"
        title="@lang('employee::general.choose_image')">
        <input type="file" name="{{ $name }}" accept=".png, .jpg, .jpeg" @disabled($disabled)
            class="@error($name) is-invalid @enderror" />
        <i class="ki-outline ki-pencil fs-7"></i>
        <input type="hidden" name="{{ $name }}_old" value="1">
    </label>

    <!-- Cancel Button -->
    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
        style="position: absolute; top: 10px; left: 10px;" data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
        title="@lang('employee::general.cancel')">
        <i class="ki-outline ki-cross fs-2"></i>
    </span>
</div>

@if ($errors->has($name))
    <div class="invalid-feedback d-block" id="image_error">{{ $errors->first($name) }}</div>
@endif
