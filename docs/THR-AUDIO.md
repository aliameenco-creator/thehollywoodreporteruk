# THR Audio — "Listen to this article"

Plugin: `wp-content/plugins/thr-audio` (version 2.4.0; all THR packages share the release version). Packaged by `bin/package.py` as `thr-audio.zip`.

It makes spoken versions of articles with ElevenLabs and shows a player at the top of each article, with play/pause, seek, ±15 seconds, speed (1x / 1.25x / 1.5x / 2x) and a voice menu.

---

## How it works

1. **Publish → audio.** When any article is published, whether written by hand or by the n8n automation, the plugin queues:
   - the **default voice in full**,
   - a **~20-second opening clip** for each other voice ticked in the editor (all ticked by default).
2. **Background, straight away.** The queue starts as soon as the publish request finishes, through a background request to `admin-ajax.php?action=thr_audio_run` protected by a secret token. Publishing never waits for audio. A WP-Cron event (`thr_audio_cron`) is the fallback if that request is blocked.
3. **Saved to the Audio Library.** Each recording is saved as an MP3 in `wp-content/uploads/thr-audio/YYYY/MM/`, attached to the article, then listed in the post's `thr_audio` field. Recordings are hidden from the Media Library (uploaded MP3s still show there) and listed under **Audio Library** in the admin menu. The article's page cache is purged (LiteSpeed) so the player shows the new voice.
4. **Reader picks another voice:**
   - **At the start of the article:** the opening clip plays at once. The site makes the full version behind it, and the player swaps over at the same moment.
   - **Mid-article:** the current voice keeps playing and switches at the same point when the new one is ready.
   - **The wait:** with the Flash model this takes ~5–15 seconds. The player shows "Preparing…", then "taking longer than usual" after 30 seconds, then "Try again" if it fails.
   - **Once made,** a voice is saved and every later reader gets it instantly.
5. **Edits after publishing** don't remake audio automatically, to keep costs down. The editor box marks the voice **Outdated** and an editor clicks **Remake**.

## Audio stalling on Hostinger's CDN over HTTP/3 (found 6 November 2026)

**The test:** real Chrome against the live site, from Pakistan.
- **Over HTTP/2:** every recording downloads in full in 2–5 seconds, and playing, switching and replaying all start within about 0.1–0.8 seconds.
- **Over HTTP/3 (QUIC):** the same MP3s crawl (85–350 KB in 12 seconds) or hang with no response. Chrome moves to HTTP/3 after the CDN advertises it (`alt-svc: h3`), so a player that worked can hang on the next reload.
- **Not yet known:** whether this is Hostinger's CDN or the local internet provider slowing HTTP/3. It hasn't been tested from the UK.

**Player changes in 2.1.2:**
- The "metadata" preload when the player scrolls into view is gone. Chrome paused that download part-way, which made the hang more likely and added a second download running at the same time.
- Hover, focus or touch on play still starts loading the full file.
- **Watchdog:** if sound hasn't started 2.5 seconds after play, with no data arriving, the player asks again. The second and third tries add `?r=` so they use a new request. After three tries it shows "Couldn't load this voice" with "Try again". A slow but working download, where data is still arriving, isn't interrupted.

**The real fix is the delivery path, not the player.** Options, in order:
- **Turn the Hostinger CDN off for a short test**, to see if the origin serves HTTP/2 only.
- **Ask Hostinger support to turn off HTTP/3 on the CDN.**
- **Serve audio from `audio.` on Cloudflare (R2)**, where HTTP/3 can be switched off.

## Storage on Cloudflare R2 (2.2)

**Why:** Hostinger's CDN stalled MP3s over HTTP/3 (above). Recordings can now be served from Cloudflare R2 at `https://audio.thehollywoodreporteruk.com`, where HTTP/3 is off and every file is cached at Cloudflare's edge for a year.

**Cloudflare (set up 8 November 2026):**
- Zone `thehollywoodreporteruk.com` on Cloudflare (Free plan). The domain is registered at r4l.com; the main site and mail stay on Hostinger as DNS-only (grey cloud) records.
- Bucket `thr-audio-uk`, Western Europe. The public `r2.dev` address is off.
- Custom domain `audio.thehollywoodreporteruk.com` on the bucket, minimum TLS 1.2.
- CORS: `GET`/`HEAD` from the site only, `Range` allowed.
- Cache rule: everything on `audio.` cached for a year at the edge and in browsers.
- Rate limit: 120 requests per 10 seconds per IP on `audio.`, then blocked for 10 seconds.
- HTTP/3 off. This is a zone-wide setting; while the main site is grey cloud it only affects `audio.`.
- Measured: first request 1.8 s (from R2), then 0.3 s from cache (`cf-cache-status: HIT`), range requests return `206` from cache, no `alt-svc: h3`.

**How it works in WordPress:**
- Recordings stay ordinary attachments. The Audio Library, editor box and podcast feed are unchanged.
- With **Settings → THR Audio → Storage** on **Cloudflare R2**, each new recording is uploaded to R2 straight after it is saved, under the same path as on this server (`thr-audio/YYYY/MM/name.mp3`), then read back to check its size.
- `wp_get_attachment_url()` returns the `audio.` address for files on R2. Switching back to **This server** points every address at the local copy again, and clears the page cache and podcast feed.
- If an upload fails, the local copy keeps playing and the file shows as failed in the Audio Library.
- **Local copies are kept.** Nothing in 2.2 removes MP3s from this server except the deletes below.

**Moving existing recordings:** the Audio Library shows how many recordings are not on R2 yet. **Copy all to R2** copies 25 at a time and keeps going until done. **Retry failed** retries the ones that failed.

**Deleting:**
- Deleting a recording (Remake, Audio Library delete) also deletes its R2 copy. A failed R2 delete is kept and retried each time the Audio Library is opened.
- **Deleting an article permanently** now deletes the recordings the plugin made for it, on this server and on R2. Before 2.2 they were left behind.
- Trashed, draft or unpublished articles keep their audio so they can be restored. The player, reader endpoint and podcast feed only use published articles.
- With `THR_CF_ZONE_ID` and `THR_CF_API_TOKEN` set, a deleted file is also purged from Cloudflare's cache at once. Without them, a deleted file's address can keep working from cache until Cloudflare evicts it.

**Setup:**
1. In Cloudflare: **R2 → Manage API tokens → Create API token** with **Object Read & Write**, applied to the **`thr-audio-uk` bucket only**. Copy the Access Key ID and Secret Access Key (shown once).
2. Optional: **My Profile → API Tokens → Create Token** with **Zone → Cache Purge** for `thehollywoodreporteruk.com` only.
3. Add to `wp-config.php` on the server, above "That's all, stop editing!":
   ```php
   define( 'THR_R2_ACCOUNT_ID', 'fb897a57dec93f174912bc73c245a56a' );
   define( 'THR_R2_ACCESS_KEY_ID', '…' );
   define( 'THR_R2_SECRET_ACCESS_KEY', '…' );
   define( 'THR_R2_BUCKET', 'thr-audio-uk' );
   define( 'THR_R2_PUBLIC_URL', 'https://audio.thehollywoodreporteruk.com' );
   // Optional:
   define( 'THR_CF_ZONE_ID', '7cb5efd9130d6fb176c2121b417c74ae' );
   define( 'THR_CF_API_TOKEN', '…' );
   ```
4. **Settings → THR Audio → Storage → Test R2.** It uploads a small file, reads it back from the public address, then deletes it.
5. Switch Storage to **Cloudflare R2**, then **Audio Library → Copy all to R2**.

**Tested** on 8 November 2026 in WordPress Playground (PHP 8.2) with R2's replies simulated: 32 checks covering upload, addresses in both modes, failed uploads, copying existing files, deletes and retries, permanent article delete, and the admin screens. **Not yet tested against the real bucket**: the request signing is only proven once **Test R2** passes with a real key.

## Queue recovery (2.3.1)

**What went wrong on 9 November 2026:**
- 21 articles were published at once (03:07 UTC), each queueing 3 full voices.
- At 03:09 the background worker stopped mid-job, probably because the host ended the PHP request.
- It left its lock behind. The reader endpoint only restarted the queue when no lock existed at all, and the WP-Cron fallback was a one-off event, so nothing restarted it.
- The job in progress had already been taken off the queue, so it was lost. Lakeem Rose's article got 2 of its 3 voices.
- It was restarted by hand at 03:21 with a reader request for an article that had no audio.

**The fixes in 2.3.1:**
- **Jobs aren't lost:** a job stays in the queue, marked running, until it finishes or fails for good. A job "running" for more than 5 minutes belonged to a dead worker and is run again.
- **The reader endpoint restarts the queue when no worker is alive.** A lock untouched for 5 minutes counts as dead.
- **Repeating safety net:** `thr_audio_watchdog` runs every 5 minutes on WP-Cron and starts a worker if jobs are waiting and none is alive.
- **Shorter shifts:** the worker now runs 45 seconds per shift, down from 150. Live, Hostinger stopped a worker about 60 seconds after it started.

**Tested** in Playground with 16 checks:
- a dead worker with a stale lock and an in-flight job is recovered by the watchdog
- a live worker is left alone
- the endpoint requests a restart past a stale lock
- permanent failures leave the queue

The storage (32) and Distribute (72) suites still pass.

**Hostinger:** WP-Cron only runs on visits. Add the server cron job (every 5 minutes, `wp-cron.php`), so the watchdog also runs at night and in maintenance mode.

## THR Distribute (2.3)

With THR Distribute active, each article's content package gets an `audio` section:
- every voice's address (on R2 when Storage is R2), length, size and model,
- whether it is up to date, the read-aloud script as the transcript, and the podcast episode ID.

A change to a published article's recordings sends an `audio.updated` event. Details are in [THR-DISTRIBUTE.md](THR-DISTRIBUTE.md). Without THR Distribute this code does nothing.

## Other voices: full or opening clip (2.1.1)

**Settings → THR Audio → Behaviour → Other voices**:
- **Full recording for every voice** (the default from 2.1.1): every ticked voice is made in full on publish, so switching voices is instant. Each extra voice costs about one more article's worth of characters.
- **Opening clip only:** the earlier, cheaper behaviour. Other voices get a ~20-second clip and are made in full when a reader picks them.
- **Older articles:** "Make audio now for ticked voices" in the Article Audio box fills in the missing full recordings.

**Voices made on request no longer depend on the background request.** On 6 November 2026, a voice requested on the live site took several minutes, because the self-request or WP-Cron was slow to start the work.
- Now the reader endpoint replies "generating", then does the work in the same PHP process after the reply has gone (`litespeed_finish_request` / `fastcgi_finish_request`).
- Each poll from the player restarts the work if it is queued and no worker is running.
- The old background request is only used when the server can't finish a request early.

**Player fixes (2.1.1):**
- Switching voice after the article has finished starts the new voice from the beginning. It used to play its last second and stop.
- A playback error retries once quietly before showing "Couldn't load this voice".
- Any message clears once sound is playing.

## Playing instantly

The page itself loads no audio. To make the click start at once:
- **In view:** once the player has been on screen for about a second, the browser reads the start of the file (`preload="metadata"`).
- **Reaching for play:** hovering, focusing or touching the play button buffers the opening (`preload="auto"`).
- **Voice menu:** opening it fetches the other voices' opening clips (about 160 KB each), so picking a voice also starts at once.
- **Caching:** every file gets a random tail in its name, so a remake always has a new address. `uploads/thr-audio/.htaccess` (written by the plugin) lets browsers and the CDN keep MP3s for a year (`Cache-Control: public, max-age=31536000, immutable`).

Measured on 6 November 2026: Hostinger's CDN (hcdn) missed every range request for an MP3, and the server took 0.7–0.9 seconds before the first byte. MP3s had no `Cache-Control` header.

**Switching voices safely:** each request for a voice is tied to that choice. If a reader picks voice B and then C, a late answer for B is dropped and never plays under C. Each file load is numbered, so a late "jump to position" from an earlier file can't move the new one.

## Audio Library (admin menu)

- **Lists every recording the plugin made:** article, voice, full or opening clip, length, size and date, with a player, **Download** and **Copy link**.
- **Filters:** voice, type, month, and a search on article titles. 50 rows a page.
- **Header totals:** recordings, storage used, characters made this month, and the podcast feed address.
- **Delete:** only shown for recordings no article uses any more, for example after **Remove** in the Article Audio box.
- **Access:** anyone who can edit posts. No extra tables. Each file carries `_thr_audio_voice`, `_thr_audio_kind` and `_thr_audio_bytes`. Files made before 2.1 are labelled once, on the first admin visit after updating.

## Podcast feed (Spotify, Apple Podcasts)

- **Feed:** `/feed/thr-audio/`. Every published article whose default voice has a full recording is an episode, newest first (300 by default).
- **Stable episode IDs:** `thr-audio-<host>-<post ID>`. A remade recording updates the episode rather than adding a new one.
- **Settings → THR Audio → Podcast:** show title, description, author, owner email, cover image (square, 1400–3000 px), category, number of episodes, and an on/off switch.
- **Caching:** the feed is cached and rebuilt when audio changes, an article is published or unpublished, or the settings are saved.
- **To list it:** submit the feed once in Spotify for Creators and Apple Podcasts Connect.
  - The feed has to be publicly reachable, so maintenance mode must be off.
  - Check each platform's current rules on AI-voiced content first.

## Setup checklist

1. Install and activate `thr-audio.zip`. The theme's `single.php` calls `thr_audio_player()` between the hero image and the share bar. If a theme doesn't, the plugin adds the player at the top of the article body itself.
2. Add the key to `wp-config.php`, above "That's all, stop editing!":
   ```php
   define( 'THR_ELEVENLABS_API_KEY', 'your-key' );
   ```
   The key is deliberately **not** stored in the database or the settings screen, and it never reaches the browser.
3. Open **Settings → THR Audio** and:
   - enter the **4 voices**: name, region and gender (what readers see) plus the ElevenLabs Voice ID,
   - pick the **default** voice,
   - pick the **model**: the list loads from the ElevenLabs account,
   - set the **monthly character limit** just under the plan's allowance,
   - click **Test connection**: it shows the plan and the credits used.
4. Test on a draft in **Test mode** first. It makes no ElevenLabs calls and costs nothing: every voice uses `assets/audio/sample.mp3`. Then switch to **Live**.
5. **Hostinger:** add a server cron job every 5 minutes loading `https://thehollywoodreporteruk.com/wp-cron.php`. It's the backup so queued audio always gets made.

### Getting a Voice ID
In ElevenLabs, open the Voice Library, pick a voice, then **Add to my voices**. Open it in My Voices and copy the **Voice ID**.

## Settings (Settings → THR Audio)

| Setting | Default | Notes |
|---|---|---|
| Mode | Test | Live needs the key in wp-config.php |
| Model | Eleven Flash v2.5 | Flash/Turbo use about half the credits of the high-quality model. Applies to new audio only. |
| File format | MP3 64 kbps | Fine for speech; 128 kbps doubles the file size |
| Voices | 4 empty slots (v1–v4) | Slots are fixed, so renaming a voice keeps its existing audio |
| On publish | On | Makes audio automatically when an article is published |
| Opening clip length | 350 characters | ≈ 20 seconds; always ends on a full sentence |
| Readers can request voices | On | Makes the full version when a reader picks a voice |
| Limit per visitor | 10 new voices/hour | Stops bots burning credits |
| Monthly limit | 2,000,000 characters | Generation stops and an email is sent when reached; 0 = no limit |
| Alert email | Site admin email | Receives the monthly-limit email |

## The Article Audio box (post editor)

One row per voice:
- **On publish:** whether that voice is made when the article is published.
- **Status:** Not made yet / Queued / Making… / Opening clip ready / Full recording ready / Outdated / Failed (with the reason). It shows the length, **which model made it**, and the date.
- **Listen:** previews of the full recording and the opening clip.
- **Actions:**
  - **Make full recording** or **Remake**,
  - **Use my own MP3**: pick a file from Media,
  - **Remove**: takes the voice off the article; the MP3 stays in Media.
- **Make audio now for ticked voices:** for older articles, or ones published before the plugin was set up.

Actions apply immediately; you don't need to save the post. The box refreshes itself while audio is being made.

The **Posts list** has an **Audio** column: "Making…", "✓ 1 full, 3 clips", "—", or "Failed — open to retry".

## Costs (as of November 2026; check elevenlabs.io/pricing/api)

- **Volume:** ~30 articles a day, about 900 a month.
- **Per article:** the default voice in full plus three opening clips is roughly 2,500 characters for a ~1,500-character article, so about 2.3M–3.6M characters a month depending on article length.
- **Chosen plan:** **Scale (~$299/month, ~3.7M characters on the high-quality model, about double on Flash).** Pro (~$99) is too small; Business (~$990) is only needed if all 4 voices are made in full for every article.
- **Avoid pay-as-you-go top-ups:** they cost about 2–3 times more per credit. Keep **auto top-up off**.
- **After the first week:** check usage and move down to Pro if usage is under a third of the allowance, or switch to Flash before upgrading.

## Data (for developers)

**`thr_audio`** (post meta, object, exposed to the REST API so outside tools can write it):
```json
{
  "default": "v1",
  "variants": [
    { "voice": "v1", "label": "Emma", "region": "London", "gender": "female",
      "attachment_id": 1234, "url": "", "duration": 179,
      "preview_attachment_id": 1235, "preview_duration": 21,
      "model": "eleven_flash_v2_5", "text_hash": "md5 of the script", "source": "elevenlabs",
      "generated_at": "2026-11-02T14:05:00Z" }
  ]
}
```
- **Full recording:** `attachment_id`, or `url` for an outside file. **Opening clip:** `preview_attachment_id`.
- **`text_hash`** is the MD5 of the article script when the audio was made. A mismatch with the current text means **Outdated**.
- **`source`** is `elevenlabs`, `test` or `manual`.
- **Voices** not in Settings (for example ones added by hand in 1.x) still play but can't be made on request.

**Other meta:**
- `thr_audio_status`: per voice and per kind (`full` or `preview`), the job state, error and time.
- `thr_audio_publish_voices`: the slots ticked for publish. If absent, all voices are used.
- `_thr_audio_generated` (on attachments): marks files the plugin made. Only those are deleted when a voice is remade.

**Options:**
- `thr_audio_settings`: the settings.
- `thr_audio_queue`: pending jobs.
- `thr_audio_usage_YYYYMM`: characters sent this month.
- `thr_audio_run_token`: the background-request secret.
- `thr_audio_lock_*`: short-lived locks.

**Reader endpoint:** `POST /wp-json/thr-audio/v1/voice` with `post`, `voice` and `create` (1 asks for the voice to be made, 0 only checks).
- **Replies:** `ready` (with `url` and `duration`), `generating`, `none`, `failed`, `busy` (rate limited) or `unavailable`.
- **Only accepted for:** published articles and configured voices, within the per-visitor limit and the monthly cap.
- **Caching:** it uses POST and sends `Cache-Control: no-store` so LiteSpeed never caches replies.

**What gets read aloud** (`includes/text.php`):
- **In order:** headline, dek (`thr_dek`), then the body. The byline is not read.
- **Not read:** images and captions, embeds, scripts, code, tables and bare links.
- **Long articles** are split into pieces of at most 2,500 characters at paragraph or sentence breaks. The neighbouring text is sent with each piece so the voice stays consistent.
- **Filter:** `thr_audio_script`.

**Filters and hooks:** `thr_audio_post_types`, `thr_audio_script`, `thr_audio_client_ip` (set this if the site moves behind a proxy or CDN that changes `REMOTE_ADDR`), and the action `thr_audio_purge_post`.

## Files

| File | What it does |
|---|---|
| `thr-audio.php` | Loads everything; post types; deactivation clears the cron event |
| `includes/settings.php` | Settings page, voice slots, model list, Test connection |
| `includes/class-thr-audio-elevenlabs.php` | ElevenLabs API calls (text-to-speech, models, subscription); test-mode sample |
| `includes/text.php` | Turns an article into the script, the opening clip and the pieces |
| `includes/meta.php` | `thr_audio` field, cleaning, reading voices for the player |
| `includes/generator.php` | Publish trigger, queue, background worker, retries, saving files, spending cap |
| `includes/rest.php` | Reader endpoint with rate limit |
| `includes/meta-box.php` | Article Audio box, its AJAX actions, Posts list column |
| `includes/player.php` | Player markup, asset loading, fallback placement |
| `includes/library.php` | Audio Library screen, hiding recordings from Media, delete, 2.1 upgrade |
| `includes/podcast.php` | Podcast feed and its settings |
| `includes/distribute.php` | Adds recordings and the script to THR Distribute packages; raises `audio.updated` |
| `includes/storage.php` | Cloudflare R2 upload, `audio.` addresses, copying existing files, deletes and article clean-up |
| `assets/js/player.js` | Front-end player (vanilla JS, deferred) |
| `assets/js/admin.js` | Editor box behaviour |
| `assets/css/player.css` | Player styles (theme tokens from `variables.css`) |
| `assets/audio/sample.mp3` | Test-mode stand-in recording |

## Reliability and security

- **Retries:** temporary failures (network, ElevenLabs busy or 5xx, empty reply) are retried 3 times with a growing wait. Permanent ones (bad key, unknown voice, no credits, cap reached) fail at once with the reason shown in the editor.
- **Locks:** one background worker at a time, and readers' requests are handled before publish jobs. The same post, voice and kind is never queued twice.
- **Permissions:**
  - settings require `manage_options`,
  - editor actions require `edit_post` on that article plus a nonce,
  - the background worker requires the secret token,
  - errors shown to readers never include internal details, and logs never contain the key.

## Testing

It was tested end to end on 2 November 2026 in a local WordPress (WordPress Playground 3.1.40, PHP 8.2) in Test mode:
- publish queues the right jobs, the background trigger completes them by itself, and files land in Media attached to the post,
- reader requests reach `generating` then `ready`, drafts and unknown voices are refused, and the rate limit and no-cache headers work,
- remaking deletes the old file, editing marks audio Outdated, removing works, and the player renders on the article page.

**Not yet tested:**
- real ElevenLabs calls (needs the key),
- the player's voice switching in a real browser,
- the background trigger on Hostinger (the maintenance-mode plugin could block it; the server cron is the backup).

## Decisions and why

- **Built into the plugin, not n8n:** it covers manual and automated posts alike, and readers can ask for voices on the spot, which n8n can't serve.
- **ElevenLabs:** the only provider found with natural British regional accents (Google, Azure and Polly are mostly standard southern English).
- **One full voice plus opening clips:** making all 4 voices in full for 900 articles a month would cost roughly 4 times as much, and most readers never switch.
- **No "AI-generated voice" label** on the player, by request.
- **Trinity Audio was considered and dropped:** a third-party script, per-article pricing (~$0.67/article), and no accent choice.

## Still to do

- [ ] After the 2.1 release, measure an MP3 on the live site again: first-byte time, `Cache-Control` header and `x-hcdn-cache-status`.
- [ ] Podcast: upload the cover image, fill in the Podcast settings, then submit the feed to Spotify and Apple once the site is out of maintenance mode.
- [ ] R2: create the bucket-only API token, add it to `wp-config.php`, run **Test R2**, switch Storage to R2, then **Copy all to R2**.
- [ ] R2: after a week or two of everything playing from `audio.`, decide whether to remove local copies (manual, not built yet).
- [ ] Before putting the main site behind the orange cloud: turn off Hostinger's CDN, make `thr_audio_client_ip` read `CF-Connecting-IP`, connect LiteSpeed to Cloudflare, and allow the site's own `admin-ajax.php` / `wp-cron.php` requests.

- [ ] Choose the 4 voices (name, region, gender, Voice ID) and enter them in Settings.
- [ ] Subscribe to Scale, turn auto top-up off, add the key, Test connection, switch to Live.
- [ ] Add the Hostinger cron job for `wp-cron.php`.
- [ ] Check real cost per article after the first week; adjust the plan or model.
- [ ] Optional later: make all voices automatically for articles that pass a view threshold; backfill older articles in batches.
