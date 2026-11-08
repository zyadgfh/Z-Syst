@extends('layouts.master')
@section('title') {{ __('المساعد الذكي') }} @endsection
@section('main_content')
<div class="container-fluid" style="max-width: 760px" x-data="chat()">
    <div class="card"><div class="card-body">
        <h5>🤖 {{ __('اسأل عن مبيعاتك ومخزونك بالعربي') }}</h5>
        <div class="border rounded p-3 mb-3" style="min-height: 260px; max-height: 420px; overflow-y: auto">
            <template x-for="(m, i) in messages" :key="i">
                <div class="mb-2" :class="m.me ? 'text-end' : ''">
                    <span class="d-inline-block px-3 py-2 rounded" :class="m.me ? 'bg-primary text-white' : 'bg-light'"
                          style="white-space: pre-line" x-text="m.text"></span>
                </div>
            </template>
        </div>
        <form @submit.prevent="ask()" class="d-flex gap-2">
            <input class="form-control" x-model="q" placeholder="{{ __('مثل: كام بيعت الشهر ده؟') }}">
            <button class="btn btn-primary">{{ __('اسأل') }}</button>
        </form>
        <div class="mt-2 small text-muted">
            جرّب: «كام بيعت النهاردة؟» · «عدد فواتير امبارح» · «الربح الشهر ده» · «مخزون فيتامين سي» · «ناقص مخزون» · «أعلى الأصناف مبيعًا»
        </div>
    </div></div>
</div>
<script>
function chat() {
    return {
        messages: [{me: false, text: 'أهلاً 👋 اسألني عن المبيعات أو المخزون.'}],
        q: '',
        async ask() {
            const text = this.q.trim();
            if (!text) return;
            this.messages.push({me: true, text});
            this.q = '';
            const r = await fetch('{{ route('admin.assistant.ask') }}', {method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({q: text})});
            const j = await r.json();
            this.messages.push({me: false, text: j.answer || j.message || 'خطأ'});
            this.$nextTick(() => { const box = document.querySelector('[style*="max-height"]'); box && (box.scrollTop = box.scrollHeight); });
        }
    }
}
</script>
@endsection
