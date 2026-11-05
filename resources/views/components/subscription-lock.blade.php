@php
    $reason = subscription_lock_reason();
    $isMissing = $reason === 'missing';
    $onDashboard = request()->routeIs('dashboard') || request()->path() === '' || request()->path() === '/';
@endphp

<div class="subscription-lock-ribbon" role="status">
    <p>@lang($isMissing ? 'general::general.subscription_lock_body_missing' : 'general::general.subscription_lock_ribbon')</p>
    <a href="{{ route('subscription.manage') }}">@lang('general::general.subscription_lock_renew')</a>
</div>

@if ($onDashboard)
    <div class="subscription-lock-modal" id="subscriptionLockModal" role="dialog" aria-modal="true" aria-labelledby="subscriptionLockTitle">
        <div class="subscription-lock-modal__backdrop" data-subscription-lock-close></div>
        <div class="subscription-lock-modal__card">
            <div class="subscription-lock-modal__mark" aria-hidden="true">
                <img src="{{ asset('assets/media/logos/1-15.png') }}" alt="">
            </div>
            <p class="subscription-lock-modal__kicker">My Bee</p>
            <h2 id="subscriptionLockTitle">
                @lang($isMissing ? 'general::general.subscription_lock_title_missing' : 'general::general.subscription_lock_title')
            </h2>
            <p class="subscription-lock-modal__body">
                @lang($isMissing ? 'general::general.subscription_lock_body_missing' : 'general::general.subscription_lock_body')
            </p>
            <div class="subscription-lock-modal__actions">
                <a class="subscription-lock-modal__primary" href="{{ route('subscription.manage') }}">
                    @lang('general::general.subscription_lock_renew')
                </a>
                <a class="subscription-lock-modal__secondary" href="{{ route('subscription') }}">
                    @lang('general::general.subscription_lock_review')
                </a>
            </div>
            <button type="button" class="subscription-lock-modal__quiet" data-subscription-lock-close>
                @lang('general::general.subscription_lock_stay')
            </button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('subscriptionLockModal');
            if (!modal) return;
            modal.classList.add('is-open');
            document.body.classList.add('subscription-lock-open');
            modal.querySelectorAll('[data-subscription-lock-close]').forEach(function (el) {
                el.addEventListener('click', function () {
                    modal.classList.remove('is-open');
                    document.body.classList.remove('subscription-lock-open');
                });
            });
        });
    </script>
@endif

<style>
    .subscription-lock-ribbon {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        margin-bottom: 1rem;
        padding: 0.75rem 0.95rem;
        border: 1px solid rgba(184, 137, 18, 0.28);
        border-radius: 0.9rem;
        background: linear-gradient(180deg, #fffaf0 0%, #fff 100%);
        color: #5c4a16;
        font-weight: 700;
        line-height: 1.5;
    }
    .subscription-lock-ribbon p { margin: 0; font-size: 0.88rem; }
    .subscription-lock-ribbon a {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        min-height: 2.3rem;
        padding: 0.35rem 0.85rem;
        border-radius: 0.7rem;
        background: #ebb81e;
        color: #1a1a1a;
        font-weight: 800;
        text-decoration: none;
    }
    .subscription-lock-modal {
        position: fixed;
        inset: 0;
        z-index: 4000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
    }
    .subscription-lock-modal.is-open { display: flex; }
    .subscription-lock-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(23, 23, 23, 0.46);
    }
    .subscription-lock-modal__card {
        position: relative;
        width: min(100%, 28rem);
        padding: 1.6rem 1.4rem 1.25rem;
        border-radius: 1.25rem;
        background: #fffdf8;
        border: 1px solid rgba(184, 137, 18, 0.22);
        box-shadow: 0 24px 60px rgba(26, 26, 26, 0.18);
        text-align: center;
        font-family: "Cairo", system-ui, sans-serif;
    }
    .subscription-lock-modal__mark {
        width: 4.5rem;
        height: 4.5rem;
        margin: 0 auto 0.75rem;
        display: grid;
        place-items: center;
        border-radius: 1.1rem;
        background: radial-gradient(circle at 30% 20%, #fff, #fff4d2 70%);
        border: 1px solid rgba(184, 137, 18, 0.2);
    }
    .subscription-lock-modal__mark img { width: 2.7rem; height: auto; }
    .subscription-lock-modal__kicker {
        margin: 0 0 0.35rem;
        color: #b88912;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.04em;
    }
    .subscription-lock-modal__card h2 {
        margin: 0 0 0.55rem;
        font-size: 1.35rem;
        font-weight: 800;
        color: #1a1a1a;
    }
    .subscription-lock-modal__body {
        margin: 0 auto 1.15rem;
        max-width: 24rem;
        color: #5c6470;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.7;
    }
    .subscription-lock-modal__actions {
        display: grid;
        gap: 0.55rem;
    }
    .subscription-lock-modal__primary,
    .subscription-lock-modal__secondary {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 2.8rem;
        border-radius: 0.85rem;
        font-weight: 800;
        text-decoration: none;
    }
    .subscription-lock-modal__primary {
        background: linear-gradient(180deg, #f0c63a, #ebb81e 55%, #d4a417);
        color: #1a1a1a;
    }
    .subscription-lock-modal__secondary {
        background: #fff;
        color: #1a1a1a;
        border: 1px solid rgba(26, 26, 26, 0.1);
    }
    .subscription-lock-modal__quiet {
        margin-top: 0.75rem;
        border: 0;
        background: transparent;
        color: #8a8376;
        font: inherit;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
    }
    @media (max-width: 640px) {
        .subscription-lock-ribbon { flex-direction: column; align-items: stretch; }
        .subscription-lock-ribbon a { justify-content: center; }
    }
</style>
