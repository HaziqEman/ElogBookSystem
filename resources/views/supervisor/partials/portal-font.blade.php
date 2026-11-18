{{-- Shared font for the supervisor pages (matches the student portal). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@include('partials.google-fonts', ['includeCaveat' => false])
<style>
    .portal-font,
    .portal-font :where(input, select, textarea, button, summary) {
        font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
    }
</style>
