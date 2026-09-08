# XYZ Transport - Transport Brokerage Management

Laravel application for a goods-transport brokerage business where XYZ operates transport names such as **BGT** and **LST**, takes transport work from customers, assigns it to outside suppliers / transporters, tracks both sides of payment, and produces ledgers, outstanding and printable customer documents.

The interface intentionally follows the compact, keyboard-first visual language of the existing Fruit Billing software while the voucher screen works like an Excel grid rather than a conventional form.

## Core Workflow

1. Add/select master data: Transport Name, Customer, Supplier / Transporter and Vehicle Type.
2. Open the required **Voucher Number / Day**, then enter the customer's demand directly in the Voucher grid. The working Date is stored once in the header, not repeated in every row.
3. Later update the same row with supplier/transporter, vehicle and LR details.
4. Enter supplier advance directly. Click **Supplier Payment** to maintain all later supplier installments.
5. Click customer **Paid Amount** to maintain customer receipt installments.
6. Balances, ledgers, outstanding and profit reports are calculated from the database.
7. Print a customer bill or customer/supplier statement without exposing internal supplier costing on the customer bill.

## Client-approved Voucher Column Order

The client-approved business order is preserved. **L R Date is promoted to the Voucher header** because one Voucher Number represents one working date, so the grid does not repeat the same date in every row. The row order is:

1. Sr No
2. Transport Name
3. LR No
4. Vehicle Type
5. Lorry
6. SO / Ref No
7. From
8. To
9. Supplier / Transporter
10. Supplier Freight
11. Advance Paid to Supplier
12. Balance
13. Supplier Payment
14. Customer
15. Customer Freight
16. Paid Amount
17. Balance
18. Hamali Loading
19. Hamali Unloading
20. Profit
21. Bill No
22. GST
23. Remarks

## Current Calculation Rules

- **Supplier Balance** = Supplier Freight - Advance Paid to Supplier - Supplier Payment installments
- **Customer Balance** = Customer Freight - Paid Amount installments
- **Profit** = Customer Freight - Supplier Freight
- Hamali Loading, Hamali Unloading and GST are stored and shown separately.
- Hamali and GST do **not** change Profit or Outstanding until their exact business treatment is confirmed by the client.

## Included Modules

- Login
- Dashboard
- Excel-style Voucher Entry
- Payment installment modal for Supplier Payment
- Payment installment modal for Customer Paid Amount
- Customer Master
- Supplier / Transporter Master
- Vehicle Type Master
- Transport Name Master (BGT/LST seeded)
- Voucher Register
- Customer Ledger + printable statement
- Supplier Ledger + printable statement
- Customer/Supplier Outstanding
- Profit Report
- Printable customer transport bill

## Keyboard Workflow

Voucher entry:

- Page open - focus starts on **Voucher Number**; Enter loads that day.
- `Enter` - next cell; on an empty selector it opens the selector, while a selected selector advances. Payment cells advance on Enter and open installments on mouse click.
- `Left / Right` - previous / next logical voucher cell.
- `Up / Down` - same column, previous / next row.
- `Backspace` on a selector/date - clear the complete selection/date.
- `Insert` - add new row
- `Ctrl + Delete` - delete selected row
- `Ctrl + S` - Save All
- `Esc` - close the current selector/payment modal and restore focus to the exact originating cell; from the page, return focus to the top navigation.
- Payment installment modal: `Enter` moves Date → Amount → Mode → Reference → Remarks → Add.

Masters:

- `Insert` - Add record
- `Enter` - next field
- `Ctrl + S` - save modal
- `Esc` - close modal

## Local Setup

Requirements:

- PHP 8.3+
- Composer
- SQLite for quick local setup, or MySQL on hosting

Commands:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The supplied `.env` is configured for SQLite and `database/database.sqlite` is included as an empty local database file. For MySQL, update the normal `DB_*` values in `.env` before migration.

### Initial Login

- Username: `admin`
- Password: `admin123`

Change the seeded password for production use.

## Deployment

`vendor/` and `node_modules/` are intentionally not included in the delivery ZIP. On the server run `composer install --no-dev --optimize-autoloader` and configure `.env`, then run:

```bash
php artisan migrate --seed --force
php artisan optimize:clear
```

The UI uses Tailwind Browser CDN and Slim Select CDN, so an npm build is not required for the current screens.
