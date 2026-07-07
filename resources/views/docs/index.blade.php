<x-layouts.app title="PharmaSync Docs">
    <div style="display:grid; gap:24px;">
        <section>
            <h1 style="margin:0 0 12px; font-size:clamp(2rem,4vw,2.8rem);">Documentation</h1>
            <p style="margin:0;color:#475569;line-height:1.8;max-width:860px;">Browse available PharmaSync documentation pages.</p>
        </section>

        <section style="display:grid; gap:14px;">
            @foreach ($pages as $title => $url)
                <a class="button" href="{{ $url }}">{{ $title }}</a>
            @endforeach
        </section>
    </div>
</x-layouts.app>
