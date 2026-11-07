@extends('layouts.master')

@section('title')
    {{ __('Devices & Printers') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-devices">🖥 {{ __('الأجهزة') }} <span class="badge bg-secondary">{{ $devices->count() }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-printers">🖨 {{ __('الطابعات') }} <span class="badge bg-secondary">{{ $printers->count() }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-prefs">⚙ {{ __('التفضيلات') }}</a></li>
    </ul>

    {{-- ================= DEVICES ================= --}}
    <div class="tab-content">
    <div id="tab-devices" class="tab-pane fade show active">
        <div class="card mb-3"><div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center">
            <strong>{{ __('هذا الجهاز') }}:</strong>
            <span>{{ $current?->name ?? __('غير معروف') }}</span>
            <span class="badge bg-dark">{{ $current?->type ?? '—' }}</span>
            <span>{{ __('طابعة الباركود') }}: {{ $current?->barcodePrinter?->name ?? __('افتراضية/—') }}</span>
            <span>{{ __('طابعة الإيصالات') }}: {{ $current?->receiptPrinter?->name ?? __('افتراضية/—') }}</span>
            <button class="btn btn-sm btn-outline-primary ms-auto" onclick="netScan()">📡 {{ __('مسح الشبكة') }}</button>
        </div></div>

        <div class="card"><div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr>
                        <th>{{ __('الجهاز') }}</th><th>{{ __('النوع') }}</th><th>{{ __('IP') }}</th>
                        <th>{{ __('آخر ظهور') }}</th><th>{{ __('ماسح') }}</th>
                        <th>{{ __('طابعة باركود') }}</th><th>{{ __('طابعة إيصالات') }}</th>
                        <th>{{ __('الحالة') }}</th><th></th>
                    </tr></thead>
                    <tbody>
                        @forelse($devices as $device)
                            <tr class="{{ $device->id === ($current?->id) ? 'table-success' : '' }}">
                                <td>
                                    <form method="POST" action="{{ route('admin.device-setup.devices.update', $device) }}" class="d-flex gap-1">
                                        @csrf @method('PUT')
                                        <input name="name" value="{{ $device->name }}" class="form-control form-control-sm" style="width:150px">
                                        <button class="btn btn-sm btn-light">💾</button>
                                    </form>
                                </td>
                                <td><span class="badge bg-dark">{{ $device->type }}</span></td>
                                <td class="font-monospace small">{{ $device->ip_address ?? '—' }}</td>
                                <td class="small">{{ $device->last_seen_at?->diffForHumans() ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.device-setup.devices.update', $device) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="has_scanner" value="{{ $device->has_scanner ? '0' : '1' }}">
                                        <button class="btn btn-sm {{ $device->has_scanner ? 'btn-success' : 'btn-light' }}">⌨</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.device-setup.devices.update', $device) }}" class="d-flex gap-1">
                                        @csrf @method('PUT')
                                        <select name="barcode_printer_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">{{ __('افتراضي') }}</option>
                                            @foreach($printers->where('type', 'barcode') as $printer)
                                                <option value="{{ $printer->id }}" @selected($device->barcode_printer_id === $printer->id)>{{ $printer->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.device-setup.devices.update', $device) }}" class="d-flex gap-1">
                                        @csrf @method('PUT')
                                        <select name="receipt_printer_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">{{ __('افتراضي') }}</option>
                                            @foreach($printers->where('type', 'receipt') as $printer)
                                                <option value="{{ $printer->id }}" @selected($device->receipt_printer_id === $printer->id)>{{ $printer->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    @if($device->is_approved)
                                        <span class="badge bg-success">{{ __('معتمد') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ __('معلق') }}</span>
                                    @endif
                                    @if(! $device->is_active)<span class="badge bg-secondary">{{ __('معطل') }}</span>@endif
                                </td>
                                <td class="text-nowrap">
                                    <form method="POST" action="{{ route('admin.device-setup.devices.approve', $device) }}" class="d-inline">@csrf
                                        <button class="btn btn-sm {{ $device->is_approved ? 'btn-warning' : 'btn-success' }}">{{ $device->is_approved ? __('تعليق') : __('اعتماد') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.device-setup.devices.update', $device) }}" class="d-inline">@csrf @method('PUT')
                                        <input type="hidden" name="is_active" value="{{ $device->is_active ? '0' : '1' }}">
                                        <button class="btn btn-sm {{ $device->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $device->is_active ? __('تعطيل') : __('تفعيل') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.device-setup.devices.destroy', $device) }}" class="d-inline" onsubmit="return confirm('{{ __('حذف الجهاز؟') }}')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">🗑</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">{{ __('لا أجهزة — افتح أي صفحة وسيُسجل تلقائيًا (حسب التفضيلات)') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>

    {{-- ================= PRINTERS ================= --}}
    <div id="tab-printers" class="tab-pane fade">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card"><div class="card-body">
                    <h5>{{ __('إضافة طابعة') }}</h5>
                    <form method="POST" action="{{ route('admin.device-setup.printers.store') }}">
                        @csrf
                        <input name="name" class="form-control mb-2" placeholder="{{ __('الاسم (مثال: طابعة الكاشير)') }}" required>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><select name="type" class="form-select">
                                @foreach($printerTypes as $type)
                                    <option value="{{ $type }}">{{ ['barcode' => __('باركود'), 'receipt' => __('إيصالات'), 'label' => __('ملصقات')][$type] }}</option>
                                @endforeach
                            </select></div>
                            <div class="col-6"><select name="connection" class="form-select">
                                @foreach($connections as $connection)
                                    <option value="{{ $connection }}">{{ ['network' => __('شبكة'), 'usb' => 'USB', 'bluetooth' => 'Bluetooth', 'system' => __('نظام/تعريف')][$connection] }}</option>
                                @endforeach
                            </select></div>
                        </div>
                        <input name="host" class="form-control mb-2" placeholder="{{ __('IP:Port (للشبكة، مثل 192.168.1.50:9100) — اختياري') }}">
                        <div class="row g-2 mb-3">
                            <div class="col-6"><input name="vendor" class="form-control" placeholder="{{ __('المصنع') }}"></div>
                            <div class="col-6"><input name="model" class="form-control" placeholder="{{ __('الموديل') }}"></div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default_barcode" value="1" id="pdflt">
                            <label class="form-check-label" for="pdflt">{{ __('افتراضية لطباعة الباركود') }}</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_default_receipt" value="1" id="pdflt2">
                            <label class="form-check-label" for="pdflt2">{{ __('افتراضية للإيصالات') }}</label>
                        </div>
                        <button class="btn btn-primary">+ {{ __('إضافة') }}</button>
                    </form>
                </div></div>
            </div>
            <div class="col-lg-7">
                <div class="card"><div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr>
                                <th>{{ __('الاسم') }}</th><th>{{ __('النوع') }}</th><th>{{ __('الاتصال') }}</th>
                                <th>{{ __('مرتبطة') }}</th><th>{{ __('الحالة') }}</th><th></th>
                            </tr></thead>
                            <tbody>
                                @forelse($printers as $printer)
                                    <tr>
                                        <td>{{ $printer->name }} @if($printer->is_default_barcode)<span class="badge bg-primary">{{ __('باركود افتراضي') }}</span>@endif @if($printer->is_default_receipt)<span class="badge bg-success">{{ __('إيصالات افتراضي') }}</span>@endif</td>
                                        <td><span class="badge bg-dark">{{ ['barcode' => __('باركود'), 'receipt' => __('إيصالات'), 'label' => __('ملصقات')][$printer->type] }}</span></td>
                                        <td class="small">{{ $printer->connectionLabel() }}</td>
                                        <td>{{ $printer->attached_devices_count ?? 0 }} {{ __('جهاز') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.device-setup.printers.update', $printer) }}">@csrf @method('PUT')
                                                <input type="hidden" name="is_active" value="{{ $printer->is_active ? '0' : '1' }}">
                                                <button class="btn btn-sm {{ $printer->is_active ? 'btn-success' : 'btn-secondary' }}">{{ $printer->is_active ? __('مفعلة') : __('معطلة') }}</button>
                                            </form>
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('admin.device-setup.printers.test', $printer) }}" target="_blank" class="btn btn-sm btn-outline-primary">🖨 {{ __('اختبار') }}</a>
                                            <form method="POST" action="{{ route('admin.device-setup.printers.destroy', $printer) }}" class="d-inline" onsubmit="return confirm('{{ __('حذف الطابعة؟') }}')">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">🗑</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('لا طابعات — أضف الأولى من النموذج') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text">
                        {{ __('ملاحظة: ماسح الباركود يعمل مباشرة (keyboard-wedge) بلا تعريف — يُكتشف تلقائيًا في شاشة البيع. الطابعات الشبكية تُكتشف بزر «مسح الشبكة» عند التشغيل الذاتي داخل شبكة الصيدلية.') }}
                    </div>
                </div></div>
            </div>
        </div>
    </div>

    {{-- ================= PREFERENCES ================= --}}
    <div id="tab-prefs" class="tab-pane fade">
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('admin.device-setup.preferences') }}">
                @csrf
                <h5>{{ __('الماسح') }}</h5>
                <div class="row g-3 mb-4">
                    @foreach([['scanner.auto_add', 'إضافة الصنف تلقائيًا عند المسح'], ['scanner.auto_focus', 'تركيز حقل البحث تلقائيًا'], ['scanner.beep', 'صوت تأكيد عند الإضافة']] as [$key, $label])
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="{{ str_replace('.', '_', $key) }}" name="{{ $key }}" value="1" @checked($settings[$group = explode('.', $key)[0]][$sub = explode('.', $key)[1]] ?? false) @if(!in_array($key, ['scanner.auto_add','scanner.auto_focus','scanner.beep'])) hidden @endif>
                                <label class="form-check-label" for="{{ str_replace('.', '_', $key) }}">{{ __($label) }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <h5>{{ __('قواعد الطباعة') }}</h5>
                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="print_barcode_purchase" name="print.barcode_on_purchase_complete" value="1" @checked($settings['print']['barcode_on_purchase_complete'])>
                            <label class="form-check-label" for="print_barcode_purchase">{{ __('عند إغلاق فاتورة شراء: اسأل «طباعة باركود؟»') }}</label>
                        </div>
                        <div class="form-text">{{ __('عند قبول GRN تُفتح شيت ملصقات جاهزة للطباعة بعدد المستلم (بحد أقصى).') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="print_barcode_sale" name="print.barcode_on_sale" value="1" @checked($settings['print']['barcode_on_sale'])>
                            <label class="form-check-label" for="print_barcode_sale">{{ __('عند البيع: طباعة باركود (افتراضيًا لا)') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('الإيصال عند البيع') }}</label>
                        <select name="print.receipt_on_sale" class="form-select">
                            @foreach(['auto' => 'تلقائي', 'ask' => 'اسأل', 'never' => 'أبدًا'] as $value => $label)
                                <option value="{{ $value }}" @selected($settings['print']['receipt_on_sale'] === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ترميز الباركود الافتراضي') }}</label>
                        <select name="print.default_symbology" class="form-select">
                            @foreach(['EAN13', 'CODE128', 'UPC', 'QR'] as $sym)
                                <option value="{{ $sym }}" @selected($settings['print']['default_symbology'] === $sym)>{{ $sym }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('حجم الملصق') }}</label>
                        <select name="print.default_size" class="form-select">
                            @foreach(['small' => 'صغير', 'standard' => 'قياسي', 'large' => 'كبير'] as $value => $label)
                                <option value="{{ $value }}" @selected($settings['print']['default_size'] === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('أقصى ملصقات/صنف') }}</label>
                        <input type="number" name="print.max_labels_per_item" min="1" max="50" value="{{ $settings['print']['max_labels_per_item'] }}" class="form-control">
                    </div>
                </div>

                <h5 class="mt-4">{{ __('الأجهزة') }}</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="dev_auto" name="devices.auto_register" value="1" @checked($settings['devices']['auto_register'])>
                            <label class="form-check-label" for="dev_auto">{{ __('تسجيل الأجهزة الجديدة تلقائيًا') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="dev_approve" name="devices.require_approval" value="1" @checked($settings['devices']['require_approval'])>
                            <label class="form-check-label" for="dev_approve">{{ __('اشتراط موافقة قبل استخدام الجهاز') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="dev_scan" name="devices.network_scan" value="1" @checked($settings['devices']['network_scan'])>
                            <label class="form-check-label" for="dev_scan">{{ __('تفعيل مسح الشبكة (تشغيل ذاتي فقط)') }}</label>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary">💾 {{ __('حفظ التفضيلات') }}</button>
            </form>
        </div></div>
    </div>
    </div>
</div>

@push('modal')
<script>
async function netScan() {
    const res = await fetch('{{ route('admin.device-setup.scan') }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).catch(e => ({ success: false, message: String(e) }));
    if (!res.success) { alert(res.message); return; }
    const hosts = res.data.hosts ?? [];
    if (!hosts.length) { alert('{{ __('لم يُعثر على أجهزة شبكية في النطاق.') }}'); return; }
    const lines = hosts.map(h => h.ip + ':' + h.port + ' — ' + h.guessed_kind + ' (' + h.service + ')');
    alert('{{ __('نتيجة المسح') }}:\n\n' + lines.join('\n'));
}
</script>
@endpush
@endsection
