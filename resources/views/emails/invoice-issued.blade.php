<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Hi {{ $invoice->user->name }},

Please find your invoice attached. A summary is below.

<x-mail::table>
| | |
|:--|--:|
| **Amount due** | {{ $invoice->total->format() }} |
@if ($invoice->due_date)| **Due date** | {{ $invoice->due_date->format('d M Y') }} |@endif
@if ($invoice->order)| **Order** | {{ $invoice->order->order_number }} |@endif
</x-mail::table>

<x-mail::button :url="config('app.url').'/client/invoices'">
View in your portal
</x-mail::button>

Thanks,<br>
The OptiTide Team

<small>All amounts in {{ $invoice->currency }}. No refunds for change of mind. This does not limit your rights under the Australian Consumer Law.</small>
</x-mail::message>
