<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'نظام الصيانة' }} - Z-Syst Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
            }
            50% {
                box-shadow: 0 0 40px rgba(59, 130, 246, 0.8);
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        @keyframes spin-slow {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        .maintenance-icon {
            animation: float 3s ease-in-out infinite;
        }
        
        .gear-icon {
            animation: spin-slow 8s linear infinite;
        }
        
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
            background-size: 200% 100%;
            animation: gradient-move 2s linear infinite;
        }
        
        @keyframes gradient-move {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Main Card -->
        <div class="glass-card rounded-3xl p-8 md:p-12 text-center shadow-2xl">
            <!-- Icon -->
            <div class="maintenance-icon mb-8">
                <div class="relative inline-block">
                    <div class="w-32 h-32 mx-auto bg-blue-500/20 rounded-full flex items-center justify-center pulse-glow">
                        <svg class="w-16 h-16 text-blue-400 gear-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-900" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                {{ $title ?? 'نظام الصيانة' }}
            </h1>

            <!-- Message -->
            <div class="mb-8">
                <p class="text-blue-100 text-lg mb-4">
                    {{ $message ?? 'نحن نقوم بتحسين نظامنا لتقديم خدمة أفضل لك.' }}
                </p>
                <p class="text-blue-200 text-sm">
                    شكراً لصبركم. سنعود قريباً بخدمة محسنة.
                </p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="h-2 bg-blue-800/50 rounded-full overflow-hidden">
                    <div class="progress-bar h-full rounded-full" style="width: 75%"></div>
                </div>
                <div class="flex justify-between mt-2 text-blue-200 text-sm">
                    <span>جاري العمل...</span>
                    <span>75%</span>
                </div>
            </div>

            <!-- Estimated Completion -->
            @if($estimated_completion)
            <div class="glass-card rounded-xl p-4 mb-6">
                <div class="flex items-center justify-center gap-3 text-blue-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm">
                        الوقت المقدر للإنتهاء: {{ $estimated_completion }}
                    </span>
                </div>
            </div>
            @endif

            <!-- Started At -->
            @if($started_at)
            <div class="glass-card rounded-xl p-4 mb-6">
                <div class="flex items-center justify-center gap-3 text-blue-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span class="text-sm">
                        بدأ الصيانة: {{ $started_at->format('Y-m-d H:i') }}
                    </span>
                </div>
            </div>
            @endif

            <!-- Contact Info -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-white font-semibold mb-4">هل تحتاج مساعدة؟</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="tel:+201000000000" class="flex items-center justify-center gap-2 text-blue-100 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 8V5z"></path>
                        </svg>
                        <span>اتصل بنا</span>
                    </a>
                    <a href="mailto:support@z-syst.com" class="flex items-center justify-center gap-2 text-blue-100 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>البريد الإلكتروني</span>
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-blue-200 text-sm">
                <p>© 2026 Z-Syst Pharmacy Management System</p>
            </div>
        </div>

        <!-- Floating particles effect -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
            <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-blue-400/30 rounded-full animate-pulse"></div>
            <div class="absolute top-1/3 right-1/4 w-3 h-3 bg-purple-400/30 rounded-full animate-pulse" style="animation-delay: 1s"></div>
            <div class="absolute bottom-1/4 left-1/3 w-2 h-2 bg-pink-400/30 rounded-full animate-pulse" style="animation-delay: 2s"></div>
            <div class="absolute top-1/2 right-1/3 w-4 h-4 bg-blue-300/20 rounded-full animate-pulse" style="animation-delay: 0.5s"></div>
            <div class="absolute bottom-1/3 right-1/4 w-2 h-2 bg-purple-300/30 rounded-full animate-pulse" style="animation-delay: 1.5s"></div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);

        // Dynamic progress bar animation
        const progressBar = document.querySelector('.progress-bar');
        let progress = 75;
        
        setInterval(() => {
            progress = Math.min(progress + Math.random() * 2, 95);
            progressBar.style.width = progress + '%';
            progressBar.parentElement.nextElementSibling.querySelector('span:last-child').textContent = Math.round(progress) + '%';
        }, 5000);
    </script>
</body>
</html>