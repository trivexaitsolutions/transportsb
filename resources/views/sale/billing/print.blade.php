<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Tax Invoice - {{ $invoice->bill_no }}</title>
<style>
*{box-sizing:border-box}body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#d1d5db;color:#111;font-size:12px}.paper{width:210mm;min-height:297mm;margin:0 auto;background:#fff;padding:9mm 10mm}.center{text-align:center}.right{text-align:right}.bold{font-weight:800}.company-name{font-size:27px;font-weight:900;letter-spacing:.2px}.tagline{font-size:11px;font-weight:800;text-transform:uppercase;margin-top:2px}.company-meta{font-size:10px;font-weight:700;margin-top:4px}.rule{border-top:2px solid #111;margin:7px 0}.invoice-title{font-size:21px;font-weight:900;text-decoration:underline;margin:9px 0 8px}.uploaded-letterhead{text-align:center;margin-bottom:3mm}.uploaded-letterhead img{display:block;width:100%;max-height:50mm;object-fit:contain}.top-grid{display:grid;grid-template-columns:1fr 1fr;border:1px solid #111}.top-grid>div{padding:8px 9px;border-right:1px solid #111}.top-grid>div:last-child{border-right:0}.meta-line{display:flex;gap:8px;margin:5px 0;font-size:14px;line-height:1.25}.meta-line .label{font-weight:900;min-width:135px}.to-box{border:1px solid #111;border-top:0;padding:9px;min-height:92px;font-size:13px}.to-box h3{margin:0 0 5px;font-size:12px}.to-name{font-size:16px;font-weight:900}.service-box{border:1px solid #111;border-top:0;padding:7px 8px;font-weight:700;line-height:1.45}.grid{width:100%;border-collapse:collapse}.grid th,.grid td{border:1px solid #111;padding:6px 5px;vertical-align:middle}.grid th{font-size:9px;text-transform:uppercase}.grid .num{text-align:right}.totals{width:100%;border-collapse:collapse}.totals td{border:1px solid #111;padding:6px}.totals td:first-child{text-align:right;font-weight:800}.amount-words{border:1px solid #111;border-top:0;padding:8px;font-weight:800}.customer-bill-note{border:1px solid #111;border-top:0;padding:8px;font-weight:900;text-transform:uppercase}.gst-summary{margin-top:8px;width:100%;border-collapse:collapse}.gst-summary th,.gst-summary td{border:1px solid #111;padding:5px}.gst-summary th{font-size:9px}.terms-sign{display:grid;grid-template-columns:1.35fr .65fr;border:1px solid #111;border-top:0;min-height:145px}.terms{padding:8px;border-right:1px solid #111}.terms h4{margin:0 0 5px;text-decoration:underline}.terms ol{margin:4px 0 0;padding-left:18px;line-height:1.45}.signature{padding:8px;display:flex;flex-direction:column;justify-content:space-between;text-align:center}.bank{margin-top:8px;border:1px solid #111;padding:8px;line-height:1.45}.footer-note{margin-top:5px;font-size:9px;color:#374151}@media print{body{background:#fff}.paper{width:auto;min-height:auto;margin:0;padding:7mm}@page{size:A4;margin:0}}
</style>
</head>
<body>
@php
    $customer = $invoice->customer;
    $order = $invoice->salesOrder;
    $topMargin = max(0, (float) ($printSettings->letterhead_top_margin_mm ?? 0));
    $gstNo = strtoupper(trim((string) ($customer?->gst_no ?? '')));
    $pan = strlen($gstNo) >= 12 ? substr($gstNo, 2, 10) : '-';
    $stateCode = strlen($gstNo) >= 2 ? substr($gstNo, 0, 2) : '27';
    if ($stateCode === '27') $stateCode = '27 (Maharashtra)';
    $taxMode = in_array($invoice->tax_mode, ['rcm', 'hiring', 'gst', 'na'], true) ? $invoice->tax_mode : 'rcm';
    $isRcm = $taxMode === 'rcm';
    $otherCharges = round((float) $invoice->other_charges, 2);
    $freightGstTotal = round((float) $invoice->customer_freight + (float) $invoice->gst_amount, 2);
    $displayTotal = round($freightGstTotal + $otherCharges, 2);
    $amountWords = number_format($displayTotal, 2).' Rupees Only';
    if (class_exists('NumberFormatter')) {
        try {
            $fmt = new NumberFormatter('en_IN', NumberFormatter::SPELLOUT);
            $whole = (int) floor($displayTotal);
            $paise = (int) round(($displayTotal - $whole) * 100);
            $amountWords = ucwords($fmt->format($whole)).' Rupees'.($paise ? ' and '.ucwords($fmt->format($paise)).' Paise' : '').' Only';
        } catch (Throwable $e) {}
    }
    $profile = [
        'name'=>'XYZ TRANSPORT','tagline'=>'Fleet Owners & Transport Contractor','address'=>'Maharashtra, India',
        'phone'=>'9876543210','email'=>'accounts@xyztransport.com','pan'=>'ABCDE1234F','gst_no'=>'27ABCDE1234F1Z5',
        'bank_holder'=>'XYZ TRANSPORT','bank_name'=>'Bank of Baroda','bank_address'=>'Maharashtra Branch',
        'account_no'=>'000000000000','ifsc'=>'BARB0000000',
    ];
@endphp
<div class="paper">
    @if(!empty($printSettings->letterhead_image))
        <div class="uploaded-letterhead"><img src="{{ asset($printSettings->letterhead_image) }}" alt="Letterhead"></div>
    @endif
    @if($topMargin > 0)<div style="height:{{ $topMargin }}mm"></div>@endif

    @if($topMargin <= 0)
        <div class="center"><div class="company-name">{{ $profile['name'] }}</div><div class="tagline">{{ $profile['tagline'] }}</div><div class="company-meta">{{ $profile['address'] }} &nbsp;|&nbsp; Mob: {{ $profile['phone'] }} &nbsp;|&nbsp; {{ $profile['email'] }}</div><div class="company-meta">PAN: {{ $profile['pan'] }} &nbsp;&nbsp; GST IN: {{ $profile['gst_no'] }}</div></div><div class="rule"></div>
    @endif

    <div class="invoice-title center">TAX INVOICE</div>
    <div class="top-grid">
        <div>
            <div class="meta-line"><span class="label">TAX INVOICE NO.</span><span class="bold">{{ $invoice->bill_no }}</span></div>
            <div class="meta-line"><span class="label">SO NO.</span><span class="bold">{{ $order?->so_number ?: '-' }}</span></div>
            <div class="meta-line"><span class="label">TAX MODE</span><span class="bold">{{ strtoupper($taxMode) }}</span></div>
            <div class="meta-line"><span class="label">SAC NO.</span><span class="bold">9965</span></div>
        </div>
        <div>
            <div class="meta-line"><span class="label">DATE OF INVOICE</span><span class="bold">{{ optional($invoice->invoice_date)->format('d.m.Y') }}</span></div>
            <div class="meta-line"><span class="label">PAN</span><span>{{ $pan }}</span></div>
            <div class="meta-line"><span class="label">GST IN</span><span>{{ $customer?->gst_no ?: '-' }}</span></div>
            <div class="meta-line"><span class="label">STATE CODE</span><span>{{ $stateCode }}</span></div>
        </div>
    </div>

    <div class="to-box"><h3>TO</h3><div class="to-name">{{ $customer?->name ?: '-' }}</div><div style="margin-top:4px;white-space:pre-line">{{ $customer?->address ?: '-' }}</div>@if($customer?->phone)<div style="margin-top:5px"><b>Phone:</b> {{ $customer->phone }}</div>@endif</div>
    <div class="service-box"><b>{{ $order?->description ?: 'Transportation Service' }}</b><br>Transportation from <b>{{ $order?->from_location ?: '-' }}</b> to <b>{{ $order?->to_location ?: '-' }}</b> · {{ $invoice->trip_count }} Trip(s)</div>

    <table class="grid">
        <thead><tr><th>Sr</th><th>LR No</th><th>LR Date</th><th>Lorry No</th><th>Vehicle Type</th><th class="num">Rate / Trip</th><th class="num">Taxable Amount</th></tr></thead>
        <tbody>
        @foreach($invoice->items as $item)
            @php($v=$item->voucher)
            <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $v?->lr_no ?: '-' }}</td><td>{{ optional($v?->lr_date)->format('d.m.Y') }}</td><td>{{ $v?->lorry_number ?: '-' }}</td><td>{{ $v?->vehicleType?->name ?: '-' }}</td><td class="num">{{ number_format((float)$item->rate,2) }}</td><td class="num">{{ number_format((float)$item->taxable_amount,2) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Customer Freight</td><td class="num" style="width:180px">₹{{ number_format((float)$invoice->customer_freight,2) }}</td></tr>
        @if($taxMode === 'na')
        <tr><td>GST</td><td class="num bold">NA</td></tr>
        @else
        <tr><td>GST @ {{ rtrim(rtrim(number_format((float)$invoice->gst_rate,2,'.',''),'0'),'.') }}%</td><td class="num">₹{{ number_format((float)$invoice->gst_amount,2) }}</td></tr>
        @endif
        <tr><td>Total</td><td class="num bold">₹{{ number_format($freightGstTotal,2) }}</td></tr>
        <tr><td>Other Charges</td><td class="num">₹{{ number_format($otherCharges,2) }}</td></tr>
        <tr><td>Total Invoice Amount</td><td class="num bold">₹{{ number_format($displayTotal,2) }}</td></tr>
    </table>
    <div class="amount-words">Rupees: {{ $amountWords }}</div>
    @if($isRcm)
        <div class="customer-bill-note">GST @5% WILL BE PAID BY SERVICE USER UNDER RCM (If Applicable). RCM @5%: ₹{{ number_format((float)$invoice->rcm_amount,2) }}</div>
    @endif

    <div class="terms-sign"><div class="terms"><h4>TERMS OF PAYMENT</h4><ol><li>Please do not deduct any amount from the bill without our consent.</li><li>Any dispute is subject to local jurisdiction only.</li><li>Payment should quote the invoice number.</li></ol>@if($invoice->remarks)<div style="margin-top:8px"><b>Remarks:</b> {{ $invoice->remarks }}</div>@endif</div><div class="signature"><div class="bold">For {{ $profile['name'] }}</div><div class="bold">Authorised Signatory</div></div></div>
    <div class="bank"><b>BANK DETAILS</b><br><b>Name:</b> {{ $profile['bank_holder'] }}<br><b>Bank:</b> {{ $profile['bank_name'] }}<br><b>Address:</b> {{ $profile['bank_address'] }}<br><b>A/C No:</b> {{ $profile['account_no'] }}<br><b>IFSC:</b> {{ $profile['ifsc'] }}</div>
    <div class="footer-note">Supplier freight, supplier payments, Hamali and internal profit are intentionally not shown on the customer tax invoice.</div>
</div>
<script>window.addEventListener('afterprint',()=>{try{parent.postMessage({type:'xyz-invoice-afterprint'},location.origin);}catch(e){}});</script>
</body>
</html>
