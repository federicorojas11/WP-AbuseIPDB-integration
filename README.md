# WP AbuseIPDB Integration

**Contributors:** federico-rojas
**Tags:** security, abuseipdb, firewall, anti-spam, ip-blocking
**Requires at least:** 5.0
**Tested up to:** 6.4
**Requires PHP:** 7.4
**Stable tag:** 0.1.0
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin that integrates with the [AbuseIPDB](https://www.abuseipdb.com/) reputation service to detect and (in upcoming releases) block malicious IP addresses before they reach your site.

> **Status:** pre-MVP. Detection and logging are functional; automatic blocking based on AbuseIPDB score is the next milestone. See the roadmap below.

## Description

**WP AbuseIPDB Integration** ties WordPress security events (failed logins, suspicious requests, REST/XML-RPC abuse, 404 probing, comment spam) to AbuseIPDB's IP reputation database. Today it records detections, surfaces them in an admin dashboard, and can auto-report high-confidence abuse back to AbuseIPDB. The full request-time firewall that blocks abusive IPs *before* WordPress renders the page is on the roadmap (see below).

### What works today

- AbuseIPDB API client (`/check` and `/report` endpoints).
- Detection of WordPress security events with configurable thresholds:
  - Login failures
  - Suspicious requests (path probing, SQLi patterns, scanner user-agents)
  - Comment spam
  - REST API abuse
  - XML-RPC access
  - 404 errors on sensitive paths
- Local detections database with filtering, bulk actions and per-IP whitelist.
- Auto-reporting to AbuseIPDB when an IP exceeds the configured abuse threshold.
- Admin dashboard with statistics, recent activity, and a Quick Setup form.
- IP2Location LITE geolocation (auto-downloaded on activation).
- Daily maintenance job (log rotation, old detection cleanup, transient purge).

### Roadmap

- **Fase 1 — Firewall (MVP):** request-time hook that checks visitor IPs against AbuseIPDB (with aggressive caching) and against the country blocklist, then blocks with HTTP 403 before WordPress boots.
- **Fase 2 — Hardening:** trusted-proxy configuration for `X-Forwarded-For`, daily API quota guard with fail-open policy, async reporting via WP-Cron, deferred IP2Location DB updates.
- **Fase 3 — UI overhaul:** native WP admin color palette, onboarding wizard, unified Dashboard / Configuration source of truth.
- **Fase 4 — Country blocking enforcement and remaining "coming soon" sections.**

## Installation

1. Upload the `wp-abuseipdb-integration` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** menu in WordPress.
3. Go to **AbuseIPDB → Configuration** and enter your AbuseIPDB API key.
4. (Optional) Adjust the abuse threshold, enable auto-reporting, and configure which security events to monitor under **AbuseIPDB → Security Rules**.

## Frequently Asked Questions

**Do I need an AbuseIPDB API key?**

Yes. Get a free key at [abuseipdb.com](https://www.abuseipdb.com/account/api). The free tier covers 1,000 checks per day, which is sufficient for most small/medium sites once request-time caching lands in Fase 1.

**Does this plugin actually block malicious IPs right now?**

Not yet. As of `0.1.0` the plugin detects, scores, and logs security events — and optionally reports them to AbuseIPDB — but does not yet block visitors at request time. Blocking is the Fase 1 milestone.

**Is there a premium version?**

No. The plugin is and will remain 100% free. If it saves you time or grief, [you can support development with a donation](https://www.buymeacoffee.com/federicorojas).

**Does it work behind Cloudflare or a reverse proxy?**

Partially. Cloudflare's `CF-Connecting-IP` and `X-Forwarded-For` headers are currently honored, but trusted-proxy validation (so an attacker cannot spoof these headers) is part of Fase 2.

## Changelog

### 0.1.0 — Stabilization pass
- Removed placeholder license/premium system; plugin is now free + donation-supported.
- Unified text domain to `wp-abuseipdb-integration` across all views and the `.pot` file.
- Resolved duplicated `register_setting` calls and a case-mismatched options group that caused silent save failures on the Configuration page.
- Deduplicated the `wp_ajax_aipdb_test_api` handler and the double instantiation of `AIPDB_Security_Rules_Admin`.
- Fixed broken Configuration tab links (`aipdb-options` → `aipdb-configuration`).
- Properly closed admin HTML wrappers via `footer.php`.
- Hardened `wp-content/uploads/aipdb-logs/` and `aipdb-data/` with `.htaccess` (Apache 2.2 + 2.4) and `index.php` to block public access.
- Wired the Dashboard Quick Setup save button to its AJAX handler (previously a no-op).
- Removed a phantom `wp_ajax_aipdb_toggle_rule` hook that pointed at a non-existent method.
- Moved all inline JS/CSS out of view files into `admin/js/admin.js` and `admin/css/admin.css`.

### 0.0.1 — Initial scaffolding
- AbuseIPDB API client.
- Security Rules detection engine with configurable thresholds.
- Detections list with filtering, bulk actions, and whitelist integration.
- Dashboard, Country Blocking, Security Rules, Detections and Configuration admin pages.

## Contributing

Issues and pull requests welcome at the project repository. Please describe the WordPress version, PHP version, and any other security plugins active when reporting a bug.

## License

GPL v2 or later. See `LICENSE.txt`.
