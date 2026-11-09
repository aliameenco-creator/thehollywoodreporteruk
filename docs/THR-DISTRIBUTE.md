# THR Distribute — content packages and events for automations

Plugin: `wp-content/plugins/thr-distribute` (version 2.4.0; all THR packages share the release version). Packaged by `bin/package.py` as `thr-distribute.zip`.

**What it does:**
- Turns each article into a **content package**: links, text, transcript, images, audio.
- Sends an **event** to one webhook (n8n) whenever an article is published, updated, unpublished or deleted, or its audio changes.
- Automations **report back** where they posted the article. When it is deleted, the event lists every place it has to be removed from.

**The rule:** WordPress is the only place content is created or deleted. Every platform follows it. Adding a platform means a new n8n workflow, not a plugin change.

---

## Setup

1. Install and activate `thr-distribute.zip`, plus `thr-audio.zip` 2.3.0 so audio is included.
2. **Settings → THR Distribute:**
   - **Webhook address:** the n8n Webhook node (POST).
   - **Site ID:** set it once and never change it. Platforms store IDs built from it. Suggested: `thruk`.
   - Tick **Send events**, save, then **Send test event**.
3. **Signing secret:** copy it from the same page into n8n, to check signatures.
4. **API account for n8n:**
   - Create a WordPress user with the role **Distribution automation**. It can read packages and report receipts only; it can't edit articles.
   - In that user's profile, add an **Application Password**.
   - In n8n, use Basic Auth with the username and that password.
5. **Images:** in the Media Library, tick **Allowed off-site** on images you own or whose licence covers YouTube, podcasts and social. Unticked images have `offsite_allowed: false`, and workflows must not use them.

Until **Send events** is on, events wait in the queue. Nothing is lost.

---

## Events

One webhook receives everything. Route by the `type` field (or the `X-THR-Event` header).

| Type | When | What a workflow should do |
|---|---|---|
| `article.published` | First published, or published again after being unpublished | Post it |
| `article.updated` | A published article changed. `changes` lists which sections. | Update the copy (title, description, captions) |
| `audio.updated` | A published article's recordings were made, remade or removed | Post or replace audio and video |
| `article.unpublished` | Moved to draft, pending, private or trash | Take it down (hide/unlist). It may come back. |
| `article.deleted` | Deleted for good | Delete it everywhere listed in `package.distributions`, then report `removed` |
| `test.ping` | **Send test event** in Settings | Reply 200 |

### Event body (`thr.event/1`)

```json
{
  "schema": "thr.event/1",
  "id": "7f1c…-uuid",
  "type": "article.updated",
  "occurred_at": "2026-11-08T16:40:00+00:00",
  "site": { "id": "thruk", "url": "https://thehollywoodreporteruk.com/" },
  "package_id": "thruk-1234",
  "post_id": 1234,
  "version": 3,
  "changes": ["text", "article"],
  "package": { … see below … }
}
```

- **`id`:** unique per event. If a workflow sees the same `id` twice, ignore the second one; retries can deliver twice.
- **`version`:** goes up each time the package content changes. Ignore an event whose version is lower than one already processed for that `package_id`.
- **`changes`:** any of `article`, `text`, `images`, `links`, `audio`, plus `status` on deletion.
- **Quick edits are merged.** Several edits made before delivery arrive as one event with the latest package and all the changes combined.

### Headers and signature

| Header | Value |
|---|---|
| `X-THR-Event` | event type |
| `X-THR-Event-Id` | event `id` |
| `X-THR-Timestamp` | Unix seconds when sent |
| `X-THR-Signature` | `sha256=` + HMAC-SHA256(secret, timestamp + "." + raw body), hex |
| `X-THR-Site` | site ID |

In n8n, check the signature on the **raw body** in a Code node, and reject timestamps older than 5 minutes.

### Delivery and retries

- **Success:** any 2xx reply counts as delivered. Reply quickly, then do the slow work, so the request doesn't time out after 15 seconds.
- **Failures:** retried after 1, 2, 4, 8… minutes, up to 6 hours apart. After 12 tries the event is marked **failed**; **Retry failed** in Settings resends it.
- **Timing:** sending runs on WP-Cron. Keep the Hostinger cron job (every 5 minutes) loading `wp-cron.php`.
- **Clean-up:** delivered events are kept 30 days, then removed.

---

## The package (`thr.package/1`)

Fields are only ever **added**, never renamed or removed. Ignore fields you don't know.

| Field | Contents |
|---|---|
| `schema`, `id`, `version`, `post_id`, `type`, `status` | Identity. `id` is `{site_id}-{post_id}`. `status` is `publish`, `draft`, `trash`, … or `deleted`. |
| `site` | `id`, `name`, `url` |
| `published_at`, `modified_at` | ISO 8601, UTC |
| `links.canonical` | The article's address (empty when not published) |
| `links.short` | WordPress short link |
| `links.tracked_template` | Canonical link with `utm_source={platform}&utm_medium=distribution&utm_campaign=thr-distribute`. Replace `{platform}`, e.g. `youtube`. |
| `article` | `title`, `kicker`, `dek`, `summary` (excerpt, else dek, else the first 40 words), `article_type`, `authors[]` (`name`, `url`), `categories[]`, `verticals[]`, `tags[]` (each `id`, `slug`, `name`, `url`), `breaking`, `sponsored` (`is_sponsored`, `name`, `url`) |
| `text.html` | Article body as HTML, with blocks and shortcodes rendered |
| `text.plain` | Body as clean text, paragraphs separated by a blank line |
| `text.script` | **The exact words read aloud:** headline, dek, then body, without images, captions, embeds or tables. Same as `audio.transcript`. |
| `text.words`, `text.reading_minutes` | Length |
| `images.featured` | `id`, `url`, `width`, `height`, `large_url`, `alt`, `caption`, `credit`, **`offsite_allowed`**, or `null` |
| `audio` | `null` until a recording exists, then the object below |
| `distributions[]` | Where it is posted (see Receipts) |

### `audio` (added by THR Audio 2.3)

```json
{
  "default_voice": "v1",
  "voices": [
    { "voice": "v1", "label": "Emma", "region": "London", "gender": "female",
      "state": "full", "url": "https://audio.thehollywoodreporteruk.com/thr-audio/2026/11/….mp3",
      "duration": 182, "bytes": 1460000, "preview_url": "", "preview_duration": 0, "preview_bytes": 0,
      "mime": "audio/mpeg", "model": "eleven_flash_v2_5", "source": "elevenlabs",
      "generated_at": "2026-11-08T16:30:00Z", "up_to_date": true }
  ],
  "ai_generated": true,
  "provider": "elevenlabs",
  "transcript": "Big Film Review.\n\nA sharp, funny film.\n\n…",
  "captions": null,
  "podcast": { "feed": "https://…/feed/thr-audio/", "episode_guid": "thr-audio-thehollywoodreporteruk.com-1234" }
}
```

- **`state`:** `full` (whole article) or `preview` (opening clip only, with `url` empty).
- **`up_to_date: false`:** the article changed after the recording was made.
- **`ai_generated`:** always true. Apply each platform's current AI-voice disclosure rules.
- **`captions`:** reserved for word-timed SRT/VTT, which isn't built yet.
- **`podcast.episode_guid`:** matches the podcast feed, so Spotify and Apple treat a remake as the same episode.

### What is withheld

When an article is **not published** (unpublished, trashed or deleted), its package carries identity, `article`, `links` and `distributions`, but **`text` and `audio` are `null` and `images.featured` is `null`**. Drafts and private content never leave the site.

---

## API

**Base:** `https://thehollywoodreporteruk.com/wp-json/thr-distribute/v1`

**Auth:** Basic Auth with the Distribution automation user and its Application Password.

| Method and path | Use |
|---|---|
| `GET /packages?since=2026-11-01T00:00:00Z&page=1&per_page=20` | Everything changed since a time (ISO 8601 or Unix), oldest change first, at most 50 a page. Page 1 also lists `deleted` packages: those deleted since then, plus any still awaiting removal. For catch-up and recovery. |
| `GET /packages/{id}` | One package. For a deleted article, the record kept at deletion, with `deleted_at` and `removal_done`. |
| `POST /packages/{id}/receipts` | Report where it was posted, or that it was removed (below) |
| `GET /events?after=0&limit=50` | The event queue in order, each with a `sequence` number. For polling instead of webhooks, or replaying. |

### Receipts

Report every placement and every removal:

```json
POST /packages/thruk-1234/receipts
{ "platform": "youtube", "item": "article", "status": "live",
  "external_id": "dQw4w9WgXcQ", "url": "https://youtube.com/watch?v=dQw4w9WgXcQ", "message": "" }
```

- **`platform`:** lowercase letters, numbers, `-` and `_`, e.g. `youtube`, `spotify`, `apple-podcasts`, `tiktok`.
- **`item`:** what was posted. `article` by default. Use `audio:v1` when a platform gets one voice.
- **`status`:**
  - `live`: posted
  - `pending`: posting has started
  - `failed`: couldn't post (put the reason in `message`)
  - `removed`: taken down after unpublish or delete
- **Empty fields keep their values.** An empty `external_id` or `url` keeps the stored one, so a removal only needs `platform`, `item` and `status`.
- **Deletion records close themselves.** A deleted article's record closes once no receipt is `live` or `pending`. Until then it appears under **Settings → THR Distribute → Deleted articles still on other platforms**.

---

## Platform notes

| Platform | How it gets content | Removal |
|---|---|---|
| Spotify, Apple Podcasts, Amazon Music | The podcast feed `/feed/thr-audio/` (THR Audio) | The episode leaves the feed when the article is unpublished or deleted. Platforms drop it on their next check (hours, not instantly). Report `removed` once it's gone. |
| YouTube | n8n builds a video from the default voice's MP3 and an image, then uploads it with `text.script` as the description or captions | n8n deletes the video by `external_id`, then reports `removed` |
| Newsletter, social | n8n uses `article`, `links.tracked_template` and `images.featured` (only if `offsite_allowed`) | Delete the post if the platform allows it, then report `removed` |

---

## Data (for developers)

**Tables** (they survive deleted articles):

| Table | Holds |
|---|---|
| `{prefix}thr_dist_events` | The outbox: type, package, payload (JSON), status (`pending`/`sent`/`failed`), attempts, next attempt, last error |
| `{prefix}thr_dist_receipts` | One row per package + platform + item |
| `{prefix}thr_dist_removed` | Each deleted article: its last package (no text or audio), when deleted, when every platform confirmed removal |

**Post meta:**
- `_thr_dist_version`: package version
- `_thr_dist_hashes`: per-section hashes, to detect changes
- `_thr_dist_changed`: Unix time of the last event or receipt, used by `since`

**Attachment meta:** `thr_offsite_ok`.

**Options:**
- `thr_distribute_settings`
- `thr_distribute_secret`: not autoloaded. It can be overridden with `define( 'THR_DISTRIBUTE_SECRET', '…' )` in `wp-config.php`.

**Hooks:**
- **Filter `thr_distribute_package`:** add to the package. THR Audio uses it.
- **Action `thr_distribute_signal`:** `( $post_id, 'article.updated' | 'audio.updated' )` raises an event for a change made outside the editor.
- **Filter `thr_distribute_post_types`:** `post`, `thr_list`, `thr_gallery`, `thr_video`.
- **Filter `thr_distribute_signal_types`.**
- **Action `thr_distribute_enqueued`.**

**Capability:** `thr_distribute`, held by the **Distribution automation** role and by administrators.

---

## Testing

Tested on 8 November 2026 in WordPress Playground (PHP 8.2) with THR Core and THR Audio active, and the webhook simulated. 71 checks covered:
- events for publish, update, unpublish, trash, republish and delete, in the right order
- merging of quick edits
- no event for no-change saves or never-published drafts
- signatures
- retries after a failed webhook, and events waiting while sending is off
- the package's links, text, script, terms and audio
- API permissions, receipts, `since`, events and deleted packages
- deletion records closing only once every platform reports removal
- the image tick box

**Not yet tested:**
- a real n8n webhook
- MySQL on Hostinger (the tests ran on Playground's SQLite)
- the event volume of a full publishing day

## Still to do

- [ ] Install `thr-distribute.zip` and `thr-audio.zip` 2.3.0 after R2 is live (THR-AUDIO.md). Set the site ID, webhook and secret. Create the automation user.
- [ ] Build the n8n receiver: check the signature, ignore duplicate IDs and older versions, route by type.
- [ ] Build the first platform workflow (YouTube), including deletion.
- [ ] THR Audio: word timings from ElevenLabs, producing `audio.captions` (SRT/VTT).
- [ ] Tick **Allowed off-site** on owned images.
