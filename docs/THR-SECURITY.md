# THR Security — basic protections with on/off switches

Plugin: `wp-content/plugins/thr-security` (version 2.4.0; all THR packages share the release version). Packaged by `bin/package.py` as `thr-security.zip`.

Everything is **off** after installing. Switch protections on one at a time in **Settings → THR Security**.

## If you are ever locked out

Either of these switches every protection off at once:
- In Hostinger's File Manager, add this line to `wp-config.php`, above "That's all, stop editing!":
  ```php
  define( 'THR_SECURITY_OFF', true );
  ```
  Remove it again to turn the protections back on.
- Or rename the folder `wp-content/plugins/thr-security`, for example to `thr-security-off`.

Then log in at the normal `/wp-login.php` and fix the setting.

## The switches

| Switch | What it does | Watch out for |
|---|---|---|
| **Private login address** | Logging in moves to e.g. `/thr-k3x9p2mq/`. `wp-login.php`, `/wp-admin/` (when logged out) and the `/login` and `/admin` shortcuts show "not found". Every login, logout, lost-password, reset-email and password-protected-post link uses the new address. | The new address is emailed to the site admin and to you when saved. **Bookmark it.** `admin-ajax.php` and `admin-post.php` stay open: the forms and the audio worker need them. |
| **Limit login attempts** | Locks an address out after N wrong passwords (default 5) for M minutes (default 30). Even the right password is refused while locked. | Add office or VPN addresses to the never-lock list. Check **Visitor address comes from** first (below). Lockouts are listed with a **Clear all lockouts** button. |
| **Visitor address comes from** | Where the visitor's IP is read: the connection, Cloudflare's `CF-Connecting-IP`, or `X-Forwarded-For` | Pick the option that shows your own IP on the settings page. If it shows a server address, all visitors would share one lockout. Switch to **Cloudflare** once the main site is behind the orange cloud. |
| **Vague login errors** | "The login details are incorrect" instead of revealing whether a user name exists | — |
| **Email on administrator login** | Emails the site admin each time an administrator logs in, with the time, IP and browser | — |
| **Limit application passwords** | Only administrators and the Distribution automation account (n8n) can create API passwords | — |
| **Turn off XML-RPC** | `xmlrpc.php` returns 403. Pingbacks are off. | Nothing on the site uses it; n8n uses the REST API |
| **Hide user names** | Logged-out visitors can't list users through `/wp-json/wp/v2/users`, `?author=1` or the users sitemap | Author pages and bylines keep working. Logged-in editors and n8n are unaffected. |
| **Security headers** | `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy` | Other websites can no longer show the site's pages inside a frame |
| **Force HTTPS in browsers (HSTS)** | Browsers use HTTPS only, for a year | **Hard to undo.** Switch on only when HTTPS works everywhere. Needs security headers on. Doesn't cover subdomains. |
| **Turn off the code editors** | Removes the theme and plugin file editors | Installing plugins and themes from zips still works |
| **Hide the WordPress version** | Removes the version from the page source and feeds | — |

## Suggested order

1. Install and activate. Nothing changes yet.
2. Check **Visitor address comes from** shows your own IP, then turn on **Limit login attempts**.
3. Turn on **XML-RPC off**, **Hide user names**, **Vague login errors**, **Hide the WordPress version**, **Turn off the code editors**, **Security headers** and **Limit application passwords**. Check the site, the audio player and the forms still work.
4. Turn on **Private login address**. Check the email arrives, bookmark the address, log out, and log in through it.
5. Optional: **Email on administrator login**.
6. Later, once HTTPS has been fine for a while: **HSTS**.

**Caching:** headers are added by WordPress. If LiteSpeed serves a cached page, it normally keeps the headers stored with it. After switching headers on, purge LiteSpeed's cache once.

## What this doesn't cover

- **Two-factor login.** Not built. Use a strong unique password for every administrator and keep administrator accounts few.
- **Firewall and bot blocking.** Cloudflare's protection applies only once the main site is behind the orange cloud (see THR-AUDIO.md, "Still to do").
- **Malware scanning and backups.** Use Hostinger's tools.

## Testing

Tested on 8 November 2026 in WordPress Playground (PHP 8.2) with real HTTP requests. 40 checks:
- **With everything off:** WordPress behaves normally.
- **Login:**
  - The old login and wp-admin are hidden, and the shortcuts no longer lead to the login page.
  - `admin-ajax.php` and `admin-post.php` stay open.
  - The private page loads and isn't cached. Its form, lost-password, password-protected-post, reset-email and logout links all use the private address.
  - Login and logout work.
- **Errors and lockouts:** vague errors work, and after 3 wrong passwords even the right one is refused.
- **Site switches:** XML-RPC is blocked, the users API, `?author=1` and the users sitemap are hidden, the posts API still works, all four headers are sent without HSTS, and the version and pingback header are hidden.
- **Settings:** slug rules are enforced and code editors are off.
- **Switching off:** everything returns to normal when switched off, and the `THR_SECURITY_OFF` emergency switch restores the normal login.

Application-password limits were checked with HTTPS simulated, because WordPress only offers them over HTTPS.

**Not yet tested on Hostinger:**
- whether LiteSpeed passes the visitor's real IP, so check **Visitor address comes from** on the live site
- whether the hosting CDN keeps the headers
