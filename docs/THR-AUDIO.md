# THR Audio — "Listen to this article"

Plugin: `wp-content/plugins/thr-audio` (version 2.0.0). Packaged by `bin/package.py` as `thr-audio.zip`.

It makes spoken versions of articles with ElevenLabs and shows a player at the top of each article, with play/pause, seek, ±15 seconds, speed (1x / 1.25x / 1.5x / 2x) and a voice menu.

---

## How it works

1. **Publish → audio.** When any article is published, whether written by hand or by the n8n automation, the plugin queues:
   - the **default voice in full**,
   - a **~20-second opening clip** for each other voice ticked in the editor (all ticked by default).
2. **Background, straight away.** The queue starts as soon as the publish request finishes, through a background request to `admin-ajax.php?action=thr_audio_run` protected by a secret token. Publishing never waits for audio. A WP-Cron event (`thr_audio_cron`) is the fallback if that request is blocked.
3. **Saved to Media.** Each recording is saved as an MP3 in the Media Library, attached to the article, then listed in the post's `thr_audio` field. The article's page cache is purged (LiteSpeed) so the player shows the new voice.
4. **Reader picks another voice:**
   - **At the start of the article:** the opening clip plays at once. The site makes the full version behind it, and the player swaps over at the same moment.
   - **Mid-article:** the current voice keeps playing and switches at the same point when the new one is ready.
   - **The wait:** with the Flash model this takes ~5–15 seconds. The player shows "Preparing…", then "taking longer than usual" after 30 seconds, then "Try again" if it fails.
   - **Once made,** a voice is saved and every later reader gets it instantly.
5. **Edits after publishing** don't remake audio automatically, to keep costs down. The editor box marks the voice **Outdated** and an editor clicks **Remake**.

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

- [ ] Choose the 4 voices (name, region, gender, Voice ID) and enter them in Settings.
- [ ] Subscribe to Scale, turn auto top-up off, add the key, Test connection, switch to Live.
- [ ] Add the Hostinger cron job for `wp-cron.php`.
- [ ] Check real cost per article after the first week; adjust the plan or model.
- [ ] Optional later: make all voices automatically for articles that pass a view threshold; backfill older articles in batches.
