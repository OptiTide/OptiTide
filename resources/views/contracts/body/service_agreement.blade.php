@php($user = $model->user)
@php($order = $model->order)
<h2>Website Design &amp; Development Service Agreement</h2>
<p>This agreement is made between <strong>OptiTide</strong> ("the Agency") and
<strong>{{ $user->company_name ?: $user->name }}</strong> ("the Client")
@if ($order) in respect of order <strong>{{ $order->order_number }}</strong>@endif.</p>

<h3>1. Scope of work</h3>
<p>The Agency will design and develop the website and related deliverables described in the Client's onboarding submission and the purchased package. Additional work outside that scope may be quoted separately.</p>

<h3>2. Client responsibilities</h3>
<p>The Client will provide brand assets, content, and timely feedback. Delays in providing these may extend delivery timelines.</p>

<h3>3. Fees &amp; payment</h3>
<p>Fees are as stated at checkout, in Australian Dollars. Work commences once payment is received and this agreement is signed.</p>

<h3>4. Revisions &amp; approval</h3>
<p>The Agency will present designs for review in the Client portal. The Client may request revisions within the scope of the purchased package before final delivery and approval.</p>

<h3>5. Intellectual property</h3>
<p>On full payment, ownership of the final delivered website is transferred to the Client. The Agency retains the right to showcase the work in its portfolio.</p>

<h3>6. Refunds</h3>
<p>No refunds are provided for change of mind once work has commenced. This does not limit the Client's rights under the Australian Consumer Law.</p>

<h3>7. Governing law</h3>
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
