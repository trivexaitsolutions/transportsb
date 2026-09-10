<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Selected Customer Bills - {{ $customer->name }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#e5e7eb;color:#111;font-family:Arial,Helvetica,sans-serif;font-size:10px}.toolbar{max-width:210mm;margin:10px auto 0;display:flex;justify-content:flex-end;gap:8px}.toolbar button{border:1px solid #475569;background:#fff;padding:7px 15px;font-weight:800;cursor:pointer}.toolbar .primary{background:#065f46;color:#fff;border-color:#065f46}.paper{width:210mm;min-height:297mm;margin:8px auto 20px;background:#fff;padding:8mm 8mm 9mm}.letterhead-spacer{height:{{ max(0, (float) ($letterheadTopMarginMm ?? 0)) }}mm}.paper + .paper{page-break-before:always}.letterhead{text-align:center;min-height:25mm;border-bottom:1.5px solid #111;padding-bottom:5px;display:flex;flex-direction:column;justify-content:flex-end}.company-name{font-size:24px;font-weight:900;letter-spacing:.4px}.tagline{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}.company-tax{margin-top:5px;text-align:left;font-size:9px;font-weight:700}.title{text-align:center;font-size:16px;font-weight:900;margin:7px 0 5px}.sub-title{text-align:center;font-size:9px;margin-bottom:5px}.party-grid{display:grid;grid-template-columns:1fr 1fr;border:1px solid #111}.party-grid>div{min-height:34mm;padding:6px 7px}.party-grid>div+div{border-left:1px solid #111}.to-title{font-size:11px;font-weight:900;margin-bottom:5px}.party-name{font-size:13px;font-weight:900;line-height:1.25}.address{white-space:pre-line;line-height:1.35;margin-top:3px}.detail-row{display:grid;grid-template-columns:92px 1fr;gap:4px;margin:3px 0;line-height:1.3}.detail-row b{font-weight:900}.contract{border:1px solid #111;border-top:0;padding:4px 6px;font-weight:800}.bill-grid{width:100%;border-collapse:collapse;table-layout:fixed}.bill-grid th,.bill-grid td{border:1px solid #111;padding:4px 2px;vertical-align:middle;word-break:break-word}.bill-grid th{text-align:center;font-size:7.6px;text-transform:uppercase;font-weight:900;line-height:1.15}.bill-grid td{font-size:8.2px}.bill-grid .num{text-align:right;font-variant-numeric:tabular-nums}.bill-grid .center{text-align:center}.bill-grid .strong{font-weight:900}.bill-grid .total-row td{font-weight:900;background:#f4f4f4}.amount-words{border:1px solid #111;border-top:0;padding:5px 6px;font-weight:900;line-height:1.3}.customer-bill-note{border:1px solid #111;border-top:0;padding:5px 6px;font-weight:900;text-transform:uppercase;line-height:1.3}.gst-title{font-weight:900;margin-top:6px;margin-bottom:2px}.gst-summary{width:100%;border-collapse:collapse;table-layout:fixed}.gst-summary th,.gst-summary td{border:1px solid #111;padding:4px}.gst-summary th{font-size:8px}.gst-summary td{font-size:8.5px}.bottom{display:grid;grid-template-columns:1.25fr .75fr;border:1px solid #111;border-top:0;min-height:50mm}.bottom-left{padding:6px;border-right:1px solid #111}.declaration{font-weight:900;margin-bottom:4px}.terms-title{font-weight:900;text-decoration:underline;margin-top:4px}.terms{margin:4px 0 7px;padding-left:17px;line-height:1.55}.bank-title{font-size:12px;font-weight:900;text-transform:uppercase;margin:4px 0}.bank-grid{line-height:1.55;font-size:9px}.sign{padding:7px;text-align:center;display:flex;flex-direction:column;justify-content:space-between;font-size:10px;font-weight:900}.stamp-space{height:30mm;display:flex;align-items:center;justify-content:center;color:#777;font-size:9px}.period-note{text-align:right;margin:4px 0;font-size:8px}.footer{margin-top:5px;text-align:center;font-size:7px;color:#555}.w-sr{width:5%}.w-bill{width:9%}.w-lr{width:8%}.w-date{width:9%}.w-lorry{width:10%}.w-place{width:10%}.w-amt{width:10%}
        @media print{body{background:#fff}.toolbar{display:none}.paper{width:auto;min-height:0;margin:0;padding:5mm 6mm 6mm}.paper + .paper{page-break-before:always}@page{size:A4 portrait;margin:0}}
    </style>
</head>
<body>
@php
    $amountInWords = function (float $amount): string {
        $fallback = number_format($amount, 2).' Rupees Only';
        if (!class_exists('NumberFormatter')) return $fallback;
        try {
            $fmt = new NumberFormatter('en_IN', NumberFormatter::SPELLOUT);
            $whole = (int) floor($amount);
            $paise = (int) round(($amount - $whole) * 100);
            $words = ucwords($fmt->format($whole)).' Rupees';
            if ($paise > 0) $words .= ' and '.ucwords($fmt->format($paise)).' Paise';
            return $words.' Only';
        } catch (Throwable $e) {
            return $fallback;
        }
    };
@endphp
@if(!request()->boolean('embedded'))<div class="toolbar"><button type="button" onclick="history.back()">Back</button><button type="button" class="primary" onclick="window.print()">Print</button></div>@endif

@foreach($groups as $group)
@php
    $rows = $group['rows'];
    $amount = $group['amount'];
    $taxable = $group['taxable'];
    $gst = $group['gst'];
    $total = $group['total'];
@endphp
<div class="paper">
    @if(($letterheadTopMarginMm ?? 0) > 0)<div class="letterhead-spacer" aria-hidden="true"></div>@endif
    <div class="letterhead">
        <div class="company-name">{{ $printProfile['name'] }}</div>
        <div class="tagline">{{ $printProfile['tagline'] }}</div>
        <div style="font-size:8.5px;font-weight:700;margin-top:2px">{{ $printProfile['address'] }} &nbsp;|&nbsp; Mob: {{ $printProfile['phone'] }} &nbsp;|&nbsp; {{ $printProfile['email'] }}</div>
        <div class="company-tax">PAN : {{ $printProfile['pan'] }} &nbsp;&nbsp;&nbsp; GST IN : {{ $printProfile['gst_no'] }}</div>
    </div>

    <div class="title">TAX INVOICE</div>
    <div class="sub-title">Under Rule 46 of the CGST Rules, 2017</div>

    <div class="party-grid">
        <div>
            <div class="to-title">TO</div>
            <div class="party-name">{{ $customer->name }}</div>
            <div class="address">{{ $customer->address ?: '-' }}</div>
            @if($customer->phone)<div style="margin-top:4px"><b>Phone:</b> {{ $customer->phone }}</div>@endif
        </div>
        <div>
            <div class="detail-row"><b>Date of Invoice</b><span>{{ now()->format('d.m.Y') }}</span></div>
            <div class="detail-row"><b>Place of Service</b><span>{{ $group['placeOfService'] }}</span></div>
            <div class="detail-row"><b>PAN</b><span>{{ $customerPan }}</span></div>
            <div class="detail-row"><b>GST IN</b><span>{{ $customer->gst_no ?: '-' }}</span></div>
            <div class="detail-row"><b>State Code</b><span>{{ $stateCode }}</span></div>
        </div>
    </div>
    <div class="contract">Contract / Ref No.: {{ $group['contractReference'] }}</div>
    <div class="period-note">Selected period: {{ \Carbon\Carbon::parse($from)->format('d-m-Y') }} to {{ \Carbon\Carbon::parse($to)->format('d-m-Y') }}</div>

    <table class="bill-grid">
        <thead>
            <tr>
                <th class="w-sr">SR</th>
                <th class="w-bill">Bill No</th>
                <th class="w-lr">LR No</th>
                <th class="w-date">LR Date</th>
                <th class="w-lorry">Lorry No</th>
                <th class="w-place">From</th>
                <th class="w-place">To</th>
                <th class="w-amt">Amount</th>
                <th class="w-amt">Taxable Amount</th>
                <th class="w-amt">GST</th>
                <th class="w-amt">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $voucher)
            @php
                $taxableBase = (float) $voucher->customer_freight;
                $base = $taxableBase + (float) $voucher->hamali_loading + (float) $voucher->hamali_unloading + (float) $voucher->other_charges;
                $gstAmount = (float) $voucher->gst;
                $rowTotal = $base + $gstAmount;
                $rate = (float) ($voucher->gstRate?->rate ?? 0);
            @endphp
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $voucher->bill_no ?: '-' }}</td>
                <td>{{ $voucher->lr_no ?: '-' }}</td>
                <td class="center">{{ optional($voucher->lr_date)->format('d.m.Y') }}</td>
                <td>{{ $voucher->lorry_no ?: '-' }}</td>
                <td>{{ $voucher->from_place ?: '-' }}</td>
                <td>{{ $voucher->to_place ?: '-' }}</td>
                <td class="num">{{ number_format($base,2) }}</td>
                <td class="num">{{ number_format($taxableBase,2) }}</td>
                <td class="num">{{ number_format($gstAmount,2) }}<br><small>@ {{ rtrim(rtrim(number_format($rate,2,'.',''),'0'),'.') }}%</small></td>
                <td class="num strong">{{ number_format($rowTotal,2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="7" class="num">Total Amount</td>
                <td class="num">{{ number_format($amount,2) }}</td>
                <td class="num">{{ number_format($taxable,2) }}</td>
                <td class="num">{{ number_format($gst,2) }}</td>
                <td class="num">{{ number_format($total,2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="amount-words">Rupees In Words: {{ $amountInWords($total) }}</div>
    @if(trim((string) ($customer->bill_note ?? '')) !== '')
        <div class="customer-bill-note">{{ $customer->bill_note }}</div>
    @endif

    <div class="gst-title">Details of GST</div>
    <table class="gst-summary">
        <thead><tr><th>GST Rate</th><th class="num">Taxable Amount</th><th class="num">GST Amount</th><th class="num">Total</th></tr></thead>
        <tbody>
            @foreach($group['gstSummary'] as $summary)
            <tr><td>{{ rtrim(rtrim(number_format($summary['rate'],2,'.',''),'0'),'.') }}%</td><td class="num">{{ number_format($summary['taxable'],2) }}</td><td class="num">{{ number_format($summary['gst'],2) }}</td><td class="num strong">{{ number_format($summary['total'],2) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="bottom">
        <div class="bottom-left">
            <div class="declaration">Declaration:-</div>
            <div class="terms-title">TERMS OF PAYMENT</div>
            <ol class="terms">
                <li>Please do not deduct any amount from the Bill without our consent.</li>
                <li>Any disputes are subject to Nagpur jurisdiction only.</li>
                <li>We reserve the right to charge @18% per annum if payment for the same is not received in time.</li>
            </ol>
            <div class="bank-title">BANK DETAILS</div>
            <div class="bank-grid">
                <b>NAME :</b> {{ $printProfile['bank_holder'] }}<br>
                <b>BANK NAME :</b> {{ $printProfile['bank_name'] }}<br>
                <b>ADDRESS :</b> {{ $printProfile['bank_address'] }}<br>
                <b>A/C No :</b> {{ $printProfile['account_no'] }}<br>
                <b>IFS CODE :</b> {{ $printProfile['ifsc'] }}
            </div>
        </div>
        <div class="sign">
            <div>For {{ $printProfile['name'] }}</div>
            <div class="stamp-space">STAMP / SIGN</div>
            <div>Authorised Signatory</div>
        </div>
    </div>
    <div class="footer">Printed from {{ $printProfile['name'] }} · {{ now()->format('d-m-Y h:i A') }}</div>
</div>
@endforeach
@if(!request()->boolean('embedded'))<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),250));</script>@endif
</body>
</html>
