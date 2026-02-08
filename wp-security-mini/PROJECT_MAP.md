# Project Map: wp-security-mini

This document provides a quick, file-by-file map of the project so future work can quickly locate where to make changes.

## Root
- `wp-security-mini.php` — Main plugin bootstrap. Registers hooks, loads classes, and wires the plugin into WordPress.
- `README.txt` — WordPress-style readme describing the plugin, usage, and metadata.
- `PROJECT_MAP.md` — This project map.

## `/includes`
- `class-admin.php` — Admin UI controller: registers admin pages, handles settings, and connects templates to data.
- `class-ddos.php` — DDoS protection logic and request throttling/lockouts.
- `class-logger.php` — Logging utilities for security events and login history.
- `class-security.php` — Core security checks and actions (login tracking, IP checks, etc.).

## `/templates`
- `dashboard.php` — Admin dashboard view for the plugin.
- `settings.php` — Settings form template.
- `top-ips.php` — Report view for top IPs.
- `login-history.php` — Login history table view.

## `/assets`
- `admin.css` — Admin UI styling.
- `admin.js` — Admin UI behaviors and interactions.

## Working Notes
- Start at `wp-security-mini.php` to understand plugin initialization and class loading.
- Changes to admin UI typically touch `class-admin.php` plus a template in `/templates` and possibly `/assets/admin.css` or `/assets/admin.js`.
- Security behavior changes usually live in `class-security.php` and `class-ddos.php`, with logging in `class-logger.php`.
