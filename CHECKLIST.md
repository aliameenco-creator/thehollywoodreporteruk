# THR UK Build Checklist

Work top to bottom, ticking `[x]` as each item is done. Don't start a section until the one before it is complete.

- **Owner:** **YOU** = you do it manually · **AI** = Claude/Codex writes it · **BOTH** = AI builds it, you check it.
- **Full specs:** see [THR-BUILD-PLAN.md](THR-BUILD-PLAN.md); section numbers (§) refer to that file.

---

## PHASE 0: Before we start (YOU, ~1–2 hours)

Nothing gets coded until this section is done.

### 0.1 Install on your PC

| ✓ | Item | Done when |
|---|---|---|
| [ ] | **Git for Windows** | `git --version` works in the terminal |
| [ ] | **Node.js 20 LTS** | `node -v` shows v20+ |
| [ ] | **LocalWP** (localwp.com) | The app opens |
| [ ] | **VS Code** + Claude Code extension | Already done ✅ |
| [ ] | **GitHub CLI** (`gh`) + `gh auth login` | `gh auth status` shows you're logged in |

### 0.2 Accounts & hosting

| ✓ | Item | Done when |
|---|---|---|
| [ ] | **GitHub:** create a **private** repo, e.g. `thr-uk` | Repo URL ready |
| [ ] | **Hostinger:** Business or Cloud plan (needs SSH + WP-CLI + object cache) | hPanel login works |
| [ ] | Domain connected to Hostinger | Domain loads the Hostinger default page |
| [ ] | WordPress installed on the **production** domain (hPanel → Auto Installer) | `/wp-admin` login works |
| [ ] | Subdomain `staging.yourdomain.com` + a **separate** WordPress install | Staging `/wp-admin` login works |
| [ ] | Staging password-protected (hPanel → Password Protect Directories) | Browser asks for a password |
| [ ] | Both sites: Settings → Reading → "Discourage search engines" ✔ (until launch) | Ticked |
| [ ] | hPanel → Advanced → **SSH Access → Enable** | Note: host, port **65002**, username |
| [ ] | hPanel → PHP Configuration → **PHP 8.2 or 8.3** (both sites) | Set |
| [ ] | Install **Yoast SEO**, **Co-Authors Plus**, **LiteSpeed Cache** on both sites | Activated |

### 0.3 Brand & content inputs (send to AI or put in `docs/inputs/`)

| ✓ | Item | Notes |
|---|---|---|
| [ ] | **Site name** exactly as it should appear (e.g. "The Hollywood Reporter UK") | Used in titles, schema, footer |
| [ ] | **Logo SVG** (+ white version if any) | We will not recreate THR's logotype unless you are licensed to use it |
| [ ] | Favicon / app icon (512×512 PNG) | |
| [ ] | Social profile URLs (Facebook, Instagram, LinkedIn, Threads, TikTok, X, YouTube) | Any missing ones are hidden |
| [ ] | **Tip-line email** address | Receives tip-form submissions |
| [ ] | Contact email + company/legal name + address | Footer, masthead, schema |
| [ ] | **Newsletter provider** (Mailchimp / Brevo / other) + list or form URL | Wires up newsletter boxes |
| [ ] | Subscribe / magazine URL (if any) | "Get the Magazine" box + SUBSCRIBE link |
| [ ] | Legal page text: Terms, Privacy, Cookie Policy, Accessibility | Placeholder text until supplied |
| [ ] | Google accounts: **GA4** property + **Search Console** access | Needed on Day 4 |

### 0.4 Decisions (tick your choice)

| ✓ | Decision | Options |
|---|---|---|
| [ ] | Section labels | THR exact ("Film" label, `/c/movies/` slug) **(default)** · or localise |
| [ ] | UK-specific vertical(s)? | e.g. "London Calling" vertical: yes / no |
| [ ] | Fonts at launch | **Free (Newsreader + Karla)** (default) · Adobe Fonts (needs a Creative Cloud kit ID) |
| [ ] | Cookie consent tool (UK law) | Free option: **Complianz (free)** or **CookieYes (free tier)** |
| [ ] | Ads at launch | Empty labelled slots **(default)** · Google AdSense · Google Ad Manager |
| [ ] | `noai, noimageai` robots tag (THR uses it) | On / Off |

---

## PHASE 1 (DAY 1): Foundation, data model, seeded site

Use [the exact URL/category specification](docs/URL-AND-CATEGORY-STRUCTURE.md) and [the seed manifest](docs/site-structure.json) for the one-click structure action.

- [ ] AI: Validate and seed exactly 26 categories (6 parents + 20 children), 25 topics, 7 verticals, 12 video categories and 11 draft Pages from the manifest.
- [ ] AI: Add archive/Page heading and optional subtitle fields; enforce one H1 per template and distinguish article headline, SEO title, dek and kicker.
- [ ] AI: Implement primary-category permalink selection and gallery ID rewrite handling in thr-core.
- [ ] AI: Tools → THR Importer → Create Site Structure runs the same operation as `wp thr seed --only=structure`, reports counts, preserves editor changes and creates zero duplicates on retry.
- [ ] AI: Keep draft/legal Pages out of public navigation; publish Home before assigning it as the public front page. Removing the importer preserves all structure and routes.

### 1.1 Repo & local setup

| ✓ | Who | Task | Done when |
|---|---|---|---|
| [ ] | AI | Create the repo structure: `wp-content/themes/thr-theme`, `wp-content/plugins/thr-core`, `wp-content/plugins/thr-importer`, `docs/`, `.github/workflows/` | Folders exist |
| [ ] | AI | `AGENTS.md` / `CLAUDE.md` (coding rules: WP standards, escaping, `thr_` prefix, no jQuery, file map) | File committed |
| [ ] | AI | `.gitignore`, `README.md` (local setup + deploy steps) | Committed |
| [ ] | YOU | `git init`, connect to the GitHub repo, first push; create the `staging` branch | Repo shows on GitHub with `main` + `staging` |
| [ ] | YOU | LocalWP: create the site `thr-uk` (PHP 8.2, nginx/Apache, any) | Local site loads |
| [ ] | BOTH | Symlink the repo theme/plugins into the LocalWP site (AI gives the exact command) | The theme appears in Appearance → Themes |
| [ ] | YOU | Install Yoast, Co-Authors Plus locally | Activated |

### 1.2 `thr-core` plugin (§8)

| ✓ | Who | Task | Done when |
|---|---|---|---|
| [ ] | AI | Post types: `thr_list` (`/lists/`), `thr_gallery` (`/gallery/{slug}-{id}/`), `thr_video` (`/video/`) | Admin menus show Lists / Galleries / Videos |
| [ ] | AI | Taxonomies: `vertical` (`/e/`), `vcategory` (`/vcategory/`); category base `c`; tag base `t`; labels "Sections" / "Topics" | Settings → Permalinks shows `c` and `t` |
| [ ] | AI | Permalink `/%category%/%postname%-%post_id%/` + ID-fallback 301 | Article URL format correct |
| [ ] | AI | Term meta: vertical colour/logo/taglines; topic banner/intro | Fields visible when editing terms |
| [ ] | AI | Post meta (§4.4) + **THR editor sidebar panel** (dek, kicker, type, flags, review fields, media URL, newsletter) | Fields save and reload |
| [ ] | AI | Image credit field on media | Credit field in the media modal |
| [ ] | AI | Co-Authors Plus fields: job title, socials, public email | Visible on a guest-author profile |
| [ ] | AI | Image sizes (16:9, 2:3, 1:1, banner) + WebP | Regenerated sizes exist |
| [ ] | AI | **Settings → THR** (socials, newsletters, magazine promo, ads, tip email, video playlist order, **font preset**) | Page saves |

### 1.3 `thr-importer` plugin (§12)

| ✓ | Who | Task | Done when |
|---|---|---|---|
| [ ] | AI | Importer code: `wp thr seed`, `--only=structure`, `reset-demo` + Tools → THR Importer buttons | Commands run |
| [ ] | AI | `taxonomy.json` (THR section tree, topics, verticals + colours, vcategories) | — |
| [ ] | AI | `authors.json` (20 guest authors with titles/bios) | — |
| [ ] | AI | `posts.json` (~180: news, features, reviews with full review fields, interviews, podcasts) | — |
| [ ] | AI | `lists.json` (10), `galleries.json` (6), `videos.json` (15) | — |
| [ ] | AI | `menus.json` (section bar, mega menu, footer, legal), `pages.json` (masthead, tip-line, legal, `/p/` issue, `/h/charts`) | — |
| [ ] | AI | `settings.json` (permalinks, front page, Yoast titles, timezone Europe/London) | — |
| [ ] | AI | ~30 placeholder images in `assets/placeholders/` | — |
| [ ] | YOU | Run **`wp thr seed`** (or click the button) | Site full of content |

### 1.4 Theme skeleton (§5, §1)

| ✓ | Who | Task | Done when |
|---|---|---|---|
| [ ] | AI | Theme files + all **13 templates** as working stubs | Every URL renders something |
| [ ] | AI | Build pipeline (sass + esbuild), `npm run build` / `npm run watch` | CSS/JS compile |
| [ ] | AI | `_tokens.scss` + `theme.json` from §1 (colours, type scale, breakpoints, container) | Tokens in use |
| [ ] | AI | Fonts: self-hosted Newsreader + Karla + **font-preset loader** | Fonts render; the preset switch works |

### ✅ Day 1 tests

- [ ] All of these return 200: `/c/movies/`, `/c/movies/movie-news/`, an article `/movies/movie-news/{slug}-{id}/`, `/t/awards/`, `/e/heat-vision/`, `/lists/`, a list, a gallery, `/video/`, a video, `/vcategory/roundtables/`, an author, `/results/?q=test`, `/c/tv/page/2/`.
- [ ] A changed slug/section URL 301s to the correct one.
- [ ] Re-running the seed creates **no duplicates**.
- [ ] The editor panel saves every field.
- [ ] Commit + push to `staging`.

---

## PHASE 2 (DAY 2): Components & templates (pixel match)

Compare every item against `docs/reference/screenshots/` at **390px** and **1440px**.

### 2.1 Global

| ✓ | Who | Task | Done when |
|---|---|---|---|
| [ ] | AI | Header: ☰ · search-expand · GOT A TIP? · red logo · NEWSLETTERS · SUBSCRIBE; section bar; sticky condensed | Matches `home_1440.png` |
| [ ] | AI | Breaking bar (red, label, ×, auto-expire) | Shows when a post is flagged |
| [ ] | AI | Mega menu (10 columns, newsletter form, mini-footer) + mobile drawer (accordions) | Keyboard + Esc work |
| [ ] | AI | Footer (support / about / legal / follow / newsletter / tip) | Matches the plan (§6) |
| [ ] | AI | Ad slot component (labelled "ADVERTISEMENT", reserved size) | No layout shift |

### 2.2 Cards & components

| ✓ | Who | Task |
|---|---|---|
| [ ] | AI | `card.php` variants: top-story, secondary, river, latest, framed-grid, text-only, numbered, rail-large, rail-thumb, video, voice, product, cover |
| [ ] | AI | Relative time ("6 hours ago" → "Oct 2, 2026 5:00 pm"), "VIEW ALL ➜" link, SVG icon sprite |

### 2.3 Templates

| ✓ | Who | Template | Key checks |
|---|---|---|---|
| [ ] | AI | `single.php` standard | Brand banner, breadcrumbs, full-width H1/dek, byline row, share bar (vertical desktop / horizontal mobile), caption + credit, inline ads, inline newsletter, Related Stories, Read More About, More from, rail |
| [ ] | AI | `single.php` review | Honey `#F7F1E7` review box, Bottom Line, credits, Full credits |
| [ ] | AI | `single.php` feature / cover / interview / podcast | Hero / no rail / Q&A / audio |
| [ ] | AI | `single-thr_list.php` | Items: H2 + image + "Image Credit" + text; optional numbers |
| [ ] | AI | `single-thr_gallery.php` | Full-screen shell, "1 / 24", arrows/swipe/keys, deep links, no-JS list |
| [ ] | AI | `single-thr_video.php` + `archive-thr_video.php` | YouTube facade, Most Recent list, Related Videos, playlist rows |
| [ ] | AI | `category.php` | Pills (All/Features/News/Reviews/Videos), featured + 3 cards, river 12/16, MORE STORIES → page 2 |
| [ ] | AI | `archive.php` | Topic banner + intro; vertical header (wordmark, taglines, colour); lists/vcategory rivers |
| [ ] | AI | `author.php` | Photo, name, title, contact/follow, bio, "More from {name}" |
| [ ] | AI | `search.php` | Summary line, facets (authors/topics/sections), date range, type, sort, autocomplete, mobile bottom sheet |
| [ ] | AI | `page.php` + block patterns | Issue hero, cover package, section package, chart embed |
| [ ] | AI | Tip form block | Sends email; honeypot + rate limit |
| [ ] | AI | `404.php` | Suggestions + search |

### ✅ Day 2 tests

- [ ] Every template checked at 390 / 768 / 1024 / 1440.
- [ ] No horizontal scroll anywhere.
- [ ] Gallery works with mouse, keyboard and touch.
- [ ] YOU: visual sign-off against the screenshots.
- [ ] Commit + push.

---

## PHASE 3 (DAY 3): Homepage engine, automation, SEO, deployment

### 3.1 Homepage

| ✓ | Who | Task |
|---|---|---|
| [ ] | AI | `THR_Query` (sources, dedupe, caching) |
| [ ] | AI | **Story Module block** with all layouts: top-grid · latest-list · vertical-channel · labelled-row · video-showcase · most-popular-row · reviews-columns · featured-voices · boxed-grid · lifestyle · podcasts · magazine · grid · river |
| [ ] | AI | Seeded Home page in THR order (§5.1): Top grid → Featured Channels → Special Coverage → Featured Videos → Most Popular → Reviews + Featured Voices → Shopping → Lifestyle + Podcasts → Magazine |
| [ ] | YOU | Test as an editor: swap the top story, reorder modules, change a source |

### 3.2 Automation

| ✓ | Who | Task |
|---|---|---|
| [ ] | AI | Most Popular (beacon + 24h/7d rollup) |
| [ ] | AI | Related Stories (inline), More from, Must Reads |
| [ ] | AI | River "More Stories" load-more + crawlable page links |

### 3.3 SEO

| ✓ | Who | Task |
|---|---|---|
| [ ] | BOTH | Yoast: title templates, NewsArticle, organisation/logo/socials, breadcrumbs, sitemaps |
| [ ] | AI | Schema additions: Review, VideoObject, ItemList, ProfilePage |
| [ ] | AI | Google News sitemap (48h) + `robots.txt` entry |
| [ ] | AI | Noindex rules (search, thin topics); canonicals on topics |

### 3.4 Performance

| ✓ | Who | Task |
|---|---|---|
| [ ] | AI | One CSS + one deferred JS, 2 font preloads, LCP priority, lazy images, YouTube facade |
| [ ] | AI | Query caching; remove unused WP assets |

### 3.5 Deployment

| ✓ | Who | Task | Done when |
|---|---|---|---|
| [ ] | AI | `ci.yml` (php -l, PHPCS, build) | Green on PR |
| [ ] | AI | `deploy-staging.yml` (build → rsync over SSH :65002 → purge cache, flush rewrites) | — |
| [ ] | YOU | Create an SSH key; add the public key in hPanel; add GitHub secrets: `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `SSH_PRIVATE_KEY`, `STAGING_PATH`, `PROD_PATH` | Secrets saved |
| [ ] | YOU | Push to `staging` | The Action deploys; the staging site updates |
| [ ] | YOU | On staging: activate theme + `thr-core` + importer → run seed | Staging matches local |
| [ ] | YOU | LiteSpeed Cache settings (AI gives the exact list) | Cache on |

### ✅ Day 3 tests

- [ ] Homepage modules editable; no duplicate stories.
- [ ] Rich Results Test passes: NewsArticle, Review, VideoObject, ItemList, Breadcrumb.
- [ ] `/news-sitemap.xml` is valid.
- [ ] Push → staging live in under 3 minutes.
- [ ] Lighthouse mobile ≥ 85 on home and an article.

---

## PHASE 4 (DAY 4): QA, launch, handoff

### 4.1 QA

| ✓ | Who | Task |
|---|---|---|
| [ ] | BOTH | Responsive pass at 390 / 430 / 768 / 1024 / 1440 + a real iPhone + a real Android |
| [ ] | BOTH | Browsers: Chrome, Safari (iOS), Firefox, Edge |
| [ ] | AI | Accessibility: keyboard navigation, focus states, contrast, alt text, skip link, landmarks |
| [ ] | AI | Performance to target: Lighthouse mobile ≥ 90, CLS < 0.05, LCP < 2.5s |
| [ ] | AI | `docs/editor-guide.md` (writing articles, lists, galleries, videos, homepage, menus, settings) |

### 4.2 Production launch

| ✓ | Who | Task |
|---|---|---|
| [ ] | AI | `deploy-production.yml` (manual approval via the GitHub "production" environment) |
| [ ] | YOU | GitHub: protect `main`; set yourself as the required reviewer for production |
| [ ] | YOU | Merge `staging` → `main` → approve the deploy |
| [ ] | YOU | Production: activate theme + core; upload the importer **temporarily** → `wp thr seed --only=structure` → **delete the importer** |
| [ ] | YOU | Enter the real content: logo, socials, legal pages, newsletter settings, tip email (Settings → THR) |
| [ ] | YOU | Create editor accounts (Editor / Author / Contributor) |
| [ ] | YOU | Cookie consent plugin configured |
| [ ] | YOU | Untick "Discourage search engines" on **production only** |
| [ ] | YOU | Search Console + Bing: verify, submit the sitemap + news sitemap; apply to Google News Publisher Center |
| [ ] | YOU | GA4 connected (respects cookie consent) |
| [ ] | YOU | Hostinger backups on + an uptime monitor (e.g. UptimeRobot, free) |

### ✅ Launch checklist (final gate)

- [ ] All URL types return 200; stale URLs 301.
- [ ] No demo content or importer on production.
- [ ] Staging still password-protected + noindex.
- [ ] Rich Results pass for every content type.
- [ ] Lighthouse mobile ≥ 90.
- [ ] Tip form email received.
- [ ] Newsletter signups reach the provider.
- [ ] Cache purges on publish.
- [ ] Editor handoff session done + guide delivered.

---

## Progress log

| Date | Phase | Notes |
|---|---|---|
| | | |
