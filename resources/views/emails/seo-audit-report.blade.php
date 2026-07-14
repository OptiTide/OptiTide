@php($host = parse_url($lead->website_url, PHP_URL_HOST) ?: 'your website')
@php($score = $lead->meta['audit']['overall_score'] ?? null)
<x-mail::message>
# Your SEO audit is ready

Thanks for requesting an instant SEO audit for **{{ $host }}**. Your full report is attached as a PDF — it scores your site out of 100 and lists the highest-impact fixes.

@if ($score !== null)
**Overall score: {{ $score }}/100**
@endif

Want us to implement these fixes for you? Just reply to this email and our team will be in touch.

<x-mail::button :url="config('app.url').'/services'">
See how we can help
</x-mail::button>

Thanks,<br>
The OptiTide Team
</x-mail::message>
