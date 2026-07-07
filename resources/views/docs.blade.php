<x-layouts.app title="PharmaSync Docs">
    <div style="display:grid; gap:24px;">
        <section>
            <h1 style="margin:0 0 12px; font-size:clamp(2rem,4vw,2.8rem);">PharmaSync Documentation</h1>
            <p style="margin:0;color:#475569;line-height:1.8;max-width:860px;">Rendered markdown documentation for the PharmaSync product feature list.</p>
        </section>
        <section style="background:#ffffff; border:1px solid #e5e7eb; border-radius:24px; padding:32px; color:#111827; line-height:1.85;">
            {!! $content !!}
        </section>
    </div>
</x-layouts.app>
