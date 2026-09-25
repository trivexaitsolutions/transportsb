<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $title }} - {{ $party->name }}</title>
<style>
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#d1d5db;color:#111;font-size:11px}
.paper{width:210mm;min-height:297mm;margin:0 auto;background:#fff;padding:9mm 10mm}
.center{text-align:center}.right{text-align:right}.bold{font-weight:800}
.uploaded-letterhead{text-align:center;margin-bottom:3mm}.uploaded-letterhead img{display:block;width:100%;max-height:50mm;object-fit:contain}
.company-name{font-size:26px;font-weight:900;letter-spacing:.2px}.tagline{font-size:11px;font-weight:800;text-transform:uppercase;margin-top:2px}.company-meta{font-size:10px;font-weight:700;margin-top:4px}.rule{border-top:2px solid #111;margin:7px 0}
.title{font-size:19px;font-weight:900;text-decoration:underline;margin:8px 0 7px}
.party-box{display:grid;grid-template-columns:1.25fr .75fr;border:1px solid #111}
.party-box>div{padding:7px 8px}.party-box>div+div{border-left:1px solid #111}
.party-name{font-size:15px;font-weight:900}.muted{color:#444}.meta{line-height:1.45}
.summary{width:100%;border-collapse:collapse;margin-top:7px}.summary td{border:1px solid #111;padding:6px 7px}.summary .label{font-weight:800;background:#f3f4f6}.summary .amount{text-align:right;font-weight:900}
.ledger{width:100%;border-collapse:collapse;margin-top:7px}.ledger th,.ledger td{border:1px solid #111;padding:5px 5px;vertical-align:top}.ledger th{font-size:9px;text-transform:uppercase;background:#f3f4f6}.ledger .money{text-align:right;white-space:nowrap;font-weight:800}.ledger .total td{background:#eef6ff;font-weight:900;border-top:2px solid #111}.ledger .previous td{background:#fff8d6;font-weight:900;border-top:1px solid #111}
.ledger .empty td{text-align:center;padding:20px;color:#555}.note{font-size:9px;color:#444;font-weight:normal}.footer{margin-top:5px;font-size:9px;color:#4b5563}
@media print{
  body{background:#fff}.paper{width:auto;min-height:auto;margin:0;padding:7mm}
  @page{size:A4 portrait;margin:0}
}
</style>
</head>
<body>
@php
    $topMargin = max(0, (float) ($printSettings->letterhead_top_margin_mm ?? 0));
    $code = trim((string) ($party->code ?? ''));
    $phone = trim((string) ($party->phone ?? ''));
    $gst = trim((string) ($party->gst_no ?? ''));
    $address = trim((string) ($party->address ?? ''));
@endphp

<div class="paper">
    @if(!empty($printSettings->letterhead_image))
        <div class="uploaded-letterhead">
            <img src="{{ asset($printSettings->letterhead_image) }}" alt="Letterhead">
        </div>
    @endif

    @if($topMargin > 0)
        <div style="height:{{ $topMargin }}mm"></div>
    @endif

    @if($topMargin <= 0)
        <div class="center">
            <div class="company-name">XYZ TRANSPORT</div>
            <div class="tagline">Fleet Owners &amp; Transport Contractor</div>
        </div>
        <div class="rule"></div>
    @endif

    <div class="title center">{{ strtoupper($title) }}</div>

    <div class="party-box">
        <div>
            <div class="muted">{{ strtoupper($partyLabel) }}</div>
            <div class="party-name">{{ $party->name }}</div>
            @if($code)<div class="meta"><b>Code:</b> {{ $code }}</div>@endif
            @if($phone)<div class="meta"><b>Phone:</b> {{ $phone }}</div>@endif
            @if($gst)<div class="meta"><b>GST No.:</b> {{ $gst }}</div>@endif
            @if($address)<div class="meta"><b>Address:</b> {{ $address }}</div>@endif
        </div>
        <div class="meta">
            <div><b>From Date:</b> {{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</div>
            <div><b>To Date:</b> {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}</div>
            <div><b>Printed:</b> {{ now()->format('d-m-Y h:i A') }}</div>
        </div>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Previous Outstanding</td>
            <td class="amount">₹{{ number_format(abs($previousOutstanding),2) }} {{ $previousOutstanding >= 0 ? 'Due' : 'Advance' }}</td>
            <td class="label">Period Debit</td>
            <td class="amount">₹{{ number_format($debitTotal,2) }}</td>
        </tr>
        <tr>
            <td class="label">Period Credit</td>
            <td class="amount">₹{{ number_format($creditTotal,2) }}</td>
            <td class="label">Closing Outstanding</td>
            <td class="amount">₹{{ number_format(abs($closingOutstanding),2) }} {{ $closingOutstanding >= 0 ? 'Due' : 'Advance' }}</td>
        </tr>
    </table>

    <table class="ledger">
        <thead>
            <tr>
                <th style="width:12%">Date</th>
                <th style="width:18%">Particular</th>
                <th style="width:17%">Reference</th>
                <th>Remarks</th>
                <th style="width:13%;text-align:right">Debit</th>
                <th style="width:13%;text-align:right">Credit</th>
                <th style="width:15%;text-align:right">Outstanding</th>
            </tr>
        </thead>
        <tbody>
        @forelse($entries as $entry)
            <tr>
                <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d-m-Y') }}</td>
                <td><b>{{ $entry['particular'] }}</b></td>
                <td>{{ $entry['reference'] ?: '-' }}</td>
                <td>{{ $entry['remarks'] ?: '-' }}</td>
                <td class="money">{{ $entry['debit'] > 0 ? '₹'.number_format($entry['debit'],2) : '-' }}</td>
                <td class="money">{{ $entry['credit'] > 0 ? '₹'.number_format($entry['credit'],2) : '-' }}</td>
                <td class="money">
                    ₹{{ number_format(abs($entry['balance']),2) }}
                    <span class="note">{{ $entry['balance'] >= 0 ? 'Due' : 'Advance' }}</span>
                </td>
            </tr>
        @empty
            <tr class="empty"><td colspan="7">No Debit/Credit entries found in this date range.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr class="total">
            <td colspan="4">TOTAL</td>
            <td class="money">₹{{ number_format($debitTotal,2) }}</td>
            <td class="money">₹{{ number_format($creditTotal,2) }}</td>
            {{-- Print view me bhi TOTAL row ka Outstanding column blank kar diya --}}
            <td class="money">-</td>
        </tr>
        <tr class="previous">
            <td>{{ \Carbon\Carbon::parse($fromDate)->subDay()->format('d-m-Y') }}</td>
            <td colspan="3">Previous Outstanding</td>
            <td class="money">-</td>
            <td class="money">-</td>
            <td class="money">
                ₹{{ number_format(abs($previousOutstanding),2) }}
                <span class="note">{{ $previousOutstanding >= 0 ? 'Due' : 'Advance' }}</span>
            </td>
        </tr>
        </tfoot>
    </table>

    <div class="footer">Newest entry is shown first. Debit increases outstanding; Credit reduces outstanding.</div>
</div>

</body>
</html>