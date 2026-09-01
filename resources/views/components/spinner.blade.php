<!-- Loading Spinner Component -->
<div class="spinner {{ $size ?? 'md' }}" {{ $attributes }}>
    <div class="spinner-ring"></div>
</div>

<style>
.spinner {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.spinner.sm {
    width: 20px;
    height: 20px;
}

.spinner.md {
    width: 32px;
    height: 32px;
}

.spinner.lg {
    width: 48px;
    height: 48px;
}

.spinner-ring {
    width: 100%;
    height: 100%;
    border: 3px solid var(--color-muted);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
