<!-- Alert Component -->
<div class="alert alert-{{ $type ?? 'info' }}" {{ $attributes }}>
    @if(isset($dismissible) && $dismissible)
    <button class="alert-close" onclick="this.parentElement.remove()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>
    @endif
    <div class="alert-content">
        @if(isset($icon))
        <div class="alert-icon">{{ $icon }}</div>
        @endif
        <div class="alert-message">
            @if(isset($title))
            <h4>{{ $title }}</h4>
            @endif
            <p>{{ $message }}</p>
        </div>
    </div>
</div>

<style>
.alert {
    padding: var(--space-md);
    border-radius: var(--radius-md);
    border: 1px solid var(--color-border);
    margin-bottom: var(--space-md);
    display: flex;
    gap: var(--space-md);
    align-items: flex-start;
}

.alert-info {
    background: rgba(3, 105, 161, 0.1);
    border-color: var(--color-accent);
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border-color: var(--color-success);
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border-color: var(--color-warning);
}

.alert-destructive {
    background: rgba(220, 38, 38, 0.1);
    border-color: var(--color-destructive);
}

.alert-close {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: var(--space-xs);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
    transition: all 200ms ease;
    color: var(--color-muted);
    margin-left: auto;
}

.alert-close:hover {
    background: rgba(0, 0, 0, 0.1);
}

.alert-content {
    display: flex;
    gap: var(--space-md);
    align-items: flex-start;
    flex: 1;
}

.alert-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: var(--radius-sm);
    flex-shrink: 0;
}

.alert-info .alert-icon {
    background: rgba(3, 105, 161, 0.2);
    color: var(--color-accent);
}

.alert-success .alert-icon {
    background: rgba(34, 197, 94, 0.2);
    color: var(--color-success);
}

.alert-warning .alert-icon {
    background: rgba(245, 158, 11, 0.2);
    color: var(--color-warning);
}

.alert-destructive .alert-icon {
    background: rgba(220, 38, 38, 0.2);
    color: var(--color-destructive);
}

.alert-message h4 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: var(--space-xs);
    color: var(--color-foreground);
}

.alert-message p {
    font-size: 14px;
    margin: 0;
    color: var(--color-muted);
}
</style>
