# Phase 24 UX Audit

**Date:** 2026-06-05  
**Phase:** 24 — UX Polish  
**Scope:** Dashboard, History, Billing, Settings, Global layout

---

## Dashboard States

### upload (empty — no file selected)

**Current behavior:**  
Dashed dropzone with upload icon, "Drop your file here" heading, "PNG, JPG, WEBP and PDF supported in beta" subtitle, "Choose file" button, privacy note. Alpine drag-and-drop highlight works.

**Problem:**  
- The "Choose file" button label disappears and is replaced by "Uploading…" during Livewire upload (`wire:loading`/`wire:target="upload,storeUpload"`) — this is already implemented.  
- The file `<input>` has no explicit `<label>` element with a `for` attribute; the label is a wrapping `<label>` tag with no visible text — screen readers may announce the button text but the association is implicit.  
- No `aria-label` on the drop zone container itself.

**Planned Phase 24 task:** CONV-383 (upload loading), CONV-393 (accessibility labels)

---

### upload (file uploaded — summary)

**Current behavior:**  
Shows file chip (icon, name, size/extension), "Replace" and "Remove" buttons, "Choose format" CTA.

**Problem:**  
- "Replace" and "Remove" are plain text buttons without `aria-label` to distinguish them from each other for screen readers.  
- No toast feedback after successful upload.

**Planned Phase 24 task:** CONV-390 (toast on upload success), CONV-393 (accessibility)

---

### format (target selection)

**Current behavior:**  
Back button, file chip, format cards grid (responsive 1→2→3 col). Cards have `wire:loading.attr="disabled"` and `wire:target="selectTargetFormat"`. Spinner + "Loading converter settings…" shown while loading. Empty state shown if no targets.

**Problem:**  
- `wire:loading` text and spinner are present, but the test in CONV-384 will verify the exact `wire:target` attribute is in the rendered HTML.  
- Card `<button>` elements do not indicate which one is selected after hover — only hover color change, no selected/active state badge.  
- No toast when target is selected (not strictly required, but noted).

**Planned Phase 24 task:** CONV-384/385 (target loading state test + impl), CONV-393 (focus ring already on `.ca-focus-ring`)

---

### settings (options form)

**Current behavior:**  
Back button, heading "Settings for X to Y", dynamic options form (segmented, select, toggle, range, color, number fields), estimated cost row, "Continue" button.

**Problem:**  
- Dynamic field components (`fields/segmented.blade.php`, etc.) need audit for explicit `<label>` associations with `for`/`id`.  
- "Continue" button has no loading/disabled state — if the user clicks rapidly, multiple Livewire requests may fire.  
- Estimated cost shows `null` fallback text "Credit cost will be calculated before conversion." — this is acceptable UX but could be clearer.

**Planned Phase 24 task:** CONV-393 (labels in dynamic fields), CONV-395 (responsive form layout)

---

### convert (ready to convert)

**Current behavior:**  
Back button, "Ready to convert X to Y" panel, optional error block (insufficient credits + buy CTA), "Convert Now" button with `wire:loading` states ("Convert Now" / "Starting…") and `wire:loading.attr="disabled"`.

**Problem:**  
- Double-submit guard in the Livewire component already partially exists: `if ($this->step === 'converting' || $this->currentConversionJobId !== null) { return; }`. However this guard relies on `$step` and `$currentConversionJobId` being already updated before a second request fires — a rapid double click could bypass it. CONV-386 test will verify.  
- No toast dispatched on conversion start.

**Planned Phase 24 task:** CONV-386/387 (double submit test + guard), CONV-390 (toast on convert start)

---

### converting (polling)

**Current behavior:**  
Spinning circle, "Converting your file" heading, source→target format label, "Please keep this page open…" note. `wire:poll.2s="refreshConversionStatus"` active.

**Problem:**  
- No way to cancel a running conversion from the UI.  
- Timeout after 60 poll cycles (2 min) transitions to `failed` state — this behavior is correct but not explained to the user while waiting.

**Planned Phase 24 task:** No change needed for polling mechanics. CONV-397 smoke test will confirm state renders.

---

### completed

**Current behavior:**  
"Done! Your file is ready." panel with result filename, Download link (styled as button), "Change settings", "Convert another file" CTAs. Expired variant shows "This result has expired" with "Convert another file" link.

**Problem:**  
- Download is an `<a>` element styled like a button but without `role="button"` — fine for navigation semantics, but should have an accessible name beyond "Download" if possible (e.g. "Download result.jpg").  
- No toast dispatched on transition to `completed` step.

**Planned Phase 24 task:** CONV-390 (toast on completion), CONV-393 (accessible download label)

---

### failed

**Current behavior:**  
Red error box "Conversion failed", "Change settings" and "Try another file" buttons.

**Problem:**  
- No toast dispatched on failure (inline error box is present, which is correct; toast would be additive).  
- "Change settings" and "Try another file" buttons lack aria context.

**Planned Phase 24 task:** CONV-390 (toast on failure where applicable)

---

## Tables

### Recent Conversions (dashboard, non-empty)

**Current behavior:**  
Search input, status filter `<select>`, table with 7 columns: File Name, From, To, Size, Date, Status, Actions. Actions: Download link, "Convert again" button, Star/Starred toggle button. Expiration label shown above actions.

**Problem:**  
- Status badge uses color only (inline class string via `statusBadgeClasses()`). No text alternative beyond the status word itself — color + text is acceptable, but the badge is a raw `<span>` not a `<x-badge>` component, unlike the history table.  
- "Star" / "Starred" button has no `aria-label` to clarify which row it applies to.  
- "Convert again" button has no `aria-label` for which file/job.  
- Download link is plain text — no `aria-label`.  
- Table has no `<caption>` or `aria-label`.  
- Search `<input>` has `placeholder` but no `<label>`.  
- Status `<select>` has no `<label>`.  
- On narrow screens, 7 columns will overflow — no responsive handling.

**Planned Phase 24 task:** CONV-393 (aria-labels), CONV-396 (responsive tables)

---

### Recent Conversions (dashboard, empty)

**Current behavior:**  
"No conversions yet" + "Upload a file to start converting" in dashed border box. No CTA button.

**Problem:**  
- Missing primary action button ("Start conversion" / link to upload area).  
- Copy is acceptable but lacks actionable next step.

**Planned Phase 24 task:** CONV-391 (dashboard empty states)

---

### Conversion History (history page, non-empty)

**Current behavior:**  
5 filter controls (search, status, source format, target format, date range), table with 9 columns: File Name, From, To, Size, Created, Completed, Status (badge component), Credits, Actions (Download, Convert Again). Pagination.

**Problem:**  
- Filter inputs have placeholder text but no `<label>` elements — accessibility gap.  
- Date filter inputs (`<input type="date">`) have no labels.  
- "Convert Again" button has no `aria-label` identifying the row.  
- 9 columns will overflow significantly on small screens.  
- No `<caption>` on the table.

**Planned Phase 24 task:** CONV-393 (labels), CONV-396 (responsive)

---

### Conversion History (history page, empty)

**Current behavior:**  
"No conversions found" + "Try adjusting your filters or upload a file to start converting". No CTA.

**Problem:**  
- "No conversions found" implies there are conversions but filters hide them. A fresh user sees this even with no filters — copy should differentiate between "no results" (filters active) and "no conversions yet" (no jobs at all).  
- No CTA link to start a conversion.

**Planned Phase 24 task:** CONV-392 (history empty state)

---

### Credit Transactions (billing page, empty)

**Current behavior:**  
Card with "No credit transactions yet" + "Your credit grants and conversion charges will appear here."

**Problem:**  
- This empty state is already reasonably informative.  
- No CTA to buy credits or start converting. Could add "Buy credits" link.

**Planned Phase 24 task:** CONV-392 (billing empty state polish)

---

### Credit Transactions (billing page, non-empty)

**Current behavior:**  
Table with Date, Type (badge), Reason, Amount (colored), Balance after. Pagination.

**Problem:**  
- Color is not the only indicator (text "+" / "−" is present) — acceptable.  
- Table has no `aria-label` or `<caption>`.  
- On mobile, 5 columns + amounts may overflow.

**Planned Phase 24 task:** CONV-396 (responsive)

---

## Global

### User Dropdown

**Current behavior:**  
Button trigger with avatar initials, name, email (hidden on mobile), chevron. `aria-expanded`, `aria-haspopup="menu"` present. Escape closes. Click outside closes. Menu items: Dashboard (link), Billing (disabled button), Settings (disabled button), Log out (form submit).

**Problem:**  
- Billing and Settings menu items are disabled placeholder buttons — users will see them as real nav items and be confused.  
- No `aria-label` on the trigger button itself (the button contains visible text, so this is borderline — acceptable but the initials span has `aria-hidden`).  
- Focus is not programmatically moved into the menu when it opens — Tab from trigger will navigate away, not into the menu items.  
- Disabled menu buttons use `disabled` HTML attribute + `aria-disabled="true"` correctly but have no tooltip explaining when they'll be available.

**Planned Phase 24 task:** CONV-394 (keyboard interaction), CONV-393 (focus states)

---

### Header Nav

**Current behavior:**  
Logo link, hidden-on-mobile nav (Dashboard, Tools placeholder, Pricing placeholder), language button (no-op), gift button (no-op), notifications button (hardcoded "3" badge), user dropdown.

**Problem:**  
- "Tools" and "Pricing" nav links are `href="#"` — dead links.  
- Language, gift, and notifications buttons are no-ops with no indication.  
- Notification badge shows hardcoded "3" — misleading.  
- Nav is completely hidden on mobile (`.hidden md:flex`) with no hamburger menu.  
- No mobile nav at all — on small screens only the user dropdown is accessible.

**Planned Phase 24 task:** CONV-395 (responsive layout), CONV-393 (accessibility review of header buttons)

---

### Footer Help Cards

**Current behavior:**  
3 cards: Help Center, Contact Support, Refer a Friend — all are interactive-style cards with no links/actions.

**Problem:**  
- Cards use `variant="interactive"` (hover state) but have no `href`, `wire:click`, or any action — clicking does nothing.  
- No accessible role — they appear interactive but are inert `<div>`s.  
- "Refer a Friend" is mentioned but the referral system is not implemented.

**Planned Phase 24 task:** CONV-395 (review footer in responsive pass), CONV-393 (remove interactive variant if no action, or note as future scope)

---

### Focus States

**Current behavior:**  
`.ca-focus-ring` utility class is used on some interactive elements. Not consistently applied everywhere (table action buttons, pagination links, some form inputs).

**Problem:**  
- Inconsistent application.  
- Table row action buttons (Download, Convert again, Star, Convert Again in history) lack `.ca-focus-ring` class.

**Planned Phase 24 task:** CONV-393 (focus states audit and fix)

---

### Mobile / Tablet Layout

**Current behavior:**  
- Dashboard: converter card is full-width, format grid goes `1→2→3` columns with `sm:grid-cols-2 lg:grid-cols-3`. Recent conversions table has no `overflow-x-auto` wrapper.  
- History page: table has no responsive wrapper.  
- Header: nav hidden on mobile, no hamburger alternative.  
- App layout: `max-w-7xl px-6 py-10` — padding is fine, but inner tables can overflow.

**Problem:**  
- Recent conversions table (7 columns) will overflow horizontally on phones without a scroll container.  
- History table (9 columns) will overflow more severely.  
- No mobile navigation (hamburger or bottom bar).  
- Footer help cards may not stack well on very small screens (they use `md:grid-cols-3` — below `md` they stack, which is correct).

**Planned Phase 24 task:** CONV-395 (responsive layout pass), CONV-396 (responsive tables)

---

## Accessibility Risks Summary

| Area | Risk | Task |
|---|---|---|
| File input | No explicit `<label for>` | CONV-393 |
| Recent conversions actions | No `aria-label` identifying row | CONV-393 |
| History table actions | No `aria-label` identifying row | CONV-393 |
| Table filter inputs | No `<label>` | CONV-393 |
| Status badge (recent conversions) | Raw `<span>`, not `<x-badge>` | CONV-393 |
| User dropdown focus | Focus not moved into menu on open | CONV-394 |
| Disabled dropdown items | No explanation of when available | CONV-394 |
| Footer help cards | Interactive variant but no action | CONV-393/395 |
| Notification badge | Hardcoded count | Out of scope |
| Convert button | Loading text present, guard needs test | CONV-386/387 |
| Upload button | Loading text present, test coverage | CONV-382/383 |

---

## Phase 24 Final Check

- [ ] Phase 24 tasks CONV-381–CONV-397 completed  
- [ ] Upload loading state visible and tested  
- [ ] Target selection loading state visible and tested  
- [ ] Convert Now loading state present, double-submit guard tested  
- [ ] Toast infrastructure in place  
- [ ] Upload/conversion events dispatch toasts  
- [ ] Dashboard empty states have CTA  
- [ ] History empty state improved  
- [ ] Billing empty states reviewed  
- [ ] Icon-only actions have `aria-label`  
- [ ] File input has accessible label  
- [ ] Focus states consistent across interactive elements  
- [ ] User dropdown keyboard-accessible  
- [ ] Dashboard layout usable on desktop/tablet/mobile  
- [ ] Tables do not overflow destructively  
- [ ] Final smoke tests pass  
- [ ] `composer test` passes  
- [ ] `composer lint` passes  
- [ ] `npm run build` passes  
