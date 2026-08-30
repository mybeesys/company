# ZATCA Phase 2 — Admin settings

## What was added

- Module `Modules/Zatca` with tenant table `zatca_settings`
- Admin page: `/zatca-settings` (also linked from General Settings cards)
- Package path: `packages/fatoora-zatca` (`Bl\FatooraZatca\`)
- Runtime config: `config/zatca.php` + tenant environment / app key override before CSR generation

## Setup on each tenant DB

```bash
php artisan tenants:migrate
# or your usual tenant migrate command
```

## Environment (.env)

```
ZATCA_ENVIRONMENT=local
ZATCA_APP_KEY=
```

Production requires a valid `ZATCA_APP_KEY` (also enterable on the settings page; stored encrypted).

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
