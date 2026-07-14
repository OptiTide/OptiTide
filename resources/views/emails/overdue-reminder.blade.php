<x-mail::message>
{!! nl2br(e($body)) !!}

<x-mail::button :url="config('app.url').'/client/invoices'">
View &amp; pay invoice {{ $invoice->invoice_number }}
</x-mail::button>

Thanks,<br>
The OptiTide Team
</x-mail::message>
