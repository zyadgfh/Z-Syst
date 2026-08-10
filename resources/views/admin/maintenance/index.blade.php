@extends('layouts.master')

@section('title')
    {{ __('Maintenance Mode') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="row">
            <div class="col-12">
                <div class="card new-card border-0 maintenance-page">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4>{{ __('Maintenance Mode Management') }}</h4>
                                <p class="text-muted mb-0">{{ __('Control system-wide maintenance mode for all tenants') }}</p>
                            </div>
                            <div class="header-actions">
                                <button id="theme-toggle-btn" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-moon"></i>
                                </button>
                                <button id="refresh-status-btn" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-sync-alt"></i> {{ __('Refresh') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Loading State -->
                        <div id="loading-state" class="loading-state">
                            <div class="loading-spinner">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">{{ __('Loading...') }}</span>
                                </div>
                            </div>
                            <p class="text-muted">{{ __('Loading maintenance status...') }}</p>
                        </div>

                        <!-- Main Content -->
                        <div id="main-content" class="main-content" style="display: none;">
                            <!-- Maintenance Status Card -->
                            <div class="maintenance-status-wrapper">
                                <div class="maintenance-status-card" id="maintenance-status-card">
                                    <div class="status-header">
                                        <div class="status-indicator-wrapper">
                                            <div class="status-indicator" id="maintenance-status-indicator">
                                                <div class="status-dot"></div>
                                            </div>
                                        </div>
                                        <div class="status-info">
                                            <h5 class="status-title" id="maintenance-status-text">{{ __('Checking status...') }}</h5>
                                            <p class="status-subtitle" id="maintenance-subtitle">{{ __('Loading maintenance status') }}</p>
                                        </div>
                                        <div class="status-actions">
                                            <button id="maintenance-toggle-btn" class="btn btn-lg status-btn" disabled>
                                                <span class="btn-text">{{ __('Loading...') }}</span>
                                                <span class="btn-icon">
                                                    <i class="fas fa-power-off"></i>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Maintenance Details -->
                            <div id="maintenance-details" class="maintenance-details" style="display: none;">
                                <div class="details-header">
                                    <h6>{{ __('Maintenance Details') }}</h6>
                                    <span class="details-badge" id="maintenance-status-badge"></span>
                                </div>
                                <div class="details-grid">
                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-heading"></i>
                                        </div>
                                        <div class="detail-content">
                                            <label>{{ __('Title') }}</label>
                                            <p id="maintenance-title">-</p>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="detail-content">
                                            <label>{{ __('Estimated Completion') }}</label>
                                            <p id="maintenance-estimated">-</p>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-play-circle"></i>
                                        </div>
                                        <div class="detail-content">
                                            <label>{{ __('Started At') }}</label>
                                            <p id="maintenance-started">-</p>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-stop-circle"></i>
                                        </div>
                                        <div class="detail-content">
                                            <label>{{ __('Ended At') }}</label>
                                            <p id="maintenance-ended">-</p>
                                        </div>
                                    </div>
                                    <div class="detail-item full-width">
                                        <div class="detail-icon">
                                            <i class="fas fa-comment-alt"></i>
                                        </div>
                                        <div class="detail-content">
                                            <label>{{ __('Message') }}</label>
                                            <p id="maintenance-message-text">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="quick-actions">
                                <button id="show-activate-form-btn" class="btn btn-action btn-action-primary">
                                    <i class="fas fa-power-off"></i>
                                    <span>{{ __('Activate Maintenance') }}</span>
                                </button>
                                <button id="show-schedule-form-btn" class="btn btn-action btn-action-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>{{ __('Schedule Maintenance') }}</span>
                                </button>
                                <button id="view-history-btn" class="btn btn-action btn-action-secondary">
                                    <i class="fas fa-history"></i>
                                    <span>{{ __('View History') }}</span>
                                </button>
                            </div>

                            <!-- Maintenance Form -->
                            <div id="maintenance-form" class="maintenance-form animated-form" style="display: none;">
                                <div class="form-header">
                                    <h5>{{ __('Activate Maintenance Mode') }}</h5>
                                    <button type="button" class="btn-close" id="close-activate-form">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <form id="activate-maintenance-form">
                                    @csrf
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="title">{{ __('Title') }}</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="{{ __('System Maintenance') }}" required>
                                            <small class="form-text">{{ __('Display title for maintenance page') }}</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="estimated_completion">{{ __('Estimated Completion') }}</label>
                                            <input type="text" class="form-control" id="estimated_completion" 
                                                   name="estimated_completion" value="2 hours" required>
                                            <small class="form-text">{{ __('Estimated time for maintenance completion') }}</small>
                                        </div>
                                        <div class="form-group full-width">
                                            <label for="message">{{ __('Message') }}</label>
                                            <textarea class="form-control" id="message" name="message" rows="3" required>{{ __('نحن نقوم بتحسين نظامنا لتقديم خدمة أفضل') }}</textarea>
                                            <small class="form-text">{{ __('Message displayed to users during maintenance') }}</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="allowed_ips">{{ __('Allowed IPs (comma separated)') }}</label>
                                            <input type="text" class="form-control" id="allowed_ips" name="allowed_ips" 
                                                   placeholder="192.168.1.1, 192.168.1.2">
                                            <small class="form-text">{{ __('Leave empty to allow no specific IPs') }}</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="allowed_users">{{ __('Allowed User IDs (comma separated)') }}</label>
                                            <input type="text" class="form-control" id="allowed_users" name="allowed_users" 
                                                   placeholder="1, 2, 3">
                                            <small class="form-text">{{ __('Leave empty to allow no specific users') }}</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="ended_at">{{ __('End Time (Optional)') }}</label>
                                            <input type="datetime-local" class="form-control" id="ended_at" name="ended_at">
                                            <small class="form-text">{{ __('Auto-deactivate maintenance at this time') }}</small>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i>
                                            {{ __('Activate Maintenance Mode') }}
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="cancel-activate-btn">
                                            <i class="fas fa-times"></i>
                                            {{ __('Cancel') }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Schedule Maintenance Form -->
                            <div id="schedule-maintenance-form" class="maintenance-form animated-form" style="display: none;">
                                <div class="form-header">
                                    <h5>{{ __('Schedule Maintenance') }}</h5>
                                    <button type="button" class="btn-close" id="close-schedule-form">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <form id="schedule-maintenance-form">
                                    @csrf
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="schedule_title">{{ __('Title') }}</label>
                                            <input type="text" class="form-control" id="schedule_title" name="title" 
                                                   value="{{ __('Scheduled Maintenance') }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="schedule_estimated">{{ __('Estimated Completion') }}</label>
                                            <input type="text" class="form-control" id="schedule_estimated" 
                                                   name="estimated_completion" value="1 hour" required>
                                        </div>
                                        <div class="form-group full-width">
                                            <label for="schedule_message">{{ __('Message') }}</label>
                                            <textarea class="form-control" id="schedule_message" name="message" rows="3" required>{{ __('صيانة مجدولة لتحسين النظام') }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="scheduled_for">{{ __('Scheduled For') }}</label>
                                            <input type="datetime-local" class="form-control" id="scheduled_for" name="scheduled_for" required>
                                            <small class="form-text">{{ __('When maintenance should automatically start') }}</small>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-info">
                                            <i class="fas fa-calendar-check"></i>
                                            {{ __('Schedule Maintenance') }}
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="cancel-schedule-btn">
                                            <i class="fas fa-times"></i>
                                            {{ __('Cancel') }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Maintenance History -->
                            <div id="maintenance-history" class="maintenance-history animated-form" style="display: none;">
                                <div class="history-header">
                                    <h5>{{ __('Maintenance History') }}</h5>
                                    <button type="button" class="btn-close" id="close-history">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('ID') }}</th>
                                                <th>{{ __('Title') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Started At') }}</th>
                                                <th>{{ __('Ended At') }}</th>
                                                <th>{{ __('Duration') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="maintenance-history-body">
                                            <!-- History will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Error State -->
                        <div id="error-state" class="error-state" style="display: none;">
                            <div class="error-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h5>{{ __('Error Loading Maintenance Status') }}</h5>
                            <p class="text-muted" id="error-message">{{ __('An error occurred while loading the maintenance status. Please try again.') }}</p>
                            <button id="retry-btn" class="btn btn-primary">
                                <i class="fas fa-redo"></i>
                                {{ __('Retry') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #e9ecef;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --text-muted: #adb5bd;
            --border-color: #dee2e6;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --card-bg: rgba(255, 255, 255, 0.95);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-primary: #1a1a2e;
                --bg-secondary: #16213e;
                --bg-tertiary: #0f3460;
                --text-primary: #e94560;
                --text-secondary: #a8a8a8;
                --text-muted: #7f8c8d;
                --border-color: #2c3e50;
                --shadow-color: rgba(0, 0, 0, 0.3);
                --card-bg: rgba(26, 26, 46, 0.95);
            }
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a2e;
            --bg-secondary: #16213e;
            --bg-tertiary: #0f3460;
            --text-primary: #e94560;
            --text-secondary: #a8a8a8;
            --text-muted: #7f8c8d;
            --border-color: #2c3e50;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --card-bg: rgba(26, 26, 46, 0.95);
        }

        .maintenance-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        @media (prefers-color-scheme: dark) {
            .maintenance-page {
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            }
        }

        [data-theme="dark"] .maintenance-page {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .maintenance-page .card-body {
            background: var(--card-bg);
            border-radius: 0 0 15px 15px;
            color: var(--text-primary);
        }

        .loading-state, .error-state {
            text-align: center;
            padding: 60px 20px;
        }

        .loading-spinner {
            margin-bottom: 20px;
        }

        .loading-spinner .spinner-border {
            width: 50px;
            height: 50px;
        }

        .error-icon {
            font-size: 60px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .loading-state p, .error-state p {
            color: var(--text-secondary);
        }

        .error-state h5 {
            color: var(--text-primary);
        }

        .maintenance-status-wrapper {
            margin-bottom: 30px;
        }

        .maintenance-status-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .maintenance-status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .status-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .status-indicator-wrapper {
            display: flex;
            align-items: center;
        }

        .status-indicator {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .status-indicator.inactive {
            background: rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.5);
        }

        .status-indicator.active {
            background: rgba(40, 167, 69, 0.2);
            border-color: rgba(40, 167, 69, 0.5);
        }

        .status-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }

        .status-info {
            flex: 1;
            min-width: 200px;
        }

        .status-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .status-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        .status-actions {
            display: flex;
            align-items: center;
        }

        .status-btn {
            min-width: 180px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .status-btn:hover {
            transform: scale(1.05);
        }

        .status-btn.btn-success {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }

        .status-btn.btn-danger {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .maintenance-details {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .details-header h6 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .details-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .details-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .details-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: var(--bg-primary);
            border-radius: 8px;
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .detail-item.full-width {
            grid-column: 1 / -1;
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }

        .detail-content {
            flex: 1;
        }

        .detail-content label {
            display: block;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 5px;
            font-weight: 500;
        }

        .detail-content p {
            margin: 0;
            font-weight: 500;
            color: var(--text-primary);
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            min-width: 200px;
            padding: 15px 25px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-action-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-action-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .btn-action-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .maintenance-form {
            background: var(--bg-primary);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px var(--shadow-color);
            margin-bottom: 30px;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-header h5 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .btn-close:hover {
            color: #dc3545;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .form-group .form-control {
            border-radius: 8px;
            border: 2px solid var(--border-color);
            padding: 12px 15px;
            transition: all 0.3s ease;
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .form-group .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group .form-text {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .form-actions .btn {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .maintenance-history {
            background: var(--bg-primary);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px var(--shadow-color);
            animation: slideUp 0.3s ease;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .history-header h5 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .status-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .status-actions {
                width: 100%;
            }

            .status-btn {
                width: 100%;
            }

            .quick-actions {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }
        }

        /* RTL Support */
        [dir="rtl"] .detail-item {
            flex-direction: row-reverse;
        }

        [dir="rtl"] .form-actions {
            flex-direction: row-reverse;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load maintenance status on page load
            loadMaintenanceStatus();

            // Event listeners
            document.getElementById('refresh-status-btn').addEventListener('click', loadMaintenanceStatus);
            document.getElementById('theme-toggle-btn').addEventListener('click', toggleTheme);
            document.getElementById('maintenance-toggle-btn').addEventListener('click', handleToggleMaintenance);
            document.getElementById('show-activate-form-btn').addEventListener('click', showActivateForm);
            document.getElementById('show-schedule-form-btn').addEventListener('click', showScheduleForm);
            document.getElementById('view-history-btn').addEventListener('click', showHistory);
            document.getElementById('close-activate-form').addEventListener('click', hideAllForms);
            document.getElementById('close-schedule-form').addEventListener('click', hideAllForms);
            document.getElementById('close-history').addEventListener('click', hideAllForms);
            document.getElementById('cancel-activate-btn').addEventListener('click', hideAllForms);
            document.getElementById('cancel-schedule-btn').addEventListener('click', hideAllForms);
            document.getElementById('retry-btn').addEventListener('click', loadMaintenanceStatus);

            // Form submissions
            document.getElementById('activate-maintenance-form').addEventListener('submit', handleActivateMaintenance);
            document.getElementById('schedule-maintenance-form').addEventListener('submit', handleScheduleMaintenance);
        });

        function showLoading() {
            document.getElementById('loading-state').style.display = 'block';
            document.getElementById('main-content').style.display = 'none';
            document.getElementById('error-state').style.display = 'none';
        }

        function showMainContent() {
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('main-content').style.display = 'block';
            document.getElementById('error-state').style.display = 'none';
        }

        function showError(message) {
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('main-content').style.display = 'none';
            document.getElementById('error-state').style.display = 'block';
            document.getElementById('error-message').textContent = message;
        }

        function loadMaintenanceStatus() {
            showLoading();

            fetch('/admin/maintenance/status', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateMaintenanceStatus(data.maintenance);
                    showMainContent();
                } else {
                    showError(data.message || '{{ __("Failed to load maintenance status") }}');
                }
            })
            .catch(error => {
                console.error('Error loading maintenance status:', error);
                showError('{{ __("An error occurred while loading maintenance status") }}');
            });
        }

        function updateMaintenanceStatus(maintenance) {
            const statusIndicator = document.getElementById('maintenance-status-indicator');
            const statusText = document.getElementById('maintenance-status-text');
            const statusSubtitle = document.getElementById('maintenance-subtitle');
            const toggleBtn = document.getElementById('maintenance-toggle-btn');
            const detailsDiv = document.getElementById('maintenance-details');
            const statusBadge = document.getElementById('maintenance-status-badge');

            if (maintenance && maintenance.is_enabled) {
                statusIndicator.classList.remove('inactive');
                statusIndicator.classList.add('active');
                statusText.textContent = '{{ __("Maintenance Mode Active") }}';
                statusSubtitle.textContent = maintenance.message || '{{ __("System is under maintenance") }}';
                toggleBtn.innerHTML = `
                    <span class="btn-text">{{ __("Deactivate Maintenance") }}</span>
                    <span class="btn-icon"><i class="fas fa-power-off"></i></span>
                `;
                toggleBtn.dataset.active = 'true';
                toggleBtn.classList.remove('btn-success');
                toggleBtn.classList.add('btn-danger');
                
                // Show details
                detailsDiv.style.display = 'block';
                statusBadge.textContent = '{{ __("Active") }}';
                statusBadge.classList.add('active');
                statusBadge.classList.remove('inactive');
                
                document.getElementById('maintenance-title').textContent = maintenance.title || '-';
                document.getElementById('maintenance-estimated').textContent = maintenance.estimated_completion || '-';
                document.getElementById('maintenance-started').textContent = maintenance.started_at || '-';
                document.getElementById('maintenance-ended').textContent = maintenance.ended_at || '-';
                document.getElementById('maintenance-message-text').textContent = maintenance.message || '-';
            } else {
                statusIndicator.classList.remove('active');
                statusIndicator.classList.add('inactive');
                statusText.textContent = '{{ __("Maintenance Mode Inactive") }}';
                statusSubtitle.textContent = '{{ __("System is running normally") }}';
                toggleBtn.innerHTML = `
                    <span class="btn-text">{{ __("Activate Maintenance") }}</span>
                    <span class="btn-icon"><i class="fas fa-power-off"></i></span>
                `;
                toggleBtn.dataset.active = 'false';
                toggleBtn.classList.remove('btn-danger');
                toggleBtn.classList.add('btn-success');
                
                // Hide details
                detailsDiv.style.display = 'none';
            }

            toggleBtn.disabled = false;
        }

        function handleToggleMaintenance() {
            const isActive = document.getElementById('maintenance-toggle-btn').dataset.active === 'true';
            if (isActive) {
                deactivateMaintenance();
            } else {
                showActivateForm();
            }
        }

        function showActivateForm() {
            hideAllForms();
            document.getElementById('maintenance-form').style.display = 'block';
        }

        function showScheduleForm() {
            hideAllForms();
            document.getElementById('schedule-maintenance-form').style.display = 'block';
        }

        function showHistory() {
            hideAllForms();
            loadMaintenanceHistory();
            document.getElementById('maintenance-history').style.display = 'block';
        }

        function hideAllForms() {
            document.getElementById('maintenance-form').style.display = 'none';
            document.getElementById('schedule-maintenance-form').style.display = 'none';
            document.getElementById('maintenance-history').style.display = 'none';
        }

        function handleActivateMaintenance(e) {
            e.preventDefault();
            
            const form = document.getElementById('activate-maintenance-form');
            const formData = new FormData(form);
            
            // Convert FormData to JSON
            const data = {};
            formData.forEach((value, key) => {
                if (key === 'allowed_ips' || key === 'allowed_users') {
                    data[key] = value.split(',').map(item => item.trim()).filter(item => item);
                } else {
                    data[key] = value;
                }
            });

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Activating...") }}';
            
            fetch('/admin/maintenance/activate', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert('{{ __("Maintenance mode activated successfully") }}');
                    hideAllForms();
                    loadMaintenanceStatus();
                } else {
                    showErrorAlert(data.message || '{{ __("Failed to activate maintenance mode") }}');
                }
            })
            .catch(error => {
                console.error('Error activating maintenance:', error);
                showErrorAlert('{{ __("An error occurred") }}');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function deactivateMaintenance() {
            if (!confirm('{{ __("Are you sure you want to deactivate maintenance mode?") }}')) {
                return;
            }

            const toggleBtn = document.getElementById('maintenance-toggle-btn');
            const originalText = toggleBtn.innerHTML;
            toggleBtn.disabled = true;
            toggleBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Deactivating...") }}';

            fetch('/admin/maintenance/deactivate', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert('{{ __("Maintenance mode deactivated successfully") }}');
                    loadMaintenanceStatus();
                } else {
                    showErrorAlert(data.message || '{{ __("Failed to deactivate maintenance mode") }}');
                }
            })
            .catch(error => {
                console.error('Error deactivating maintenance:', error);
                showErrorAlert('{{ __("An error occurred") }}');
            })
            .finally(() => {
                toggleBtn.disabled = false;
                toggleBtn.innerHTML = originalText;
            });
        }

        function handleScheduleMaintenance(e) {
            e.preventDefault();
            
            const form = document.getElementById('schedule-maintenance-form');
            const formData = new FormData(form);
            
            // Convert FormData to JSON
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Scheduling...") }}';
            
            fetch('/admin/maintenance/schedule', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert('{{ __("Maintenance scheduled successfully") }}');
                    hideAllForms();
                    loadMaintenanceStatus();
                } else {
                    showErrorAlert(data.message || '{{ __("Failed to schedule maintenance") }}');
                }
            })
            .catch(error => {
                console.error('Error scheduling maintenance:', error);
                showErrorAlert('{{ __("An error occurred") }}');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function loadMaintenanceHistory() {
            fetch('/admin/maintenance/history', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMaintenanceHistory(data.history);
                }
            })
            .catch(error => {
                console.error('Error loading maintenance history:', error);
            });
        }

        function displayMaintenanceHistory(history) {
            const tbody = document.getElementById('maintenance-history-body');
            tbody.innerHTML = '';

            if (history && history.length > 0) {
                history.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.id}</td>
                        <td>${item.title || '-'}</td>
                        <td>
                            <span class="badge ${item.is_enabled ? 'badge-success' : 'badge-secondary'}">
                                ${item.is_enabled ? '{{ __("Active") }}' : '{{ __("Inactive") }}'}
                            </span>
                        </td>
                        <td>${item.started_at || '-'}</td>
                        <td>${item.ended_at || '-'}</td>
                        <td>${item.duration || '-'}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">{{ __("No maintenance history found") }}</td></tr>';
            }
        }

        function showSuccessAlert(message) {
            // Simple alert for now, can be replaced with a toast notification
            alert(message);
        }

        function showErrorAlert(message) {
            // Simple alert for now, can be replaced with a toast notification
            alert(message);
        }

        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            const icon = document.querySelector('#theme-toggle-btn i');
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // Initialize theme from localStorage or system preference
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            } else if (prefersDark) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
            
            // Update icon based on current theme
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const icon = document.querySelector('#theme-toggle-btn i');
            if (icon) {
                icon.className = currentTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        // Initialize theme on page load
        initializeTheme();
    </script>
@endsection