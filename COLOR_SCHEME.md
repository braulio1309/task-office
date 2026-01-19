# Color Scheme Configuration

This document describes the color scheme used throughout the application and provides guidance for future updates.

## Current Color Scheme (Inmobipina Brand)

Based on the Inmobipina logo, the system uses the following brand colors:

### Primary Colors

- **Brand Orange** (Primary): `#D96017`
  - Main brand color used for buttons, links, highlights, and primary actions
  - Replaces the previous blue color (#4466F2)

- **Brand Green** (Secondary): `#14632E`
  - Secondary brand color used for accents and charts
  - Provides visual variety while maintaining brand consistency

### Supporting Colors

- **Success**: `#46c35f`
- **Info**: `#38a4f8`
- **Warning**: `#FC6510`
- **Danger**: `#fc2c10`
- **Error**: `#cc3300`

## Where Colors Are Defined

### 1. SASS Variables (Main Configuration)
**File**: `resources/sass/core/_variables.scss`

```scss
// Brand Colors - Inmobipina Color Scheme
$brand-color: #D96017;              // Primary orange from logo
$brand-secondary-color: #14632E;    // Secondary green from logo
```

### 2. CSS Custom Properties (CSS Variables)
**File**: `resources/sass/core/_theme-colors.scss`

CSS custom properties are available for use in Vue components and stylesheets:

```css
:root {
  --brand-color: #D96017;              /* Primary brand color */
  --brand-secondary-color: #14632E;    /* Secondary brand color */
  --brand-color-10: rgba(217, 96, 23, 0.1);  /* 10% opacity */
  --brand-color-40: rgba(217, 96, 23, 0.4);  /* 40% opacity */
  --brand-color-50: rgba(217, 96, 23, 0.5);  /* 50% opacity */
}
```

**Usage in Vue components:**
```css
.my-element {
  color: var(--brand-color);
  background-color: var(--brand-color-10);
}
```

This file uses the variables from `_variables.scss` to generate CSS custom properties for both light and dark themes.

### 3. Brand-Specific Styles
**File**: `resources/sass/_brand.scss`

Contains brand-specific overrides for buttons, links, navigation, and other UI elements.

## Files Updated in This Change

### Backend (PHP)
1. `app/Services/App/Dashboard/AcademicDashboardService.php` - Chart colors
2. `app/Services/App/Dashboard/HrmDashboardService.php` - Chart colors
3. `app/Services/App/Dashboard/EcommerceDashboardService.php` - Chart colors
4. `app/Http/Controllers/TestingController.php` - Chart colors
5. `database/seeders/App/NotificationTemplateSeeder.php` - Email button colors

### Frontend (Vue.js)
1. `resources/js/app/Components/Views/Documents/Index.vue` - Border hover color
2. `resources/js/app/Components/Views/Demo/UI/Charts/Charts.vue` - Chart colors
3. `resources/js/app/Components/Views/Demo/Dashboard/POS.vue` - Background colors
4. `resources/js/app/Components/Views/Demo/SocialNetwork/others/NewComment.vue` - Button colors
5. `resources/js/app/Components/Views/Demo/Pages/PaymentMethodView.vue` - Payment gateway theme
6. `resources/js/core/components/datatable/helpers/SavedFilterList.vue` - Active/hover states

### Assets
1. `public/images/no_data.svg` - Illustration colors

## How to Update Colors in the Future

To update the brand colors throughout the entire system:

### Step 1: Update SASS Variables
Edit `resources/sass/core/_variables.scss` and change the color values:

```scss
$brand-color: #NEW_PRIMARY_COLOR;
$brand-secondary-color: #NEW_SECONDARY_COLOR;
```

### Step 2: Search for Hardcoded Colors
Run the following commands to find any hardcoded color references:

```bash
# Search for old primary color
grep -r "#D96017" --include="*.php" --include="*.vue" --include="*.js"

# Search for old secondary color
grep -r "#14632E" --include="*.php" --include="*.vue" --include="*.js"
```

### Step 3: Update Hardcoded References
Manually update any hardcoded color values found in:
- PHP service files (especially dashboard services with chart data)
- Vue component styles
- Email templates in database seeders
- SVG files

### Step 4: Rebuild Assets
Compile the SASS and JavaScript:

```bash
npm run production
# or for development
npm run dev
```

### Step 5: Test
- Check all pages, especially dashboards with charts
- Verify buttons, links, and hover states
- Test both light and dark themes
- Check email templates

## Best Practices

1. **Use SASS Variables**: Always prefer using `$brand-color` variable in SCSS files instead of hardcoding color values
2. **Use CSS Custom Properties**: In Vue component styles, use `var(--brand-color)` instead of hardcoded hex values for better maintainability
3. **Avoid Hardcoding in JavaScript**: For chart colors and dynamic styles in JavaScript/PHP, consider creating a configuration constant
4. **Use Opacity Variants**: Use pre-defined opacity variants like `var(--brand-color-10)` instead of manually adding alpha channels
5. **Chart Colors**: For charts, consider using a color palette array that includes both primary and secondary colors
6. **Accessibility**: Ensure sufficient contrast ratios (minimum 4.5:1 for text) when changing colors

## Color Palette for Charts

When creating charts, use this recommended palette that includes the brand colors:

```javascript
const chartColors = [
  '#D96017',  // Brand Orange (Primary)
  '#14632E',  // Brand Green (Secondary)
  '#f96868',  // Red
  '#46c35f',  // Success Green
  '#38a4f8',  // Info Blue
  '#6a008a',  // Purple
];
```

## Notes

- The system supports both light and dark themes
- Color variations (lighter/darker) are automatically calculated using SASS functions
- Some third-party components may need theme configuration (e.g., Razorpay payment gateway)
