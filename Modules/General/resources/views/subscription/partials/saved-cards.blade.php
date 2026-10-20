@php
    $repo = $cardsRepo;
    $cards = $savedCards ?? collect();
@endphp

<style>
    .bee-vault {
        --bv-ink: #1a1a1a;
        --bv-muted: #6b7280;
        --bv-line: rgba(26, 26, 26, 0.09);
        --bv-accent: #ebb81e;
        --bv-accent-2: #b88912;
        --bv-soft: #faf6eb;
    }
    .bee-vault__toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.85rem;
        margin-bottom: 1.15rem;
    }
    .bee-vault__hint {
        margin: 0;
        color: var(--bv-muted);
        font-size: 0.86rem;
        max-width: 46rem;
        line-height: 1.55;
    }
    .bee-vault__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
    }
    .bee-card {
        position: relative;
        border-radius: 1.15rem;
        padding: 1.1rem 1.15rem 1rem;
        min-height: 168px;
        color: #fffdf8;
        background:
            radial-gradient(circle at 88% 12%, rgba(235, 184, 30, 0.42), transparent 40%),
            radial-gradient(circle at 10% 90%, rgba(184, 137, 18, 0.22), transparent 46%),
            linear-gradient(145deg, #2c2416 0%, #1a1a1a 52%, #3d3118 100%);
        border: 1px solid rgba(235, 184, 30, 0.28);
        box-shadow: 0 16px 36px rgba(26, 26, 26, 0.12);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        animation: bee-card-in .45s ease both;
    }
    .bee-card:nth-child(2) { animation-delay: .06s; }
    .bee-card:nth-child(3) { animation-delay: .12s; }
    @keyframes bee-card-in {
        from { opacity: 0; transform: translateY(10px) scale(0.98); }
        to { opacity: 1; transform: none; }
    }
    .bee-card::before {
        content: "";
        position: absolute;
        inset-inline-end: -6%;
        bottom: -22%;
        width: 120px;
        height: 120px;
        background: rgba(235, 184, 30, 0.16);
        clip-path: polygon(25% 6%, 75% 6%, 100% 50%, 75% 94%, 25% 94%, 0 50%);
        pointer-events: none;
    }
    .bee-card__top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem;
        position: relative;
        z-index: 1;
    }
    .bee-card__brand {
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.92;
    }
    .bee-card__chip {
        width: 2.1rem;
        height: 1.55rem;
        border-radius: 0.35rem;
        background: linear-gradient(135deg, #f0c43a, #b88912 55%, #f6d96a);
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.12);
    }
    .bee-card__default {
        display: inline-flex;
        align-items: center;
        gap: 0.28rem;
        font-size: 0.7rem;
        font-weight: 800;
        color: #1a1a1a;
        background: linear-gradient(135deg, #f0c43a, #ebb81e);
        border-radius: 999px;
        padding: 0.22rem 0.55rem;
        margin-top: 0.25rem;
    }
    .bee-card__pan {
        position: relative;
        z-index: 1;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        margin-top: 0.35rem;
        direction: ltr;
        text-align: start;
    }
    .bee-card__meta {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.75rem;
        opacity: 0.82;
        margin-top: auto;
    }
    .bee-card__meta strong {
        display: block;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        opacity: 0.7;
        margin-bottom: 0.12rem;
    }
    .bee-card__actions {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.35rem;
    }
    .bee-card__btn {
        border: 1px solid rgba(255, 253, 248, 0.22);
        background: rgba(255, 253, 248, 0.08);
        color: #fffdf8;
        border-radius: 999px;
        padding: 0.28rem 0.7rem;
        font-size: 0.72rem;
        font-weight: 800;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease;
    }
    .bee-card__btn:hover {
        background: rgba(255, 253, 248, 0.16);
        border-color: rgba(235, 184, 30, 0.55);
    }
    .bee-card__btn--accent {
        background: linear-gradient(135deg, #f0c43a, #ebb81e);
        color: #1a1a1a;
        border-color: transparent;
    }
    .bee-card__btn--danger:hover {
        background: rgba(185, 28, 28, 0.85);
        border-color: transparent;
    }
    .bee-vault__empty {
        text-align: center;
        padding: 1.75rem 1rem;
        color: var(--bv-muted);
        border: 1px dashed var(--bv-line);
        border-radius: 1rem;
        background: rgba(250, 246, 235, 0.65);
    }
    .bee-vault__empty a {
        color: var(--bv-accent-2);
        font-weight: 800;
    }

    .bee-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(26, 26, 26, 0.48);
        backdrop-filter: blur(4px);
        z-index: 1055;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .bee-modal-backdrop.is-open { display: flex; }
    .bee-modal {
        width: min(480px, 100%);
        max-height: min(90vh, 640px);
        overflow: auto;
        background: #ffffff;
        border-radius: 1.15rem;
        border: 1px solid rgba(26, 26, 26, 0.12);
        box-shadow: 0 24px 60px rgba(26, 26, 26, 0.22);
        animation: bee-modal-in .28s ease;
    }
    @keyframes bee-modal-in {
        from { opacity: 0; transform: translateY(12px) scale(0.98); }
        to { opacity: 1; transform: none; }
    }
    .bee-modal__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid var(--bv-line);
    }
    .bee-modal__head h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
    }
    .bee-modal__close {
        border: 0;
        background: transparent;
        font-size: 1.35rem;
        line-height: 1;
        cursor: pointer;
        color: var(--bv-muted);
    }
    .bee-modal__body { padding: 1.1rem 1.15rem 1.25rem; }
    .bee-modal__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        justify-content: flex-end;
        margin-top: 1rem;
    }
    .bee-field { margin-bottom: 0.85rem; }
    .bee-field label {
        display: block;
        font-size: 0.78rem;
        font-weight: 800;
        margin-bottom: 0.3rem;
        color: var(--bv-ink);
    }
    .bee-field input[type="text"],
    .bee-field input[type="password"] {
        width: 100%;
        border: 2px solid rgba(26, 26, 26, 0.28);
        border-radius: 0.75rem;
        padding: 0.7rem 0.85rem;
        min-height: 2.85rem;
        background: #ffffff;
        color: #1a1a1a;
        font: inherit;
        font-size: 1rem;
        box-shadow: 0 0 0 3px rgba(235, 184, 30, 0.12);
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .bee-field input[type="text"]:focus,
    .bee-field input[type="password"]:focus {
        border-color: #ebb81e;
        box-shadow: 0 0 0 4px rgba(235, 184, 30, 0.28);
    }
    .bee-field input[type="password"]::placeholder {
        color: #9ca3af;
        opacity: 1;
    }
    .bee-modal {
        background: #ffffff;
    }
    .bee-modal__body {
        background: #ffffff;
    }
    .bee-check {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.86rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }
    .bee-modal__note {
        font-size: 0.78rem;
        color: var(--bv-muted);
        margin: 0.35rem 0 0;
        line-height: 1.45;
    }
    .bee-modal__secure {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0 0 0.85rem;
        padding: 0.55rem 0.7rem;
        border-radius: 0.7rem;
        background: rgba(235, 184, 30, 0.12);
        color: var(--bv-accent-2);
        font-size: 0.78rem;
        font-weight: 700;
    }
    .bee-modal--wide { width: min(720px, 96vw); }
    .bee-modal--add {
        max-height: min(94vh, 900px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .bee-modal--add .bee-modal__head {
        padding: 0.95rem 1.25rem;
        flex: 0 0 auto;
    }
    .bee-modal--add .bee-modal__head h3 {
        font-size: 1.2rem;
    }
    .bee-modal--add .bee-modal__body {
        padding: 0.55rem 1.15rem 1rem;
        overflow: hidden;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .bee-modal--add .bee-modal__note {
        margin: 0 0 0.55rem;
        font-size: 0.86rem;
        flex: 0 0 auto;
    }
    .bee-modal__frame {
        width: 100%;
        height: 560px;
        min-height: 520px;
        flex: 1 1 auto;
        border: 0;
        border-radius: 0.85rem;
        background: #fff;
        overflow: hidden;
    }
    .bee-card--add {
        background: linear-gradient(160deg, #fffdf8, #f7f1e3);
        color: var(--bv-ink);
        border: 1.5px dashed rgba(184, 137, 18, 0.45);
        box-shadow: none;
        align-items: center;
        justify-content: center;
        text-align: center;
        cursor: pointer;
        min-height: 168px;
    }
    .bee-card--add::before { display: none; }
    .bee-card--add .bee-card__plus {
        width: 2.6rem; height: 2.6rem; display: grid; place-items: center;
        border-radius: 999px; background: rgba(235, 184, 30, 0.2);
        color: var(--bv-accent-2); font-size: 1.45rem; font-weight: 800; margin-bottom: 0.45rem;
    }
</style>

<div class="bee-vault">
    <div class="bee-vault__toolbar">
        <p class="bee-vault__hint">@lang('general::general.saved_cards_hint')</p>
        <button type="button" class="sub-btn sub-btn--primary" data-bee-open="add">
            <i class="fas fa-plus"></i>
            @lang('general::general.saved_card_add')
        </button>
    </div>

    @if ($cards->isEmpty())
        <div class="bee-vault__empty">
            <p class="mb-3">@lang('general::general.saved_cards_empty')</p>
            <button type="button" class="sub-btn sub-btn--primary" data-bee-open="add">
                @lang('general::general.saved_card_add')
            </button>
        </div>
    @endif

    <div class="bee-vault__grid">
            @foreach ($cards as $card)
                @php
                    $brand = strtoupper((string) ($card->brand ?: 'CARD'));
                    $label = $repo?->label($card) ?? $brand;
                    $expiry = $repo?->expiryLabel($card) ?? '—';
                @endphp
                <article class="bee-card">
                    <div class="bee-card__top">
                        <div>
                            <div class="bee-card__brand">{{ $brand }}</div>
                            @if ($card->is_default)
                                <span class="bee-card__default">★ @lang('general::general.saved_card_default')</span>
                            @endif
                        </div>
                        <span class="bee-card__chip" aria-hidden="true"></span>
                    </div>
                    <div class="bee-card__pan">•••• •••• •••• {{ $card->last_four ?: '••••' }}</div>
                    <div class="bee-card__meta">
                        <div>
                            <strong>@lang('general::general.saved_card_holder')</strong>
                            {{ $card->holder ?: $label }}
                        </div>
                        <div>
                            <strong>@lang('general::general.saved_card_expiry')</strong>
                            {{ $expiry }}
                        </div>
                    </div>
                    <div class="bee-card__actions">
                        @unless ($card->is_default)
                            <form method="POST" action="{{ route('subscription.cards.default', ['cardId' => $card->id]) }}">
                                @csrf
                                <button type="submit" class="bee-card__btn bee-card__btn--accent">@lang('general::general.saved_card_make_default')</button>
                            </form>
                        @endunless
                        <button
                            type="button"
                            class="bee-card__btn"
                            data-bee-open="edit"
                            data-nickname="{{ $card->nickname ?? '' }}"
                            data-holder="{{ $card->holder ?? '' }}"
                            data-default="{{ $card->is_default ? '1' : '0' }}"
                            data-action="{{ route('subscription.cards.update', ['cardId' => $card->id]) }}"
                        >@lang('general::general.saved_card_edit')</button>
                        <button
                            type="button"
                            class="bee-card__btn bee-card__btn--danger"
                            data-bee-open="delete"
                            data-label="{{ $label }}"
                            data-action="{{ route('subscription.cards.delete', ['cardId' => $card->id]) }}"
                        >@lang('general::general.saved_card_delete')</button>
                    </div>
                </article>
            @endforeach

        <button type="button" class="bee-card bee-card--add" data-bee-open="add">
            <span class="bee-card__plus">+</span>
            <strong>@lang('general::general.saved_card_add')</strong>
            <span style="font-size:.78rem;color:var(--bv-muted);margin-top:.2rem;">@lang('general::general.saved_card_add_short')</span>
        </button>
    </div>
</div>

{{-- Add card modal (MyBee form — stays on this page) --}}
<div class="bee-modal-backdrop" id="bee-modal-add" role="dialog" aria-modal="true" aria-labelledby="bee-add-title">
    <div class="bee-modal bee-modal--wide bee-modal--add">
        <div class="bee-modal__head">
            <h3 id="bee-add-title">@lang('general::general.saved_card_add')</h3>
            <button type="button" class="bee-modal__close" data-bee-close aria-label="Close">&times;</button>
        </div>
        <div class="bee-modal__body">
            <p class="bee-modal__note">@lang('general::general.saved_card_add_modal_hint')</p>
            <iframe
                class="bee-modal__frame"
                id="bee-add-frame"
                title="@lang('general::general.saved_card_add')"
                src="about:blank"
                allow="payment *"
            ></iframe>
        </div>
    </div>
</div>

{{-- Edit + password --}}
<div class="bee-modal-backdrop" id="bee-modal-edit" role="dialog" aria-modal="true" aria-labelledby="bee-edit-title">
    <div class="bee-modal">
        <div class="bee-modal__head">
            <h3 id="bee-edit-title">@lang('general::general.saved_card_edit_title')</h3>
            <button type="button" class="bee-modal__close" data-bee-close aria-label="Close">&times;</button>
        </div>
        <div class="bee-modal__body">
            <p class="bee-modal__secure">🔒 @lang('general::general.saved_card_password_hint')</p>
            <form method="POST" id="bee-edit-form" autocomplete="off">
                @csrf
                @method('PUT')
                <div class="bee-field">
                    <label for="bee-edit-nickname">@lang('general::general.saved_card_nickname')</label>
                    <input type="text" id="bee-edit-nickname" name="nickname" maxlength="80"
                           placeholder="{{ __('general::general.saved_card_nickname_placeholder') }}">
                </div>
                <div class="bee-field">
                    <label for="bee-edit-holder">@lang('general::general.saved_card_holder')</label>
                    <input type="text" id="bee-edit-holder" name="holder" maxlength="120">
                </div>
                <label class="bee-check">
                    <input type="checkbox" id="bee-edit-default" name="is_default" value="1">
                    <span>@lang('general::general.saved_card_make_default')</span>
                </label>
                <div class="bee-field">
                    <label for="bee-edit-password">@lang('general::general.saved_card_password')</label>
                    <input type="password" id="bee-edit-password" name="current_password" required
                           autocomplete="current-password"
                           placeholder="{{ __('general::general.saved_card_password_placeholder') }}">
                </div>
                <p class="bee-modal__note">@lang('general::general.saved_card_edit_hint')</p>
                <div class="bee-modal__actions">
                    <button type="button" class="sub-btn sub-btn--ghost" data-bee-close>@lang('general::general.saved_card_cancel')</button>
                    <button type="submit" class="sub-btn sub-btn--primary">@lang('general::general.saved_card_save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete + password --}}
<div class="bee-modal-backdrop" id="bee-modal-delete" role="dialog" aria-modal="true" aria-labelledby="bee-delete-title">
    <div class="bee-modal">
        <div class="bee-modal__head">
            <h3 id="bee-delete-title">@lang('general::general.saved_card_delete_title')</h3>
            <button type="button" class="bee-modal__close" data-bee-close aria-label="Close">&times;</button>
        </div>
        <div class="bee-modal__body">
            <p class="bee-modal__secure">🔒 @lang('general::general.saved_card_password_hint')</p>
            <p id="bee-delete-text" class="mb-3"></p>
            <form method="POST" id="bee-delete-form" autocomplete="off">
                @csrf
                @method('DELETE')
                <div class="bee-field">
                    <label for="bee-delete-password">@lang('general::general.saved_card_password')</label>
                    <input type="password" id="bee-delete-password" name="current_password" required
                           autocomplete="current-password"
                           placeholder="{{ __('general::general.saved_card_password_placeholder') }}">
                </div>
                <div class="bee-modal__actions">
                    <button type="button" class="sub-btn sub-btn--ghost" data-bee-close>@lang('general::general.saved_card_cancel')</button>
                    <button type="submit" class="sub-btn sub-btn--primary" style="background:#b91c1c;border-color:#b91c1c;">
                        @lang('general::general.saved_card_delete_confirm_btn')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var deleteTpl = @json(__('general::general.saved_card_delete_confirm_named'));
    var addUrl = @json(route('subscription.cards.add'));

    function openModal(id) {
        var el = document.getElementById(id);
        if (el) el.classList.add('is-open');
        document.documentElement.style.overflow = 'hidden';
    }
    function closeModals() {
        document.querySelectorAll('.bee-modal-backdrop.is-open').forEach(function (el) {
            el.classList.remove('is-open');
        });
        document.documentElement.style.overflow = '';
        var editPw = document.getElementById('bee-edit-password');
        var delPw = document.getElementById('bee-delete-password');
        var frame = document.getElementById('bee-add-frame');
        if (editPw) editPw.value = '';
        if (delPw) delPw.value = '';
        if (frame) frame.src = 'about:blank';
    }

    document.querySelectorAll('[data-bee-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var kind = btn.getAttribute('data-bee-open');
            if (kind === 'add') {
                var frame = document.getElementById('bee-add-frame');
                if (frame) frame.src = addUrl;
                openModal('bee-modal-add');
                return;
            }
            if (kind === 'edit') {
                var form = document.getElementById('bee-edit-form');
                form.action = btn.getAttribute('data-action');
                document.getElementById('bee-edit-nickname').value = btn.getAttribute('data-nickname') || '';
                document.getElementById('bee-edit-holder').value = btn.getAttribute('data-holder') || '';
                document.getElementById('bee-edit-default').checked = btn.getAttribute('data-default') === '1';
                document.getElementById('bee-edit-password').value = '';
                openModal('bee-modal-edit');
                return;
            }
            if (kind === 'delete') {
                var delForm = document.getElementById('bee-delete-form');
                delForm.action = btn.getAttribute('data-action');
                var label = btn.getAttribute('data-label') || '';
                document.getElementById('bee-delete-text').textContent = deleteTpl.replace(':card', label);
                document.getElementById('bee-delete-password').value = '';
                openModal('bee-modal-delete');
            }
        });
    });

    document.querySelectorAll('[data-bee-close]').forEach(function (btn) {
        btn.addEventListener('click', closeModals);
    });
    document.querySelectorAll('.bee-modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) closeModals();
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModals();
    });

    window.addEventListener('message', function (event) {
        var data = event.data || {};
        if (!data || data.type !== 'vault-card-height' || !data.height) return;
        var frame = document.getElementById('bee-add-frame');
        if (!frame) return;
        var h = Math.max(500, Math.min(720, Number(data.height) || 0));
        frame.style.height = h + 'px';
        frame.style.minHeight = h + 'px';
    });
})();
</script>
