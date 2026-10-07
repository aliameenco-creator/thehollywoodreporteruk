# THR-Style WordPress Publication — Complete Build Plan (2–4 days)

**Decisions locked in:**
- Follow THR's structure exactly.
- Free near-match fonts that can be swapped through one setting.
- Hosting on **Hostinger**.

**Status:** every page type on the live site has been inspected (HTML, CSS and real browser renders). No structural assumptions remain.

---

## 0. Research: what was inspected and how

Every page type was downloaded from TheHollywoodReporter.com with a normal browser request and analysed:
- **DOM structure:** component names and order.
- **CSS:** design tokens, fonts, breakpoints.
- **Markup:** schema, meta, canonical, menus, footer.
- **Rendering:** key pages were rendered in headless Chrome at **390px and 1440px** to confirm the visual layout.

Reference screenshots are saved in `docs/reference/screenshots/` (internal reference only; never publish them).

| Page type | URL inspected | Rendered |
|---|---|---|
| Homepage | `/` | 390 + 1440 (full length) |
| Section (parent) | `/c/movies/` | DOM |
| Section (leaf) | `/c/movies/movie-reviews/` | DOM |
| Standard article | `/movies/movie-news/…-1236721814/` | 390 |
| Review | `/movies/movie-reviews/…-1236723005/` | 1440 |
| List (single + archive) | `/lists/best-ai-apocalypse-movies/`, `/lists/` | DOM |
| Gallery | `/gallery/…-1236712856/` | DOM |
| Video (single + landing) | `/video/…/`, `/video/` | DOM |
| Author | `/author/david-rooney/` | DOM |
| Topic | `/t/awards/`, `/t/box-office/` | DOM |
| Vertical | `/e/heat-vision/` | DOM |
| Search | `/?s=` → `/results/?q=netflix` | DOM + JS config |
| Special issue | `/p/the-music-issue/` | DOM |
| Hub | `/h/charts/` | DOM |
| 404, Tip line, Masthead | — | DOM |

**Platform:** THR runs on WordPress VIP (Penske Media) with PMC's in-house design system, "Larva". Every feature below is native-WordPress achievable.

**Decisions that differ from your original notes:**

| Original idea | Decision | Why |
|---|---|---|
| Separate templates per article type | One `single.php` + swappable parts | Same SEO/schema path; fewer files |
| Custom author system | **Co-Authors Plus** (free, WordPress VIP standard) | Multi-author, guest authors, `/author/` pages |
| Custom SEO fields | **Yoast SEO (free)** | THR uses a separate SEO headline (the `<title>` differs from the H1); Yoast does exactly this |
| Homepage settings page | **"THR Story Module" block**, stacked on the Home page | Elementor-like controls in Gutenberg |
| Custom "topic" taxonomy | **Native WordPress tags with base `t`** | THR's own search config maps topics to `post_tag` |
| Avoid `/c/` `/t/` | **Copy THR exactly** | Native WordPress settings; no rewrite hacks; no URL conflicts |
| Scrape articles | AI-generated demo content shaped like THR's anatomy | Structure is what matters; content isn't licensed |
| Theme updater | Not needed | GitHub Actions → Hostinger over SSH |

---

## 1. THR design system (from live CSS + renders)

### 1.1 Colours

| Token | Hex | Where it's used |
|---|---|---|
| `--brand-primary` | **#D92128** | **Logo**, kickers/section labels, breadcrumbs, bylines ("BY DAVID ROONEY"), in-text links, breaking-news bar, Subscribe link, "SEE MY OPTIONS" buttons, Most Popular numbers, arrow icons |
| `--brand-primary-alt` | #EC1C24 | Red borders |
| `--brand-secondary` | #0926A2 | Rare accents |
| `--accent` | #101010 | Near-black |
| `--black` / `--white` | #000 / #FFF | Text / page background |
| `--grey-darkest` | #323232 | Secondary text |
| `--grey-dark` | #5A5A5A | Meta, captions |
| `--grey` | #8C8C8C | Timestamps, "ADVERTISEMENT" labels, credits |
| `--grey-light` | #DCDCDC | 1px rules between stories/modules |
| `--grey-lightest` | #EFEFEF | Panels, hover |
| `--honey-light` | **#F7F1E7** | **Review summary box** background |

**Vertical colours** are stored as term meta, so editors can set them:

| Vertical | Colour |
|---|---|
| Heat Vision | #6442AC |
| Live Feed | #008080 |
| The Race | #956E37 |
| THR, Esq | #3454DB |
| Rambling Reporter | #A74165 |
| The Fien Print | #4B6A88 |
| Behind the Screen | #008000 |

### 1.2 Fonts (swappable)

| Role | THR | Our default (free, self-hosted) | Used for |
|---|---|---|---|
| Primary | Kepler Std Semicondensed Display, **bold** | **Newsreader** 700 (display opsz) | Headlines, H1, module headings ("LATEST NEWS", "MOST POPULAR") |
| Secondary | Kepler Std 400 / italic | **Newsreader** 400/400i | Deks, card headlines, "TOP STORY" label, vertical taglines (italic) |
| Body | Kepler Std | **Newsreader** 400 | Article text |
| Accent | **Karla** | **Karla** (identical) | Nav, kickers, bylines, dates, buttons, breadcrumbs, river deks, "Related Stories" heading, review-box labels |

**Settings → THR → Typography → Font preset** offers three choices, all through CSS variables, so the switch is instant:
- **Free:** Newsreader + Karla.
- **Adobe Fonts:** enter a kit ID to get the exact Kepler fonts. Adobe Fonts web use is included in Creative Cloud.
- **Custom upload:** woff2 files plus family names.

### 1.3 Type scale (THR values; mobile → desktop ≥1000px)

| Token | Mobile | Desktop | Used for |
|---|---|---|---|
| `primary-xxl` | 34/36 | 54/46 | Homepage top story, feature H1 |
| `primary-xl` | 34/32 | 54/48 | **Article H1** |
| `primary-l` | 32/35 | 48/44 | Module headings |
| `primary-m` | 28/20 | 35/30 | Large card headlines |
| `primary-s` | 20/20 (25/24 tablet) | 29/28 | Section river heading, sub-headings |
| `secondary-xl` | 20/23 | 24/27 | **Article dek** |
| `secondary-l` | 18/21 | 20/23 | Card headlines, rail lists |
| `secondary-m` / `-s` | 18/20 · 16/18 | — | Small headlines |
| `secondary-italic-s` | 18/18 | — | Vertical taglines |
| `secondary-uppercase-xs` | 14/20, ls .0875rem | 16/22 | "TOP STORY" label |
| `body-m` | **20/25** | **22/28** | **Article paragraphs** |
| `accent-s` | — | 16/19 | River deks (Karla) |
| `accent-bold-xl` | 18/22 | 19/22 | — |
| `accent-bold-m` | 15/21 | — | Timestamps, section labels in river |
| `accent-bold-s` | 14/17, ls .044rem | — | Kickers |
| `accent-bold-xs` | 12/14 | — | "BY AUTHOR" lines |
| `accent-uppercase-xs/-s` | 12/18 · 12/14 | 16/19 | Breadcrumbs, nav, section tags |

Headlines are bold with slightly negative tracking. The base line-height is 1.35.

### 1.4 Layout & visual signatures

- **Breakpoints:** 768 (tablet) · **1000 (desktop switch)** · 1260 (desktop-xl) · 1520.
- **Container:** 1260px max.
- **Grid:** content ~600–660px + **300px right rail**. The rail is hidden below 1000px.
- **Signatures to reproduce:**
  - **Framed images:** card images have a thin dark border with a white inset.
  - **"TOP STORY" label:** a white box with a 1px border sitting on the top-centre edge of the lead image.
  - **Centred top story:** headline, dek and "BY …" are all centred.
  - **Red kickers** (Karla bold uppercase) above headlines; red-label + grey-time rows in rivers.
  - **Module headings:** uppercase serif with a 1px rule above ("LATEST NEWS", "MOST POPULAR").
  - **"VIEW ALL ➜" / "MORE NEWS ➜"** links with a red (or vertical-coloured) double-arrow icon.
  - **Vertical wordmarks:** wide-tracked caps in the vertical's colour ("H E A T  V I S I O N").
  - **Double-bordered promo box** (Get the Magazine).
  - **Full-width red breaking bar** with a close ×.
- **Images:** 16:9 cards/heroes, 2:3 magazine covers, circular author headshots (Featured Voices). Gallery and list images keep their natural ratio.
- **Ads:** "ADVERTISEMENT" label (grey, tiny caps) above each reserved slot. Positions: header leaderboard, rail 300×250/300×600, in-river, in-article after paragraph 1 and every ~4 paragraphs.

---

## 2. Architecture

```
WordPress + PHP 8.2/8.3 on Hostinger (LiteSpeed)
├── thr-theme      presentation: templates, CSS, JS, components
├── thr-core       data/behaviour: post types, taxonomies, meta, query engine, blocks,
│                  popular tracking, search, news sitemap, settings, font presets, tip form
├── thr-importer   demo/structure seeding (staging only, removable)
└── Free plugins:  Yoast SEO · Co-Authors Plus · LiteSpeed Cache · Redirection (launch)
```

Not used: Elementor, ACF Pro, Jetpack, form plugins, mega-menu plugins, schema plugins.

---

## 3. URL structure (THR exact)

**Exact setup inventory:** [URL, category and heading specification](docs/URL-AND-CATEGORY-STRUCTURE.md) defines every starter slug, parent relationship, heading/subtitle field and one-click importer requirement. [site-structure.json](docs/site-structure.json) is the versioned seed manifest: **26 categories (6 parents + 20 children), 25 topics, 7 verticals, 12 video categories, 11 draft Pages**. Structure mode creates no demo stories or authors. Additional editorial terms and issue/hub Pages can be added later.

**Implementation requirement:** thr-core must explicitly select the primary category when generating article permalinks; Yoast selection alone is not the routing implementation. Gallery ID suffixes also need custom permalink/rewrite handling. These remain active after the importer is deleted.

| Content | URL | How |
|---|---|---|
| Section / subsection | `/c/movies/`, `/c/movies/movie-news/` | Category base `c` (native) |
| Article | `/movies/movie-news/{slug}-{id}/` | Permalink `/%category%/%postname%-%post_id%/` (native; uses the primary category) |
| Topic | `/t/awards/` | **Native tags**, tag base `t` |
| Vertical | `/e/heat-vision/` | Taxonomy `vertical`, slug `e` |
| List | `/lists/{slug}/`, archive `/lists/` | CPT `thr_list` |
| Gallery | `/gallery/{slug}-{id}/` | CPT `thr_gallery` |
| Video | `/video/{slug}/`, landing `/video/` | CPT `thr_video` |
| Video category | `/vcategory/{slug}/` | Taxonomy `vcategory` |
| Author | `/author/{slug}/` | Co-Authors Plus |
| Search | `/results/?q=…` (`/?s=` redirects here) | `thr-core` rewrite → `search.php` |
| Special issue / hub | `/p/{slug}/`, `/h/{slug}/` | Pages under parent pages `p` / `h` |
| Static | `/masthead/`, `/contact/`, `/tip-line/`, `/accessibility/` | Pages |
| Pagination | `/c/tv/page/2/` | Native ("More Stories" links here) |
| Feeds | `/feed/`, `/c/movies/feed/` | Native |

**Duplicate-URL safety:**
- Yoast canonical points to the primary-section URL; alternate category paths 301.
- The `-{id}` suffix lets `thr-core` resolve and 301 any stale URL.

**Better than THR:** THR's topic pages have no canonical tag and no meta description. Ours will have both, via Yoast term fields.

---

## 4. Content model

### 4.1 Post types

| Type | Purpose | Distinct fields |
|---|---|---|
| `post` | News, features, reviews, interviews, cover stories, podcasts | Article type + all article fields |
| `thr_list` | Ranked/curated lists, guides, calendars | **List items** (custom block: title, image, credit, description) |
| `thr_gallery` | Photo galleries | **Slides** (custom block or core gallery + per-image title/caption/credit) |
| `thr_video` | Videos | Video URL (YouTube), duration, `vcategory` |
| `page` | Static, `/p/` issues, `/h/` hubs, Home | Built with blocks |

All content types share sections, tags, verticals, authors, the card component and the SEO setup.

**Article types** (`post`, a field) select the layout part:
- `standard`
- `feature` (big hero, no rail)
- `review` (review summary box + full credits)
- `cover-story`
- `interview`
- `podcast` (audio embed)

### 4.2 Taxonomies

| Taxonomy | URL | Seeded terms |
|---|---|---|
| Sections (`category`) | `/c/` | News (General News, Culture & Politics, LA/Local) · Movies (Movie News, Movie Features, Movie Reviews, Movie Videos) · TV (TV News, TV Features, TV Reviews) · Music (Music News, Music Industry News, Music Features, Film and TV Music News) · Lifestyle (Lifestyle News, Arts, Style, Shopping, Real Estate) · Business (Business News) |
| Topics (`post_tag`) | `/t/` | Awards, International, THR Cover Story, THR Investigates, Obituaries, Box Office, TV Ratings, Grammys, K-Pop, Country Music, Feinberg Forecast, Awards Chatter Podcast, Beyond the Book, Labor, Representation, Film & TV Tax Credits, Broadway Grosses, plus people/titles/companies |
| Verticals (`vertical`) | `/e/` | Heat Vision, Live Feed, The Race, THR Esq, The Fien Print, Rambling Reporter, Behind the Screen. Meta: colour, wordmark/logo, 2 taglines, description. |
| Video categories (`vcategory`) | `/vcategory/` | THR News, Roundtables, THR On The Cover, Hollywood Firsts, Heat Vision Breakdown, Women in Entertainment, Red Carpet, Oscars, Emmys, Golden Globes, Social Impact Summit, Closer Look |

**Topic term meta:** banner image (1500×598), intro text (rich text with newsletter link).

### 4.3 Authors (Co-Authors Plus)

Fields:
- photo
- name
- **job title** (e.g. "Chief Film Critic")
- bio
- X, Instagram, LinkedIn, Threads, website
- **public contact email** (THR shows mailto)

Bylines render as "BY NAME" in red; multiple authors as "BY A AND B".

### 4.4 Article fields

| Field | Storage |
|---|---|
| H1 title | core |
| SEO headline, meta description, canonical, social image | Yoast |
| Dek | `thr_dek` |
| Kicker override | `thr_kicker` (default: primary subsection) |
| Article type | `thr_article_type` |
| Sections + primary · tags · vertical · authors | taxonomies / Yoast primary / CAP |
| Featured image, caption, **credit** | core + `thr_credit` (rendered: caption, then credit in grey caps) |
| Featured / breaking (+expiry) / sponsored (+name, URL) | `thr_featured`, `thr_breaking*`, `thr_sponsored*` |
| Review: subject title, **The Bottom Line**, Venue, Release date, Cast, Director, Screenwriter, Rating + running time, Full credits | `thr_review_*` |
| Media URL (podcast/video) | `thr_media_url` |
| Newsletter box variant (which newsletter to promote inline) | `thr_newsletter` (default per vertical/section) |
| Exclude from automatic modules | `thr_exclude_auto` |

---

## 5. Templates (13) and exactly what each renders

The theme has 13 template files plus `header.php`, `footer.php` and `searchform.php`. Everything else is reusable parts.

### 1. `front-page.php`: Homepage

Renders the Home page's blocks. Default layout (seeded; editors can change it), top to bottom:

1. **Header leaderboard ad** → header → **breaking bar**.
2. **Top grid** (2/3 + 1/3):
   - **Left:** **Top Story** (framed 16:9 image, "TOP STORY" label on the image edge, centred `primary-xxl` headline, centred dek, "BY AUTHOR").
   - Under the top story, behind a rule: **3 secondary stories** in columns (red kicker, framed image, `primary-s` headline, Karla dek, BY line).
   - **Right:** **LATEST NEWS** list of ~18 items (red subsection label + grey "08:43 AM", `secondary-l` headline), with a rail ad after item 3, and **"MORE NEWS ➜"** at the end.
3. **Featured Channels:** two **vertical blocks** side by side with a divider (e.g. HEAT VISION / LIVE FEED). Each has the coloured wordmark, italic tagline, framed lead image, time in the vertical's colour, centred headline + dek, 2 text-only stories, and "VIEW ALL ➜".
4. Ad.
5. **Special Coverage rows** (one or more, e.g. "What We're Watching", "NYFF 2026"): a left label column (serif title, italic tagline, SEE ALL) + **4 cards**.
6. **Featured Videos:** title + SEE ALL on the left. In the centre, a **large player** (poster + play button) with headline and dek. 2 small video cards on each side.
7. **Most Popular:** a row of **3 items with big red numbers** ("1.", "2.", "3."), next arrow and dots (carousel of 6–9).
8. Ad.
9. **Reviews** (2/3): **MOVIES** and **TV** columns (red sub-headings + SEE ALL), with headline + BY lists and a framed first image.
10. **Featured Voices** (1/3): circular headshots, red author name, column headline.
11. **Shopping With THR:** bordered box, 4 product/story cards.
12. **Lifestyle** (2/3, with tagline): primary story + secondary list. **Podcasts** (1/3).
13. **Highlights from the Magazine:** cover-story cards.
14. Footer.

**Mobile:**
- Slim header (☰, red logo), ad, breaking bar.
- Top story full width with the label.
- Secondary stories become thumb-left rows with red kickers.
- "LATEST NEWS" heading, then thumb-left items (label + time + headline).
- In-feed ads.
- Then each module stacks; rows of cards become horizontal scroll.

### 2. `single.php`: articles (all types)

**Desktop:**
1. Leaderboard ad.
2. Header + breaking bar.
3. **Vertical brand banner** (only if the post has a vertical): coloured wordmark between rules.
4. **Breadcrumbs:** HOME > MOVIES > MOVIE REVIEWS (red caps; last item bold).
5. **H1** (`primary-xl`, **full width across content + rail**).
6. **Dek** (`secondary-xl`, full width).
7. **Byline row:** red "BY NAME" + icon · grey caps "OCTOBER 6, 2026 12:00AM".
8. Two columns begin. **Left:**
   1. hero image;
   2. caption (serif small) + **CREDIT** (grey caps);
   3. **vertical share bar** (Facebook, X, Google News/preferred source, Flipboard, +more), sticky, left of the body;
   4. body (`body-m`, red links) with an ad after paragraph 1 and every ~4 paragraphs;
   5. **inline newsletter box** (vertical/section newsletter: logo, "GET THE … NEWSLETTER", text, email field + SIGN UP);
   6. **Related Stories** (Karla bold heading, 2 cards side by side: thumb + red kicker + headline), injected after paragraph ~3–4;
   7. inline images with "Image Credit";
   8. **Review summary box** (reviews only): honey #F7F1E7 background, subject title (Karla bold) on the left; on the right **The Bottom Line** (italic) plus Venue / Release date / Cast / Director / Screenwriter / "Rated PG-13, 2 hours 20 minutes" (bold labels);
   9. end of body;
   10. **Full credits** (reviews);
   11. **"Read More About:"** tag links;
   12. **THR Newsletters** signup;
   13. **More from The Hollywood Reporter** (6-card grid).
9. **Right rail (300px, ≥1000px):**
   1. ad 300×250;
   2. **Get the Magazine** box (double border, latest cover image, "THE DEFINITIVE VOICE OF ENTERTAINMENT NEWS", subscribe text, red SEE MY OPTIONS);
   3. **MOST POPULAR** (first item large image + headline + BY, then 5 thumb-left items);
   4. **Must Reads** (2–3 cards);
   5. sticky ad.

**Mobile:**
- Brand banner → breadcrumbs → H1 → dek → byline/date.
- The **share bar is a horizontal row** of square icons.
- Then image + caption, then body.
- No rail. Most Popular and Must Reads move below "More from".

**Type differences:**
- `feature` / `cover-story`: full-bleed hero above the H1, larger type, no rail.
- `podcast`: audio embed under the dek.
- `interview`: Q/A styling.

### 3. `single-thr_list.php`: lists

- **Header:** same article header (breadcrumbs, H1, dek, byline, "Published on" date) and intro body.
- **Items:** an ordered set of **items**, each with:
  - H2 title (e.g. "*The Terminator* (1984)"),
  - image + "Image Credit: …",
  - description paragraphs.
- Optional **numbering** (toggle).
- Ads between every ~3 items.
- **End of page and rail:** same as an article.

### 4. `single-thr_gallery.php`: galleries (immersive, own shell)

- **Full-screen gallery shell, without the normal site header:**
  - **Header bar:** logo · gallery title · share icons · close.
  - **Main:** a **"1 / 24" counter**, then the **image slider** (arrows, swipe, keyboard).
  - **Sidebar:** slide title + caption + credit + ad.
- Each slide gets its own hash URL (`#!2/slug`) for sharing.
- **Without JS (SEO):** every slide is server-rendered as a list (H2 title, image, "Image Credit", caption), so all content is indexable.
- **Mobile:** image on top; title, caption and credit below; swipe to navigate.

### 5. `single-thr_video.php`: video

- Sub-header "Video".
- **Showcase:**
  - the large player (YouTube via a lightweight facade, so the iframe loads only on click);
  - beside it, a **"Most Recent"** list of video cards that swap the player.
- Title, description, byline, date, tags.
- **Related Videos** card grid (same `vcategory`).
- `VideoObject` schema.

### 6. `archive-thr_video.php`: `/video/` landing

- **Header:** H1 "Video" + **"Popular on THR"** links (vcategories).
- **Showcase:** player + **"Most Recent"** list.
- **Playlist rows:** one per vcategory (Women in Entertainment, THR News, Roundtables, On The Cover, Social Impact Summit…), each a featured video + cards + SEE ALL.
- Row order is set in Settings → THR, as a sortable list of vcategories.

### 7. `category.php`: sections and subsections

- **Section header:** H1 (e.g. "Movies") + **subsection pills**: All · Features · News · Reviews · Videos.
- **Parent section** (`/c/movies/`):
  - **featured story** (large image left; red kicker, `primary` headline, Karla dek, BY right);
  - **3-card row** (image, red kicker, headline, dek, BY);
  - **"Latest Movies"** river of **12 stories**;
  - **MORE STORIES →** link to `/page/2/`.
- **Leaf subsection** (`/c/movies/movie-reviews/`): H1 "Latest Movie Reviews" + river of **16** + MORE STORIES.
- **River item** (exact THR anatomy): headline (`secondary-l`) · Karla dek · [red subsection label · relative time] · "By Author" · **image on the right**.
- **Times:** "6 hours ago" / "2 days ago" up to ~3 days, then "Oct 2, 2026 5:00 pm".
- **Rail:** ad slots (sticky).

### 8. `archive.php`: topics (`/t/`), verticals (`/e/`), `/lists/`, `/vcategory/`

- **Topic:** optional **banner image** (1500×598) + **intro text** (from term meta; e.g. "Keep up with … Sign up for newsletters here.") → H2 topic name → river (12) → MORE STORIES → **rail: Most Popular + Get the Magazine**.
- **Vertical:** **brand header** (coloured wordmark between rules, two italic taglines with an ornament) → featured story + 3 cards → **"{Vertical}'s Latest News"** river (16) → MORE STORIES.
- **`/lists/` and `/vcategory/`:** H2 + river + pagination.

### 9. `author.php`

- **Author blurb:** photo, **H1 name**, **job title**, "Contact or follow this author" (X, email…), **bio** (`body-m`).
- **"More from {Name}"** river (12) + pagination.
- `ProfilePage` + `Person` schema.

### 10. `search.php`: `/results/?q=`

THR uses Elasticsearch; we match its UI with native WordPress queries.
- **Search box** with autocomplete (REST endpoint returns titles as you type).
- **Summary line:** "Showing 1–20 of 340 for 'netflix'".
- **Filters:**
  - **Authors** (checkboxes, top 7 + more)
  - **Tags/Topics** (top 7)
  - **Sections** (top 7)
  - **Date range:** All · Past 24 Hours · Past 7 Days · Past 30 Days · Past 12 Months
  - **Content type:** Articles / Lists / Galleries / Videos
- **Sort By:** Relevance / Newest.
- Results use the river card; numbered pagination.
- **Mobile:** filters open in a bottom sheet.
- `noindex,follow`.
- Facet counts are cached per query (transient).
- Upgrade path: Relevanssi (free) if relevance needs to improve.

### 11. `page.php`: static, `/p/` special issues, `/h/` hubs

- Full-width block canvas, matching THR, whose `/p/` and `/h/` pages are pure core blocks: Cover, Group, Columns, Heading, Image, **Query Loop**, Details, Embed (Datawrapper/YouTube), Separator, Spacer.
- We add **block styles** (THR package heading, kicker label, framed image) and **block patterns**: "Issue hero", "Cover story package", "Section package with kicker", "Chart embed".
- **Masthead:** H1 + staff list by department (H4 headings).
- **Tip line:** intro + "Ways to Provide News Tips" + **Tipline form**.
  - The form is a `thr/tip-form` block in `thr-core`, with fields name (optional), email (optional), tip, and attachment.
  - Protection: nonce + honeypot + rate limit. Submissions are emailed to the tip address.

### 12. `404.php`

"404 / Page Not Found", "Here are some suggestions…" (latest 6 stories), and "Or try searching for it here…" with a search box. THR shows the same.

### 13. `index.php`

Required fallback (a simple river).

---

## 6. Header, mega menu, footer

**Desktop header**
- **Top row:** ☰ · search icon (expands into an inline search bar, red when open) · **GOT A TIP?** — centred **red logo** — **NEWSLETTERS** · **SUBSCRIBE** (red).
- **Section bar** (Karla bold caps, centred, rules above and below): NEWS · FILM · TV · MUSIC · AWARDS · LIFESTYLE · BUSINESS · INTERNATIONAL · COVERS · CHARTS · LISTS · VIDEO
  - Links: Awards → `/t/awards/`, International → `/t/international/`, Covers → `/t/thr-cover-story/`, Charts → `/h/charts/`, Lists → `/lists/`, Video → `/video/`.
- Sticky condensed version on scroll.
- **Breaking bar** below: red, "BREAKING NEWS" label + headline + ×. Dismissal is remembered per story.

**Mobile header:** ☰ · red logo (centred) · search. The section bar is hidden; navigation is in the drawer.

**Mega menu / drawer** (full-screen; accordions on mobile):
- **Top:** logo, Log In, **newsletter signup form**.
- **Columns:**

| Column | Links |
|---|---|
| Newsletters | Today In Entertainment · Weekender · Feinberg Forecast · Heat Vision · Now See This · London Calling · Loose Threads |
| News | Latest News · THR Cover Stories · THR Investigates · Culture & Politics · Obituaries · LA/Local |
| Film | Box Office · Heat Vision · News · Features · Reviews |
| TV | Reviews · Premiere Dates · Ratings · The Fien Print · Live Feed |
| Business | THR, Esq · Production News / Incentives · Unions / Labor · Signings / Representation |
| Music | Music News · Music Industry News · Music Features · Film and TV Music News · Grammys · K-Pop · Country Music |
| Awards | The Race · Feinberg Forecast · Awards Chatter Podcast · News · THR Presents |
| Lifestyle | Next Big Thing · Beyond the Book · Arts · Style · Shopping · Real Estate · Rambling Reporter |
| THR Charts | Weekly Streaming Ratings · Weekend Box Office · Weekly Broadcast Ratings · Weekly Broadway Grosses |
| Digital Issues | `/p/` special issues |

- **Mini-footer:** About Us · Careers · Contact Us + copyright line.
- All menus are WordPress menus (seeded) rendered by a custom walker.
- Accessibility: `aria-expanded`, focus trap, Esc closes.

**Footer**
- **Columns:**
  - **Subscriber Support:** Get the Magazine · Customer Service · Back Issues
  - **The Hollywood Reporter:** About Us · Careers · Contact Us · Accessibility
  - **Legal:** Terms of Use · Privacy Policy · Privacy Preferences · Cookie Policy (UK)
  - **Follow Us:** Facebook, Instagram, LinkedIn, Threads, TikTok, X, YouTube
  - **Newsletter Sign Up**
  - **Have a Tip?** "Send us a tip using our anonymous form." → SEND US A TIP
- Copyright line.
- THR's PMC-network "Most Popular / You may also like" widgets are omitted.

---

## 7. Reusable components (theme `template-parts/`)

| Group | Components |
|---|---|
| Cards | **One `card.php`** with variants: `top-story`, `secondary` (kicker+image+headline+dek+by), `river` (text left, image right), `latest` (label+time+headline, no image), `framed-grid`, `text-only`, `numbered` (big red number), `rail-large`, `rail-thumb`, `video` (play icon), `voice` (circle headshot), `product`, `cover` (2:3) |
| Modules | top-grid · latest-list · vertical-channel · labelled-row · video-showcase · most-popular-row · reviews-columns · featured-voices · boxed-grid · lifestyle · podcasts · magazine · river + more-stories |
| Header | leaderboard · masthead · section-bar · search-expand · breaking-bar · mega-menu · mobile-drawer · newsletter-form |
| Article | brand-banner · breadcrumbs · headline/dek · byline-row · share-bar (vertical/horizontal) · hero-media · caption-credit · inline-newsletter · related-inline · review-summary · full-credits · read-more-about · more-from-thr · list-item · gallery-shell · video-player (facade) |
| Rail | ad · get-the-magazine · most-popular · must-reads |
| Archive | section-header + pills · topic-banner · vertical-header · author-blurb · pagination/more-stories |
| Search | search-form (autocomplete) · facets · result summary |
| Utility | ad-slot (labelled, reserved size) · view-all link (arrow icon) · relative-time · SVG icon sprite |

---

## 8. `thr-core` plugin

```
thr-core/
├── thr-core.php
├── includes/
│   ├── post-types.php      thr_list, thr_gallery, thr_video (+ permalink structures)
│   ├── taxonomies.php      vertical (/e/), vcategory; tag base t; category base c; relabel Sections/Topics
│   ├── term-meta.php       vertical colour/logo/taglines; topic banner/intro
│   ├── post-meta.php       all §4.4 fields (show_in_rest)
│   ├── attachment-credit.php
│   ├── coauthors.php       job title, socials, public email
│   ├── permalinks.php      enforce structures; ID-suffix fallback 301
│   ├── image-sizes.php     16:9 400/800/1200/1600 · 2:3 400/800 · 1:1 150/300 · banner 1500x598; WebP
│   ├── query.php           THR_Query (sources, dedupe, cache)
│   ├── popular.php         sendBeacon counter → 24h/7d rollup
│   ├── related.php         tag/section scoring
│   ├── search.php          /results/ route, facets, autocomplete REST, /?s= redirect
│   ├── breaking.php · news-sitemap.php · seo.php (Review/VideoObject/ItemList schema, topic canonical fix)
│   ├── tip-form.php        handler (nonce, honeypot, rate-limit, wp_mail)
│   ├── settings.php        Settings → THR: socials, newsletters list, magazine promo, ads, video playlist order, tip email, FONT PRESET
│   ├── admin.php · rest.php
├── blocks/  story-module · list-item · gallery-slide · related-stories · tip-form · newsletter-box
├── src/editor/article-panel.js
└── package.json (@wordpress/scripts)
```

**Story Module block**
- **Sources:** Latest · Section · Tag · Vertical · Author · Post type · Article type · Featured flag · Most Popular · vcategory · **Manual pick**.
- **Layouts** (one per homepage module): `top-grid` · `latest-list` · `vertical-channel` · `labelled-row` · `video-showcase` · `most-popular-row` · `reviews-columns` · `featured-voices` · `boxed-grid` · `lifestyle` · `podcasts` · `magazine` · `grid-3/4` · `river`.
- **Display:** heading, tagline, link, count, offset, show/hide toggles.
- **Behaviour:** dedupe across the page, optional ad after the module.

---

## 9. SEO & schema

| Item | Implementation |
|---|---|
| Titles | Articles: SEO headline only (THR uses the bare headline). Sections: "Movies \| Site". Authors: "Name \| Site". Topics: "Awards \| Site" |
| Schema | Organization (sitewide) · WebPage · **NewsArticle** + Person + ImageObject (articles, lists, galleries, videos, hubs) · `Review` (reviews) · `VideoObject` (video) · `ItemList` (lists) · `ProfilePage` (authors) · BreadcrumbList |
| Canonicals | All pages, **including topics** (THR misses these) |
| Sitemaps | Yoast (posts, lists, galleries, videos, sections, topics, verticals, authors) + **Google News sitemap** (48h) |
| Robots | Search `noindex,follow` · thin topics noindex · `max-image-preview:large` · optional `noai, noimageai` toggle (THR sends this) |
| Internal links | Breadcrumbs, kickers, Read More About, Related Stories, More from, author links, river labels |
| Launch | Search Console, Bing, Google News Publisher Center |

---

## 10. Performance

- **Targets** (mobile): LCP < 2.5s, CLS < 0.05, INP < 200ms, Lighthouse ≥ 90.
- **Assets:**
  - One CSS file + inline critical header CSS.
  - One deferred vanilla JS bundle (<25KB, including gallery/slider/tabs).
  - 2 font preloads.
- **Video:** YouTube facade (no iframe until click).
- **Images:** responsive crops with fixed ratios; lead image high priority; the rest lazy.
- **Ads:** reserved slot heights, so no layout shift.
- **Caching:** query/object caching; LiteSpeed page cache + Memcached object cache on Hostinger; popular tracking via beacon (cache-safe).

---

## 11. Dynamic vs hard-coded

| Dynamic (editors) | Hard-coded (GitHub) |
|---|---|
| Homepage modules and order · `/p/` `/h/` pages · all menus · sections/tags/verticals (colours, logos, taglines, banners) · authors · flags · Settings → THR (socials, newsletters, magazine promo, ads, video playlist order, tip email, font preset) · Yoast fields | Component markup, CSS/tokens, template layouts, rail order, related/popular logic, schema, sitemaps, ad positions |

---

## 12. Importer (`thr-importer`)

**Data:**
- **Structure:** sections, tags (+banners/intros), verticals (+colours/taglines), vcategories.
- **People:** 20 authors (+titles, bios).
- **Content:**
  - 180 posts across all types, with review fields filled;
  - 10 lists (with items);
  - 6 galleries (with slides);
  - 15 videos (YouTube IDs of public trailers);
  - the Home page built from block markup in §5.1.
- **Pages:** `/p/` issue, `/h/charts`, masthead, tip line, legal.
- **Navigation and settings:** all menus (section bar, mega menu, footer), and settings (permalink structures, bases `c`/`t`, front page, Yoast, timezone Europe/London).

**Commands:** `wp thr seed` · `wp thr seed --only=structure` (production) · `wp thr reset-demo`. There is also a Tools → THR Importer button.

**Behaviour:**
- Idempotent (matched by external ID).
- Local placeholder images; no network calls.
- Dates spread over 30 days.

---

## 13. GitHub & Hostinger deployment

**Hostinger** (Business/Cloud plan):
- Production + `staging.` subdomain (separate WordPress, password-protected, noindex).
- SSH on **port 65002**.
- PHP 8.2/8.3.
- LiteSpeed Cache + Memcached.

**Repo:** `.github/workflows/{ci,deploy-staging,deploy-production}.yml` · `wp-content/themes/thr-theme` · `wp-content/plugins/thr-core` · `wp-content/plugins/thr-importer` · `docs/` · `AGENTS.md`.

**Flow:**
- `feature/*` → PR → `staging` (auto-deploy).
- `staging` → PR → `main` (deploy after manual approval).
- **CI:** `php -l`, PHPCS, build.
- **Deploy:**
  1. Build.
  2. `rsync` over SSH into `public_html/wp-content/...`. The importer goes to staging only.
  3. `wp litespeed-purge all`, then `wp rewrite flush`.

Hostinger's hPanel Git deploy is not used: it has no build step and wants to own the whole directory.

**Local dev:** LocalWP on Windows.

---

## 14. Day-by-day

### Day 1: Foundation and data

- **Tasks:**
  - Repo + `AGENTS.md`; LocalWP + Yoast + CAP.
  - **thr-core:** post types (incl. gallery), taxonomies (`c`/`t`/`e`/`vcategory`), term/post meta + editor panel, permalinks, image sizes, settings + font presets.
  - **thr-importer** + all JSON.
  - **Theme skeleton:** 13 templates, tokens (§1), fonts, base type, header/footer v1.
  - Seed.
- **Manual:** GitHub, LocalWP, Hostinger plan, staging subdomain, SSH key.
- **Tests:** every URL type in §3 returns 200; stale URLs 301; re-seed creates no duplicates; font preset switches; editor fields save.
- **Output:** seeded local site with THR's exact structure.

### Day 2: Components and templates (pixel match)

- **Tasks:**
  - Card variants.
  - Header (sticky, search expand, breaking bar), mega menu, drawer, footer.
  - `single.php` (all types, review box, share bars, inline newsletter, related, rail), list, gallery shell, video single + landing.
  - Category (parent + leaf), archive (topic banner, vertical header), author, search + facets + autocomplete, page + block patterns, 404, tip form.
- **Manual:** logo SVG; visual review against `docs/reference/screenshots`.
- **Tests:** each template at 390/768/1024/1440; gallery keyboard/swipe; mega menu keyboard/Esc; no horizontal scroll.
- **Output:** all templates match THR.

### Day 3: Homepage engine, automation, SEO, deploy

- **Tasks:**
  - THR_Query + Story Module (all 14 layouts) + seeded homepage.
  - Popular, Related, More from, Must Reads, relative times.
  - SEO/schema/news sitemap.
  - Performance pass.
  - GitHub Actions → Hostinger staging → seed.
- **Manual:** GitHub secrets, LiteSpeed settings.
- **Tests:** module edit/reorder; dedupe; Rich Results (NewsArticle, Review, VideoObject, ItemList, Breadcrumb); news sitemap; push-to-deploy < 3 min; Lighthouse ≥ 85.
- **Output:** feature-complete staging.

### Day 4: QA and launch

- **Tasks:**
  - Responsive and real-device QA.
  - Accessibility.
  - Performance to targets.
  - Cross-browser.
  - Editor guide.
  - Production deploy (with approval) → structure-only seed → remove importer → Search Console/Bing/News/GA4 → handoff.
- **Manual:** DNS/SSL, editor accounts, legal copy, socials, Google tools.
- **Output:** live and editors trained.

---

## 15. Editor workflow

1. **Posts / Lists / Galleries / Videos → Add New.**
2. THR panel: dek, type, flags, review fields.
3. Sections (+ primary), Topics (tags), Vertical, Authors.
4. Featured image (caption + credit), then the body (list items / gallery slides as blocks).
5. Yoast SEO headline + description.
6. The pre-publish check runs → Publish.
7. **Homepage:** Pages → Home (swap the top story, reorder modules).
8. **Menus:** Appearance → Menus. **Settings and fonts:** Settings → THR.

Roles: Editor / Author / Contributor.

---

## 16. Launch checklist

- [ ] Permalinks: `c` / `t` bases, `/%category%/%postname%-%post_id%/`, CPT structures; all URL types return 200; stale URLs 301.
- [ ] Importer and demo content removed from production.
- [ ] Yoast configured; canonicals on topics; News sitemap in `robots.txt`.
- [ ] Rich Results pass for every content type.
- [ ] Search Console, Bing, News Publisher Center.
- [ ] Lighthouse mobile ≥ 90; CLS < 0.05.
- [ ] LiteSpeed + object cache; purge on publish.
- [ ] UK cookie consent + privacy/cookie pages.
- [ ] Tip form delivers email; spam protection works.
- [ ] Backups + uptime monitor.
- [ ] Staging password-protected + noindex.
- [ ] Font preset confirmed.
- [ ] `main` protected; production deploy requires approval.
- [ ] Editors trained; guide delivered.

---

## 17. End-to-end verification

1. Run `wp thr seed`, then open every URL type (§3) and template (§5).
2. Compare against `docs/reference/screenshots/` at 390 and 1440.
3. Edit Home modules; verify dedupe.
4. Breaking flag → bar appears → expires.
5. Switch the font preset.
6. Gallery: arrows, swipe, deep links; no-JS list renders.
7. Search facets + date range + sort + autocomplete.
8. Push to staging → auto-deploy; merge to main → approval → production.
9. Rich Results, Lighthouse, Search Console inspection.
