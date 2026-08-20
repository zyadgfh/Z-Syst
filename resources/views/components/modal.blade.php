<!-- Modal Component - matches z-syst-pharmacy-web design system -->
<div class="modal-overlay" id="{{ $id ?? 'modal' }}" style="display: {{ $show ?? 'none' }};">
    <div class="modal">
        <div class="modal-header">
            <h3>{{ $title ?? 'Modal Title' }}</h3>
            <button class="modal-close" onclick="closeModal('{{ $id ?? 'modal' }}')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            {{ $slot ?? 'Modal content goes here' }}
        </div>
        @if(isset($footer) && $footer)
        <div class="modal-footer">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: var(--space-xl);
}

.modal {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: var(--shadow-xl);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-lg);
    padding-bottom: var(--space-md);
    border-bottom: 1px solid var(--color-border);
}

.modal-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-foreground);
    margin: 0;
}

.modal-close {
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
}

.modal-close:hover {
    background: rgba(220, 38, 38, 0.1);
    color: var(--color-destructive);
}

.modal-body {
    margin-bottom: var(--space-lg);
}

.modal-footer {
    padding-top: var(--space-md);
    border-top: 1px solid var(--color-border);
    display: flex;
    justify-content: flex-end;
    gap: var(--space-sm);
}
</style>

<script>
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}
</script>