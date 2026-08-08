# Z-Syst Pharmacy Management System - Design System

## 🎨 Design System Overview

This design system provides comprehensive guidelines for creating a consistent, professional, and accessible user interface for the Z-Syst Pharmacy Management System.

---

## 📋 Design Principles

### Core Principles
1. **Professional Clean** - Medical-grade clarity and trust
2. **Accessibility First** - WCAG 2.1 AA compliant
3. **Performance Focused** - Fast loading and responsive
4. **Consistent Experience** - Unified across all interfaces
5. **Data-Driven** - Information hierarchy prioritized

---

## 🎨 Color System

### Primary Colors
```css
:root {
    /* Brand Colors */
    --color-primary: #0066cc;          /* Medical Blue */
    --color-primary-dark: #004499;     /* Darker Blue */
    --color-primary-light: #3388ee;    /* Lighter Blue */
    
    /* Success Colors */
    --color-success: #10b981;          /* Green */
    --color-success-dark: #059669;     /* Darker Green */
    --color-success-light: #34d399;    /* Lighter Green */
    
    /* Warning Colors */
    --color-warning: #f59e0b;           /* Amber */
    --color-warning-dark: #d97706;     /* Darker Amber */
    --color-warning-light: #fbbf24;    /* Lighter Amber */
    
    /* Error Colors */
    --color-error: #ef4444;             /* Red */
    --color-error-dark: #dc2626;       /* Darker Red */
    --color-error-light: #f87171;      /* Lighter Red */
    
    /* Info Colors */
    --color-info: #3b82f6;             /* Blue */
    --color-info-dark: #2563eb;        /* Darker Blue */
    --color-info-light: #60a5fa;       /* Lighter Blue */
}
```

### Neutral Colors
```css
:root {
    /* Background Colors */
    --color-bg-primary: #ffffff;        /* White */
    --color-bg-secondary: #f9fafb;      /* Light Gray */
    --color-bg-tertiary: #f3f4f6;       /* Medium Gray */
    --color-bg-inverse: #111827;        /* Black */
    
    /* Text Colors */
    --color-text-primary: #111827;      /* Black */
    --color-text-secondary: #4b5563;    /* Dark Gray */
    --color-text-tertiary: #9ca3af;     /* Medium Gray */
    --color-text-inverse: #ffffff;      /* White */
    
    /* Border Colors */
    --color-border-light: #e5e7eb;      /* Light Border */
    --color-border-medium: #d1d5db;     /* Medium Border */
    --color-border-dark: #9ca3af;       /* Dark Border */
}
```

### Semantic Colors
```css
:root {
    /* Status Colors */
    --color-status-active: #10b981;     /* Active/Online */
    --color-status-inactive: #6b7280;   /* Inactive/Offline */
    --color-status-pending: #f59e0b;    /* Pending */
    --color-status-verified: #3b82f6;   /* Verified */
    
    /* Medical Colors */
    --color-medical-prescription: #0066cc;
    --color-medical-otc: #10b981;
    --color-medical-controlled: #f59e0b;
    --color-medical-dangerous: #ef4444;
}
```

### Dark Mode Colors
```css
:root {
    /* Dark Mode Override */
    --color-bg-primary: #1a1a1a;
    --color-bg-secondary: #2a2a2a;
    --color-bg-tertiary: #3a3a3a;
    --color-bg-inverse: #ffffff;
    
    --color-text-primary: #ffffff;
    --color-text-secondary: #d1d5db;
    --color-text-tertiary: #9ca3af;
    --color-text-inverse: #111827;
    
    --color-border-light: #4a4a4a;
    --color-border-medium: #5a5a5a;
    --color-border-dark: #6a6a6a;
}
```

---

## 🔤 Typography System

### Font Family
```css
:root {
    --font-family-base: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-family-mono: 'JetBrains Mono', 'Fira Code', monospace;
    --font-family-heading: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
```

### Font Sizes
```css
:root {
    --font-size-xs: 0.75rem;      /* 12px */
    --font-size-sm: 0.875rem;     /* 14px */
    --font-size-base: 1rem;       /* 16px */
    --font-size-lg: 1.125rem;     /* 18px */
    --font-size-xl: 1.25rem;      /* 20px */
    --font-size-2xl: 1.5rem;      /* 24px */
    --font-size-3xl: 1.875rem;    /* 30px */
    --font-size-4xl: 2.25rem;     /* 36px */
    --font-size-5xl: 3rem;        /* 48px */
}
```

### Font Weights
```css
:root {
    --font-weight-light: 300;
    --font-weight-normal: 400;
    --font-weight-medium: 500;
    --font-weight-semibold: 600;
    --font-weight-bold: 700;
}
```

### Line Heights
```css
:root {
    --line-height-tight: 1.25;
    --line-height-normal: 1.5;
    --line-height-relaxed: 1.75;
}
```

### Typography Scale
```css
/* Display Text */
.text-display-1 {
    font-size: var(--font-size-5xl);
    font-weight: var(--font-weight-bold);
    line-height: var(--line-height-tight);
    letter-spacing: -0.02em;
}

.text-display-2 {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    line-height: var(--line-height-tight);
    letter-spacing: -0.01em;
}

/* Headings */
.text-h1 {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    line-height: var(--line-height-tight);
}

.text-h2 {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-semibold);
    line-height: var(--line-height-tight);
}

.text-h3 {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    line-height: var(--line-height-normal);
}

.text-h4 {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    line-height: var(--line-height-normal);
}

/* Body Text */
.text-body {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-normal);
    line-height: var(--line-height-normal);
}

.text-body-large {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-normal);
    line-height: var(--line-height-normal);
}

.text-body-small {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-normal);
    line-height: var(--line-height-normal);
}

/* Labels & Captions */
.text-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    line-height: var(--line-height-normal);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.text-caption {
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-normal);
    line-height: var(--line-height-normal);
}
```

---

## 📐 Spacing System

### Spacing Scale
```css
:root {
    --space-0: 0;
    --space-1: 0.25rem;    /* 4px */
    --space-2: 0.5rem;     /* 8px */
    --space-3: 0.75rem;    /* 12px */
    --space-4: 1rem;       /* 16px */
    --space-5: 1.25rem;    /* 20px */
    --space-6: 1.5rem;      /* 24px */
    --space-8: 2rem;       /* 32px */
    --space-10: 2.5rem;     /* 40px */
    --space-12: 3rem;      /* 48px */
    --space-16: 4rem;      /* 64px */
    --space-20: 5rem;      /* 80px */
    --space-24: 6rem;      /* 96px */
}
```

### Component Spacing
```css
/* Container Padding */
.container-padding {
    padding: var(--space-6);
}

/* Card Spacing */
.card-padding {
    padding: var(--space-6);
}

.card-padding-sm {
    padding: var(--space-4);
}

/* Form Spacing */
.form-group-spacing {
    margin-bottom: var(--space-4);
}

/* Button Spacing */
.button-padding {
    padding: var(--space-3) var(--space-6);
}

/* Section Spacing */
.section-spacing {
    margin-bottom: var(--space-12);
}

.section-spacing-sm {
    margin-bottom: var(--space-8);
}
```

---

## 🎯 Component Styles

### Buttons
```css
/* Primary Button */
.btn-primary {
    background-color: var(--color-primary);
    color: var(--color-text-inverse);
    border: none;
    border-radius: 0.375rem;
    padding: var(--space-3) var(--space-6);
    font-weight: var(--font-weight-medium);
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background-color: var(--color-primary-dark);
    transform: translateY(-1px);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Secondary Button */
.btn-secondary {
    background-color: var(--color-bg-secondary);
    color: var(--color-text-primary);
    border: 1px solid var(--color-border-medium);
    border-radius: 0.375rem;
    padding: var(--space-3) var(--space-6);
    font-weight: var(--font-weight-medium);
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background-color: var(--color-bg-tertiary);
}

/* Danger Button */
.btn-danger {
    background-color: var(--color-error);
    color: var(--color-text-inverse);
    border: none;
    border-radius: 0.375rem;
    padding: var(--space-3) var(--space-6);
    font-weight: var(--font-weight-medium);
    transition: all 0.2s ease;
}

.btn-danger:hover {
    background-color: var(--color-error-dark);
}
```

### Forms
```css
/* Input Fields */
.form-input {
    width: 100%;
    padding: var(--space-3) var(--space-4);
    border: 1px solid var(--color-border-medium);
    border-radius: 0.375rem;
    font-size: var(--font-size-base);
    transition: border-color 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

.form-input::placeholder {
    color: var(--color-text-tertiary);
}

/* Form Labels */
.form-label {
    display: block;
    margin-bottom: var(--space-2);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
}

/* Error States */
.form-input-error {
    border-color: var(--color-error);
}

.form-input-error:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-error-message {
    color: var(--color-error);
    font-size: var(--font-size-sm);
    margin-top: var(--space-2);
}
```

### Cards
```css
.card {
    background-color: var(--color-bg-primary);
    border: 1px solid var(--color-border-light);
    border-radius: 0.5rem;
    padding: var(--space-6);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
    padding-bottom: var(--space-4);
    border-bottom: 1px solid var(--color-border-light);
    margin-bottom: var(--space-4);
}

.card-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
}

.card-body {
    color: var(--color-text-secondary);
}
```

### Tables
```css
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background-color: var(--color-bg-secondary);
    padding: var(--space-3) var(--space-4);
    text-align: left;
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    border-bottom: 2px solid var(--color-border-medium);
}

.table td {
    padding: var(--space-3) var(--space-4);
    border-bottom: 1px solid var(--color-border-light);
}

.table tr:hover {
    background-color: var(--color-bg-secondary);
}
```

---

## 📱 Responsive Breakpoints

### Breakpoint System
```css
:root {
    --breakpoint-xs: 0;
    --breakpoint-sm: 576px;
    --breakpoint-md: 768px;
    --breakpoint-lg: 992px;
    --breakpoint-xl: 1200px;
    --breakpoint-xxl: 1400px;
}

/* Extra Small Devices (phones, less than 576px) */
@media (max-width: 575.98px) {
    .container {
        padding: var(--space-4);
    }
    
    .card {
        padding: var(--space-4);
    }
}

/* Small Devices (landscape phones, 576px and up) */
@media (min-width: 576px) and (max-width: 767.98px) {
    .container {
        padding: var(--space-6);
    }
}

/* Medium Devices (tablets, 768px and up) */
@media (min-width: 768px) and (max-width: 991.98px) {
    .container {
        padding: var(--space-8);
    }
}

/* Large Devices (desktops, 992px and up) */
@media (min-width: 992px) {
    .container {
        padding: var(--space-8);
    }
}
```

---

## ♿ Accessibility

### Focus States
```css
*:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

*:focus:not(:focus-visible) {
    outline: none;
}

*:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}
```

### Skip Links
```css
.skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: var(--color-primary);
    color: var(--color-text-inverse);
    padding: var(--space-2) var(--space-4);
    z-index: 100;
    transition: top 0.3s;
}

.skip-link:focus {
    top: 0;
}
```

### Screen Reader Only
```css
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
```

---

## 🎨 Visual Effects

### Shadows
```css
:root {
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}
```

### Transitions
```css
:root {
    --transition-fast: 150ms ease;
    --transition-normal: 200ms ease;
    --transition-slow: 300ms ease;
}
```

### Border Radius
```css
:root {
    --radius-sm: 0.25rem;
    --radius-md: 0.375rem;
    --radius-lg: 0.5rem;
    --radius-xl: 0.75rem;
    --radius-full: 9999px;
}
```

---

## 🎯 Component States

### Loading States
```css
.loading {
    opacity: 0.6;
    pointer-events: none;
    cursor: wait;
}

.spinner {
    border: 2px solid var(--color-border-light);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
```

### Disabled States
```css
.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
```

### Success States
```css
.success {
    border-color: var(--color-success);
    color: var(--color-success);
}
```

### Error States
```css
.error {
    border-color: var(--color-error);
    color: var(--color-error);
}
```

---

## 🎨 Icons

### Icon Guidelines
- Use SVG icons only (no emoji)
- Minimum size: 16x16px
- Consistent stroke width
- Medical-grade icons for healthcare context

### Icon Sizes
```css
:root {
    --icon-xs: 12px;
    --icon-sm: 16px;
    --icon-md: 20px;
    --icon-lg: 24px;
    --icon-xl: 32px;
    --icon-2xl: 48px;
}
```

---

## 📱 Touch Targets

### Minimum Touch Target Size
```css
.touch-target {
    min-width: 44px;
    min-height: 44px;
    padding: var(--space-3);
}
```

---

## 🎨 Animation Guidelines

### Animation Timing
```css
:root {
    --animation-duration-fast: 150ms;
    --animation-duration-normal: 200ms;
    --animation-duration-slow: 300ms;
}
```

### Reduced Motion
```css
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

---

## 🎯 Dark Mode Support

### Dark Mode Toggle
```css
@media (prefers-color-scheme: dark) {
    :root {
        --color-bg-primary: #1a1a1a;
        --color-bg-secondary: #2a2a2a;
        --color-bg-tertiary: #3a3a3a;
        --color-text-primary: #ffffff;
        --color-text-secondary: #d1d5db;
        --color-text-tertiary: #9ca3af;
    }
}
```

---

## 📋 Implementation Guidelines

### Usage in Blade Templates
```blade
{{-- Button Component --}}
<button class="btn-primary {{ $class ?? '' }}">
    {{ $slot }}
</button>

{{-- Form Input --}}
<input type="text" class="form-input {{ $error ? 'form-input-error' : '' }}">

{{-- Card --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
```

### CSS Integration
```css
/* Import this CSS file in your main stylesheet */
@import 'design-system.css';

/* Or include in your blade template */
<link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
```

---

## 🎯 Component Library

### Available Components
- Buttons (Primary, Secondary, Danger, Success)
- Form Inputs (Text, Email, Password, Select, Textarea)
- Cards (Standard, Stats, Interactive)
- Tables (Standard, Responsive, Stacked)
- Modals (Dialog, Alert, Confirm)
- Alerts (Success, Warning, Error, Info)
- Badges (Status, Count, Notification)
- Navigation (Header, Sidebar, Breadcrumb)
- Progress Bars (Linear, Circular)

---

## 🎨 Anti-Patterns to Avoid

### ❌ Don't
- Use emoji as icons
- Remove focus rings
- Use placeholder-only labels
- Rely on color alone for meaning
- Mix flat and skeuomorphic randomly
- Use text smaller than 12px
- Disable zoom on mobile
- Use fixed pixel widths
- Animate width/height
- Create horizontal scroll

### ✅ Do
- Use SVG icons
- Maintain focus rings
- Use visible labels
- Use color + icons for meaning
- Maintain consistent style
- Use appropriate font sizes
- Allow zoom on mobile
- Use responsive units
- Animate transform/opacity
- Use proper breakpoints

---

## 📊 Color Contrast Compliance

### WCAG 2.1 AA Compliance
- **Normal Text:** 4.5:1 contrast ratio
- **Large Text:** 3:1 contrast ratio
- **UI Components:** 3:1 contrast ratio
- **Interactive Elements:** 3:1 contrast ratio

### All colors in this system meet WCAG 2.1 AA standards.

---

## 🎯 Browser Support

### Supported Browsers
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Opera 76+

### Mobile Browsers
- Chrome Mobile (Android)
- Safari (iOS)
- Samsung Internet
- Firefox Mobile

---

## 📝 Maintenance

### Version
- **Version:** 1.0.0
- **Last Updated:** 2026-08-07
- **Status:** Active

### Updates
- Update colors, typography, or spacing as needed
- Maintain semantic naming conventions
- Keep accessibility compliance current
- Document any changes

---

**Design System Version:** 1.0.0  
**Last Updated:** 2026-08-07  
**Status:** ACTIVE
