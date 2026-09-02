@extends('layouts.app')

@section('title', __('employee::referrals.title'))

@section('css')
    <style>
        .referrals-page .dash-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 6px 22px rgba(62, 57, 107, 0.08);
        }

        .referrals-hero {
            background: linear-gradient(135deg, #fffef0 0%, #ffffff 55%, #f8f9fc 100%);
            border: 1px solid #eef1f7;
            overflow: hidden;
            position: relative;
        }

        .referrals-hero::after {
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

        .referrals-stat {
            border-radius: 12px;
            border: 1px solid #eef1f7;
            background: #fff;
            padding: 1rem 1.15rem;
            min-width: 130px;
        }

        .referrals-stat-value {
            font-size: 1.45rem;
            font-weight: 700;
            color: #181c32;
        }

        .referrals-stat-label {
            font-size: 0.78rem;
            color: #7e8299;
            margin-top: 0.25rem;
        }

        .referrals-copy-box {
            background: #f8f9fc;
            border: 1px dashed #dbe1ea;
            border-radius: 12px;
            padding: 1rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.9rem;
            word-break: break-all;
            direction: ltr;
            text-align: start;
        }

        .referrals-textarea {
            min-height: 140px;
            resize: vertical;
            direction: inherit;
            white-space: pre-wrap;
        }

        .activity-item {
            border-bottom: 1px dashed #eef1f7;
            padding: 0.85rem 0;
        }

        .activity-item:last-child { border-bottom: 0; }
    </style>
@endsection

@section('content')
    <div class="referrals-page">
        <div class="card dash-card referrals-hero mb-6">
            <div class="card-body position-relative">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
                    <div class="flex-grow-1" style="max-width: 720px;">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="symbol symbol-50px">
                                <span class="symbol-label bg-light-warning">
                                    <i class="ki-outline ki-gift fs-2x text-warning"></i>
                                </span>
                            </span>
                            <div>
                                <h2 class="mb-1 fs-2 fw-bold">@lang('employee::referrals.title')</h2>
                                <p class="text-muted mb-0">@lang('employee::referrals.subtitle')</p>
                            </div>
                        </div>
                    </div>

                    @if (($stats['total_points'] ?? 0) > 0)
                        <div class="referrals-stat">
                            <div class="referrals-stat-value text-warning">{{ $stats['total_points'] }}</div>
                            <div class="referrals-stat-label">@lang('employee::referrals.stats_points')</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if (! $ready)
            <div class="card dash-card">
                <div class="card-body text-center py-10 text-muted">
                    @lang('employee::referrals.not_ready')
                </div>
            </div>
        @elseif (! $enabled)
            <div class="card dash-card">
                <div class="card-body text-center py-10 text-muted">
                    @lang('employee::referrals.program_disabled')
                </div>
            </div>
        @else
            @if ($stats)
                <div class="d-flex flex-wrap gap-3 mb-6">
                    @foreach ([
                        ['value' => $stats['invitations'], 'label' => __('employee::referrals.stats_invitations')],
                        ['value' => $stats['visits'], 'label' => __('employee::referrals.stats_visits')],
                        ['value' => $stats['distinct_visits'], 'label' => __('employee::referrals.stats_distinct')],
                        ['value' => $stats['conversions'], 'label' => __('employee::referrals.stats_conversions')],
                        ['value' => $stats['total_points'], 'label' => __('employee::referrals.stats_points')],
                    ] as $stat)
                        <div class="referrals-stat">
                            <div class="referrals-stat-value">{{ $stat['value'] }}</div>
                            <div class="referrals-stat-label">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="row g-4 mb-6">
                <div class="col-lg-6">
                    <div class="card dash-card h-100">
                        <div class="card-body">
                            <h4 class="mb-4">@lang('employee::referrals.your_link')</h4>
                            <div class="referrals-copy-box mb-3" id="referral-link">{{ $inviteUrl }}</div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-light-primary js-copy" data-target="referral-link">
                                    <i class="ki-outline ki-copy fs-4 me-1"></i>
                                    @lang('employee::referrals.copy_link')
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card dash-card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">@lang('employee::referrals.promotional_text')</h4>
                            <textarea class="form-control referrals-textarea mb-3" id="referral-text" readonly>{{ $promotionalText }}</textarea>
                            <p class="text-muted fs-7 mb-3">@lang('employee::referrals.admin_text_hint')</p>
                            <button type="button" class="btn btn-primary js-copy-text" data-target="referral-text">
                                <i class="ki-outline ki-copy fs-4 me-1"></i>
                                @lang('employee::referrals.copy_text')
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @dashboardcan(\Modules\Employee\Support\ReferralsPermissions::CREATE)
            <div class="card dash-card mb-6">
                <div class="card-body">
                    <h4 class="mb-4">@lang('employee::referrals.send_email')</h4>
                    <form method="POST" action="{{ route('referrals.send') }}">
                        @csrf
                        <div class="mb-3">
                            <textarea
                                name="emails"
                                class="form-control"
                                rows="3"
                                placeholder="@lang('employee::referrals.emails_placeholder')"
                                required
                            >{{ old('emails') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-outline ki-sms fs-4 me-1"></i>
                            @lang('employee::referrals.send_button')
                        </button>
                    </form>
                </div>
            </div>
            @enddashboardcan

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card dash-card h-100">
                        <div class="card-body">
                            <h4 class="mb-4">@lang('employee::referrals.recent_invitations')</h4>
                            @forelse ($recentInvitations as $invitation)
                                <div class="activity-item d-flex justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $invitation->channel === 'email' ? __('employee::referrals.channel_email') : __('employee::referrals.channel_copy') }}
                                        </div>
                                        @if ($invitation->recipient_emails)
                                            <div class="text-muted fs-7">{{ implode(', ', json_decode($invitation->recipient_emails, true) ?: []) }}</div>
                                        @endif
                                    </div>
                                    <div class="text-muted fs-7 text-nowrap">{{ \Carbon\Carbon::parse($invitation->created_at)->diffForHumans() }}</div>
                                </div>
                            @empty
                                <div class="text-muted">@lang('employee::referrals.no_activity')</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card dash-card h-100">
                        <div class="card-body">
                            <h4 class="mb-4">@lang('employee::referrals.recent_conversions')</h4>
                            @forelse ($recentConversions as $conversion)
                                <div class="activity-item d-flex justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $conversion->company_name ?: '—' }}</div>
                                        <div class="text-muted fs-7">
                                            @lang('employee::referrals.conversion_status_' . $conversion->status)
                                            · @lang('employee::referrals.conversion_points', ['points' => $conversion->points_awarded])
                                        </div>
                                    </div>
                                    <div class="text-muted fs-7 text-nowrap">{{ \Carbon\Carbon::parse($conversion->created_at)->diffForHumans() }}</div>
                                </div>
                            @empty
                                <div class="text-muted">@lang('employee::referrals.no_activity')</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('script')
    <script>
        const copiedLabel = @json(__('employee::referrals.copied'));
        const copyRecordUrl = @json(route('referrals.copy'));
        const csrf = @json(csrf_token());

        async function recordCopy() {
            try {
                await fetch(copyRecordUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });
            } catch (e) {}
        }

        async function copyText(value, button) {
            try {
                await navigator.clipboard.writeText(value);
            } catch (e) {
                const area = document.createElement('textarea');
                area.value = value;
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                document.body.removeChild(area);
            }

            if (button) {
                const original = button.innerHTML;
                button.innerHTML = copiedLabel;
                setTimeout(() => button.innerHTML = original, 1500);
            }
        }

        document.querySelectorAll('.js-copy').forEach((button) => {
            button.addEventListener('click', async () => {
                const target = document.getElementById(button.dataset.target);
                if (!target) return;
                await copyText(target.textContent.trim(), button);
            });
        });

        document.querySelector('.js-copy-text')?.addEventListener('click', async (event) => {
            const targetId = event.currentTarget.dataset.target || 'referral-text';
            const el = document.getElementById(targetId);
            const text = el?.value ?? el?.textContent ?? '';
            await copyText(text.trim(), event.currentTarget);
            await recordCopy();
        });
    </script>
@endsection
