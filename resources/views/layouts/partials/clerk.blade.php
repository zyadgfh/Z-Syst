{{-- Clerk Authentication - CDN Setup --}}
@if(env('VITE_CLERK_PUBLISHABLE_KEY'))
@php
    $clerkKey = env('VITE_CLERK_PUBLISHABLE_KEY');
    // Derive the Frontend API domain from the publishable key
    $clerkDomain = substr(base64_decode(substr($clerkKey, strpos($clerkKey, '_') + 1)), 0, -1);
@endphp
<script
    defer
    crossorigin="anonymous"
    src="https://{{ $clerkDomain }}/npm/@clerk/ui@1/dist/ui.browser.js"
    type="text/javascript"
></script>
<script
    defer
    crossorigin="anonymous"
    data-clerk-publishable-key="{{ $clerkKey }}"
    src="https://{{ $clerkDomain }}/npm/@clerk/clerk-js@6/dist/clerk.browser.js"
    type="text/javascript"
></script>
<script>
    window.addEventListener('load', async function () {
        if (typeof Clerk === 'undefined') {
            console.warn('Clerk JS did not load. Check your VITE_CLERK_PUBLISHABLE_KEY.');
            return;
        }
        await Clerk.load({
            ui: { ClerkUI: window.__internal_ClerkUICtor },
        });
        console.log('Clerk loaded successfully.');
    });
</script>
@endif
