# Coding & Architecture Standards — The Hollywood Reporter UK

This project adheres strictly to WordPress VIP and Penske Media Corporation (PMC Larva) coding standards.

---

## 1. Security & Sanitization

1. **Non-negotiable Escaping on Output:**
   - Always escape data late at the point of output:
     - `esc_html()` or `esc_html_e()` for plain text.
     - `esc_attr()` or `esc_attr_e()` for HTML element attributes.
     - `esc_url()` for URLs rendered in attributes or links.
     - `wp_kses_post()` for user-generated or editorial HTML (e.g. rich text bios or credits).

2. **Data Sanitization on Input:**
   - Always sanitize incoming `$_POST`, `$_GET`, and REST parameters:
     - `sanitize_text_field()` for single-line strings.
     - `sanitize_textarea_field()` for plain multi-line inputs.
     - `esc_url_raw()` for database-stored URLs.
     - `sanitize_key()` for identifiers, taxonomy slugs, and layout names.
     - `absint()` / `(int)` for numeric IDs.

3. **CSRF & Capability Verification:**
   - Every admin action and POST form must verify a nonce with `wp_verify_nonce()` or `check_admin_referer()`.
   - Always enforce capability checks before persisting settings: `current_user_can( 'manage_options' )` or `current_user_can( 'edit_posts' )`.

---

## 2. Prefixes & Namespacing

- All custom functions, global variables, and action/filter hooks must use the prefix: `thr_` (lowercase).
- All classes must use: `THR_` (uppercase).
- All custom post types: `thr_list`, `thr_gallery`, `thr_video`.
- All CSS classes: `.thr-` (e.g. `.thr-container`, `.thr-card`, `.thr-review-box`).

---

## 3. High Performance & Scalability

1. **Zero External Blocking Dependencies:**
   - No jQuery in frontend code. Use clean vanilla ES6+ with DOMContentLoaded and debouncing.
   - All frontend JS bundles must remain under 25KB and be deferred.

2. **Object Caching & Transients:**
   - Heavy aggregations (e.g. Most Popular calculations) must utilize transients (`set_transient`, `get_transient`).
   - Page view counting must use cache-safe client-side beacons (`navigator.sendBeacon`) to preserve full-page cache efficiency on LiteSpeed and CDN edge servers.

3. **Database Efficiency:**
   - Always use `no_found_rows => true` on subqueries and rail widgets where pagination is not needed.
   - Enforce page-level deduplication via `THR_Query::$displayed_ids` to eliminate redundant article display across modules.

---

## 4. Design Tokens & Styling

- Never hardcode arbitrary hex colors in component templates. Use CSS variables defined in `assets/css/variables.css`:
  - Primary Brand Red: `var(--brand-primary)` (`#D92128`)
  - Review Background: `var(--honey-light)` (`#F7F1E7`)
  - Vertical Colors: term meta dynamically applied or vertical variables (`--vert-heat-vision`, etc.)
- Use responsive units with desktop scaling enabled at `>= 1000px`.
