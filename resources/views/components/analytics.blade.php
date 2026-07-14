@php
    // Re-validate the stored IDs at render (defence-in-depth). Each ID is a
    // strict charset ([A-Z0-9-] or digits), so interpolating it into these
    // FIXED script templates is injection-proof; anything that doesn't match is
    // dropped. We never store or render a raw <script> tag.
    $ga4 = \App\Models\Setting::get('ga4_measurement_id');
    $gtm = \App\Models\Setting::get('gtm_container_id');
    $pixel = \App\Models\Setting::get('meta_pixel_id');

    $ga4 = ($ga4 && preg_match('/^G-[A-Z0-9]{4,20}$/', $ga4)) ? $ga4 : null;
    $gtm = ($gtm && preg_match('/^GTM-[A-Z0-9]{4,20}$/', $gtm)) ? $gtm : null;
    $pixel = ($pixel && preg_match('/^[0-9]{5,20}$/', $pixel)) ? $pixel : null;
@endphp

@if ($ga4)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $ga4 }}');
    </script>
@endif

@if ($gtm)
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm }}');</script>
@endif

@if ($pixel)
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $pixel }}');
        fbq('track', 'PageView');
    </script>
@endif
