# The Hollywood Reporter UK (WordPress VIP / PMC Larva Architecture)

This repository contains the complete custom theme and plugins for **The Hollywood Reporter UK**, built strictly to WordPress VIP and Penske Media Corporation (PMC Larva) engineering standards.

---

## 1. Directory Structure

```
.
├── wp-content/
│   ├── plugins/
│   │   ├── thr-core/                  # Core editorial data model, CPTs, taxonomies, permalinks
│   │   └── thr-importer/              # One-click structure seeder (26 categories, 25 topics, etc.)
│   │
│   └── themes/
│       └── thr-theme/                 # High-performance, pixel-precise frontend publication theme
├── docs/                              # Project reference and site specifications
├── .github/
│   └── workflows/
│       └── deploy-staging.yml         # GitHub Actions deployment to Hostinger
├── .gitignore
├── README.md
└── AGENTS.md                          # Coding guidelines and security standards
```

---

## 2. Core Components

### `thr-core` Plugin
- **Custom Post Types:**
  - `thr_list` (`/lists/` archive)
  - `thr_gallery` (`/gallery/{slug}-{id}/` with custom ID rewrite)
  - `thr_video` (`/video/` archive with YouTube facade player)
- **Taxonomies:**
  - `vertical` (base `/e/`, e.g. `/e/heat-vision/`)
  - `vcategory` (base `/vcategory/`)
  - Native Category relabeled to **Sections** with base `/c/`
  - Native Tags relabeled to **Topics** with base `/t/`
- **Permalinks & Routing:**
  - Strict primary category resolution for articles: `/%category%/%postname%-%post_id%/`
  - Automatic 301 canonical redirects on stale ID matches
  - Clean search route: `/results/?q=...`
- **Editorial Fields & Panels:**
  - Dek, Kicker override, Article Types (`standard`, `review`, `feature`, `cover-story`, `interview`, `podcast`)
  - Review summary data: Subject, The Bottom Line, Director, Cast, Venue, Rating + running time, Full credits
  - Featured story and Breaking news flags
  - Image attribution field (`thr_credit`) on media attachments
- **Scalability & Security:**
  - Cache-safe Page View Beacon (`POST /wp-json/thr/v1/track-view`) with transient caching
  - Live search autocomplete endpoint (`GET /wp-json/thr/v1/search?q=...`)
  - Honeypot and rate-limited anonymous tipline form handler
  - Complete JSON-LD Schema (`NewsArticle`, `Review`, `VideoObject`, `Organization`)

### `thr-importer` Plugin
- **One-Click Site Structure Seeder:**
  - Reads `data/site-structure.json`
  - Seeds **26 Categories** (6 parents + 20 children)
  - Seeds **25 Topics** (native tags)
  - Seeds **7 Verticals** with brand colors & taglines (Heat Vision `#6442AC`, Live Feed `#008080`, The Race `#956E37`, etc.)
  - Seeds **12 Video Categories**
  - Seeds **11 Pages** (Home, Masthead, Tip Line, Terms of Use, Privacy Policy, etc.)
  - Accessible via `Tools → THR Importer` or WP-CLI (`wp thr seed --only=structure`)
  - Completely idempotent: zero duplicates on repeated runs

### `thr-theme` Theme
- **PMC Larva Design System Tokens:**
  - Brand Red: `#D92128`
  - Review Honey Box: `#F7F1E7`
  - Typography scale with responsive scaling at 1000px breakpoint
  - Free self-hosted / Google Fonts preset: **Newsreader** (Serif) + **Karla** (Sans)
- **Layout Grid & Templates:**
  - 1260px container, 660px content area + 300px sticky right rail
  - Top Story card with centered "TOP STORY" badge and 16:9 framed media
  - Review summary box with "The Bottom Line" callout
  - Off-canvas mega menu drawer and real-time live search expand

---

## 3. Local Setup & Activation

1. Link or copy `wp-content/` into your LocalWP or WordPress installation.
2. In WordPress Admin (`/wp-admin`):
   - **Plugins** → Activate **THR Core** and **THR Importer**.
   - **Appearance → Themes** → Activate **The Hollywood Reporter UK**.
3. Seed the site taxonomy structure:
   - Navigate to **Tools → THR Importer**.
   - Click **"Seed / Sync Site Structure Now"**.
4. Settings:
   - Verify **Settings → Permalinks** is set to Custom Structure `/%category%/%postname%-%post_id%/`, Category base `c`, Tag base `t`.
   - Configure brand options under **Settings → THR**.
