@extends('layouts.app')

@section('title', __('employee::my_companies.title'))

@section('content')
    <div class="card dash-card mb-6">
        <div class="card-body">
            <h3 class="mb-2">@lang('employee::my_companies.title')</h3>
            <p class="text-muted mb-0">@lang('employee::my_companies.subtitle')</p>
        </div>
    </div>

    <div class="card dash-card">
        <div class="card-body p-0">
            @if ($companies->isEmpty())
                <div class="p-10 text-center text-muted">@lang('employee::my_companies.empty')</div>
            @else
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th>@lang('employee::my_companies.company')</th>
                                <th>@lang('employee::my_companies.tenant')</th>
                                <th>@lang('employee::my_companies.role')</th>
                                <th class="text-end">@lang('employee::my_companies.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $company)
                                <tr>
                                    <td class="fw-semibold">{{ $company->company_name }}</td>
                                    <td>
                                        <span class="text-muted" dir="ltr">{{ $company->domain }}</span>
                                        @if ($company->is_current)
                                            <span class="badge badge-light-success ms-2">@lang('employee::my_companies.current')</span>
                                        @endif
                                    </td>
                                    <td>{{ $company->role }}</td>
                                    <td class="text-end">
                                        @if ($company->is_current)
                                            <span class="text-muted fs-7">@lang('employee::my_companies.already_here')</span>
                                        @else
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary js-open-company"
                                                data-url="{{ route('my-companies.switch', ['tenantId' => $company->tenant_id]) }}"
                                            >
                                                @lang('employee::my_companies.open_company')
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.querySelectorAll('.js-open-company').forEach((button) => {
            button.addEventListener('click', async function () {
                const endpoint = this.dataset.url;
                if (!endpoint) return;

                this.disabled = true;

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
                        window.open(data.url, '_blank', 'noopener,noreferrer');
                    }
                } catch (error) {
                    alert(@json(__('employee::my_companies.switch_failed')));
                } finally {
                    this.disabled = false;
                }
            });
        });
    </script>
@endsection
