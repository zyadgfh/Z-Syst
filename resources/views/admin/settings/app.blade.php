@extends('layouts.admin')

@section('title')
    {{ __('settings.Application Settings') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header border-0 p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0">{{ __('settings.Application Settings') }}</h4>
                    <div class="d-flex align-items-center gap-2">
                        {{-- Search --}}
                        <div class="position-relative" style="min-width: 280px;">
                            <input type="text" id="settings-search" class="form-control"
                                   placeholder="{{ __('settings.Search settings...') }}" autocomplete="off">
                            <i class="fas fa-search position-absolute" style="right: 12px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                        </div>
                    </div>
                </div>

                {{-- Search Results Overlay --}}
                <div id="search-results" class="d-none p-16 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0">{{ __('settings.Search Results') }} <span id="search-count" class="badge bg-primary ms-2">0</span></h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSearch()">
                            <i class="fas fa-times me-1"></i>{{ __('settings.Clear Search') }}
                        </button>
                    </div>
                    <div id="search-results-list"></div>
                </div>

                <div class="row g-0">
                    {{-- Settings Sidebar --}}
                    <div class="col-lg-3 col-md-4 border-end" style="min-height: 70vh;">
                        <div class="p-3">
                            @can('settings-edit')
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="seedDefaults()">
                                    <i class="fas fa-sync-alt me-1"></i>{{ __('settings.Seed/Refresh Defaults') }}
                                </button>
                            </div>
                            @endcan

                            <nav class="settings-nav">
                                <ul class="nav flex-column" id="settings-categories">
                                    @foreach($modules as $moduleKey => $moduleInfo)
                                    <li class="nav-item">
                                        <a class="nav-link settings-category-link {{ $currentModule === $moduleKey ? 'active' : '' }}"
                                           href="#"
                                           data-module="{{ $moduleKey }}"
                                           onclick="switchModule('{{ $moduleKey }}', event)">
                                            <i class="{{ $moduleInfo['icon'] }} me-2"></i>
                                            {{ __($moduleInfo['label']) }}
                                        </a>
                                    </li>
                                    @endforeach

                                    {{-- Audit Log --}}
                                    <li class="nav-item mt-3 border-top pt-3">
                                        <a class="nav-link settings-category-link"
                                           href="#"
                                           data-module="audit"
                                           onclick="showAuditLog(event)">
                                            <i class="fas fa-history me-2"></i>
                                            {{ __('settings.Audit Log') }}
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                    {{-- Settings Content --}}
                    <div class="col-lg-9 col-md-8">
                        <div class="p-4" id="settings-content">
                            {{-- Module Header --}}
                            <div class="d-flex align-items-center justify-content-between mb-4" id="module-header">
                                <div>
                                    <h4 class="mb-1" id="module-title">
                                        {{ __($modules[$currentModule]['label'] ?? 'Settings') }}
                                    </h4>
                                    <p class="text-muted mb-0" id="module-description">
                                        {{ __($modules[$currentModule]['description'] ?? '') }}
                                    </p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetAllModule('{{ $currentModule }}')">
                                        <i class="fas fa-undo me-1"></i>{{ __('settings.Reset All') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="saveAllModule()">
                                        <i class="fas fa-save me-1"></i>{{ __('roles.Save Changes') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Settings List --}}
                            <div id="settings-list">
                                @if(isset($groupedSettings[$currentModule]))
                                    @foreach($groupedSettings[$currentModule] as $setting)
                                        @include('admin.settings._setting_row', ['setting' => $setting])
                                    @endforeach
                                @else
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-cog fa-3x mb-3 opacity-25"></i>
                                        <p>{{ __('settings.No settings found for this module.') }}</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Audit Log View --}}
                            <div id="audit-log-view" class="d-none">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <h4 class="mb-0">{{ __('settings.Settings Audit Log') }}</h4>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hideAuditLog()">
                                        <i class="fas fa-arrow-left me-1"></i>{{ __('settings.Back to Settings') }}
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="audit-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('common.Date') }}</th>
                                                <th>{{ __('common.User') }}</th>
                                                <th>{{ __('settings.Setting') }}</th>
                                                <th>{{ __('settings.Scope') }}</th>
                                                <th>{{ __('settings.Old Value') }}</th>
                                                <th>{{ __('products.New Value') }}</th>
                                                <th>{{ __('common.Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="audit-log-body">
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    {{ __('settings.Loading audit log...') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-category-link {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        margin-bottom: 2px;
        border-radius: 6px;
        color: #6c757d;
        font-size: 14px;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .settings-category-link:hover {
        background-color: #f8f9fa;
        color: #495057;
    }
    .settings-category-link.active {
        background-color: #0d6efd;
        color: #fff;
    }
    .settings-category-link i {
        width: 20px;
        text-align: center;
    }

    .setting-row {
        padding: 16px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: all 0.15s ease;
        background: #fff;
    }
    .setting-row:hover {
        border-color: #dee2e6;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .setting-row.has-override {
        border-left: 3px solid #0d6efd;
    }

    .setting-name {
        font-weight: 600;
        font-size: 14px;
        color: #212529;
        margin-bottom: 4px;
    }
    .setting-description {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 0;
    }
    .setting-key {
        font-family: monospace;
        font-size: 11px;
        color: #adb5bd;
        margin-top: 2px;
    }

    .source-badge {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 500;
    }
    .source-badge.source-default { background: #e9ecef; color: #6c757d; }
    .source-badge.source-system { background: #d1ecf1; color: #0c5460; }
    .source-badge.source-organization { background: #d4edda; color: #155724; }
    .source-badge.source-branch { background: #fff3cd; color: #856404; }
    .source-badge.source-role { background: #f8d7da; color: #721c24; }
    .source-badge.source-user { background: #cce5ff; color: #004085; }

    .setting-control {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    /* Toggle Switch */
    .toggle-switch {
        position: relative;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 24px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }
    .toggle-switch input:checked + .toggle-slider { background-color: #0d6efd; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }

    /* Settings nav scrollbar */
    .settings-nav {
        max-height: 60vh;
        overflow-y: auto;
    }
    .settings-nav::-webkit-scrollbar {
        width: 4px;
    }
    .settings-nav::-webkit-scrollbar-thumb {
        background: #dee2e6;
        border-radius: 2px;
    }

    .unsaved-indicator {
        display: none;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #ffc107;
    }
    .has-changes .unsaved-indicator { display: inline-block; }

    .save-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        min-width: 300px;
    }
</style>

<style>
    /* Settings responsive */
    @media (max-width: 768px) {
        .col-lg-3.col-md-4 {
            border-end: none !important;
            border-bottom: 1px solid #e9ecef;
        }
        .settings-nav {
            max-height: 200px;
        }
    }
</style>

@endsection

@push('js')
<script>
    // CSRF token for AJAX
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const baseUrl = '{{ url("admin") }}';

    // Track unsaved changes
    let unsavedChanges = {};
    let currentModule = '{{ $currentModule }}';
    let scopeType = '{{ request()->get("scope_type", "system") }}';
    let scopeId = '{{ request()->get("scope_id", "") }}';

    // Switch module
    function switchModule(module, event) {
        if (event) event.preventDefault();
        if (Object.keys(unsavedChanges).length > 0) {
            if (!confirm('{{ __('settings.You have unsaved changes. Discard them?') }}')) return;
        }
        unsavedChanges = {};
        currentModule = module;

        // Update active state
        document.querySelectorAll('.settings-category-link').forEach(el => el.classList.remove('active'));
        document.querySelector(`[data-module="${module}"]`).classList.add('active');

        // Show settings content
        document.getElementById('settings-content').querySelector('#module-header').classList.remove('d-none');
        document.getElementById('settings-list').classList.remove('d-none');
        document.getElementById('audit-log-view').classList.add('d-none');

        // Fetch module settings
        loadModuleSettings(module);
    }

    function loadModuleSettings(module) {
        const params = new URLSearchParams();
        params.set('scope_type', scopeType);
        if (scopeId) params.set('scope_id', scopeId);

        fetch(`${baseUrl}/app-settings/module/${module}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            const moduleMeta = {{ json_encode(\App\Services\Settings\SettingsRegistry::moduleMeta()) }};
            const meta = moduleMeta[module] || {};

            document.getElementById('module-title').textContent = meta.label || module;
            document.getElementById('module-description').textContent = meta.description || '';

            let html = '';
            if (data.settings && data.settings.length > 0) {
                data.settings.forEach(setting => {
                    html += renderSettingRow(setting);
                });
            } else {
                html = `<div class="text-center py-5 text-muted">
                    <i class="fas fa-cog fa-3x mb-3 opacity-25"></i>
                    <p>{{ __('settings.No settings found for this module.') }}</p>
                </div>`;
            }
            document.getElementById('settings-list').innerHTML = html;
        });
    }

    function renderSettingRow(setting) {
        const def = setting.definition;
        const effectiveValue = setting.effective_value;
        const source = setting.source;
        const isOverridden = setting.is_overridden;            const canEditSystem = {{ auth()->user()->can('settings-edit') ? 'true' : 'false' }};
            const canEditOrg = {{ auth()->user()->can('settings-edit') ? 'true' : 'false' }};

        let controlHtml = '';
        let sourceHtml = '';

        // Source badge
        const sourceLabels = {
            'default': '{{ __('common.Default') }}',
            'system': '{{ __('common.System') }}',
            'organization': '{{ __('settings.Organization') }}',
            'branch': '{{ __('common.Branch') }}',
            'role': '{{ __('roles.Role') }}',
            'user': '{{ __('common.User') }}'
        };
        sourceHtml = `<span class="source-badge source-${source}">${sourceLabels[source] || source}</span>`;

        // Override indicator
        let overrideBadge = '';
        if (isOverridden) {
            overrideBadge = `<span class="badge bg-primary bg-opacity-10 text-primary ms-1" class="text-10">{{ __('common.Override') }}</span>`;
        }

        // Control based on type
        if (def.type === 'boolean') {
            controlHtml = `
                <label class="toggle-switch">
                    <input type="checkbox" data-key="${def.key}" data-type="boolean"
                           ${effectiveValue ? 'checked' : ''}
                           onchange="trackChange(this)">
                    <span class="toggle-slider"></span>
                </label>`;
        } else if (def.type === 'select' && def.type_options && def.type_options.options) {
            let optionsHtml = '';
            def.type_options.options.forEach(opt => {
                optionsHtml += `<option value="${opt.value}" ${effectiveValue == opt.value ? 'selected' : ''}>${opt.label}</option>`;
            });
            controlHtml = `
                <select class="form-select form-select-sm" class="w-200" data-key="${def.key}" data-type="select" onchange="trackChange(this)">
                    ${optionsHtml}
                </select>`;
        } else if (def.type === 'integer') {
            controlHtml = `
                <input type="number" class="form-control form-control-sm" class="w-120"
                       data-key="${def.key}" data-type="integer"
                       value="${effectiveValue !== null ? effectiveValue : ''}"
                       onchange="trackChange(this)">`;
        } else if (def.type === 'decimal') {
            controlHtml = `
                <input type="number" class="form-control form-control-sm" class="w-120"
                       data-key="${def.key}" data-type="decimal"
                       value="${effectiveValue !== null ? effectiveValue : ''}"
                       step="0.01"
                       onchange="trackChange(this)">`;
        } else {
            controlHtml = `
                <input type="text" class="form-control form-control-sm" class="w-200"
                       data-key="${def.key}" data-type="string"
                       value="${effectiveValue !== null ? String(effectiveValue).replace(/"/g, '&quot;') : ''}"
                       onchange="trackChange(this)">`;
        }

        return `
        <div class="setting-row ${isOverridden ? 'has-override' : ''}" id="setting-${def.key.replace(/\./g, '-')}">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="flex-1" style="min-width: 200px;">
                    <div class="setting-name">
                        ${def.name}
                        ${overrideBadge}
                    </div>
                    <div class="setting-description">${def.description || ''}</div>
                    <div class="setting-key mt-1">${def.key}</div>
                    <div class="mt-1">${sourceHtml}</div>
                </div>
                <div class="setting-control">
                    ${controlHtml}
                </div>
            </div>
        </div>`;
    }

    // Track changes
    function trackChange(el) {
        const key = el.dataset.key;
        const type = el.dataset.type;
        let value;

        if (type === 'boolean') {
            value = el.checked;
        } else if (type === 'integer') {
            value = parseInt(el.value);
        } else if (type === 'decimal') {
            value = parseFloat(el.value);
        } else {
            value = el.value;
        }

        unsavedChanges[key] = value;
        updateSaveIndicator();
    }

    function updateSaveIndicator() {
        const count = Object.keys(unsavedChanges).length;
        if (count > 0) {
            document.title = `(${count}) {{ __('settings.Application Settings') }} - {{ get_option('general')['title'] ?? config('app.name') }}`;
        } else {
            document.title = `{{ __('settings.Application Settings') }} - {{ get_option('general')['title'] ?? config('app.name') }}`;
        }
    }

    // Save all module settings
    function saveAllModule() {
        if (Object.keys(unsavedChanges).length === 0) {
            showToast('{{ __('settings.No changes to save.') }}', 'info');
            return;
        }

        const payload = {
            settings: unsavedChanges,
            scope_type: scopeType,
            scope_id: scopeId ? parseInt(scopeId) : null
        };

        fetch(`${baseUrl}/app-settings/update-bulk`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            unsavedChanges = {};
            updateSaveIndicator();
            showToast('{{ __('settings.Settings saved successfully.') }}', 'success');
            loadModuleSettings(currentModule);
        })
        .catch(err => {
            showToast('{{ __('settings.Error saving settings.') }}', 'error');
        });
    }

    // Reset all module settings
    function resetAllModule(module) {
        if (!confirm('{{ __('settings.Reset all settings in this module to their default values?') }}')) return;

        // Reload module to reset unsaved changes
        unsavedChanges = {};
        updateSaveIndicator();
        loadModuleSettings(module);
        showToast('{{ __('settings.Settings reset to defaults.') }}', 'info');
    }

    // Seed defaults
    function seedDefaults() {
        fetch(`${baseUrl}/app-settings/seed-defaults`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            showToast('{{ __('settings.Settings definitions seeded successfully.') }}', 'success');
            loadModuleSettings(currentModule);
        })
        .catch(err => {
            showToast('{{ __('settings.Error seeding defaults.') }}', 'error');
        });
    }

    // Search
    let searchTimeout;
    document.getElementById('settings-search').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();

        if (query.length < 2) {
            clearSearch();
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`${baseUrl}/app-settings/search`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ query })
            })
            .then(r => r.json())
            .then(data => {
                showSearchResults(data.settings, query);
            });
        }, 300);
    });

    function showSearchResults(settings, query) {
        const container = document.getElementById('search-results');
        const list = document.getElementById('search-results-list');
        const count = document.getElementById('search-count');

        if (!settings || settings.length === 0) {
            list.innerHTML = `<div class="text-center py-4 text-muted">
                <i class="fas fa-search fa-2x mb-2 opacity-25"></i>
                <p>{{ __('settings.No settings found for') }} "${query}"</p>
            </div>`;
            count.textContent = '0';
            container.classList.remove('d-none');
            return;
        }

        count.textContent = settings.length;
        let html = '';
        settings.forEach(def => {
            html += `<div class="setting-row mb-2" class="cursor-pointer" onclick="jumpToSetting('${def.key}')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="setting-name">${def.name}</div>
                        <div class="setting-description">${def.description || ''}</div>
                        <div class="setting-key">${def.key} · ${def.module}</div>
                    </div>
                    <span class="badge bg-light text-dark">${def.module}</span>
                </div>
            </div>`;
        });
        list.innerHTML = html;
        container.classList.remove('d-none');
    }

    function clearSearch() {
        document.getElementById('settings-search').value = '';
        document.getElementById('search-results').classList.add('d-none');
    }

    function jumpToSetting(key) {
        // Navigate to the module that contains this setting
        const module = key.split('.')[0];
        switchModule(module);
        clearSearch();

        // After module loads, scroll to the setting
        setTimeout(() => {
            const el = document.getElementById(`setting-${key.replace(/\./g, '-')}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.style.boxShadow = '0 0 0 2px #0d6efd';
                setTimeout(() => { el.style.boxShadow = ''; }, 2000);
            }
        }, 500);
    }

    // Audit Log
    function showAuditLog(event) {
        if (event) event.preventDefault();
        document.getElementById('settings-content').querySelector('#module-header').classList.add('d-none');
        document.getElementById('settings-list').classList.add('d-none');
        document.getElementById('audit-log-view').classList.remove('d-none');

        document.querySelectorAll('.settings-category-link').forEach(el => el.classList.remove('active'));
        document.querySelector('[data-module="audit"]').classList.add('active');

        loadAuditLog();
    }

    function hideAuditLog() {
        document.getElementById('audit-log-view').classList.add('d-none');
        switchModule(currentModule);
    }

    function loadAuditLog() {
        fetch(`${baseUrl}/app-settings/audit-log?limit=100`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('audit-log-body');
            if (!data.logs || data.logs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">{{ __('settings.No audit log entries found.') }}</td></tr>`;
                return;
            }

            let html = '';
            data.logs.forEach(log => {
                const oldVal = log.old_value !== null ? (typeof log.old_value === 'object' ? JSON.stringify(log.old_value) : log.old_value) : '—';
                const newVal = log.new_value !== null ? (typeof log.new_value === 'object' ? JSON.stringify(log.new_value) : log.new_value) : '—';
                html += `<tr>
                    <td><small>${new Date(log.created_at).toLocaleString()}</small></td>
                    <td>${log.user ? log.user.name : '—'}</td>
                    <td><code>${log.key}</code><br><small class="text-muted">${log.name || ''}</small></td>
                    <td><span class="source-badge source-${log.scope_type}">${log.scope_type}</span></td>
                    <td><small>${oldVal}</small></td>
                    <td><small>${newVal}</small></td>
                    <td><span class="badge bg-${log.action === 'reset' ? 'warning' : 'success'}">${log.action}</span></td>
                </tr>`;
            });
            tbody.innerHTML = html;
        });
    }

    // Toast notifications
    function showToast(message, type = 'success') {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

    // Warn on page unload with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (Object.keys(unsavedChanges).length > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
</script>
@endpush
