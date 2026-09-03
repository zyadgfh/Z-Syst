@extends('layouts.admin')

@section('title')
    {{ __('dashboard.Dashboard') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <!-- Modern Dashboard Header -->
        <div class="dashboard-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="dashboard-title">{{ __('dashboard.Welcome Back') }}!</h2>
                    <p class="dashboard-subtitle">{{ __('dashboard.Here\'s what\'s happening with your business today') }}</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-modern btn-primary-soft">
                        <i class="fas fa-download me-2"></i>{{ __('dashboard.Export Report') }}
                    </button>
                    <button class="btn btn-modern btn-outline-primary">
                        <i class="fas fa-sync-alt me-2"></i>{{ __('common.Refresh') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Stats Grid -->
        <div class="stats-grid-container">
            <div class="stats-grid">
                <!-- Total Shops -->
                <div class="stat-card stat-card-primary animated-card">
                    <div class="stat-card-bg"></div>
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon stat-icon-primary">
                                <i class="fas fa-store"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value" id="total_businesses">0</h3>
                            <p class="stat-label">{{ __('dashboard.Total Shops') }}</p>
                            <div class="stat-trend stat-trend-up">
                                <i class="fas fa-arrow-up"></i>
                                <span>12%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expired Businesses -->
                <div class="stat-card stat-card-warning animated-card">
                    <div class="stat-card-bg"></div>
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon stat-icon-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value" id="expired_businesses">0</h3>
                            <p class="stat-label">{{ __('dashboard.Expired Businesses') }}</p>
                            <div class="stat-trend stat-trend-down">
                                <i class="fas fa-arrow-down"></i>
                                <span>5%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan Subscribes -->
                <div class="stat-card stat-card-success animated-card">
                    <div class="stat-card-bg"></div>
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon stat-icon-success">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value" id="plan_subscribes">0</h3>
                            <p class="stat-label">{{ __('dashboard.Plan Subscribes') }}</p>
                            <div class="stat-trend stat-trend-up">
                                <i class="fas fa-arrow-up"></i>
                                <span>8%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Categories -->
                <div class="stat-card stat-card-info animated-card">
                    <div class="stat-card-bg"></div>
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon stat-icon-info">
                                <i class="fas fa-th-large"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value" id="business_categories">0</h3>
                            <p class="stat-label">{{ __('dashboard.Total Categories') }}</p>
                            <div class="stat-trend stat-trend-up">
                                <i class="fas fa-arrow-up"></i>
                                <span>3%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Plans -->
                <div class="stat-card stat-card-purple animated-card">
                    <div class="stat-card-bg"></div>
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon stat-icon-purple">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value" id="total_plans">0</h3>
                            <p class="stat-label">{{ __('dashboard.Total Plans') }}</p>
                            <div class="stat-trend stat-trend-up">
                                <i class="fas fa-arrow-up"></i>
                                <span>15%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="stat-card stat-card-gradient animated-card">
                    <div class="stat-card-bg"></div>
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <div class="stat-icon stat-icon-gradient">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value" id="total_revenue">$0</h3>
                            <p class="stat-label">{{ __('dashboard.Total Revenue') }}</p>
                            <div class="stat-trend stat-trend-up">
                                <i class="fas fa-arrow-up"></i>
                                <span>22%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Charts Section -->
        <div class="charts-section">
            <div class="row">
                <!-- Subscription Plans Chart -->
                <div class="col-xxl-4 mb-4">
                    <div class="modern-card modern-card-chart">
                        <div class="modern-card-header">
                            <div class="card-title-group">
                                <h4>{{ __('dashboard.Subscription Plans') }}</h4>
                                <p class="card-subtitle">{{ __('dashboard.Monthly subscriptions overview') }}</p>
                            </div>
                            <div class="card-actions">
                                <select class="form-select form-select-sm modern-select">
                                    @for ($i = date('Y'); $i >= 2022; $i--)
                                        <option @selected($i == date('Y')) value="{{ $i }}">{{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="modern-card-body">
                            <div class="chart-container">
                                <canvas id="plans-chart" class="modern-chart"></canvas>
                            </div>
                            <div class="chart-legend">
                                <!-- Plans will be dynamically inserted here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Finance Overview Chart -->
                <div class="col-xxl-8 mb-4">
                    <div class="modern-card modern-card-chart">
                        <div class="modern-card-header">
                            <div class="card-title-group">
                                <h4>{{ __('dashboard.Finance Overview') }}</h4>
                                <p class="card-subtitle">{{ __('dashboard.Revenue and expenses analysis') }}</p>
                            </div>
                            <div class="card-actions">
                                <select class="form-select form-select-sm modern-select">
                                    @for ($i = date('Y'); $i >= 2022; $i--)
                                        <option @selected($i == date('Y')) value="{{ $i }}">{{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="modern-card-body">
                            <div class="chart-container">
                                <canvas id="finance-chart" class="modern-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="quick-actions-section">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h4>{{ __('dashboard.Quick Actions') }}</h4>
                    <p class="card-subtitle">{{ __('dashboard.Frequently used actions') }}</p>
                </div>
                <div class="modern-card-body">
                    <div class="quick-actions-grid">
                        <a href="{{ route('admin.business.create') }}" class="quick-action-item">
                            <div class="action-icon action-icon-blue">
                                <i class="fas fa-plus"></i>
                            </div>
                            <span>{{ __('dashboard.Add Business') }}</span>
                        </a>
                        <a href="{{ route('admin.users.create') }}" class="quick-action-item">
                            <div class="action-icon action-icon-green">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <span>{{ __('dashboard.Add User') }}</span>
                        </a>
                        <a href="{{ route('admin.plans.create') }}" class="quick-action-item">
                            <div class="action-icon action-icon-purple">
                                <i class="fas fa-file-plus"></i>
                            </div>
                            <span>{{ __('dashboard.Create Plan') }}</span>
                        </a>
                        <a href="{{ route('admin.business-categories.create') }}" class="quick-action-item">
                            <div class="action-icon action-icon-orange">
                                <i class="fas fa-folder-plus"></i>
                            </div>
                            <span>{{ __('dashboard.Add Category') }}</span>
                        </a>
                        <a href="{{ route('admin.reports.active-stores.index') }}" class="quick-action-item">
                            <div class="action-icon action-icon-pink">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <span>{{ __('dashboard.View Reports') }}</span>
                        </a>
                        <a href="{{ route('admin.maintenance.index') }}" class="quick-action-item">
                            <div class="action-icon action-icon-red">
                                <i class="fas fa-tools"></i>
                            </div>
                            <span>{{ __('dashboard.Maintenance') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern CSS Styles -->
    <style>
        /* Dashboard Header */
        .dashboard-header {
            padding: 1.5rem 0;
            animation: fadeInDown 0.6s ease-out;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
        }

        /* Modern Buttons */
        .btn-modern {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary-soft {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-outline-primary {
            border-color: #667eea;
            color: #667eea;
        }

        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
        }

        /* Stats Grid */
        .stats-grid-container {
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            position: relative;
            border-radius: 1rem;
            padding: 1.5rem;
            overflow: hidden;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card-bg {
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            opacity: 0.1;
            transform: translate(30%, -30%);
        }

        .stat-card-primary .stat-card-bg { background: #667eea; }
        .stat-card-warning .stat-card-bg { background: #f59e0b; }
        .stat-card-success .stat-card-bg { background: #10b981; }
        .stat-card-info .stat-card-bg { background: #3b82f6; }
        .stat-card-purple .stat-card-bg { background: #8b5cf6; }
        .stat-card-gradient .stat-card-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

        .stat-card-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon-wrapper {
            flex-shrink: 0;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .stat-icon-purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .stat-icon-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
        }

        .stat-trend-up {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .stat-trend-down {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }

        /* Animated Cards */
        .animated-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .animated-card:nth-child(1) { animation-delay: 0.1s; }
        .animated-card:nth-child(2) { animation-delay: 0.2s; }
        .animated-card:nth-child(3) { animation-delay: 0.3s; }
        .animated-card:nth-child(4) { animation-delay: 0.4s; }
        .animated-card:nth-child(5) { animation-delay: 0.5s; }
        .animated-card:nth-child(6) { animation-delay: 0.6s; }

        /* Modern Cards */
        .modern-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .modern-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .modern-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title-group h4 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .card-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0;
        }

        .modern-select {
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .modern-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .modern-card-body {
            padding: 1.5rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .modern-chart {
            width: 100% !important;
            height: 100% !important;
        }

        /* Quick Actions */
        .quick-actions-section {
            margin-top: 2rem;
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .quick-action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            gap: 0.75rem;
        }

        .quick-action-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .action-icon-blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .action-icon-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .action-icon-purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .action-icon-orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .action-icon-pink { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
        .action-icon-red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

        .quick-action-item span {
            font-weight: 600;
            font-size: 0.875rem;
            text-align: center;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            :root {
                --text-primary: #ffffff;
                --text-secondary: #a0aec0;
                --border-color: #2d3748;
            }

            .stat-card,
            .modern-card {
                background: #1a202c;
            }

            .quick-action-item {
                background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            }
        }
    </style>

    <!-- Enhanced JavaScript -->
    <script>
        // Animate stats on load
        document.addEventListener('DOMContentLoaded', function() {
            animateStats();
            initCharts();
        });

        function animateStats() {
            const stats = [
                { id: 'total_businesses', target: 156 },
                { id: 'expired_businesses', target: 12 },
                { id: 'plan_subscribes', target: 234 },
                { id: 'business_categories', target: 45 },
                { id: 'total_plans', target: 8 },
                { id: 'total_revenue', target: 125000, prefix: '$' }
            ];

            stats.forEach(stat => {
                const element = document.getElementById(stat.id);
                if (element) {
                    animateValue(element, 0, stat.target, 1500, stat.prefix || '');
                }
            });
        }

        function animateValue(element, start, end, duration, prefix = '') {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = prefix + Math.floor(current).toLocaleString();
            }, 16);
        }

        function initCharts() {
            // Initialize modern charts here
            console.log('Charts initialized');
        }
    </script>
@endsection