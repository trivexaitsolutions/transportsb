<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tax Invoice - {{ $voucher->bill_no ?: $voucher->sr_no }}</title>
    <style>
        *{box-sizing:border-box} body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#e5e7eb;color:#111;font-size:12px}.toolbar{padding:10px;text-align:center}.toolbar button{padding:8px 18px;border:1px solid #333;background:#fff;font-weight:700;cursor:pointer}.paper{width:210mm;min-height:297mm;margin:0 auto 18px;background:#fff;padding:10mm 11mm}.letterhead-spacer{height:{{ max(0, (float) ($letterheadTopMarginMm ?? 0)) }}mm}.center{text-align:center}.right{text-align:right}.bold{font-weight:700}.company-name{font-size:27px;font-weight:900;letter-spacing:.2px}.tagline{font-size:11px;font-weight:700;text-transform:uppercase;margin-top:2px}.rule{border-top:2px solid #111;margin:7px 0}.invoice-title{font-size:19px;font-weight:900;text-decoration:underline;margin:10px 0 8px}.top-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border:1px solid #111}.top-grid>div{padding:7px 8px;border-right:1px solid #111}.top-grid>div:last-child{border-right:0}.meta-line{display:flex;gap:6px;margin:3px 0}.meta-line .label{font-weight:800;min-width:88px}.to-box{border:1px solid #111;border-top:0;padding:8px;min-height:96px}.to-box h3{margin:0 0 4px;font-size:12px}.route-line{border:1px solid #111;border-top:0;padding:7px 8px;font-weight:700;line-height:1.45}.grid{width:100%;border-collapse:collapse;margin-top:0}.grid th,.grid td{border:1px solid #111;padding:6px 5px;vertical-align:middle}.grid th{font-size:10px;text-transform:uppercase}.grid .num{text-align:right}.totals{width:100%;border-collapse:collapse}.totals td{border:1px solid #111;padding:6px}.totals td:first-child{text-align:right;font-weight:700}.amount-words{border:1px solid #111;border-top:0;padding:8px;font-weight:700}.customer-bill-note{border:1px solid #111;border-top:0;padding:7px 8px;font-weight:900;text-transform:uppercase}.gst-summary{margin-top:8px;width:100%;border-collapse:collapse}.gst-summary th,.gst-summary td{border:1px solid #111;padding:5px}.gst-summary th{font-size:10px}.terms-sign{display:grid;grid-template-columns:1.35fr .65fr;border:1px solid #111;border-top:0;min-height:145px}.terms{padding:8px;border-right:1px solid #111}.terms h4{margin:0 0 5px;text-decoration:underline}.terms ol{margin:4px 0 0;padding-left:18px;line-height:1.45}.signature{padding:8px;display:flex;flex-direction:column;justify-content:space-between;text-align:center}.bank{margin-top:8px;border:1px solid #111;padding:8px;line-height:1.45}.footer-note{margin-top:6px;font-size:9px;color:#333}.no-print{} @media print{body{background:#fff}.toolbar{display:none}.paper{width:auto;min-height:auto;margin:0;padding:7mm}.no-print{display:none}@page{size:A4;margin:0}}
    </style>
</head>
<body>
@php
    $freight = (float) $voucher->customer_freight;
    $hamaliLoading = (float) $voucher->hamali_loading;
    $hamaliUnloading = (float) $voucher->hamali_unloading;
    $otherCharges = (float) $voucher->other_charges;
    $amount = $freight + $hamaliLoading + $hamaliUnloading + $otherCharges;
    $taxable = $freight;
    $gstAmount = (float) $voucher->gst;
    $total = $amount + $gstAmount;
    $gstRate = (float) ($voucher->gstRate?->rate ?? 0);
    $paid = (float) ($voucher->customer_paid_total ?? 0);
    $balance = max(0, $total - $paid);
    $amountWords = number_format($total, 2).' Rupees';
    if (class_exists('NumberFormatter')) {
        try {
            $fmt = new NumberFormatter('en_IN', NumberFormatter::SPELLOUT);
            $whole = (int) floor($total);
            $paise = (int) round(($total - $whole) * 100);
            $amountWords = ucwords($fmt->format($whole)).' Rupees'.($paise ? ' and '.ucwords($fmt->format($paise)).' Paise' : '').' Only';
        } catch (Throwable $e) {}
    }
@endphp
<div class="toolbar no-print"><button onclick="window.print()">Print</button></div>
<div class="paper">
    @if(($letterheadTopMarginMm ?? 0) > 0)<div class="letterhead-spacer" aria-hidden="true"></div>@endif
    <div class="center">
        <div class="company-name">{{ $voucher->transportCompany?->name ?: 'XYZ TRANSPORT' }}</div>
        <div class="tagline">Fleet Owners &amp; Transport Contractor</div>
    </div>
    <div class="rule"></div>
    <div class="invoice-title center">TAX INVOICE</div>

    <div class="top-grid">
        <div>
            <div class="meta-line"><span class="label">TAX INVOICE NO.</span><span class="bold">{{ $voucher->bill_no ?: 'BILL-'.$voucher->sr_no }}</span></div>
            <div class="meta-line"><span class="label">LR NO.</span><span>{{ $voucher->lr_no ?: '-' }}</span></div>
        </div>
        <div>
            <div class="meta-line"><span class="label">DATE OF INVOICE</span><span class="bold">{{ optional($voucher->lr_date)->format('d.m.Y') }}</span></div>
            <div class="meta-line"><span class="label">PLACE OF SERVICE</span><span>{{ $voucher->to_place ?: '-' }}</span></div>
        </div>
    </div>

    <div class="to-box">
        <h3>TO</h3>
        <div class="bold" style="font-size:14px">{{ $voucher->customer?->name ?: '-' }}</div>
        <div style="margin-top:4px;white-space:pre-line">{{ $voucher->customer?->address ?: '-' }}</div>
        <div style="margin-top:5px"><b>GST IN:</b> {{ $voucher->customer?->gst_no ?: '-' }}</div>
        @if($voucher->so_ref_no)<div><b>Contract / Ref No.:</b> {{ $voucher->so_ref_no }}</div>@endif
    </div>

    <div class="route-line">
        Being Transportation Charges from <b>{{ $voucher->from_place ?: '-' }}</b> to <b>{{ $voucher->to_place ?: '-' }}</b>
        @if($voucher->vehicleType?->name) · {{ $voucher->vehicleType->name }} @endif
    </div>

    <table class="grid">
        <thead>
        <tr>
            <th>Sr. No.</th>
            <th>LR No.</th>
            <th>LR Date</th>
            <th>Vehicle No.</th>
            <th>Vehicle Type</th>
            <th class="num">Taxable Amount</th>
            <th class="num">GST Rate</th>
            <th class="num">GST Amount</th>
            <th class="num">Total</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="center">1</td>
            <td>{{ $voucher->lr_no ?: '-' }}</td>
            <td>{{ optional($voucher->lr_date)->format('d.m.Y') }}</td>
            <td>{{ $voucher->lorry_no ?: '-' }}</td>
            <td>{{ $voucher->vehicleType?->name ?: '-' }}</td>
            <td class="num">{{ number_format($taxable,2) }}</td>
            <td class="num">{{ rtrim(rtrim(number_format($gstRate,2,'.',''),'0'),'.') }}%</td>
            <td class="num">{{ number_format($gstAmount,2) }}</td>
            <td class="num bold">{{ number_format($total,2) }}</td>
        </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Customer Freight</td><td class="num" style="width:160px">₹{{ number_format($freight,2) }}</td></tr>
        @if($hamaliLoading > 0)<tr><td>Hamali Loading</td><td class="num">₹{{ number_format($hamaliLoading,2) }}</td></tr>@endif
        @if($hamaliUnloading > 0)<tr><td>Hamali Unloading</td><td class="num">₹{{ number_format($hamaliUnloading,2) }}</td></tr>@endif
        @if($otherCharges > 0)<tr><td>Other Charges</td><td class="num">₹{{ number_format($otherCharges,2) }}</td></tr>@endif
        <tr><td>Total Amount Before GST</td><td class="num">₹{{ number_format($amount,2) }}</td></tr>
        <tr><td>Total Taxable Amount</td><td class="num">₹{{ number_format($taxable,2) }}</td></tr>
        <tr><td>GST @ {{ rtrim(rtrim(number_format($gstRate,2,'.',''),'0'),'.') }}%</td><td class="num">₹{{ number_format($gstAmount,2) }}</td></tr>
        <tr><td>Total Invoice Amount</td><td class="num bold">₹{{ number_format($total,2) }}</td></tr>
    </table>
    <div class="amount-words">Rupees: {{ $amountWords }}</div>
    @if(trim((string) ($voucher->customer?->bill_note ?? '')) !== '')
        <div class="customer-bill-note">{{ $voucher->customer->bill_note }}</div>
    @endif

    <table class="gst-summary">
        <thead><tr><th>GST Rate</th><th class="right">Taxable Amount</th><th class="right">GST Amount</th><th class="right">Invoice Total</th></tr></thead>
        <tbody><tr><td>{{ rtrim(rtrim(number_format($gstRate,2,'.',''),'0'),'.') }}%</td><td class="right">{{ number_format($taxable,2) }}</td><td class="right">{{ number_format($gstAmount,2) }}</td><td class="right bold">{{ number_format($total,2) }}</td></tr></tbody>
    </table>

    <div class="terms-sign">
        <div class="terms">
            <h4>TERMS OF PAYMENT</h4>
            <ol>
                <li>Please do not deduct any amount from the bill without our consent.</li>
                <li>Any dispute is subject to local jurisdiction only.</li>
                <li>Payment should quote the invoice number.</li>
            </ol>
            @if($voucher->remarks)<div style="margin-top:8px"><b>Remarks:</b> {{ $voucher->remarks }}</div>@endif
            <div style="margin-top:10px"><b>Received:</b> ₹{{ number_format($paid,2) }} &nbsp; <b>Balance:</b> ₹{{ number_format($balance,2) }}</div>
        </div>
        <div class="signature">
            <div class="bold">For {{ $voucher->transportCompany?->name ?: 'XYZ TRANSPORT' }}</div>
            <div class="bold">Authorised Signatory</div>
        </div>
    </div>

    <div class="bank">
        <b>BANK DETAILS</b><br>
        Bank / Account / IFSC details can be configured on the final company stationery.
    </div>
    <div class="footer-note">Supplier costing, supplier payment and internal profit are intentionally not shown on the customer tax invoice.</div>
</div>
<script>
window.addEventListener('load', () => { window.setTimeout(() => window.print(), 250); });
</script>
</body>
</html>
