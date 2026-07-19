# Z-Syst Design System

## 1. Vision

Z-Syst هو نظام SaaS لصيدليات ومراكز الرعاية الصحية، ويحتاج واجهة تجمع بين:
- السرعة والوضوح في POS
- الدقة في إدارة المخزون والموارد
- سهولة الاستخدام للأدوار المتنوعة
- دعم كامل للعربية والإنجليزية من اليوم الأول

الواجهة يجب أن تكون احترافية، سريعة، قابلة للتوسع، ومتوافقة مع WCAG 2.1 AA.

---

## 2. Design Principles

1. Accessibility first
2. RTL-first
3. Mobile-first
4. Performance-first
5. Consistency over novelty
6. Clear feedback for every action
7. Component-driven development

---

## 3. Design Tokens

### 3.1 Colors

```ts
export const designTokens = {
  brand: {
    50: '#E6F4FF',
    100: '#CCE9FF',
    200: '#99D3FF',
    300: '#66BDFF',
    400: '#33A7FF',
    500: '#0091FF',
    600: '#0074CC',
    700: '#005799',
    800: '#003A66',
    900: '#001D33',
  },
  success: { light: '#DCFCE7', default: '#22C55E', dark: '#15803D' },
  warning: { light: '#FEF3C7', default: '#F59E0B', dark: '#B45309' },
  error: { light: '#FEE2E2', default: '#EF4444', dark: '#B91C1C' },
  info: { light: '#DBEAFE', default: '#3B82F6', dark: '#1E40AF' },
  neutral: {
    0: '#FFFFFF',
    50: '#FAFAFA',
    100: '#F5F5F5',
    200: '#E5E5E5',
    300: '#D4D4D4',
    400: '#A3A3A3',
    500: '#737373',
    600: '#525252',
    700: '#404040',
    800: '#262626',
    900: '#171717',
    950: '#0A0A0A',
  },
  pharmacy: {
    prescription: '#8B5CF6',
    otc: '#10B981',
    controlled: '#EF4444',
    expiry: '#F59E0B',
  },
};
```

### 3.2 Typography

```css
--font-sans: 'Inter', system-ui, sans-serif;
--font-arabic: 'Cairo', 'Tajawal', system-ui, sans-serif;
--font-mono: 'JetBrains Mono', monospace;
```

Scale:
- xs: 12px
- sm: 14px
- base: 16px
- lg: 18px
- xl: 20px
- 2xl: 24px
- 3xl: 30px
- 4xl: 36px

### 3.3 Spacing

Use a 4px grid:
- 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96

### 3.4 Radius

- sm: 4px
- md: 6px
- lg: 8px
- xl: 12px
- full: 9999px

### 3.5 Shadows

- xs: subtle elevation
- sm: cards
- md: dropdowns/modals
- lg: panels

### 3.6 Motion

- instant: 100ms
- fast: 150ms
- normal: 250ms
- slow: 400ms

---

## 4. UI Patterns

### 4.1 Layout
- Use a shell layout with top bar + sidebar
- Mobile: drawer navigation
- Desktop: persistent sidebar

### 4.2 Forms
- Label above input when possible
- Inline validation with helpful errors
- Clear empty states
- Auto-save for long forms

### 4.3 Data Tables
- Sticky header
- Sortable columns
- Row actions in a menu
- Bulk selection support

### 4.4 POS UI
- Keyboard-first interaction
- Large touch targets
- Fast cart updates
- Clear payment breakdown

---

## 5. Component Inventory

### Core
- Button
- Input
- Textarea
- Select
- Checkbox
- RadioGroup
- Switch
- Dialog
- Drawer
- DropdownMenu
- Tooltip
- Alert
- Toast
- Card
- Badge
- Avatar
- Table
- Tabs
- Pagination
- Skeleton
- EmptyState

### Pharmacy-specific
- ProductCard
- PrescriptionCard
- StockIndicator
- ExpiryBadge
- DrugInteractionAlert
- PatientInfoCard
- CashRegisterStatus

### Requirements for each component
- TypeScript strict typing
- Accessible by default
- RTL-aware layout
- Responsive behavior
- Dark mode support
- Storybook documentation
- Unit tests

---

## 6. Accessibility Standards

- Use semantic HTML
- Ensure visible focus rings
- Maintain color contrast of at least 4.5:1 for body text
- Support keyboard-only navigation
- Use meaningful aria labels
- Respect reduced motion preferences
- Ensure forms are correctly labeled

---

## 7. RTL and i18n Rules

- Prefer logical properties: ms-*, me-*, start/end
- Flip arrows and navigation direction automatically
- Keep digits LTR inside Arabic text when necessary
- Test layout in both Arabic and English
- Ensure locale-aware formatting for date, currency, and numbers

---

## 8. Performance Rules

- Use Next.js image optimization
- Prefer server components where possible
- Split heavy routes and charts dynamically
- Use virtualization for large tables
- Avoid unnecessary re-renders
- Keep initial bundle lean

---

## 9. Implementation Checklist

### Phase 1
- [x] Define design principles
- [x] Define tokens
- [x] Define component inventory
- [ ] Create base components
- [ ] Set up RTL and theme system
- [ ] Create app shell layout

### Phase 2
- [ ] Build authentication pages
- [ ] Build dashboard shell
- [ ] Build products and inventory flows
- [ ] Build POS experience

### Phase 3
- [ ] Build reports and settings
- [ ] Add dark mode
- [ ] Add tests and stories
- [ ] Optimize performance and accessibility

---

## 10. Recommended Visual Direction

Visual inspiration should be a blend of:
- Stripe Dashboard: clarity and structure
- Linear: calm modern UI
- Vercel: speed and simplicity
- Figma: polished component language

The result should feel premium, trustworthy, and efficient for pharmacy operations.
