@foreach ($accounts as $account)
    @php
        $localeAr = app()->getLocale() === 'ar';
        $label = $localeAr ? $account->name_ar : $account->name_en;
        $depth = (int) ($account->depth ?? 0);
        $hasChildren = ! empty($account->has_children);
    @endphp
    <tr class="bs-account-row {{ $hasChildren ? 'bs-parent-row' : '' }}"
        data-account-id="{{ $account->id }}"
        @if($account->parent_account_id) data-parent-id="{{ $account->parent_account_id }}" @endif>
        <td>
            <div class="bs-account-label" style="padding-inline-start: {{ ($depth + 1) * 1.1 }}rem;">
                @if($hasChildren)
                    <button type="button" class="bs-toggle-btn" data-bs-toggle-account="{{ $account->id }}" aria-label="toggle">
                        <i class="fa fa-chevron-down fa-xs"></i>
                    </button>
                @else
                    <span style="width:1.1rem;display:inline-block;"></span>
                @endif
                <span class="bs-gl-code">{{ $account->gl_code }}</span>
                <span>{{ $label }}</span>
            </div>
        </td>
        @include('accounting::reports.partials.income-statement-amount', ['amount' => $account->balance])
    </tr>
@endforeach
