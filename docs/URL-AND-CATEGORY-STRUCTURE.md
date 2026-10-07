# URL, category and heading specification

This is the proposed initial THR UK inventory for the build plan, not a fresh export of every live THR category. The machine-readable source is [site-structure.json](site-structure.json). These are implementation requirements; the custom importer has not been built yet.

## Exact setup counts

| Object | Initial count | Setup behaviour |
|---|---:|---|
| Parent sections | 6 | News, Movies, TV, Music, Lifestyle, Business |
| Subsections | 20 | Children of the six sections |
| Total WordPress categories | **26** | 26 archive URLs; no manually created archive Pages |
| Topic tags | **25** | 25 topic archive URLs |
| Editorial verticals | **7** | 7 vertical archive URLs |
| Video categories | **12** | 12 video category archive URLs |
| Total taxonomy terms | **70** | 26 + 25 + 7 + 12 |
| WordPress Pages | **11** | Home, 8 utility/editorial pages, 2 containers |
| Article layout choices | **6** | Standard, feature, review, cover story, interview, podcast |
| Additional editorial post types | **3** | Lists, galleries, videos |
| Authors and articles | **0 in structure mode** | Added by editors or a separate staging-only demo seed |

This produces 70 taxonomy archive routes and 11 Page records. It does not mean 81 ready-to-publish pages: utility pages begin as drafts, container Pages are not editorial destinations, and empty archives have no stories. Lists, videos, search, authors, pagination and feeds are additional dynamic routes, not seeded Pages.

## Categories: exact names, parents and URLs

The category name is also its default visible heading. A subsection is a WordPress child category, not a separate Page.

| News | — | `/c/news/` |
| General News | news | `/c/news/general-news/` |
| Culture & Politics | news | `/c/news/culture-politics/` |
| LA / Local | news | `/c/news/la-local/` |
| Movies | — | `/c/movies/` |
| Movie News | movies | `/c/movies/movie-news/` |
| Movie Features | movies | `/c/movies/movie-features/` |
| Movie Reviews | movies | `/c/movies/movie-reviews/` |
| Movie Videos | movies | `/c/movies/movie-videos/` |
| TV | — | `/c/tv/` |
| TV News | tv | `/c/tv/tv-news/` |
| TV Features | tv | `/c/tv/tv-features/` |
| TV Reviews | tv | `/c/tv/tv-reviews/` |
| Music | — | `/c/music/` |
| Music News | music | `/c/music/music-news/` |
| Music Industry News | music | `/c/music/music-industry-news/` |
| Music Features | music | `/c/music/music-features/` |
| Film and TV Music News | music | `/c/music/film-tv-music-news/` |
| Lifestyle | — | `/c/lifestyle/` |
| Lifestyle News | lifestyle | `/c/lifestyle/lifestyle-news/` |
| Arts | lifestyle | `/c/lifestyle/arts/` |
| Style | lifestyle | `/c/lifestyle/style/` |
| Shopping | lifestyle | `/c/lifestyle/shopping/` |
| Real Estate | lifestyle | `/c/lifestyle/real-estate/` |
| Business | — | `/c/business/` |
| Business News | business | `/c/business/business-news/` |

Keep Movies and its slug `movies` to match the existing plan. A later display-name change to Film need not change the slug. Awards, Box Office and TV Ratings remain topics; Video is a post-type archive; covers are article types plus the THR Cover Story topic. This avoids creating overlapping section archives.

## Other seeded archives

| Taxonomy | Name and slug inventory | URL pattern |
|---|---|---|
| Topics (25) | Awards (`awards`); International (`international`); THR Cover Story (`thr-cover-story`); THR Investigates (`thr-investigates`); Obituaries (`obituaries`); Box Office (`box-office`); TV Ratings (`tv-ratings`); Grammys (`grammys`); K-Pop (`k-pop`); Country Music (`country-music`); Feinberg Forecast (`feinberg-forecast`); Awards Chatter Podcast (`awards-chatter-podcast`); Beyond the Book (`beyond-the-book`); Labor (`labor`); Representation (`representation`); Film & TV Tax Credits (`film-tv-tax-credits`); Broadway Grosses (`broadway-grosses`); Netflix (`netflix`); Disney (`disney`); Warner Bros. (`warner-bros`); Oscars (`oscars`); Emmys (`emmys`); Marvel (`marvel`); HBO (`hbo`); Golden Globes (`golden-globes`) | `/t/{slug}/` |
| Verticals (7) | Heat Vision (`heat-vision`); Live Feed (`live-feed`); The Race (`the-race`); THR Esq (`thr-esq`); The Fien Print (`the-fien-print`); Rambling Reporter (`rambling-reporter`); Behind the Screen (`behind-the-screen`) | `/e/{slug}/` |
| Video categories (12) | THR News (`thr-news`); Roundtables (`roundtables`); THR On The Cover (`thr-on-the-cover`); Hollywood Firsts (`hollywood-firsts`); Heat Vision Breakdown (`heat-vision-breakdown`); Women in Entertainment (`women-in-entertainment`); Red Carpet (`red-carpet`); Oscars (`oscars`); Emmys (`emmys`); Golden Globes (`golden-globes`); Social Impact Summit (`social-impact-summit`); Closer Look (`closer-look`) | `/vcategory/{slug}/` |

Topics can grow with editorial needs. The same slug in different taxonomies, such as Oscars, is valid because each has a different URL base. Do not generate people, film titles or company tags without editorial need.

## Page inventory

| Page title | URL | Initial state / purpose |
|---|---|---|
| Home | `/` | Draft until layout is ready; assign as front page when published |
| Masthead | `/masthead/` | Draft; real staff information |
| Contact | `/contact/` | Draft; real contact details |
| Tip Line | `/tip-line/` | Draft; configure recipient and form |
| Accessibility | `/accessibility/` | Draft; approved statement |
| Privacy Policy | `/privacy-policy/` | Draft; approved policy |
| Terms of Use | `/terms-of-use/` | Draft; approved terms |
| Cookie Policy | `/cookie-policy/` | Draft; approved policy |
| Newsletters | `/newsletters/` | Draft; configure provider |
| Special Issues | `/p/` | Draft container for `/p/{issue-slug}/` |
| Hubs | `/h/` | Draft container for `/h/{hub-slug}/` |

Do not seed a second Page at `/video/` or `/lists/`: their post-type archives own those routes. Special-issue and hub child Pages are created when needed and are not part of the 11-record starter count.

## Complete route contract

| Destination | Canonical pattern / example | Owner |
|---|---|---|
| Homepage | `/` | Home Page + front-page template |
| Parent / child category | `/c/movies/`, `/c/movies/movie-news/` | Native category archives |
| News / feature / review / interview / cover / podcast | `/movies/movie-news/{article-slug}-{post-id}/` | Native posts + core permalink handling |
| Topic | `/t/netflix/` | Native tags |
| Vertical | `/e/heat-vision/` | Core taxonomy |
| List / list archive | `/lists/{slug}/`, `/lists/` | List post type |
| Gallery | `/gallery/{slug}-{post-id}/` | Gallery post type + custom permalink handling |
| Video / archive | `/video/{slug}/`, `/video/` | Video post type |
| Video category | `/vcategory/roundtables/` | Video taxonomy |
| Author | `/author/{author-slug}/` | Co-Authors Plus |
| Search | `/results/?q={search-term}` | Core search route |
| Issue / hub | `/p/{slug}/`, `/h/{slug}/` | Child Pages |
| Archive pagination | `/c/movies/page/2/` | Archive query |
| Feed | `/feed/`, `/c/movies/feed/` | Native feeds |

Reserve `c`, `t`, `e`, `vcategory`, `lists`, `gallery`, `video`, `author`, `results`, `p` and `h`; also reject Page slugs that collide with parent-section routes.

Core must explicitly choose the primary category for post permalinks. Installing Yoast and selecting a primary category alone is not a sufficient routing implementation. Resolve the selected child category and its parent path; require one primary leaf for new posts. Assign its parent as well so parent archives and queries include the story. Multisection stories keep one canonical route; alternate and stale routes redirect to it. If the primary category is absent, show an editorial validation error instead of quietly publishing under Uncategorized.

Gallery ID suffixes require a permalink filter and rewrite rule. Flush rewrite rules once after successful setup, not on every request. Canonical and redirect logic lives in thr-core so deleting the importer leaves routes working.

## How many titles, subtitles and headings?

“Section” and “subsection” mean categories. “Title” and “subtitle” mean editorial text fields; they do not create categories or URLs.

| Object | Title / heading fields | Subtitle fields | Heading rules |
|---|---|---|---|
| Each article, list, gallery or video | 1 required main title; 1 optional SEO title override | 1 optional dek | One visible H1; body sections use H2 then H3 |
| Each of the 70 taxonomy terms | 1 required name; 1 optional display-heading override; 1 optional SEO title override | 1 optional subtitle | Archive displays one H1; name is heading fallback |
| Each of the 11 Pages | 1 required Page title; 1 optional display-heading override; 1 optional SEO title override | 1 optional subtitle | Template renders one H1; avoid duplicating it in blocks |
| Each vertical | Same archive fields above | Also 2 optional brand taglines | Taglines are ordinary text, not extra H1s |
| Each homepage module | 1 configurable heading | 1 optional tagline | Module headings are H2; card titles H3 |
| Each list item / gallery slide | 1 item title | Optional description / caption | Item titles H2; no new Page or category |

Initial taxonomy setup therefore creates **70 required term names and 70 default archive headings**, using the same names; there are **70 optional subtitle slots**, initially blank. It creates **11 Page titles and 11 optional Page subtitle slots**, initially blank. SEO title overrides are optional and default to the SEO template. No invented subtitles or legal text are required to finish structure setup.

Article headline, SEO headline, dek and kicker are distinct: the headline is H1, the SEO headline is browser/search metadata, the dek is a summary paragraph, and the kicker is a short section label. Cards reuse the article headline and dek. There is no extra card-title field in version 1. Body H2/H3 counts depend on the story, with no fixed limit.

Use `thr_archive_heading` and `thr_archive_subtitle` term meta for archive overrides; `thr_page_heading` and `thr_page_subtitle` for Pages; retain `thr_dek` and `thr_kicker` for stories. Vertical taglines remain two separate fields. Manifest `heading` and `subtitle` map to the correct term/Page meta; SEO fields belong to Yoast.

The homepage has one site-level H1 supplied by its template; lead and module headlines must not create additional H1s. Gallery shells also expose one gallery H1, with slide headings below it.

## One-click importer contract

**Tools → THR Importer → Create Site Structure** reads the versioned JSON manifest and performs the same structure-only operation as `wp thr seed --only=structure`.

1. Check administrator permission and request nonce; validate the entire manifest, unique keys, parent references, route collisions and count totals before writing. Confirm thr-core post types/taxonomies are registered. Lock the operation to prevent concurrent runs.
2. Create the six parent categories first, then 20 children. Resolve manifest parent keys to actual WordPress term IDs; never hard-code database IDs.
3. Create 25 topics, seven verticals and 12 video categories. Apply missing initial metadata without overwriting editor changes.
4. Create 11 draft Pages and store their stable seed keys. Parent containers remain drafts; adding child issues/hubs is a later editorial action. Do not set a draft Home Page as the public front page.
5. Configure category/tag bases and permalink structure on a fresh site. On an existing site with published content, detect incompatible settings and stop before writes, reporting the migration needed; do not silently change live URLs.
6. Register and fill the existing plan's menu locations using resolved term/Page IDs, not database-specific IDs. Link only published destinations in public menus. Reuse matching items and preserve editor customizations. Derive section and subsection navigation from the manifest.
7. Apply fresh-install settings and flush rewrites once. Display a result report: created / reused / skipped / failed by object type, with actual IDs and counts. Record manifest version and execution status only when complete.

Identify seeded objects by stable `key` metadata and adopt compatible existing taxonomy+slug matches or Page path matches. Never treat a slug shared across taxonomies as the same object. Fail on conflicting parent/path matches. Re-running creates zero duplicates and leaves editor text intact. WordPress writes are not assumed transactional: report partial failures and support a safe retry.

Structure mode creates **no stories, fake authors or demo media**. Staging's separate demo action may seed those. Delete the importer after production setup; terms, Pages and menus remain in WordPress while thr-core retains fields and routing.

## Acceptance checks

- JSON counts match the actual arrays: 26 categories (6 parents + 20 children), 25 topics, 7 verticals, 12 video categories, 11 Pages.
- On a clean install, one action creates exactly that inventory; repeat action creates zero duplicate objects or menu items.
- Every child has the specified parent and archive path; published stories appear in both leaf and parent archives.
- Each template renders one H1; blank subtitles leave no empty markup.
- Verify representative populated archives and article routes; empty archives follow the agreed empty-state/noindex policy.
- Primary-category and slug changes redirect old article URLs; alternate category paths do not produce duplicate canonical pages.
- Removing the importer preserves the structure and routes. Structure mode never publishes demo or policy content.

