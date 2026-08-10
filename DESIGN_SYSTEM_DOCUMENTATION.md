# Z-Syst Pharmacy Design System Documentation
## Version 1.0 - 2026-08-10

---

## 🎨 Design Philosophy

The Z-Syst Pharmacy design system is built on principles of **consistency**, **accessibility**, and **user experience**. Our goal is to create a professional, trustworthy interface that supports efficient pharmacy management operations.

### Core Principles

1. **Clarity Over Decoration**: Every visual element serves a functional purpose
2. **Accessibility First**: All components are accessible by default
3. **Mobile-First**: Design for small screens, then scale up
4. **Performance-First**: Fast loading, smooth interactions
5. **Inclusive**: Works for all users, regardless of ability

---

## 🎯 Design Tokens

### Color System

#### Primary Colors (Blue)
- **Primary-50**: #eff6ff - Light backgrounds
- **Primary-500**: #3b82f6 - Main actions
- **Primary-600**: #2563eb - Primary hover states
- **Primary-900**: #1e3a8a - Dark backgrounds

#### Status Colors
- **Success**: Green (#22c55e) - Positive actions, success states
- **Warning**: Yellow (#f59e0b) - Alerts, warnings
- **Danger**: Red (#ef4444) - Destructive actions, errors
- **Info**: Blue (#3b82f6) - Information, neutral states

#### Neutral Colors
- **Gray-50 to Gray-900**: Text, borders, backgrounds
- **Semantic mapping**: Light for backgrounds, dark for text

### Typography Scale

| Token | Size | Usage |
|-------|------|-------|
| `text-xs` | 12px | Captions, labels |
| `text-sm` | 14px | Body text, secondary info |
| `text-base` | 16px | Default body text |
| `text-lg` | 18px | Emphasized text |
| `text-xl` | 20px | Section headings |
| `text-2xl` | 24px | Page headings |
| `text-3xl` | 30px | Display headings |

**Font Weights**:
- Light (300) - Decorative text
- Normal (400) - Body text
- Medium (500) - Emphasized text
- Semibold (600) - Headings
- Bold (700) - Strong emphasis

### Spacing Scale

| Token | Size | Usage |
|-------|------|-------|
| `spacing-xs` | 4px | Tight spacing |
| `spacing-sm` | 8px | Small gaps |
| `spacing-md` | 16px | Default spacing |
| `spacing-lg` | 24px | Section spacing |
| `spacing-xl` | 32px | Large spacing |
| `spacing-2xl` | 48px | Component spacing |
| `spacing-3xl` | 64px | Page spacing |

### Border Radius

| Token | Size | Usage |
|-------|------|-------|
| `radius-sm` | 4px | Small elements |
| `radius-md` | 6px | Default elements |
| `radius-lg` | 8px | Cards, buttons |
| `radius-xl` | 12px | Large cards |
| `radius-2xl` | 16px | Modals |
| `radius-full` | 9999px | Pills, badges |

### Shadow Scale

| Token | Usage |
|-------|-------|
| `shadow-sm` | Subtle elevation |
| `shadow-md` | Default elevation |
| `shadow-lg` | Cards, panels |
| `shadow-xl` | Modals, dropdowns |

---

## 🧩 Component Library

### Button Component

**Variants**: primary, secondary, success, danger, warning, outline  
**Sizes**: sm, md, lg  
**Features**: Loading states, disabled states, dark mode

```blade
<x-button variant="primary" size="md" :loading="$isLoading">
    Save Changes
</x-button>
```

### Input Component

**Features**: Labels, error states, hints, dark mode, accessibility  
**States**: Default, error, disabled, readonly

```blade
<x-input 
    type="text" 
    name="productName" 
    label="Product Name" 
    :error="$errors->productName" 
    required 
/>
```

### Card Component

**Variants**: default, primary, success, danger, warning  
**Padding**: none, sm, md, lg, xl  
**Shadow**: none, sm, md, lg, xl

```blade
<x-card variant="primary" padding="lg" shadow="lg">
    <h3>Card Title</h3>
    <p>Card content</p>
</x-card>
```

### Badge Component

**Variants**: default, primary, success, danger, warning  
**Sizes**: sm, md, lg

```blade
<x-badge variant="success" size="md">Active</x-badge>
```

### Alert Component

**Variants**: default, destructive  
**Features**: Icons, descriptions, dismissible

```blade
<x-alert variant="destructive" title="Error" description="Something went wrong">
    Additional content
</x-alert>
```

### Dialog Component

**Features**: Modal overlay, backdrop blur, accessible close button  
**Usage**: Confirmations, forms, detailed views

```blade
<x-dialog title="Confirm Action" :show="$showDialog">
    <p>Are you sure you want to proceed?</p>
    <div class="flex justify-end space-x-2 mt-4">
        <x-button variant="secondary" @click="$showDialog = false">Cancel</x-button>
        <x-button variant="danger">Confirm</x-button>
    </div>
</x-dialog>
```

### Table Component

**Features**: Striped rows, hover effects, accessible focus states  
**Variants**: default, simple

```blade
<x-table striped hover>
    <thead>
        <tr>
            <th>Product</th>
            <th>Stock</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Product A</td>
            <td>50</td>
            <td>$10.00</td>
        </tr>
    </tbody>
</x-table>
```

---

## 🌙 Dark Mode

### Implementation

Dark mode is implemented using CSS variables and automatic system preference detection.

### Manual Toggle

Users can toggle dark mode using the dark mode toggle component:

```blade
<x-dark-mode-toggle />
```

### Token Mapping

Dark mode automatically switches between light and dark tokens:

```css
:root {
    --bg-primary: #ffffff;
    --text-primary: #171717;
}

.dark {
    --bg-primary: #171717;
    --text-primary: #fafafa;
}
```

### Component Support

All components include dark mode variants:
- Buttons: Adjusted colors for dark backgrounds
- Inputs: Dark backgrounds with light text
- Cards: Dark gray backgrounds
- Tables: Dark mode row styling

---

## 📱 Responsive Design

### Breakpoint System

| Breakpoint | Size | Device |
|-----------|------|--------|
| `sm` | 640px | Small tablets |
| `md` | 768px | Tablets |
| `lg` | 1024px | Laptops |
| `xl` | 1280px | Desktops |
| `2xl` | 1536px | Large screens |

### Mobile-First Approach

All components are designed mobile-first:
1. Start with mobile layout
2. Add breakpoints for larger screens
3. Test on actual devices

### Responsive Components

**Grid Component**: Automatically adjusts columns
```blade
<x-grid :responsive="true">
    <!-- 1 column on mobile, up to 6 on desktop -->
</x-grid>
```

**Container Component**: Responsive max-widths
```blade
<x-container size="xl">
    <!-- Responsive container -->
</x-container>
```

---

## ♿ Accessibility

### WCAG 2.1 AA Compliance

All components meet WCAG 2.1 AA standards:

#### Color Contrast
- **Normal text**: 4.5:1 contrast ratio
- **Large text**: 3:1 contrast ratio
- **Interactive elements**: 3:1 contrast ratio

#### Keyboard Navigation
- All interactive elements are keyboard accessible
- Logical tab order
- Visible focus indicators
- Skip links for navigation

#### Screen Reader Support
- Semantic HTML elements
- ARIA labels and descriptions
- Live regions for dynamic content
- Alt text for images

#### Focus Management
- Clear focus indicators
- Focus trapping in modals
- Focus restoration after closures

### Accessibility Components

**Accessible Wrapper**: ARIA attributes support
```blade
<x-accessible-wrapper 
    role="region" 
    aria-label="Product list"
    aria-describedby="product-help"
>
    <!-- Content -->
</x-accessible-wrapper>
```

---

## 🎭 Animations & Transitions

### Animation Types

**Fade In**: Smooth opacity transition
```html
<div class="fade-in">Content</div>
```

**Scale In**: Grow from center
```html
<div class="scale-in">Content</div>
```

**Slide In**: Horizontal entry
```html
<div class="slide-in-right">Content</div>
```

**Loading States**: Skeleton loading
```html
<div class="loading-skeleton">Loading...</div>
```

### Transition Utilities

**All Properties**: Smooth transitions
```html
<div class="transition-all">Content</div>
```

**Specific Properties**: Targeted transitions
```html
<div class="transition-colors">Content</div>
```

### Performance

- **Hardware Acceleration**: Use transform and opacity
- **Reduced Motion**: Respects user preferences
- **Duration**: Fast (150-300ms) for responsiveness

---

## 📐 Layout Patterns

### Container Pattern

Use container components for consistent max-widths:

```blade
<x-container size="2xl">
    <h1>Page Title</h1>
    <!-- Content -->
</x-container>
```

### Grid Pattern

Use grid components for responsive layouts:

```blade
<x-grid :cols="3" gap="lg" :responsive="true">
    <x-card>Item 1</x-card>
    <x-card>Item 2</x-card>
    <x-card>Item 3</x-card>
</x-grid>
```

### Stack Pattern

Vertical spacing with consistent gaps:

```blade
<div class="space-y-4">
    <x-card>Item 1</x-card>
    <x-card>Item 2</x-card>
    <x-card>Item 3</x-card>
</div>
```

---

## 🎯 Usage Guidelines

### Do's

1. **Use design tokens** instead of hardcoded values
2. **Follow mobile-first** responsive design
3. **Test accessibility** with keyboard and screen reader
4. **Use semantic HTML** elements
5. **Provide feedback** for all user actions
6. **Keep animations** subtle and purposeful

### Don'ts

1. **Don't use** hardcoded colors or spacing
2. **Don't skip** mobile testing
3. **Don't ignore** keyboard navigation
4. **Don't use** decorative animations
5. **Don't rely** on color alone for meaning
6. **Don't create** custom components when existing ones work

---

## 📚 Component Examples

### Form with Validation

```blade
<form>
    <x-input 
        type="text" 
        name="productName" 
        label="Product Name" 
        :error="$errors->productName" 
        required 
    />
    
    <x-input 
        type="number" 
        name="stock" 
        label="Stock Quantity" 
        hint="Enter available quantity"
        required 
    />
    
    <x-button variant="primary" type="submit">
        Save Product
    </x-button>
</form>
```

### Data Table with Actions

```blade
<x-table striped hover>
    <thead>
        <tr>
            <th>Product</th>
            <th>Stock</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
        <tr>
            <td>{{ $product->productName }}</td>
            <td>{{ $product->stock }}</td>
            <td>{{ $product->price }}</td>
            <td>
                <x-button variant="secondary" size="sm">Edit</x-button>
            </td>
        </tr>
        @endforeach
    </tbody>
</x-table>
```

### Card Grid Layout

```blade
<x-container size="xl">
    <h1 class="text-2xl font-bold mb-6">Products</h1>
    
    <x-grid :cols="3" gap="lg" :responsive="true">
        @foreach($products as $product)
        <x-card variant="default" padding="md" shadow="md">
            <h3 class="font-semibold text-lg">{{ $product->productName }}</h3>
            <p class="text-gray-600 dark:text-gray-400">Stock: {{ $product->stock }}</p>
            <div class="mt-4">
                <x-button variant="primary" size="sm">View Details</x-button>
            </div>
        </x-card>
        @endforeach
    </x-grid>
</x-container>
```

---

## 🔧 Customization

### Adding New Colors

Add to `design-tokens.css`:

```css
:root {
    --color-brand-primary: #your-color;
    --color-brand-secondary: #your-color;
}
```

### Creating New Components

Follow the component pattern:
1. Create in `resources/views/components/`
2. Use design tokens
3. Include dark mode support
4. Add accessibility features
5. Document usage

### Modifying Spacing

Update spacing scale in `design-tokens.css`:

```css
:root {
    --spacing-md: 1.5rem; /* Changed from 1rem */
}
```

---

## 📖 Resources

### Files

- **Design Tokens**: `resources/css/design-tokens.css`
- **Animations**: `resources/css/animations.css`
- **Components**: `resources/views/components/`
- **Layout**: `resources/views/layouts/`

### Documentation

- **Component Usage**: See component files for examples
- **Design Tokens**: Reference token names in CSS
- **Best Practices**: Follow usage guidelines

---

## 🚀 Future Enhancements

### Planned Features

- [ ] Additional component variants
- [ ] Advanced animation library
- [ ] Theme customization API
- [ ] Component testing
- [ ] Design documentation site

### Feedback

Submit design feedback through:
- GitHub Issues
- Design Review Process
- User Testing Sessions

---

**Version**: 1.0  
**Last Updated**: 2026-08-10  
**Maintained By**: Development Team