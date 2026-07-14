@php($user = $model->user)
<h2>Hosting Services Agreement</h2>
<p>This agreement is made between <strong>OptiTide</strong> ("the Agency") and
<strong>{{ $user->company_name ?: $user->name }}</strong> ("the Client")
for the provision of ongoing web hosting services.</p>

<h3>1. Services</h3>
<p>The Agency will provide hosting per the Client's active plan, including server provisioning and an SSL certificate. Managed plans additionally include core updates, daily backups, uptime and SSL monitoring, and minor maintenance.</p>

<h3>2. Billing &amp; term</h3>
<p>Hosting is billed monthly in advance in Australian Dollars via the Client's saved payment method. The service continues month to month until cancelled.</p>

<h3>3. Uptime</h3>
<p>The Agency targets 99.9% uptime but does not guarantee uninterrupted service. Scheduled maintenance will be communicated where practical.</p>

<h3>4. Client content</h3>
<p>The Client is responsible for the legality of content hosted. The Agency may suspend service for content that is unlawful or endangers the platform.</p>

<h3>5. Cancellation</h3>
<p>Either party may cancel with reasonable notice. Fees already paid for the current billing period are non-refundable for change of mind. This does not limit the Client's rights under the Australian Consumer Law.</p>

<h3>6. Governing law</h3>
<p>This agreement is governed by the laws of Australia.</p>

<br>
<table cellpadding="4" style="width:100%; font-size:11px;">
    <tr>
        <td style="width:50%;">
            <strong>Client:</strong> {{ $user->name }}<br>
            @if ($user->company_name)<strong>Company:</strong> {{ $user->company_name }}<br>@endif
            <strong>Email:</strong> {{ $user->email }}<br>
            <strong>Date:</strong> {{ now()->format('d M Y') }}
        </td>
        <td style="width:50%;">
            <strong>Signature:</strong>
        </td>
    </tr>
</table>
