---
name: Sash Enterprise Admin
colors:
  surface: '#f9f9ff'
  surface-dim: '#d1daf1'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f1f3ff'
  surface-container: '#e8eeff'
  surface-container-high: '#dfe8ff'
  surface-container-highest: '#dae2fa'
  on-surface: '#121c2c'
  on-surface-variant: '#474555'
  inverse-surface: '#283042'
  inverse-on-surface: '#ecf0ff'
  outline: '#777587'
  outline-variant: '#c8c4d8'
  surface-tint: '#5242e2'
  primary: '#5242e2'
  on-primary: '#ffffff'
  primary-container: '#6c5ffc'
  on-primary-container: '#030026'
  inverse-primary: '#c5c0ff'
  secondary: '#006b5b'
  on-secondary: '#ffffff'
  secondary-container: '#74f5da'
  on-secondary-container: '#00705f'
  tertiary: '#b51361'
  on-tertiary: '#ffffff'
  tertiary-container: '#d7347a'
  on-tertiary-container: '#ffffff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e3dfff'
  primary-fixed-dim: '#c5c0ff'
  on-primary-fixed: '#130067'
  on-primary-fixed-variant: '#3921ca'
  secondary-fixed: '#77f8dd'
  secondary-fixed-dim: '#57dbc1'
  on-secondary-fixed: '#00201a'
  on-secondary-fixed-variant: '#005144'
  tertiary-fixed: '#ffd9e2'
  tertiary-fixed-dim: '#ffb1c7'
  on-tertiary-fixed: '#3e001d'
  on-tertiary-fixed-variant: '#8e0049'
  background: '#f9f9ff'
  on-background: '#121c2c'
  surface-variant: '#dae2fa'
typography:
  display:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  display-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
    letterSpacing: -0.015em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.005em
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Inter
    fontSize: 10px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.04em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system embodies modern corporate refinement tailored for data-dense, mission-critical enterprise SaaS applications. The visual identity balances professional restraint with high-energy analytical accents, projecting authority, operational clarity, and precision.

The style fuses Corporate Modernism with subtle soft-surface tactility:
- Surfaces lean on cool, neutral gray foundations layered with crisp, pristine white content cards.
- The interface creates visual rhythm through vibrant chromatic semantic accents, preventing enterprise cognitive fatigue while maintaining strict typographic structure.
- Micro-interactions are deliberate, frictionless, and fluid (150ms–200ms ease-out transitions), avoiding theatrical ornamentation in favor of purposeful feedback.

## Colors

The palette establishes high readability and visual categorization across complex enterprise dashboards. 

### Palette Application Rules
- **Base Canvas:** Background operates strictly on `#f2f4f9` to reduce optical glare and separate floating cards.
- **Card Surfaces:** Solid pure white (`#ffffff`) for modular content containment.
- **Primary Violet (`#6c5ffc`):** Reserved for core brand anchors, primary action triggers, active navigation items, and focus indicators.
- **Secondary Teal (`#09ad95`):** Standard operational success, positive financial shifts, and validated states.
- **Cyan (`#03c892`):** Secondary metrics, active tracking indicators, and real-time network states.
- **Amber (`#f7b924`):** Warnings, pending syncs, and intermediate state markers.
- **Coral Pink (`#fc5296`):** Critical alarms, negative variance, high-priority notifications, and destructive actions.
- **Sky Blue (`#05c3fb`):** Informational callouts, active filters, and neutral telemetry points.
- **Neutral Stack:** Text layers step from Slate 900 (`#1e2538`) for headlines, Slate 700 (`#4d5875`) for body, down to Slate 400 (`#9aa5b8`) for placeholder text and disabled states. Borders rely on an ultra-subtle Slate tint (`#e9edf4`).

## Typography

The type hierarchy pairs **Plus Jakarta Sans** for structural headers and fluent narrative readability with **Inter** for compact, high-legibility interface controls, metric callouts, and data table rows.

- **Numerics & Tabular Data:** All metric cards, data grids, and telemetry points must apply `font-feature-settings: "tnum" 1, "cv05" 1` to ensure aligned vertical tabular digits.
- **Hierarchy Enforcement:** Headers maintain strict negative letter-spacing for tight structural punch. UI labels below 12px utilize slightly expanded tracking (+0.02em to +0.04em) to maintain quick scannability across high-density layouts.

## Layout & Spacing

The structural layout utilizes a 12-column responsive fluid grid pinned to an 8px base spatial unit, paired with an optional 4px sub-grid for tight control positioning.

### Layout Breakpoints & Rules
- **Desktop (≥ 1200px):** 12-column grid, 24px (`1.5rem`) gutters, 32px (`2rem`) canvas margin. Left vertical navigation bar fixed at 260px width.
- **Tablet (768px – 1199px):** 12-column fluid grid, 20px (`1.25rem`) gutters, 24px (`1.5rem`) outer margin. Navigation collapes to an 80px iconized slim rail.
- **Mobile (< 768px):** 4-column grid, 16px (`1rem`) gutters, 16px (`1rem`) margin. Top navigation header with off-canvas slide-out drawer.

Cards inside dashboard layouts use standard inner padding of `space-lg` (24px) for desktop and `space-md` (16px) for compact viewports.

## Elevation & Depth

Visual hierarchy leverages soft, multi-layered ambient drop shadows rather than heavy structural borders, retaining an airy and high-end feel.

### Elevation Hierarchy
- **Level 0 (Flat / Canvas):** Neutral `#f2f4f9` canvas base. No shadow.
- **Level 1 (Card / Default):** Used for dashboard cards, tables, and standard content sections.
  - Shadow: `0px 4px 16px -2px rgba(108, 95, 252, 0.04), 0px 2px 6px -1px rgba(22, 28, 45, 0.05)`
  - Border: 1px solid `#e9edf4`
- **Level 2 (Hover / Active Cards):** Lifted card state on interaction or active selection.
  - Shadow: `0px 10px 24px -4px rgba(108, 95, 252, 0.08), 0px 4px 12px -2px rgba(22, 28, 45, 0.06)`
- **Level 3 (Dropdowns / Popovers / Tooltips):** Floating UI controls.
  - Shadow: `0px 16px 32px -4px rgba(22, 28, 45, 0.1), 0px 6px 16px -2px rgba(22, 28, 45, 0.06)`
  - Border: 1px solid `#e2e7f1`
- **Level 4 (Modals / Slide-over Drawers):** High-priority focus overlays.
  - Shadow: `0px 24px 48px -12px rgba(22, 28, 45, 0.18)`
  - Backdrop: `rgba(15, 23, 42, 0.45)` with `backdrop-filter: blur(4px)`

## Shapes

The geometric signature uses unified 12px (`0.75rem`) radii for core functional surfaces, providing modern visual comfort while preserving efficient space utilization in high-density data views.

- **Content Cards, Panels, & Tables:** Exactly 12px (`0.75rem`) corner radius.
- **Buttons, Form Inputs, & Select Pickers:** 8px (`0.5rem`) corner radius.
- **Badges, Status Chips, & Sparkline Tooltips:** Fully pill-shaped (`9999px`) for quick scanning.
- **Modals & Dialogs:** 16px (`1rem`) corner radius.

## Components

### Buttons
- **Primary:** Solid `#6c5ffc` background, white label, subtle shadow `0 4px 12px rgba(108, 95, 252, 0.25)`. Hover darkens to `#5a4bfa` with translateY(-1px).
- **Secondary / Soft:** 10% opacity tints of brand/accent colors (e.g., Violet soft: `rgba(108, 95, 252, 0.1)` with `#6c5ffc` text).
- **Outline:** Transparent background, 1px border in `#e2e7f1`, text in `#4d5875`. Hover to `#f8fafc`.

### Metric Cards & Sparklines
- **KPI Cards:** White surface, Level 1 shadow, 12px border-radius, 24px internal padding.
- **Layout:** Top row holds stat label and a 40x40px rounded icon container with a soft colored tint. Center row displays bold 28px display value. Bottom row pairs sparkline charts with an inline percentage pill (`+12.5%` in Teal or `-3.2%` in Coral Pink).
- **Sparklines:** Stroke width of 2.5px with an alpha gradient fill fading to zero at the baseline.

### Input Fields & Selects
- **Height & Spacing:** Standardized 42px height, 8px corner radius, 12px horizontal padding.
- **States:** Default border in `#e9edf4` on pure white background. Focus state transitions border to `#6c5ffc` with a soft outer ring: `box-shadow: 0 0 0 3px rgba(108, 95, 252, 0.15)`.

### Checkboxes & Radio Buttons
- Custom SVG controls with 6px radius (checkbox) or circular (radio). Unchecked: `#d8dee9` border. Checked: `#6c5ffc` solid fill with crisp white check/dot icon.

### Badges & Status Chips
- Pill-shaped (`rounded-full`), 24px height, padding: `2px 10px`, font: `label-sm` (Inter, uppercase tracking +0.04em).
- Rendered in soft duotone: background at 12% color opacity, text at 100% token intensity (e.g., Teal Badge: background `rgba(9, 173, 149, 0.12)`, text `#09ad95`).

### Data Tables
- Header row with subtle neutral background (`#f8fafc`), uppercase `label-sm` headers with sort glyphs.
- Row zebra striping omitted; alternating state defined through 1px horizontal dividers (`#f0f3f8`) and a 1-second hover fade to `#fbfcfe`.