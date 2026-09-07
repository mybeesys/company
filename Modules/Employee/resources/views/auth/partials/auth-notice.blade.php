@php
    $notice = null;

    if ($errors->has('subscription_missing')) {
        $notice = [
            'tone' => 'warning',
            'icon' => 'ki-outline ki-information-5',
            'title' => __('employee::responses.auth_notice_subscription_missing_title'),
            'message' => $errors->first('subscription_missing'),
        ];
    } elseif ($errors->has('subscription_expired')) {
        $notice = [
            'tone' => 'warning',
            'icon' => 'ki-outline ki-time',
            'title' => __('employee::responses.auth_notice_subscription_expired_title'),
            'message' => $errors->first('subscription_expired'),
        ];
    } elseif ($errors->has('company')) {
        $notice = [
            'tone' => 'danger',
            'icon' => 'ki-outline ki-shield-cross',
            'title' => __('employee::responses.auth_notice_company_title'),
            'message' => $errors->first('company'),
        ];
    } elseif ($errors->any()) {
        $notice = [
            'tone' => 'danger',
            'icon' => 'ki-outline ki-information-2',
            'title' => __('employee::responses.auth_notice_general_title'),
            'message' => $errors->first(),
        ];
    }
@endphp

@if ($notice)
    <div class="login-auth-notice login-auth-notice--{{ $notice['tone'] }} mb-7" role="alert" aria-live="polite">
        <div class="login-auth-notice__icon" aria-hidden="true">
            <i class="{{ $notice['icon'] }}"></i>
        </div>
        <div class="login-auth-notice__content">
            <p class="login-auth-notice__title">{{ $notice['title'] }}</p>
            <p class="login-auth-notice__message">{{ $notice['message'] }}</p>
            @if (in_array($notice['tone'], ['warning'], true))
                <p class="login-auth-notice__hint mb-0">
                    @lang('employee::responses.auth_notice_support_hint')
                </p>
            @endif
        </div>
    </div>
@endif
