# XYZ Transport V2 — Master Foundation

This is the clean Transport V2 foundation. The **Master module UI/UX is intentionally lifted from the latest old XYZ Transport project** rather than redesigned.

Included Masters:
- Customers
- Suppliers / Transporters
- Vehicle Types
- Transport Names
- GST Master
- Settings (Letterhead image + top blank margin)

Keyboard behavior:
- `Insert` opens Add modal
- `Enter` moves through modal fields / edits focused master row
- `↑ / ↓` moves between master rows
- `/` focuses Search
- `Ctrl + S` saves an open modal
- `Esc` closes the modal and restores focus

Reusable `/masters/options/{type}` JSON endpoints and `public/js/master-selector.js` are kept for future SO / Trip / Ledger searchable modal selectors.

## Local setup
Database is configured in `.env` for MySQL database `transportsb`.

Default seeded login:
- Email: `admin@gmail.com`
- Password: `123456`

For an empty/development database:

```bash
php artisan migrate:fresh --seed
```

Seed data includes GST 0%, 5%, 18%, 28% and Transport Names BGT/LST.

## Transport V2 - Sale module

The `Sale` menu now contains:

- **SO / Sales Order**: customer-linked order with SO number/date, From/To, Description/Service, Trips Quantity, Per Trip Cost, Value, GST, Other Charges and Total Amount. Used/remaining trips are calculated from Voucher entries. Completed SOs remain in history but no longer appear for new Voucher selection.
- **Voucher**: Excel-style truck/trip entry against SO. Route and customer are inherited from the SO; From/To are not re-entered. Master-backed fields use searchable keyboard modals. Supplier payments remain trip-level and support installment history.

After updating an existing Transport V2 database, run:

```bash
php artisan migrate
php artisan optimize:clear
```
