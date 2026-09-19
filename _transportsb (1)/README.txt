XYZ Transport V2 - Billed Voucher Delete Toaster Fix

Changed behavior:
- A voucher/trip already included in a customer invoice cannot be deleted.
- Backend now returns a clean friendly validation message instead of exposing SQLSTATE/foreign-key errors.
- Voucher delete errors are shown as a top-right toaster notification.
- Successful deletions also show a success toaster.
- Reusable global AppToast helper added to the main layout.

After replacing files run:
php artisan optimize:clear

No migration required.
