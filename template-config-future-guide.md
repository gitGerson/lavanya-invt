# Future Guide (#4) — Template-Specific Config (Per Template Settings)
(For later implementation)

This guide is intentionally a “design guide” only. It helps you plan how to support:
- Section toggles (enable/disable sections per invitation)
- Per-template extra assets (ornaments, frames, separators)
- Theme customization (colors, fonts, spacing)
WITHOUT falling back to unstructured key-value content.

---

## A) What template config usually needs

1) **Section visibility**
- show/hide gallery
- show/hide gifts
- show/hide countdown
- show/hide rsvp
- show/hide map

2) **Theme**
- primary/secondary colors
- typography (font families)
- spacing scale
- border radius

3) **Template extra assets**
- ornaments
- frames
- separators
- section icons

---

## B) Recommended Pattern (Light config layer)

### Option 1 (simple): JSON column on invitations
Add `invitations.template_settings` (json).

Pros:
- fastest
- no extra tables
Cons:
- harder to query
- no referential integrity

Example JSON:
```json
{
  "sections": {
    "gallery": true,
    "gifts": true,
    "rsvp": true
  },
  "theme": {
    "primary": "#1f2937",
    "font_heading": "Playfair Display",
    "font_body": "Inter"
  },
  "assets": {
    "ornament_top": 123,
    "ornament_bottom": 124
  }
}
```

### Option 2 (more structured): invitation_sections table
Tables:
- `template_sections` (defines what sections exist for a template)
- `invitation_sections` (per invitation: enabled, order, title overrides)

Pros:
- clean
- queryable
- supports reorder/title overrides
Cons:
- more tables and admin UI

Minimal columns:
- `template_sections`: `template_id`, `key`, `default_enabled`, `default_sort`
- `invitation_sections`: `invitation_id`, `key`, `enabled`, `sort_order`, `title_override`

### Option 3 (assets): template_assets + invitation_asset_overrides
- `template_assets`: per template asset slots (e.g. ornament_top)
- `invitation_asset_overrides`: per invitation pick an asset for the slot

---

## C) How to integrate into TemplateRenderer later

- Load `template_settings` or `invitation_sections`
- Merge into payload:
  - `$settings`
  - `$sections`
  - `$theme`

Then templates can do:
```blade
@if(($settings['sections']['gallery'] ?? true) && count($dto['gallery'] ?? []))
  ...
@endif
```

---

## D) Filament UI later

- A “Template Settings” tab in Invitation edit:
  - toggles for sections
  - color pickers for theme
  - file uploads/select assets for ornaments

---

## E) Recommendation

Start with **Option 1 (JSON on invitations)** for speed.
Migrate to Options 2/3 once you have:
- multiple templates
- many customizations
- need section reordering
