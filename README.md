# WP AbuseIPDB Integration

**Contributors:** federico-rojas
**Tags:** security, abuseipdb, firewall, ip-blocking, brute-force
**Requires at least:** 5.0
**Tested up to:** 6.6
**Requires PHP:** 7.4
**Stable tag:** 1.0.0
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

A WordPress firewall that uses the [AbuseIPDB](https://www.abuseipdb.com/) reputation database to **block malicious IP addresses before they reach your site**.

## Description

**WP AbuseIPDB Integration** checks every front-end visitor's IP early in the WordPress request cycle and blocks abusive ones with a branded HTTP 403 page — before your theme, plugins, or content render.

Each visitor IP is evaluated in this order, stopping at the first match:

1. **Whitelist** — trusted IPs always pass, no API call.
2. **Manual blocklist** — IPs you blocked by hand are denied instantly, no API call.
3. **Score cache** — a recent AbuseIPDB result for that IP is reused (configurable TTL).
4. **Country filter** *(optional)* — if the IP geolocates to one of your trusted countries (via the bundled IP2Location LITE database), it skips the AbuseIPDB check entirely. This saves API quota for your normal local traffic.
5. **Live AbuseIPDB check** — the IP's abuse confidence score is fetched; if it meets your threshold, the request is blocked and logged.

The plugin is **fail-open**: if the AbuseIPDB API errors or the daily quota is spent, visitors are allowed through and the incident is logged. Your site never goes dark because of an upstream dependency.

## Features

- **Request-time firewall** hooked at `plugins_loaded` priority 1 — blocks before WordPress finishes booting.
- **AbuseIPDB API client** for the `/check` and `/report` endpoints, with a daily quota guard and aggressive per-IP caching.
- **Manual blocklist** with a dedicated **Blocked IPs** page (add/remove, optional expiry) and one-click "Block IP" from any logged detection.
- **Country filter** powered by the bundled IP2Location LITE database: traffic from countries you trust bypasses the AbuseIPDB lookup, while everyone else is verified. No 30 MB download — the database ships with the plugin.
- **Security Rules engine** that turns WordPress security events into detections: failed logins, suspicious requests (path probing, scanner user-agents), comment spam, REST API and XML-RPC abuse, and 404 probing. Detections reuse the firewall's cache to avoid duplicate API calls.
- **Auto-reporting** of blocked IPs back to AbuseIPDB once they pass your threshold (optional).
- **Firewall Log** (Detections) — a searchable, sortable table with filters by event type, threat level, **country**, **minimum score**, and date range; bulk delete and bulk whitelist; pagination; and a per-row details view.
- **Dashboard** with at-a-glance KPIs (IPs checked, threats blocked, average confidence, API quota), a recent-checks feed, a **live IP lookup** tool, and live **API status**.
- **Settings** on a single, card-based screen — API key with connection test, a gradient confidence-threshold slider, protection/logging toggles, cache and quota controls, IP whitelist, and a data-on-uninstall switch.
- **Daily maintenance** job: rotates logs, prunes old detections, clears expired manual blocks, and purges stale transients.
- **Branded 403 block page** showing the visitor's IP and the block reason.
- **Privacy by default:** logs and data live in `wp-content/uploads/aipdb-logs/` and `aipdb-data/`, hardened against public access with `.htaccess` and `index.php`.

## Installation

1. Upload the `wp-abuseipdb-integration` folder to `/wp-content/plugins/`.
2. Activate the plugin from the **Plugins** menu in WordPress.
3. Go to **AbuseIPDB → Settings** and paste your AbuseIPDB API key, then click **Test Connection**.
4. Turn on **Enable request-time protection** and set your **Detection threshold**.
5. (Optional) Configure monitored events under **AbuseIPDB → Security Rules**, trusted countries under **Country Blocking**, and manual entries under **Blocked IPs**.

## Frequently Asked Questions

**Do I need an AbuseIPDB API key?**
Yes. Get a free key at [abuseipdb.com](https://www.abuseipdb.com/account/api). The free tier allows 1,000 checks per day, which is plenty for most small/medium sites thanks to caching, the manual blocklist, and the country filter.

**Does it actually block in real time?**
Yes. Abusive IPs are served a 403 at `plugins_loaded` priority 1 — before the theme or content render.

**How does the country filter work?**
If enabled, the plugin geolocates the visitor's IP with the bundled IP2Location LITE database. IPs from your **trusted countries** skip the AbuseIPDB call and are allowed; all other IPs are checked against AbuseIPDB as usual. This keeps your API usage focused on foreign/unknown traffic.

**Does it work behind Cloudflare or a reverse proxy?**
Partially. `CF-Connecting-IP` and `X-Forwarded-For` are honored. Trusted-proxy validation (to prevent header spoofing) is on the roadmap.

**Is there a premium version?**
No. The plugin is free. If it saves you time or grief, [you can support development with a donation](https://www.buymeacoffee.com/federicorojas).

## Changelog

### 1.0.0 — First stable release
- **Country filter is now functional.** When enabled, IPs from trusted countries bypass the AbuseIPDB check via the bundled IP2Location LITE database; all other IPs are verified. Removed the dead "coming soon" behavior tab and the risky on-activation 30 MB database download (the database now ships with the plugin).
- **No more duplicate API calls.** The Security Rules engine reuses the firewall's score cache instead of calling AbuseIPDB again for the same IP in the same request.
- **Redesigned admin UI** in a clean, card-based "Guardian" layout: a KPI dashboard with recent checks, live IP lookup and API status; a Firewall Log with country and minimum-score filters; and a single-screen, no-tabs Settings page. In-page tab navigation was removed in favor of the WordPress sidebar menu.
- **New manual IP lookup tool** on the dashboard, backed by `AIPDB_Firewall::check_ip_manually()`.
- **Cache hit/miss tracking** wired into the firewall so the dashboard's cache stats reflect reality.
- Removed dead "Advanced" settings (`emergency_mode`, `debug_mode`, `custom_user_agent`, proxy/third-party placeholders) and cleaned up stale activation defaults.

### 0.2.0 — Request-time firewall (MVP)
- New `AIPDB_Firewall` hooked at `plugins_loaded` priority 1: whitelist → manual blocklist → cached score → live AbuseIPDB. IPs above the threshold get a 403 block page.
- Fail-open policy, daily API quota guard, and per-IP score caching.
- New **Blocked IPs** admin view and one-click **Block IP** action from detections.
- Branded block page template; visitors with `manage_options` are never blocked; firewall skips WP-CLI, cron, and admin.

### 0.1.0 — Stabilization
- Removed placeholder license/premium system; unified the text domain; fixed duplicated settings registration and broken Configuration links; hardened the logs/data directories; moved inline JS/CSS into asset files.

### 0.0.1 — Initial scaffolding
- AbuseIPDB API client, Security Rules engine, detections list, and the first admin pages.

## License

GPL v2 or later. See `LICENSE.txt`.
