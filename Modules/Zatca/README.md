# ZATCA Phase 2 — Admin settings

## What was added

- Module `Modules/Zatca` with tenant table `zatca_settings`
- Admin page: `/zatca-settings` (also linked from General Settings cards)
- Dual package trees:
  - Sandbox: `packages/fatoora-zatca` (`Bl\FatooraZatca\`) — used for `local` / `simulation`
  - Production: `packages/fatoora-zatca-production` — used only when `ZATCA_ENVIRONMENT=production`
- Runtime config: `config/zatca.php` + env-owned environment / app key

## Setup on each tenant DB

```bash
php artisan tenants:migrate
# or your usual tenant migrate command
```

## Environment (.env) — source of truth

```
ZATCA_ENVIRONMENT=local
ZATCA_APP_KEY=
ZATCA_LOCK_CONNECTION_FROM_ENV=true
```

| Value | Package used | Portal |
|-------|--------------|--------|
| `local` | `packages/fatoora-zatca` | developer-portal |
| `simulation` | `packages/fatoora-zatca` | simulation |
| `production` | `packages/fatoora-zatca-production` | core |

Production requires a valid `ZATCA_APP_KEY` in `.env` (not editable from the UI when lock is on).

After changing these values:

```bash
php artisan config:clear
# or for production deploys:
php artisan config:cache
```

## Going live (safe checklist)

1. Keep `ZATCA_ENVIRONMENT=local` while testing — current sandbox invoices stay on the sandbox package.
2. Place the vendor production ZIP under `packages/fatoora-zatca-production` (already done if you received the licensed build).
3. Set `ZATCA_APP_KEY=...` in `.env`.
4. When ready: set `ZATCA_ENVIRONMENT=production`, run `php artisan config:cache`, then regenerate certificates for that tenant on production OTP.

## Flow

1. Open **General Settings** → **Tax connection**, or go to `/zatca-settings`
2. Fill seller, address, OTP from Fatoora portal
3. **Save & generate ZATCA certificates**
4. Result (`private_key`, `cert_production`, `secret_production`, …) is stored in `generated_credentials` JSON

## Mapping to package `Setting` object

| Form field | Package property |
|------------|------------------|
| OTP | `otp` |
| Email | `emailAddress` |
| CN (optional) | `commonName` (auto `ENV-OTP-VAT` if empty) |
| Organization unit | `organizationalUnitName` |
| Organization name | `organizationName` |
| VAT | `taxNumber` |
| Building / street | `registeredAddress` |
| Business category | `businessCategory` |
| CRN | `registrationNumber` |
| Invoice type | `invoiceType` (`0100`/`1000`/`1100`) |
| Country | `countryName` |

## Invoice reporting (sell)

1. Configure certificates on **Connection settings**
2. Open **Send sell invoice** tab
3. Sync one invoice or select many (B2C / B2B)
4. Status + PIH hash chain are stored in `zatca_invoice_syncs` / `zatca_settings`

Services:
- `ZatcaInvoiceMapper` — ERP sell → Seller / Client / Invoice / InvoiceItem
- `ZatcaSellSyncService` — report via `B2C` / `B2B`, persist result, lock hash chain
- `App\Support\Zatca\FatooraZatcaPackage` — picks sandbox vs production package from `.env`
