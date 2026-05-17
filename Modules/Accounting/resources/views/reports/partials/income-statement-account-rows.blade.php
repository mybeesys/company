@foreach ($accounts as $account)
    @php
        $localeAr = app()->getLocale() === 'ar';
        $label = $localeAr ? $account->name_ar : $account->name_en;
        $depth = (int) ($account->depth ?? 0);
        $hasChildren = ! empty($account->has_children);
    @endphp
    <tr class="is-account-row {{ $hasChildren ? 'is-parent-row' : '' }}"
        data-account-id="{{ $account->id }}"
        @if($account->parent_account_id) data-parent-id="{{ $account->parent_account_id }}" @endif
        data-depth="{{ $depth }}">
        <td>
            <div class="is-account-label" style="padding-inline-start: {{ $depth * 1.1 }}rem;">
                @if($hasChildren)
                    <button type="button" class="is-toggle-btn" data-toggle-account="{{ $account->id }}" aria-label="toggle">
                        <i class="fa fa-chevron-down fa-xs"></i>
                    </button>
                @else
                    <span class="is-indent"></span>
                @endif
                <span class="is-gl-code">{{ $account->gl_code }}</span>
                <span>{{ $label }}</span>
            </div>
        </td>
        @include('accounting::reports.partials.income-statement-amount', ['amount' => $account->amount])
    </tr>
@endforeach
