{{-- Flash Messages Toast Notifications --}}
@if(session('success') || session('error') || session('warning') || session('info'))
<div id="flash-toast-container" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:320px;max-width:420px;">
    @if(session('success'))
    <div class="flash-toast flash-toast-success" role="alert">
        <div class="flash-toast-icon"><i class="fas fa-check-circle"></i></div>
        <div class="flash-toast-content">
            <strong>{{ __('Success') }}</strong>
            <p>{{ session('success') }}</p>
        </div>
        <button class="flash-toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="flash-toast flash-toast-error" role="alert">
        <div class="flash-toast-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="flash-toast-content">
            <strong>{{ __('Error') }}</strong>
            <p>{{ session('error') }}</p>
        </div>
        <button class="flash-toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    @if(session('warning'))
    <div class="flash-toast flash-toast-warning" role="alert">
        <div class="flash-toast-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="flash-toast-content">
            <strong>{{ __('Warning') }}</strong>
            <p>{{ session('warning') }}</p>
        </div>
        <button class="flash-toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif

    @if(session('info'))
    <div class="flash-toast flash-toast-info" role="alert">
        <div class="flash-toast-icon"><i class="fas fa-info-circle"></i></div>
        <div class="flash-toast-content">
            <strong>{{ __('Info') }}</strong>
            <p>{{ session('info') }}</p>
        </div>
        <button class="flash-toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    @endif
</div>

<style>
.flash-toast {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 16px 20px; margin-bottom: 10px; border-radius: 12px;
    background: #fff; box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    border-left: 4px solid; animation: flashSlideIn 0.3s ease-out;
    font-family: 'Open Sans', sans-serif;
}
.flash-toast-success { border-left-color: #22c55e; }
.flash-toast-error   { border-left-color: #ef4444; }
.flash-toast-warning { border-left-color: #f59e0b; }
.flash-toast-info    { border-left-color: #3b82f6; }
.flash-toast-icon    { font-size: 20px; margin-top: 2px; }
.flash-toast-success .flash-toast-icon { color: #22c55e; }
.flash-toast-error   .flash-toast-icon { color: #ef4444; }
.flash-toast-warning .flash-toast-icon { color: #f59e0b; }
.flash-toast-info    .flash-toast-icon { color: #3b82f6; }
.flash-toast-content strong { display: block; font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 2px; }
.flash-toast-content p { margin: 0; font-size: 13px; color: #6b7280; line-height: 1.4; }
.flash-toast-close { background: none; border: none; font-size: 20px; color: #9ca3af; cursor: pointer; padding: 0 4px; line-height: 1; }
.flash-toast-close:hover { color: #374151; }
@keyframes flashSlideIn { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toasts = document.querySelectorAll('.flash-toast');
    toasts.forEach(function(toast) {
        setTimeout(function() {
            toast.style.transition = 'opacity 0.3s, transform 0.3s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            setTimeout(function() { toast.remove(); }, 300);
        }, 5000);
    });
});
</script>
@endif
