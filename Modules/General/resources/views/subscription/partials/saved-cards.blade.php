@php
    $repo = $cardsRepo;
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <p class="text-muted fs-7 mb-0">@lang('general::general.saved_cards_hint')</p>
    <a href="{{ route('subscription.cards.add') }}" class="sub-btn sub-btn--primary">
        <i class="fas fa-plus"></i>
        @lang('general::general.saved_card_add')
    </a>
</div>

@if (($savedCards ?? collect())->isEmpty())
    <div class="sub-empty">
        @lang('general::general.saved_cards_empty')
        <div class="mt-3">
            <a href="{{ route('subscription.cards.add') }}" class="sub-btn sub-btn--primary">@lang('general::general.saved_card_add')</a>
        </div>
    </div>
@else
    <div class="sub-invoice-list">
        @foreach ($savedCards as $card)
            @php
                $mask = strtoupper(($card->brand ?: 'CARD')).' •••• '.($card->last_four ?: '••••');
            @endphp
            <div class="sub-invoice-card">
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.85rem;">
                    <div>
                        <div class="fw-bold fs-5">
                            {{ $repo?->label($card) ?? $mask }}
                            @if ($card->is_default)
                                <span class="sub-pill sub-pill--ok ms-2">@lang('general::general.saved_card_default')</span>
                            @endif
                        </div>
                        <div class="text-muted fs-7 mt-1">
                            <span dir="ltr">{{ $mask }}</span>
                            · @lang('general::general.saved_card_expiry'): {{ $repo?->expiryLabel($card) ?? '—' }}
                            @if (! empty($card->holder))
                                · {{ $card->holder }}
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @unless ($card->is_default)
                            <form method="POST" action="{{ route('subscription.cards.default', ['cardId' => $card->id]) }}">
                                @csrf
                                <button type="submit" class="sub-btn sub-btn--ghost">@lang('general::general.saved_card_make_default')</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('subscription.cards.delete', ['cardId' => $card->id]) }}"
                              onsubmit="return confirm(@json(__('general::general.saved_card_delete_confirm')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sub-btn sub-btn--ghost text-danger">@lang('general::general.saved_card_delete')</button>
                        </form>
                    </div>
                </div>

                <details class="mt-3">
                    <summary class="sub-btn sub-btn--ghost" style="display:inline-flex;cursor:pointer;list-style:none;">
                        @lang('general::general.saved_card_edit')
                    </summary>
                    <form method="POST" action="{{ route('subscription.cards.update', ['cardId' => $card->id]) }}" class="mt-3 p-3" style="background:rgba(26,26,26,0.03);border-radius:0.85rem;">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fs-7">@lang('general::general.saved_card_nickname')</label>
                                <input type="text" name="nickname" class="form-control form-control-sm" maxlength="80"
                                       value="{{ old('nickname', $card->nickname ?? '') }}"
                                       placeholder="{{ __('general::general.saved_card_nickname_placeholder') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-7">@lang('general::general.saved_card_holder')</label>
                                <input type="text" name="holder" class="form-control form-control-sm" maxlength="120"
                                       value="{{ old('holder', $card->holder ?? '') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <label class="d-flex align-items-center gap-2 mb-2 fs-7">
                                    <input type="checkbox" name="is_default" value="1"
                                           @if(old('is_default', $card->is_default)) checked @endif>
                                    <span>@lang('general::general.saved_card_make_default')</span>
                                </label>
                            </div>
                        </div>
                        <p class="text-muted fs-8 mt-3 mb-2">@lang('general::general.saved_card_edit_hint')</p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="sub-btn sub-btn--primary">@lang('general::general.saved_card_save')</button>
                            <a href="{{ route('subscription.cards.add') }}" class="sub-btn sub-btn--ghost">
                                @lang('general::general.saved_card_replace')
                            </a>
                        </div>
                    </form>
                </details>
            </div>
        @endforeach
    </div>
@endif
