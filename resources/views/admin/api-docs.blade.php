@extends('layouts.master')
@section('title') {{ __('توثيق API') }} @endsection
@section('main_content')
<div class="container-fluid" x-data="docs()">
    <h5>🛠 {{ __('توثيق API v1') }} <a class="btn btn-sm btn-outline-secondary" href="/api/v1/docs" target="_blank">openapi.json</a></h5>
    <template x-for="(ops, path) in paths" :key="path">
        <div class="card mb-2"><div class="card-body py-2">
            <code x-text="path" class="fw-bold"></code>
            <template x-for="(op, method) in ops" :key="method">
                <div class="ms-2 small">
                    <span class="badge" :class="method === 'get' ? 'bg-success' : (method === 'delete' ? 'bg-danger' : 'bg-primary')" x-text="method.toUpperCase()"></span>
                    <span x-text="op.summary"></span>
                    <span class="badge bg-light text-dark" x-text="op.security.length ? 'auth 🔑' : 'public'"></span>
                </div>
            </template>
        </div></div>
    </template>
</div>
<script>
function docs() {
    return { paths: {}, async init() { this.paths = (await (await fetch('/api/v1/docs')).json()).paths; } }
}
document.addEventListener('DOMContentLoaded', () => document.querySelector('[x-data="docs()"]')?.__x?.init?.());
</script>
@endsection
