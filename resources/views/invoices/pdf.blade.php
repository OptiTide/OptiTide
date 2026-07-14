@php($money = fn ($m) => $m?->format() ?? '')
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 12px; line-height: 1.5; }
        .header { border-bottom: 3px solid #0284c7; padding-bottom: 16px; margin-bottom: 24px; }
        .brand { font-size: 24px; font-weight: bold; color: #0f172a; }
        .brand span { color: #0284c7; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        table.meta { width: 100%; margin-bottom: 24px; }
        table.meta td { vertical-align: top; padding: 0; }
        h1.title { font-size: 20px; margin: 0 0 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; color: #475569; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        table.items td.num, table.items th.num { text-align: right; }
        table.totals { width: 40%; margin-left: 60%; margin-top: 16px; }
        table.totals td { padding: 4px 10px; }
        table.totals tr.grand td { font-weight: bold; font-size: 14px; border-top: 2px solid #0f172a; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: bold; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-draft, .status-void { background: #f1f5f9; color: #475569; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <table class="meta">
            <tr>
                <td>
                    <div class="brand">Opti<span>Tide</span></div>
                    @php($company = config('company'))
                    <div class="muted">
                        {{ $company['legal_name'] }}<br>
                        @if (filled($company['abn']))ABN {{ $company['abn'] }}<br>@endif
                        @if (filled($company['address']['line1'])){{ $company['address']['line1'] }}<br>@endif
                        @if (filled($company['address']['locality'])){{ $company['address']['locality'] }} {{ $company['address']['region'] }} {{ $company['address']['postcode'] }}<br>@endif
                        {{ $company['address']['country'] }}
                    </div>
                </td>
                <td class="right">
                    <h1 class="title">TAX INVOICE</h1>
                    <div class="muted">{{ $invoice->invoice_number }}</div>
                    <div class="status status-{{ $invoice->status->value }}">{{ $invoice->status->getLabel() }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td>
                <strong>Billed to</strong><br>
                {{ $invoice->user->name }}<br>
                @if ($invoice->user->company_name){{ $invoice->user->company_name }}<br>@endif
                {{ $invoice->user->email }}
            </td>
            <td class="right">
                <strong>Issued:</strong> {{ ($invoice->sent_at ?? $invoice->created_at)?->format('d M Y') }}<br>
                @if ($invoice->due_date)<strong>Due:</strong> {{ $invoice->due_date->format('d M Y') }}<br>@endif
                @if ($invoice->order)<strong>Order:</strong> {{ $invoice->order->order_number }}@endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit price</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $money($item->unit_price) }}</td>
                    <td class="num">{{ $money($item->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No line items.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        {{-- Line items are GST-inclusive, so the total is the sum of the lines
             and GST is shown as the component included within it. --}}
        <tr class="grand"><td>Total</td><td class="right">{{ $money($invoice->total) }}</td></tr>
        @if (! $invoice->tax->isZero())
            <tr><td class="muted">Includes GST (10%)</td><td class="right muted">{{ $money($invoice->tax) }}</td></tr>
        @endif
        @if (! $invoice->amount_paid->isZero())
            <tr><td>Paid</td><td class="right">{{ $money($invoice->amount_paid) }}</td></tr>
            <tr><td>Balance due</td><td class="right">{{ $money($invoice->total->subtract($invoice->amount_paid)) }}</td></tr>
        @endif
    </table>

    <div class="footer">
        All amounts in {{ $invoice->currency }}. No refunds for change of mind. This does not limit your rights under the Australian Consumer Law.
    </div>
</body>
</html>
