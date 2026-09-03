@extends('layouts.master')

@section('title', 'تفضيلات الإشعارات')

@section('main_content')
    <div class="container-fluid" style="padding: 24px; max-width: 960px;">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('admin.dashboard.index') }}" class="link-blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 class="heading-bold">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" class="icon-align-lg">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                تفضيلات الإشعارات وأجهزة الدفع
            </h4>
        </div>

        {{-- Notification Type Preferences --}}
        <div class="card mb-4" class="card-clean">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f2; background: linear-gradient(135deg, #e8f0fe 0%, #f0f4ff 100%);">
                <h5 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" class="icon-align-lg"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 6v6l4 2"/></svg>
                    أنواع الإشعارات
                </h5>
                <p style="margin: 4px 0 0; font-size: 13px; color: #6e6e73;">اختر أنواع الإشعارات التي تريد تلقيها</p>
            </div>
            <div class="p-16-20">
                <form id="prefForm">
                    @foreach ($notificationTypes as $type => $info)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 0; {{ !$loop->last ? 'border-bottom: 1px solid #f0f0f2;' : '' }}">
                            <div class="flex-1-min">
                                <div style="font-weight: 600; font-size: 14px; color: #1d1d1f;">{{ $info['label'] }}</div>
                                <div style="font-size: 12px; color: #86868b; margin-top: 2px;">{{ $info['description'] }}</div>
                            </div>
                            <label style="position: relative; display: inline-block; width: 52px; height: 28px; flex-shrink: 0; margin-left: 16px;">
                                <input type="checkbox" name="types[]" value="{{ $type }}"
                                    {{ ($preferences[$type] ?? true) ? 'checked' : '' }}
                                    onchange="toggleNotificationType('{{ $type }}', this)"
                                    style="opacity: 0; width: 0; height: 0;">
                                <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: {{ ($preferences[$type] ?? true) ? '#34c759' : '#d2d2d7' }}; border-radius: 28px; transition: 0.3s;" class="toggle-slider" data-type="{{ $type }}">
                                    <span style="position: absolute; content: ''; height: 22px; width: 22px; left: {{ ($preferences[$type] ?? true) ? '26px' : '3px' }}; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.15);" class="toggle-dot" data-type="{{ $type }}"></span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </form>
            </div>
        </div>

        {{-- Registered Devices --}}
        <div class="card" class="card-clean">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f2; background: linear-gradient(135deg, #fff3e0 0%, #fff8e1 100%);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h5 class="section-title">
                            📱 الأجهزة المسجلة
                            <span style="background: #ff9500; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-right: 6px;">{{ $devices->count() }}</span>
                        </h5>
                        <p style="margin: 4px 0 0; font-size: 13px; color: #6e6e73;">جميع الأجهزة المسجلة للإشعارات في هذا الحساب</p>
                    </div>
                </div>
            </div>

            @if ($devices->isEmpty())
                <div style="padding: 40px 20px; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 12px;">📭</div>
                    <p style="color: #86868b; font-size: 14px; margin: 0;">لا توجد أجهزة مسجلة للإشعارات بعد</p>
                    <p style="color: #86868b; font-size: 13px; margin: 4px 0 0;">سيظهر هنا أي جهاز يسجل للإشعارات من لوحة التحكم</p>
                </div>
            @else
                <div class="p-8-0">
                    @foreach ($devices as $device)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; {{ !$loop->last ? 'border-bottom: 1px solid #f0f0f2;' : '' }}" data-device-id="{{ $device->id }}">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                {{-- Platform icon --}}
                                <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; {{ $device->is_active ? 'background: #d4edda;' : 'background: #f8d7da;' }}">
                                    @if ($device->platform === 'ios')
                                        🍎
                                    @elseif ($device->platform === 'android')
                                        🤖
                                    @else
                                        🌐
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px; color: #1d1d1f;">
                                        {{ $device->user->name ?? 'مستخدم غير معروف' }}
                                        @if ($device->is_active)
                                            <span style="background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; margin-right: 6px;">نشط</span>
                                        @else
                                            <span style="background: #f8d7da; color: #721c24; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; margin-right: 6px;">معطّل</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 12px; color: #86868b; margin-top: 2px;">
                                        {{ strtoupper($device->platform) }} · {{ $device->last_used_at ? $device->last_used_at->diffForHumans() : 'لم يُستخدم بعد' }}
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                @if ($device->is_active)
                                    <button onclick="deactivateDevice({{ $device->id }}, this)" style="background: #fff3e0; color: #e65100; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.95)'" onmouseup="this.style.transform='scale(1)'">
                                        تعطيل
                                    </button>
                                @endif
                                <button onclick="removeDevice({{ $device->id }}, this)" style="background: #fff0f0; color: #ff3b30; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.95)'" onmouseup="this.style.transform='scale(1)'">
                                    حذف
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleNotificationType(type, checkbox) {
            var slider = document.querySelector('.toggle-slider[data-type="' + type + '"]');
            var dot = document.querySelector('.toggle-dot[data-type="' + type + '"]');
            if (checkbox.checked) {
                slider.style.background = '#34c759';
                dot.style.left = '26px';
            } else {
                slider.style.background = '#d2d2d7';
                dot.style.left = '3px';
            }

            // Collect all enabled types
            var enabledTypes = [];
            document.querySelectorAll('input[name="types[]"]:checked').forEach(function(cb) {
                enabledTypes.push(cb.value);
            });

            fetch('{{ route("admin.push-notifications.update-preferences") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ types: enabledTypes }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            })
            .catch(function() {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            });
        }

        function deactivateDevice(deviceId, btn) {
            if (!confirm('هل تريد تعطيل هذا الجهاز؟ لن يتلقى إشعارات بعد الآن.')) return;

            btn.disabled = true;
            btn.textContent = 'جاري التعطيل...';

            fetch('{{ url("admin/push-notifications/devices") }}/' + deviceId + '/deactivate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var row = btn.closest('[data-device-id]');
                    var badge = row.querySelector('span');
                    if (badge) {
                        badge.style.background = '#f8d7da';
                        badge.style.color = '#721c24';
                        badge.textContent = 'معطّل';
                    }
                    btn.remove();
                }
            });
        }

        function removeDevice(deviceId, btn) {
            if (!confirm('هل تريد حذف هذا الجهاز نهائياً؟')) return;

            btn.disabled = true;
            btn.textContent = 'جاري الحذف...';

            fetch('{{ url("admin/push-notifications/devices") }}/' + deviceId + '/remove', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var row = btn.closest('[data-device-id]');
                    row.style.transition = 'opacity 0.3s, height 0.3s';
                    row.style.opacity = '0';
                    setTimeout(function() { row.remove(); }, 300);
                }
            });
        }
    </script>
    @endpush
@endsection
