# The Hollywood Reporter UK — Site Guide

How to run the site day to day: publishing stories, lists, galleries and videos, the homepage, newsletters, forms, SEO and cookies. The last section covers the one-time setup on the live site.

---

## 1. Publishing a story (Posts → Add Post)

Write the headline and body as normal. The right-hand sidebar (the **Post** tab) has three THR panels.

### Story checklist
A live tick-list that shows what is still missing before you publish:

| Item | Why it matters |
|---|---|
| Headline | Must be longer than a few words. |
| Dek (summary line) | Shown under the headline, on cards, and used as the Google description. |
| Section chosen | Decides the story's URL and breadcrumbs. |
| At least one topic | Powers "Read More About" and Related Stories. |
| Featured image | Needed on the homepage and when shared on social media. |
| Image credit | Every photo needs a credit (see section 5). |
| Review box filled in | Reviews only: the title and the bottom line. |

You can publish with items unticked. The list is a guide, not a lock.

### Story settings
- **Story type**: Standard news, Review, Feature, Cover story, Interview or Podcast. Choosing **Review** adds the *Review details* panel and the honey-coloured review box on the page.
- **Dek**: one sentence, ideally under 160 characters. The counter shows the length.
- **Label above headline**: optional, for example `EXCLUSIVE`.
- **Top story on the homepage**: the newest story with this switched on becomes the big lead story.
- **Show in the red Breaking News bar**: puts the story in the red bar across the top of every page. Switch it off when the story stops being breaking.

### Review details (reviews only)
Title of the work, the bottom line, director, writer, cast, where to watch, rating and running time, and optional full credits. These fill the review box and tell Google the page is a review.

### Sections, Topics, Verticals
- **Sections**: tick **one subsection**, such as *Movie News* or *TV Reviews*. Its parent (Movies, TV) is implied. The URL becomes `/movies/movie-news/your-headline-123/` and the breadcrumbs read *Home > Movies > Movie News*.
- If a story sits in two sections, use Yoast's **Make primary** link to choose the one for the URL and breadcrumbs.
- **Topics**: people, shows and themes, such as *BAFTA* or *Box Office*. Two to five is plenty.
- **Verticals** (optional): branded strands such as *Heat Vision*. A story in a vertical shows the vertical's name as a banner above the breadcrumbs.

### Tracking stories in the Posts list
The **Story** column shows each story's type, plus **Top story**, **Breaking** and **No image** flags. Use it to spot what is on the homepage or still in the breaking bar.

---

## 2. Lists (Lists → Add New)

Ranked or curated pieces, such as *"The 25 Best British TV Shows of 2026"*. They live at `/lists/your-title/` and are gathered on the `/lists/` page.

1. Write the intro, then add each entry with a **Heading** block (`25. Show Name`), an **Image** block and a paragraph.
2. Add a featured image and a dek, as for a story.

## 3. Galleries (Galleries → Add New)

Photo stories, such as red-carpet galleries. They live at `/gallery/your-title-123/`.

1. Set the lead photo as the **Featured image**.
2. Add a **Gallery** block in the body and upload the photos. Give each photo a caption and a credit in the Media Library.
3. Tick a section so the gallery gets breadcrumbs (for example *Home > Movies > Movie News*).

## 4. Videos (Videos → Add New)

Video posts with a click-to-play YouTube player. They live at `/video/your-title/` and are listed on `/video/`.

1. Paste the YouTube link into **Story settings → YouTube link**.
2. Set a featured image. It appears as the poster frame, so YouTube only loads (and only sets its cookies) when the reader presses play.
3. Choose a **Video Category** for the video section pages.

## 5. Media (photos and credits)

When you upload or select an image, fill in:
- **Alt text**: a plain description for screen readers and Google.
- **Caption**: optional, shown under the photo.
- **Image Credit**: the photographer or agency, such as *Getty Images*. It shows in small capitals after the caption, and the story checklist flags it if missing.

---

## 6. Homepage

The homepage builds itself. There is nothing to arrange by hand.
- **Lead story**: the newest post with *Top story on the homepage* switched on.
- **Three cards under the lead and the Featured Reporting river**: the newest posts, never repeating a story already shown higher up the page.
- **Latest News rail**: the six newest posts, including the lead (as on THR).
- **Most Popular**: the most-read stories, counted automatically.
- **Breaking bar**: the newest post with *Show in the red Breaking News bar* on. Readers can close it.

## 7. Phones: the bottom tab bar

On phones, a bar with **Home · Sections · Search · Video · Newsletters** sits at the bottom of the homepage, section pages and search results. It is deliberately **hidden on articles** so it never covers text while someone reads, and it slides away as the reader scrolls down. Turn it off under **Settings → THR Settings → Mobile**.

On desktop, a compact header with the menu, logo, sections and Subscribe button slides in once you scroll past the main header.

---

## 8. Newsletters

- **The `/newsletters/` page** lists the newsletters with checkboxes, an email box and a consent tick. The footer and menu sign-up boxes send readers there with their email already filled in.
- **Subscribers** (left menu, admins only) lists every sign-up: email, newsletters chosen and consent time. Signing up again merges the choices instead of creating a duplicate. **Export CSV** downloads the full list.
- **Sending** newsletters needs a mail service such as **Brevo** (free up to 300 emails a day) or **Mailchimp**. Import the CSV, or have a developer connect the provider to the `thr_newsletter_signup` hook so new subscribers are sent across automatically.
- To change the newsletter line-up, ask a developer to edit `thr_newsletters()` in `thr-core/includes/newsletter.php`.

## 9. Contact Us and Tip Line

- **`/contact/`**: name, email, a topic (general, editorial, corrections, advertising and partnerships, subscriptions, careers), subject and message. Messages are emailed to the address in **Settings → THR Settings → Contact Form Email Recipient**. Nothing is stored.
- **`/tip-line/`**: the anonymous tip form. Tips go to **Tip Line Email Recipient**.
- Both forms have spam protection (a hidden trap field and a limit of five messages per hour per visitor), and these pages are never cached.
- **Email delivery**: install **WP Mail SMTP** and connect it to a Hostinger mailbox (for example `newsroom@yourdomain`). Without it, form emails often land in spam.

---

## 10. SEO: use Yoast SEO (free)

**Recommendation: install Yoast SEO.** It works with this theme and handles titles, meta descriptions, social previews, canonical URLs, XML sitemaps, structured data and `llms.txt`. The THR Core plugin adds only what Yoast lacks for a news site:

| Feature | Provided by |
|---|---|
| SEO title and meta description per story | Yoast (if left empty, THR fills it from the **dek**) |
| Facebook / X previews | Yoast (same dek fallback) |
| XML sitemap (`/sitemap_index.xml`) | Yoast |
| **Google News sitemap** (`/news-sitemap.xml`, last 48 hours) | THR Core (Yoast charges for this) |
| Article structured data, typed as **NewsArticle** | Yoast, with THR setting the type |
| Review structured data (title, director) | THR Core |
| `llms.txt` (a summary for AI assistants) | Yoast → Settings → Site features → **llms.txt** |
| Primary section for the URL and breadcrumbs | Yoast's **Make primary** |
| Breadcrumbs on the page | The theme (leave Yoast's breadcrumbs **off**) |

Yoast setup, in its first-time configuration:
1. **Site representation**: Organisation, *The Hollywood Reporter UK*, upload the logo.
2. **Settings → Advanced → Crawl optimisation**: do **not** turn on "Remove categories prefix". The site's URLs depend on the `/c/` section prefix.
3. **Settings → Site features**: turn on **XML sitemaps** and **llms.txt**.
4. **Google Search Console**: verify the domain, then submit `sitemap_index.xml` and `news-sitemap.xml`.

If Yoast is ever switched off, the site still prints a basic description and social tags from the dek, so nothing breaks.

---

## 11. Cookies: is a banner required?

**Not today.** UK law (PECR, UK GDPR) needs consent only for *non-essential* cookies, such as advertising, analytics and tracking pixels. As built, the site sets none for readers:
- WordPress sets login cookies only for staff who sign in.
- Closing the breaking bar remembers that choice in the reader's own browser. That is a preference the reader asked for, so it is exempt.
- YouTube loads only when a reader presses play, through YouTube's privacy-enhanced (no-cookie) player.
- There are no ads on the site.

What you still need:
- Publish the **Cookie Policy** page (it is currently a draft) explaining the above, and make sure the **Privacy Policy** covers newsletter and contact-form data.
- The moment you add **Google Analytics, a Meta pixel, or ad tags**, you need a consent banner that blocks them until the reader agrees. **Complianz** (free) or **CookieYes** handle this.
- If you want visitor statistics **without** a banner, use a cookieless tool such as **Plausible** or **Cloudflare Web Analytics**.
- Fonts currently load from Google Fonts. That sets no cookies, but it does share readers' IP addresses with Google, so mention it in the Privacy Policy, or have the fonts self-hosted later.

---

## 12. One-time setup on the live site (after installing this update)

1. Install the new theme and plugin versions: Dashboard → Updates once releases are set up, or upload the zips this time.
2. **Tools → THR Importer → Seed / Sync Site Structure**: this publishes the Contact and Newsletters pages and adds their intro text if they are empty. Nothing else is changed.
3. **Settings → THR Settings**: set the Contact and Tip Line email addresses, and check the Mobile tab bar option.
4. Install **Yoast SEO** and follow section 10.
5. Install **WP Mail SMTP** and send a test email.
6. **LiteSpeed Cache → Purge All**, so visitors get the new design.
7. Delete WordPress's default **"Hello world!"** post if it still exists.
