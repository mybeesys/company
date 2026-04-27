@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">{{ __('menuItemLang.menu_feedback') }}</span>
            </h3>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gy-5">
                    <thead>
                        <tr class="fw-semibold text-muted">
                            <th>#</th>
                            <th>{{ __('reservation::lang.feedback_menu_title') }}</th>
                            <th>{{ __('reservation::lang.feedback_stars') }}</th>
                            <th>{{ __('reservation::lang.feedback_comment') }}</th>
                            <th>{{ __('reservation::lang.feedback_date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($feedbacks as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>
                                    <div class="fw-bold">{{ $row->menu_title ?? '—' }}</div>
                                    <div class="text-muted fs-7">{{ $row->menu_sub_title ?? '' }}</div>
                                    <div class="text-muted fs-8"><code>{{ \Illuminate\Support\Str::limit($row->token, 24) }}</code></div>
                                </td>
                                <td>
                                    <span class="text-warning">{{ str_repeat('★', (int) $row->stars) }}{{ str_repeat('☆', 5 - (int) $row->stars) }}</span>
                                </td>
                                <td style="max-width: 320px;">{{ $row->comment ?: '—' }}</td>
                                <td class="text-nowrap">{{ $row->created_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-10">{{ __('reservation::lang.feedback_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $feedbacks->links() }}
        </div>
    </div>
@endsection
