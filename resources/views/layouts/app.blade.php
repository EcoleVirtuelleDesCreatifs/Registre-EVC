<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <title>@yield('title', 'Registre officiel') · {{ config('evc.school') }}</title>
    <meta name="description" content="@yield('meta_description', 'Registre officiel de vérification des documents délivrés par l’École Virtuelle des Créatifs : certificats professionnels, attestations et lettres de recommandation. Vérifiez l’authenticité d’un document par sa référence ou son QR code.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta name="author" content="{{ config('evc.school') }}">
    <meta name="theme-color" content="#0f1d3d">
    <meta name="geo.region" content="CI-AB">
    <meta name="geo.placename" content="Abidjan">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Registre officiel — {{ config('evc.school') }}">
    <meta property="og:locale" content="fr_CI">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', 'Registre officiel') · {{ config('evc.school') }}">
    <meta property="og:description" content="@yield('meta_description', 'Vérifiez l’authenticité des certificats, attestations et lettres de recommandation délivrés par l’École Virtuelle des Créatifs.')">
    <meta property="og:image" content="{{ asset('images/logo-evc-couleur.png') }}">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', 'Registre officiel') · {{ config('evc.school') }}">
    <meta name="twitter:description" content="@yield('meta_description', 'Vérifiez l’authenticité des documents officiels de l’École Virtuelle des Créatifs.')">
    <meta name="twitter:image" content="{{ asset('images/logo-evc-couleur.png') }}">

    {{-- Données structurées JSON-LD --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "{{ config('evc.school') }}",
        "alternateName": "EVC",
        "url": "{{ config('app.url') }}",
        "logo": "{{ asset('images/logo-evc-couleur.png') }}",
        "address": {"@type": "PostalAddress", "addressLocality": "Abidjan", "addressCountry": "CI"},
        "contactPoint": {"@type": "ContactPoint", "email": "{{ config('evc.contact_emails')[0] ?? '' }}", "contactType": "customer service"}
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Registre officiel de vérification des documents",
        "url": "{{ config('app.url') }}",
        "publisher": {"@type": "Organization", "name": "{{ config('evc.school') }}"},
        "inLanguage": "fr"
    }
    </script>
    @stack('head')

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/evc.css') }}?v=2">
</head>
<body>
<header class="top"><div class="in">
    <a href="{{ route('public.index') }}"><img src="{{ asset('images/logo-evc-blanc.png') }}" alt="École Virtuelle des Créatifs"></a>
    <div class="brand"><div class="t1">@yield('brand_title', 'Registre officiel')</div><div class="t2">@yield('brand_sub', 'Vérification des documents')</div></div>
    <nav>@yield('nav')</nav>
</div></header>
@hasSection('layout_raw')
    @yield('layout_raw')
@else
    <main>@yield('content')</main>
    <footer class="foot">© {{ date('Y') }} {{ config('evc.school') }} · Abidjan, Côte d’Ivoire · <a href="{{ route('public.privacy') }}">Confidentialité</a></footer>
@endif
@stack('scripts')
</body>
</html>
