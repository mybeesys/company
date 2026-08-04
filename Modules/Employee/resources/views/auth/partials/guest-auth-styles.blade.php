<style>
    :root {
        --login-glass: rgba(255, 255, 255, 0.78);
        --login-glass-edge: rgba(255, 255, 255, 0.55);
        --login-glass-shadow: 0 28px 60px -18px rgba(15, 23, 42, 0.35);
        --login-text: #0f172a;
        --login-muted: #64748b;
        --login-accent: #4f46e5;
        --login-accent-soft: rgba(79, 70, 229, 0.12);
        --login-topbar: #ebb81e;
    }

    [data-bs-theme="dark"] {
        --login-glass: rgba(17, 24, 39, 0.72);
        --login-glass-edge: rgba(148, 163, 184, 0.14);
        --login-glass-shadow: 0 28px 60px -12px rgba(0, 0, 0, 0.55);
        --login-text: #f1f5f9;
        --login-muted: #94a3b8;
        --login-accent: #c4b5fd;
        --login-accent-soft: rgba(196, 181, 253, 0.12);
    }

    .login-auth-page {
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
        color: var(--login-text);
        background-color: #0f172a;
        background-image: linear-gradient(180deg, rgba(15, 23, 42, 0.45) 0%, rgba(15, 23, 42, 0.72) 100%),
            url('{{ asset('assets/media/auth/bg4.jpg') }}');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        background-repeat: no-repeat;
    }

    [data-bs-theme="dark"] .login-auth-page {
        background-image: linear-gradient(180deg, rgba(15, 23, 42, 0.55) 0%, rgba(15, 23, 42, 0.82) 100%),
            url('{{ asset('assets/media/auth/bg4-dark.jpg') }}');
    }

    [data-bs-theme="light"] .login-auth-page {
        background-color: #e2e8f0;
        background-image: linear-gradient(180deg, rgba(248, 250, 252, 0.82) 0%, rgba(241, 245, 249, 0.92) 100%),
            url('{{ asset('assets/media/auth/bg4.jpg') }}');
    }

    .login-auth-wrap {
        position: relative;
        z-index: 1;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(1rem, 3vw, 2.5rem);
    }

    .login-auth-card {
        width: 100%;
        max-width: 1040px;
        border-radius: 1.25rem;
        background: var(--login-glass);
        border: 1px solid var(--login-glass-edge);
        border-top: 4px solid var(--login-topbar);
        box-shadow: var(--login-glass-shadow);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        overflow: visible;
    }

    .login-auth-card--solo {
        max-width: 440px;
        margin-inline: auto;
    }

    .login-auth-card--solo .login-auth-logo img {
        width: 200px;
        max-width: 72vw;
        max-height: 120px;
    }

    .login-auth-panel {
        padding: clamp(1.75rem, 4vw, 2.75rem) clamp(1.5rem, 3vw, 2.5rem);
    }

    .login-auth-panel--solo {
        text-align: center;
    }

    .login-auth-panel--solo .login-auth-title {
        margin-top: 0.75rem;
    }

    .login-auth-lang-row {
        display: flex;
        justify-content: center;
        margin-bottom: 1.25rem;
    }

    .login-auth-panel--brand {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        justify-content: center;
        min-height: 100%;
        border-bottom: 1px solid rgba(148, 163, 184, 0.22);
    }

    @media (min-width: 992px) {
        .login-auth-panel--brand {
            border-bottom: 0;
            border-inline-end: 1px solid rgba(148, 163, 184, 0.22);
        }
    }

    .login-auth-logo img {
        width: 369px;
        max-width: min(369px, 88vw);
        height: auto;
        max-height: min(400px, 42vh);
        object-fit: contain;
    }

    .login-auth-tenant {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 1rem;
        padding: 0.35rem 0.9rem;
        border-radius: 999px;
        font-size: 0.88rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        text-transform: none;
        color: var(--login-accent);
        background: var(--login-accent-soft);
        max-width: min(100%, 18rem);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .login-auth-title {
        font-size: clamp(1.35rem, 3.2vw, 1.85rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.25;
        margin: 1.25rem 0 0;
        color: var(--login-text);
    }

    .login-auth-sub {
        margin: 0.65rem 0 0;
        font-size: 0.95rem;
        color: var(--login-muted);
        line-height: 1.6;
        max-width: 26rem;
    }

    .login-auth-aside-footer {
        margin-top: auto;
        padding-top: 2rem;
        width: 100%;
        max-width: 22rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .login-auth-links {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.75rem 1.25rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .login-auth-panel--form {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-auth-divider {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin: 0 0 1.35rem;
        color: var(--login-muted);
        font-size: 0.78rem;
        font-weight: 600;
    }

    .login-auth-divider::before,
    .login-auth-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.4), transparent);
    }

    .login-password-field .form-control {
        padding-inline-end: 2.85rem;
        min-height: 46px;
    }

    .login-password-toggle {
        position: absolute;
        inset-inline-end: 0.4rem;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: rgba(148, 163, 184, 0.12);
        color: var(--login-muted);
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.45rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color 0.15s ease, background 0.15s ease;
    }

    .login-password-toggle:hover {
        color: var(--login-accent);
        background: var(--login-accent-soft);
    }

    .login-password-toggle .ki-outline {
        font-size: 1.15rem;
        line-height: 1;
    }

    .login-lang-wrap {
        z-index: 20;
    }

    .login-lang-wrap.is-open .login-lang-chevron {
        transform: rotate(180deg);
    }

    .login-lang-chevron {
        transition: transform 0.15s ease;
    }

    .login-lang-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        inset-inline-start: 0;
        margin-top: 6px;
        min-width: 200px;
        padding: 0.5rem 0;
        border-radius: 0.5rem;
        background: var(--login-glass);
        border: 1px solid var(--login-glass-edge);
        box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.22);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    [data-bs-theme="dark"] .login-lang-dropdown {
        box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.45);
    }

    .login-lang-wrap.is-open .login-lang-dropdown {
        display: block;
    }

    .login-lang-dropdown .login-lang-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.55rem 1.1rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--login-text);
        text-decoration: none;
        transition: background 0.12s ease, color 0.12s ease;
    }

    .login-lang-dropdown .login-lang-link:hover {
        background: var(--login-accent-soft);
        color: var(--login-accent);
    }

    .login-auth-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin: 1.15rem 0 1.35rem;
    }

    .login-auth-actions .form-check {
        margin: 0;
        padding-inline-start: 0;
    }

    .login-auth-actions .form-check-input {
        width: 1.12rem;
        height: 1.12rem;
        cursor: pointer;
        border-radius: 0.35rem;
    }

    .login-auth-actions .form-check-label {
        cursor: pointer;
        font-weight: 600;
        font-size: 0.88rem;
        color: var(--login-text);
        padding-inline-start: 0.35rem;
    }

    .login-auth-submit {
        border-radius: 0.75rem;
        font-weight: 700;
        padding-block: 0.82rem;
        box-shadow: 0 10px 24px -10px rgba(79, 70, 229, 0.45);
    }

    #initial-loader {
        transition: opacity 0.28s ease;
    }

    #initial-loader.login-loader--hide {
        opacity: 0;
        pointer-events: none;
    }

    .login-auth-card .form-label {
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.4rem;
        color: var(--login-text);
    }

    .login-auth-card .alert {
        border-radius: 0.75rem;
    }
</style>
