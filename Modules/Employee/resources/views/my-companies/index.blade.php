@extends('layouts.app')

@section('title', __('employee::my_companies.title'))

@section('css')
    <style>
        .my-companies-page .dash-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 6px 22px rgba(62, 57, 107, 0.08);
        }

        .my-companies-hero {
            background: linear-gradient(135deg, #fffef0 0%, #ffffff 55%, #f8f9fc 100%);
            border: 1px solid #eef1f7;
            overflow: hidden;
            position: relative;
        }

        .my-companies-hero::after {
            content: '';
            position: absolute;
            top: -40px;
            inset-inline-end: -40px;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: rgba(245, 233, 2, 0.18);
            pointer-events: none;
        }

        .my-companies-stat {
            border-radius: 12px;
            border: 1px solid #eef1f7;
            background: #fff;
            padding: 1rem 1.15rem;
            min-width: 140px;
        }

        .my-companies-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #181c32;
            line-height: 1.2;
        }

        .my-companies-stat-label {
            font-size: 0.8rem;
            color: #7e8299;
            margin-top: 0.25rem;
        }

        .company-card {
            border: 1px solid #eef1f7;
            border-radius: 14px;
            background: #fff;
            padding: 1.25rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .company-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(26, 39, 89, 0.08);
        }

        .company-card.is-current {
            border-color: rgba(80, 205, 137, 0.45);
            background: linear-gradient(180deg, #f8fff9 0%, #ffffff 100%);
            box-shadow: 0 8px 20px rgba(80, 205, 137, 0.12);
        }

        .company-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff8d6;
            color: #b8860b;
            flex-shrink: 0;
        }

        .company-card.is-current .company-card-icon {
            background: #e8fff3;
            color: #50cd89;
        }

        .company-card-domain {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.85rem;
            color: #7e8299;
            direction: ltr;
            text-align: start;
            word-break: break-all;
        }

        .company-card-footer {
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px dashed #eef1f7;
        }

        .my-companies-empty {
            padding: 3.5rem 1.5rem;
            text-align: center;
        }

        .my-companies-empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            margin: 0 auto 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f8fa;
            color: #a1a5b7;
        }

        [data-bs-theme="dark"] .my-companies-hero {
            background: linear-gradient(135deg, #1e1e2d 0%, #151521 100%);
            border-color: #2b2b40;
        }

        [data-bs-theme="dark"] .my-companies-stat,
        [data-bs-theme="dark"] .company-card {
            background: #1e1e2d;
            border-color: #2b2b40;
        }

        [data-bs-theme="dark"] .company-card.is-current {
            background: linear-gradient(180deg, #1a2e26 0%, #1e1e2d 100%);
            border-color: rgba(80, 205, 137, 0.35);
        }

        [data-bs-theme="dark"] .my-companies-stat-value,
        [data-bs-theme="dark"] .company-card .fw-bold {
            color: #f5f8fa;
        }
    </style>
@endsection

@section('content')
    @php
        $currentCount = $companies->where('is_current', true)->count();
        $otherCount = $companies->count() - $currentCount;

        $roleLabel = static function (?string $role): string {
            return match ($role) {
                'owner' => __('employee::my_companies.roles.owner'),
                'admin' => __('employee::my_companies.roles.admin'),
                default => __('employee::my_companies.roles.member'),
            };
        };

        $roleBadgeClass = static function (?string $role): string {
            return match ($role) {
                'owner' => 'badge-light-warning',
                'admin' => 'badge-light-primary',
                default => 'badge-light-secondary',
            };
        };
    @endphp

    <div class="my-companies-page">
        <div class="card dash-card my-companies-hero mb-6">
            <div class="card-body position-relative">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
                    <div class="flex-grow-1" style="max-width: 720px;">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="company-card-icon">
                                <i class="ki-outline ki-abstract-26 fs-2x"></i>
                            </span>
                            <div>
                                <h2 class="mb-1 fs-2 fw-bold">@lang('employee::my_companies.title')</h2>
                                <p class="text-muted mb-0 fs-6">@lang('employee::my_companies.subtitle')</p>
                            </div>
                        </div>
                    </div>

                    @if ($companies->isNotEmpty())
                        <div class="d-flex flex-wrap gap-3">
                            <div class="my-companies-stat">
                                <div class="my-companies-stat-value">{{ $companies->count() }}</div>
                                <div class="my-companies-stat-label">@lang('employee::my_companies.stats_total')</div>
                            </div>
                            <div class="my-companies-stat">
                                <div class="my-companies-stat-value text-success">{{ $currentCount }}</div>
                                <div class="my-companies-stat-label">@lang('employee::my_companies.stats_current')</div>
                            </div>
                            @if ($otherCount > 0)
                                <div class="my-companies-stat">
                                    <div class="my-companies-stat-value text-primary">{{ $otherCount }}</div>
                                    <div class="my-companies-stat-label">@lang('employee::my_companies.stats_available')</div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($companies->isEmpty())
            <div class="card dash-card">
                <div class="card-body my-companies-empty">
                    <div class="my-companies-empty-icon">
                        <i class="ki-outline ki-information-2 fs-2x"></i>
                    </div>
                    <h4 class="mb-2">@lang('employee::my_companies.empty_title')</h4>
                    <p class="text-muted mb-0 mx-auto" style="max-width: 560px;">@lang('employee::my_companies.empty')</p>
                </div>
            </div>
        @else
            <div class="row g-4">
                @foreach ($companies as $company)
                    <div class="col-md-6 col-xl-4">
                        <div @class(['company-card', 'is-current' => $company->is_current])>
                            <div class="d-flex align-items-start gap-3">
                                <span class="company-card-icon">
                                    <i class="ki-outline ki-office-bag fs-2x"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <h5 class="fw-bold mb-0 text-truncate">{{ $company->company_name }}</h5>
                                        @if ($company->is_current)
                                            <span class="badge badge-light-success">@lang('employee::my_companies.current')</span>
                                        @endif
                                    </div>
                                    <div class="company-card-domain">{{ $company->domain }}</div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="text-muted fs-7">@lang('employee::my_companies.role'):</span>
                                <span @class(['badge', $roleBadgeClass($company->role)])>
                                    {{ $roleLabel($company->role) }}
                                </span>
                            </div>

                            <div class="company-card-footer">
                                @if ($company->is_current)
                                    <div class="d-flex align-items-center gap-2 text-success">
                                        <i class="ki-outline ki-check-circle fs-4"></i>
                                        <span class="fw-semibold fs-7">@lang('employee::my_companies.already_here')</span>
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        class="btn btn-primary w-100 js-open-company"
                                        data-url="{{ route('my-companies.switch', ['tenantId' => $company->tenant_id]) }}"
                                    >
                                        <span class="indicator-label d-inline-flex align-items-center justify-content-center gap-2">
                                            <i class="ki-outline ki-exit-right fs-4"></i>
                                            @lang('employee::my_companies.open_company')
                                        </span>
                                        <span class="indicator-progress d-none">
                                            @lang('employee::my_companies.opening')
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('script')
    <script>
        document.querySelectorAll('.js-open-company').forEach((button) => {
            button.addEventListener('click', async function () {
                const endpoint = this.dataset.url;
                if (!endpoint) return;

                const label = this.querySelector('.indicator-label');
                const progress = this.querySelector('.indicator-progress');

                this.disabled = true;
                label?.classList.add('d-none');
                progress?.classList.remove('d-none');

                try {
                    const response = await fetch(endpoint, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('switch failed');
                    }

                    const data = await response.json();
                    if (data.url) {
                        window.location.href = data.url;
                    }
                } catch (error) {
                    alert(@json(__('employee::my_companies.switch_failed')));
                    this.disabled = false;
                    label?.classList.remove('d-none');
                    progress?.classList.add('d-none');
                }
            });
        });
    </script>
@endsection
