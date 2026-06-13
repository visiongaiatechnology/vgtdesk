# 🖥️ VGT WP-Desk — Hardened WordPress Operator Workspace

> *"WordPress stays WordPress. The operator gets a hardened desktop above it."*
> *AGPLv3 — Local-first, framework-free and built for operators, not SaaS dashboards.*

---

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0--Beta__v4-brightgreen?style=for-the-badge)](#)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Overheat_OS--Layer-blue?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Vanilla_JS_%2F_CSS3-orange?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-BETA__V4-yellow?style=for-the-badge)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaiaTechnology-red?style=for-the-badge)](https://visiongaiatechnology.de)
[![Security Review](https://img.shields.io/badge/Security_Posture-v4_Internal_Review-brightgreen?style=for-the-badge)](#security-posture--vgt-wp-desk-v4-stable)

---

## ⚠️ DISCLAIMER: BETA SOFTWARE

This project is currently in **Beta v4** and part of ongoing development at VisionGaia Technology. It is **not** yet a finalized production release.

**Use at your own risk.** Test thoroughly in a staging environment before deploying to live sites.

Found a bug or have an improvement? **Open an issue or contact us.**

---

## 🔐 Security Posture

VGT WP-Desk v4 Stable has undergone an internal security posture review covering the desktop runtime, Security Center integrations, AJAX control layer, local telemetry, upload workflow and same-origin iframe workspace.

Within the reviewed scope, no exploitable vulnerabilities were identified.

See: [SECURITY_POSTURE.md](SECURITY_POSTURE.md)

---

<img width="2558" height="1230" alt="image" src="https://github.com/user-attachments/assets/dad7dc14-ce7f-43cd-ac6c-3412cc7d2b6d" />


---

## 🔍 What is VGT WP-Desk?

VGT WP-Desk is a **modular, zero-dependency WordPress Operator Workspace** that transforms the classic WordPress admin interface into a high-performance, OS-style desktop environment with integrated security, diagnostics and local telemetry controls.

It is not a traditional admin theme and not a standalone security plugin. WP-Desk acts as a **hardened backend operating layer**: WordPress Core and third-party plugin interfaces remain intact, while WP-Desk provides a modern multi-window workspace, persistent per-user desktop state, same-origin iframe isolation and a unified Security Center.

Engineered under the **Zero-Overheat Doctrine**, WP-Desk avoids heavy frameworks, external runtimes and build pipelines. The system is built with vanilla JavaScript, PHP and CSS, served locally from the WordPress installation, and designed to preserve compatibility with native WordPress hooks, admin screens and plugin workflows.

Installed plugins are automatically detected, mapped as desktop applications and opened inside a chromeless same-origin iframe workspace. This replaces fragmented sidebar navigation with a consistent operator cockpit while keeping the underlying WordPress admin screens functional and recognizable.

Starting with **Beta v4**, WP-Desk evolves into a full **local-first operations and security environment**. The integrated Security Center combines **Sentinel**, **Throne Guard** and **Dattrack** into a single control layer for request protection, privilege hardening, diagnostics, local telemetry and recovery workflows.

```text
Classic WordPress Admin:
→ Fragmented sidebar navigation
→ Context-switching overhead
→ No persistent workspace state
→ Limited operational visibility
→ No unified security control layer

VGT WP-Desk Operator Workspace:
→ OS-style multi-window desktop for WordPress
→ Per-user opt-in with classic admin fallback
→ Hook-preserving iframe workspace
→ Automatic plugin-to-app mapping
→ Folder Mode, layouts, widgets and persistent state
→ Command Center for diagnostics and runtime operations
→ Security Center integrating Sentinel, Throne Guard and Dattrack
→ VGT Build Center unifying Form Builder, Chronos and Book Reader
→ Sentinel Hardening Auditor with Cyberpunk scoring UI
→ Local telemetry without third-party tracking
→ Same-origin deep-link protection
→ CSP-aware asset loading and DOM XSS hardening
→ Zero-CDN, zero-build, zero-framework runtime
```
### Security Center

The WP-Desk Security Center brings the core VGT security components into one operator interface:

<img width="2557" height="1232" alt="image" src="https://github.com/user-attachments/assets/212845ab-96e0-4845-9827-fded10cf5465" />


```text
VGT WP-Desk Security Center
├── Sentinel
│   ├── WAF status
│   ├── threat logs
│   ├── ban / unban controls
│   └── request and payload protection
│
├── Throne Guard
│   ├── privilege hardening
│   ├── Master role protection
│   ├── Superkey session gate
│   └── toxic capability stripping
│
└── Dattrack
    ├── local telemetry
    ├── privacy-focused analytics
    ├── visitor event insight
    └── sovereign data storage
```

### VGT Build Center

Beta v4 consolidates the builder toolchain under a single unified app icon (`dashicons-hammer`), replacing three separate menu entries:

```text
VGT Build Center
├── Form Builder (Omega Vault)   ← AES-256-GCM encrypted form submissions
├── Chronos Builder              ← WYSIWYG countdown and automation timer editor
└── VGT Book Reader              ← Interactive PDF and media presentation system
```

Submenus and AJAX routing load each builder interface directly into the iframe workspace — no context switching required.

WP-Desk is designed for administrators, publishers, developers and security-conscious operators who want WordPress to remain self-hosted, extensible and familiar — while gaining a faster workspace, stronger operational control and a hardened local security layer.

---

## 🏛️ Architecture

```
WordPress Admin Request
↓
Heuristic Session Detection (PHP Engine)
→ URL indicator: vgt_iframe=true
→ Sec-Fetch-Dest: iframe header
→ HTTP Referer analysis on form submissions
↓
SENTINEL CE BOOT SEQUENCE
→ AEGIS WAF: anomaly scoring on all HTTP methods
→ Input: 5-layer normalization pipeline
→ Admin/editor exemption: SQLi/XSS weights zeroed; RCE/LFI active
→ WAF exception: edit_posts + post.php / edit.php / wp-json REST endpoints
→ CERBERUS: perimeter ban before WordPress user logic
→ AIRLOCK: multipart/form-data binary inspection
→ HADES: path masking + iframe continuity enforcement
→ GHOST TRAP: honeypot access = instant hard-ban
→ CHRONOS: async integrity scan (time-sliced cron)
↓
Throne Guard Capability Hardening & Session Gate
→ 14 toxic Administrator permissions stripped → Master role exclusive
→ SHA256 HMAC session fingerprinting (IP + User-Agent)
→ Superkey verification (Argon2/Bcrypt) to unlock Master enclave
→ Hardware Deactivation Lock
→ CSP Admin Exception: nonce removed from admin CSP — preserves WP Core inline scripts
↓
Modular PHP Kernel (Beta v4)
→ desktop.php             ← lightweight bootstrapper / loader only
→ WPDeskSettings          ← DB schema, settings tables, defaults
→ WPDeskAppBuilder        ← dynamic WordPress menu parser → app matrix
→ WPDeskPlugin            ← central controller: hooks, assets, AJAX dispatch, iframe rules
↓
Per-User Opt-in Check
→ Desktop mode off by default
→ Admin notice in classic backend → explicit per-user activation
→ vgt_bypass=1 cookie → classic view for session
↓
IframeTransformer + CSP Nonce Bridge
→ CSS-Grid injection into native WordPress list tables
→ All injected styles/scripts receive Throne Guard request nonce
↓
Desktop Engine (9 Modules, Zero-Overheat)
→ core → windows → draggable → icons → menus → widgets → spotlight → modals → folders
→ VGTDeskEngine singleton — modules extend via Object.assign
→ WordPress dependency chain guarantees load order
↓
RAM Hibernation Layer
→ Minimized windows: iframe suspended to about:blank (memory freed)
→ Restore: last URL rehydrated seamlessly from data-suspendedUrl
↓
Persistent Settings (Relational DB)
→ {prefix}vgt_desk_settings — UNIQUE(user_id, setting_key)
→ Delta-merge via array_replace_recursive
→ Migration: wp_usermeta data imported on first load
```

<img width="1870" height="1142" alt="image" src="https://github.com/user-attachments/assets/fbbb1b7f-cfce-45b7-86f3-28878a32cc09" />

---

## 🧩 Feature Matrix

### ⚡ 2.1 IframeTransformer — Hook-Preserving Tile Engine

| Parameter | Value |
|---|---|
| **Method** | CSS-Grid injection into native WordPress admin list table DOM |
| **Hook Preservation** | 100% — SEO columns, custom fields, all third-party hooks intact |
| **Transformed Views** | Posts, Pages, Comments, Plugins |
| **Layout Engine** | `display: grid !important` on native `tbody` element |
| **CSP Compliance** | Full — Throne Guard nonce propagated to all injected assets |
| **Dark Theme Injection** | Media, Themes, Menus pages receive consistent dark-mode overrides |
| **SVG Branding** | Native SVG colors preserved — `filter: invert()` removed |

<img width="1914" height="914" alt="Tile Engine Layout" src="https://github.com/user-attachments/assets/3f3df87a-45ef-4666-a879-8831f791a0e2" />

---

### 🖱️ 2.2 Multi-Window Workspace

| Feature | Detail |
|---|---|
| **8-Edge Resizing** | Eight invisible edge/corner zones on every window |
| **Drag Threshold (4px)** | Micro-jitter filtered — double-click isolation preserved |
| **Double-Click Maximize** | Header double-click toggles maximize; drag-to-restore on dragging maximized window |
| **Aero Snap** | Drag to top: maximize preview → drop to maximize. Drag to left/right edge: half-screen snap with preview outline |
| **Window Bounds Guard** | Drag capped at `top: 0` — windows cannot slide under top bar |
| **Resize Bounds Guard** | Top-edge resize stops at `top: 0` with correct height compensation |
| **Iframe Isolation** | Chromeless iframe per window — full WordPress functionality, no navigation bleed |
| **Hades Continuity** | Plugin redirect breakouts from iframe workspace structurally prevented |
| **Custom Scrollbars** | Global slim translucent scrollbars matching glassmorphic style |

<img width="1917" height="908" alt="Multi-Window Workspace" src="https://github.com/user-attachments/assets/1b9b8656-5866-4443-a9a8-ed2c7f7a724b" />

---

### 📁 2.3 Desktop Folder Mode

App grouping directly on the desktop workspace — no page reload required.

**Creating & Managing Folders:**
- Right-click empty desktop area → context menu → **📁 New Folder** → glassmorphic name prompt
- Folders snap to the desktop icon grid with collision-avoidance
- Right-click a folder icon → rename ✏️ or delete 🗑️ directly

**Drag-and-Drop Grouping:**
- Drag any app icon onto a folder icon — bounding box overlap detection triggers grouping
- App icon disappears from desktop; coordinates cleared; settings saved automatically
- Folder icon updates app count

**Folder Window:**
- Click folder → dedicated folder window with grouped app grid
- Launch any grouped app directly from the folder window
- Hover an app → × button restores it to the desktop workspace
- Rename or delete folder — all contained apps are restored to default grid positions on deletion

**Persistence:**
- Full folder structure (names, contents, positions) persisted in `vgt_desk_settings` via delta-merge AJAX
- Survives page reload — state rehydrated from DB on load

---

### 🔤 2.4 WordPress Submenus

Native WordPress submenus surfaced as glassmorphic dropdown popups — no navigation away from the desktop required.

**Backend (PHP):**
- `build_dynamic_plugin_apps()` in `desktop.php` parses the global `$submenu` array
- Capability check via `current_user_can` — users only see submenus they have access to
- Submenu structure injected into the JS config under each app's `submenus` array

**Frontend (XSS-Safe):**
- Click an app icon or start menu item with submenus → `openSubmenuPopup()` renders at cursor position
- All submenu items built via `document.createElement` + `textContent` — zero innerHTML injection
- Clicking a submenu item loads the child page in the parent window's iframe (`vgt_iframe=true`)
- Window title bar updates: `Parent Title › Submenu Title`

---

### 🖼️ 2.5 Multi-Layout Workspace

Three complete OS aesthetic styles, switchable at runtime via Control Center or Spotlight CLI.

| Layout | Style | Dock Position | Maximize Behavior |
|---|---|---|---|
| **macOS Cupertino** | Menu bar top, floating centered dock | Bottom | Full workspace bounds |
| **Windows Redmond** | Bottom taskbar (full width), Windows 11 dock inside | Bottom bar | `height: calc(100% - 48px)` — taskbar stays visible |
| **Linux Tux** | Vertical sidebar dock (left) | Left side | `left: 80px; width: calc(100% - 80px)` |

**Windows 10 Start Menu:**
Three-column layout: left sidebar strip (user profile, settings, power), center scrollable A–Z app list, right 3-column pinned tiles grid. Sections auto-hide when search filters empty them.

**Spotlight CLI:** `/layout [macos|windows|linux]` — switches layout with sound feedback and icon recalculation.

**Aero Snap Layout-Awareness:** Snap preview zones and half-screen drop targets shift to clear the active layout's taskbar/sidebar boundaries.

**SVG Protocol Fix:** `esc_attr` instead of `esc_url` for `data:image/svg+xml;base64` URIs — WordPress no longer strips the `data:` protocol from custom SVGs.

---

### ⚙️ 2.6 Command Center

Unified administration panel replacing the previous settings window — split glassmorphic layout with left navigation tabs and scrollable right content.

**Real-Time Diagnostics:**
- CPU load + RAM usage with live progress bars (red highlight above 80%)
- Active Sentinel WAF / Throne Guard state
- Database footprint across all 6 VGT metadata/log tables
- Embedded terminal console with real-time system event log stream
- 5-second polling loop — active only when Command Center is open

**Enclave Security Center:**
- Active IP Ban Manager — queries both Sentinel CE and Sentinel V7 tables
- Unban IP: `ajax_unban_ip()` deletes blacklist record live
- Superkey update: `ajax_update_superkey()` — verifies current key via `password_verify`, enforces 12-character minimum

**Display & Personalization:**
- Resolution scaling slider (10px–24px) — updates `--vgt-font-size` on `:root` + DB sync
- Layout switcher (macOS / Windows / Linux)
- Wallpaper selector + custom URL input (same-origin enforced — Beta v4)
- HSL accent color theme matrix

**Keyboard Shortcuts Mapper:**
- Click "Record" → captures modifier key (Ctrl/Alt/Shift/Meta) + `e.code`
- Saved to user settings — persisted in `vgt_desk_settings`
- Global `initShortcuts()` listener matches recorded keys to desktop window actions

---

### 🛡️ 2.7 Sentinel Hardening Auditor *(New in Beta v4)*

The WordPress security audit system is now deeply integrated into the VGT Security Center, replacing the previous standalone view.

**Sentinel Auto-Awareness:**
Automatically detects whether Sentinel CE or Enterprise V7 is active, checks which defense modules are running (Airlock, Cerberus, Titan) and reflects their state positively in the security index — no manual configuration.

**Cyberpunk Scoring UI:**
- **Cyber-corner brackets** — cards render with glowing cyan corner markers
- **Animated scan line** — neon-blue vertical scanner animates during audit execution
- **Angled cyber button** — start button rendered with futuristic `clip-path` bevel
- **Cockpit-style score display** — large luminous digital index readout
- **Interactive tier grid** — four tier boxes (DIAMANT / PLATIN GOLD / VGT SECURED / CRITICAL RISK) remain greyed out at idle; illuminate in their respective neon color on scan completion based on achieved score
- **Red warning row highlighting** — all unresolved audit items receive a left-edge red indicator bar, a red-tinted row background and bright red status text — directing the administrator immediately to open vulnerabilities

**Score Index Fix:**
`ScoreMax` is no longer hardcoded to 33 but dynamically counts the actual number of test vectors executed (29). 27 passed tests now correctly report 93% instead of the previously distorted 82%.

---

### 🔒 2.8 Security Architecture — Defense in Depth

**Integrated Sentinel CE (v1.7.0):**

AEGIS, CERBERUS, AIRLOCK, HADES, CHRONOS, and GHOST TRAP integrated into boot sequence. See [Sentinel CE README](https://github.com/visiongaiatechnology/sentinelcom) for full module documentation.

**AEGIS WAF Exceptions:**
- `edit_posts` / `manage_options` users: all pattern weights zeroed on `post.php`, `edit.php`, `/wp-json/wp/v2/` — administrators can save posts containing technical terms without 403 blocks
- WooCommerce checkout fields cleared before scan
- Gutenberg REST API thresholds raised to 50

**Integrity Scanner Hardening:**
- `integrity_matrix.php` no longer stores executable PHP and is never loaded via `include`
- Refactored to PHP/JSON hybrid: `<?php defined('ABSPATH') || exit; ?>` header prevents direct-access leak; data loaded via `file_get_contents` + `json_decode` — execution pathway eliminated

**Path Jail (Plugin Scanner):**
- Trailing slash boundary enforced on both base directory and target directory
- Prevents directory shadowing (`plugins-backup/` matching against `plugins/`)

**DOM XSS (Mock Console):**
- Terminal input HTML-entity-escaped via strict regex before `innerHTML` render — self-XSS vector closed

**Login Settings CSS Injection:**
- `preg_replace('/[()\'\"\\\\]/', '', ...)` applied to background image and logo URL values at save and render time
- Prevents quote/parenthesis characters from breaking `url('...')` CSS context

**Wallpaper Same-Origin Enforcement (Beta v4):**
- On save, wallpaper URLs are restricted to same-origin connections, the WordPress media library (including CDNs) or local base64 presets
- Eliminates cross-site image tracking vectors via external wallpaper servers

**CSP Admin Fix:**
- Nonce removed from `Content-Security-Policy` header during `is_admin()` — WordPress Core inline scripts (Heartbeat, inline-edit) no longer blocked by overly strict CSP

**SAMEORIGIN Iframe Fix (Beta v4):**
- `vault.php` previously sent `X-Frame-Options: DENY` — this blocked the Form Builder from loading inside the desktop workspace
- Replaced with `SAMEORIGIN` combined with CSP `frame-ancestors 'self'`

**Scope-Limited Error Handlers (Beta v4):**
- Globally registered error handlers in the Security Center are now strictly scoped to `VGT_WPDESK_PATH`
- Warnings from WordPress Core or third-party plugins can no longer trigger a "Critical Kernel Failure" in the audit system

**SQL Identifier Hardening (Beta v4):**
- SQL identifiers in the database optimization routine are backtick-escaped via `str_replace('`','``',$table)` — injection pattern closed

**AJAX Exception Hardening (Beta v4 — DIAMANT VGT SUPREME):**
- Five major AJAX actions (`ajax_ban_ip`, `ajax_get_task_manager_stats`, `ajax_unschedule_cron`, `ajax_kill_transient`, `ajax_optimize_database`) now catch typed exception hierarchies: `SecurityException`, `ValidationException`, `StorageException`, and a final `\Throwable` fallback
- Opaque generic responses returned to client on failure — full tracebacks written to server `error_log` only

**Throne Guard Integration (v2.6.0):**
- 14 toxic capabilities stripped from `administrator` → `master` role exclusive
- SHA256 HMAC session fingerprinting — session hijacking neutralized
- Superkey vault (Argon2/Bcrypt) with `password_verify` verification
- Hardware Deactivation Lock
- Jailed Upload Vault (`/mcp_vault/`) — GD/Imagick CDR re-encoding, EXIF strip, `umask(0077)`

**WP-Desk Layer:**
- AJAX Capability Guard on all persistence endpoints
- Deep-Link Origin Guard: `URL.origin` validated — external origins blocked
- CSRF Nonce Hardening on all state-changing AJAX actions
- `escapeHTML()` + `cleanUrl()` (`javascript:` → `about:blank`) on all DOM injection

---

### 💡 2.9 Desktop Engine Modularization

`desktop.js` (3,250+ lines) decomposed into 9 isolated modules under `assets/js/modules/`. Each module extends the global `window.VGTDeskEngine` singleton via `Object.assign` — no build pipeline, no transpiler.

| Module | Responsibility |
|---|---|
| `desktop-core.js` | Config, XSS escaping, URL safety, AJAX sync, clock, Web Audio |
| `desktop-windows.js` | Create, close, minimize, maximize, focus, iframe navigation, Command Center |
| `desktop-draggable.js` | Window drag, resize, Aero Snap layout grids |
| `desktop-icons.js` | Icon grid arrangement, drag-and-drop, collision detection |
| `desktop-menus.js` | Dock, start menu, context menus, keyboard shortcuts, submenu popups, layout switcher |
| `desktop-widgets.js` | Clock, Notes, Sentinel widget, CPU latency graph animation |
| `desktop-spotlight.js` | Spotlight search (Alt+Space), CLI command parser (`/layout`) |
| `desktop-modals.js` | Glassmorphic modal dialogs replacing native `prompt()` / `confirm()` |
| `desktop-folders.js` | Folder create/rename/delete, drag-and-drop grouping, folder window management |

**Main Orchestrator (`desktop.js`):** Reduced to 61 lines. Triggers module init routines in dependency order after `DOMContentLoaded`.

**WordPress Dependency Chain:** `vgt-desktop-core` is the foundation (carries `wp_localize_script` config). All modules declare it as dependency. `vgt-desktop-js` declares all 9 modules as dependencies — WordPress guarantees load order automatically.

---

### 💾 2.10 Performance & Persistence

**RAM Hibernation (Iframe Suspend):**
- Minimizing a window: after CSS animation completes, `suspendIframe(id)` saves current `location.href` to `data-suspendedUrl` → iframe replaced with `about:blank` → client RAM freed
- Restoring (dock/start menu): `data-suspendedUrl` rehydrated into iframe seamlessly
- Closing: same suspension before removal

**Custom Relational DB Layer:**
- `{prefix}vgt_desk_settings` — `UNIQUE(user_id, setting_key)` index
- Delta-merge: `array_replace_recursive` for JSON objects (folders, window positions, icon coordinates)
- Migration: existing `wp_usermeta` data imported automatically on first desktop load
- Single-query reads per settings fetch — no N+1 on startup

**Per-User Opt-in:**
- Desktop mode **off by default** for all users
- Admin notice in classic backend → explicit per-user activation button
- `Settings → Desktop as Default View` toggle for persistent preference
- `?vgt_bypass=1` → classic view for session; `auto_redirect` set to `false` in DB — redirect loops permanently prevented

**Viewport Dimension Auto-Fit (Beta v4):**
- `#vgt-shell-root` width/height boundaries dynamically recalculated on resize (`innerWidth / zoom`, `innerHeight / zoom`) — eliminates blank borders and scrollbars under any zoom factor
- Parent WordPress layout containers (`html`, `body`, `#wpwrap`, `#wpcontent`) forced to strictly clipped boundaries (`overflow: hidden !important`)
- Widgets positioned on the right half of the workspace are saved as relative `right` properties — correct edge-relative spacing maintained across zoom changes

**Cookie Hardening:**
- `setcookie()` corrected to PHP 7.3+ `$cookie_options` array signature — fatal error on classic view toggle eliminated

---

### 🎨 2.11 Glassmorphic UI & Custom Modals

**Wallpaper Engine:** Local WebP assets, preset collection + custom URL (same-origin enforced), zero third-party CDN requests.

**Accent Colors:** Indigo / Emerald / Cyan / Amber / Rose — dynamically applied to badges, buttons, dock LEDs, Sentinel widget, snap preview borders.

**Custom Modal Dialogues (`showModal`):**
- Replaces all native `prompt()` and `confirm()` calls: folder create, folder rename, folder delete, settings reset
- Glassmorphic overlay, styled to match desktop theme
- Used in: `createNewFolder()`, `renameFolder()`, `deleteFolder()`, `resetAllSettings()`

**Widget Visibility Manager:** Individual toggle grid in Control Center — Clock, System Metrics, Notes, Sentinel each independently togglable with immediate visual feedback.

---

## 📜 Changelog

### v1.0.0-Beta v4 — Stability, Scaling & Security Update *(Current)*

#### Modular PHP Kernel Architecture

The monolithic `desktop.php` has been fully decoupled into a clean object-oriented service structure:

- `desktop.php` — reduced to a lightweight bootstrapper that declares the license and delegates startup
- `class-vgt-wpdesk-settings.php` — encapsulates all DB schema creation, settings tables and defaults
- `class-vgt-wpdesk-app-builder.php` — dynamically parses the WordPress menu and builds the app matrix for the desktop dock
- `class-vgt-wpdesk-plugin.php` — central controller for hooks, assets, AJAX dispatching and iframe rules

#### VGT Build Center Consolidation

Form Builder, Chronos Builder and Book Reader consolidated under a single **VGT Build Center** app icon (`dashicons-hammer`). The main menu is decluttered; submenus and AJAX routing navigate each builder interface inside the iframe workspace seamlessly.

#### Sentinel Hardening Auditor Integration

- **Sentinel Auto-Awareness:** Detects active Sentinel CE or V7, checks running defense modules (Airlock, Cerberus, Titan) and reflects their status in the security index automatically
- Raw JSON export boxes and export buttons removed — interface integrated cleanly into the admin view
- **Cyberpunk UI:** Cyan corner brackets, animated neon scan line, angled clip-path start button, cockpit-style score display, interactive tier grid (DIAMANT / PLATIN GOLD / VGT SECURED / CRITICAL RISK) illuminating on scan completion
- **Red warning row highlighting** on all unresolved audit items
- **Score index fix:** `ScoreMax` now dynamically counts actual test vectors (29) — 27 passed tests correctly report 93% instead of 82%

#### Dattrack Telemetry Integration & V7 Conflict Resolution

- Dattrack integrated as an opt-in telemetry module within the desktop settings ecosystem
- **Sentinel V7 class collision fix:** `class_exists('VGT_Crypto')` guards prevent PHP fatal `Cannot declare class` errors when premium suites are present
- **Dynamic module activation:** `class_exists` + `method_exists` defensive wrappers on `VGT_Dashboard::get_vault_metrics()` — no critical failure on telemetry view navigation
- **Premium isolation guard:** Engine boot deferred to `plugins_loaded` — if Sentinel V7 is active, Dattrack dashboard, app icons, widgets and wizard steps are hidden automatically (wizard shortens from 7 to 6 steps)

#### UI Resolution Scaling & Window Snapping Fixes

- **JS compiler restoration:** Fixed `SyntaxError: Identifier 'zoom' has already been declared` in `desktop-draggable.js` — this blocked all desktop JS module execution when scaling was adjusted
- **Viewport auto-fit:** Dynamic `#vgt-shell-root` boundary recalculation on resize prevents blank borders and scrollbars at any zoom level
- **Canvas overflow overrides:** Parent WordPress layout containers forced to `overflow: hidden !important`
- **Drift-free widget docking:** Right-half widgets saved as relative `right` coordinates — correct spacing maintained across zoom changes
- **Invisible wall fix:** Drag-and-drop boundaries recalculated relative to scaled zoom grids — free movement across all screen edges restored

#### Security Hardening (DIAMANT VGT SUPREME)

- **AJAX exception hardening:** Five major admin AJAX actions now catch typed exception hierarchies (`SecurityException`, `ValidationException`, `StorageException`, `\Throwable`) — opaque generic responses to client, full tracebacks to `error_log` only
- **Wallpaper same-origin enforcement:** External wallpaper URLs blocked — same-origin, WordPress media library or local base64 presets only
- **SAMEORIGIN iframe fix:** `vault.php` `X-Frame-Options: DENY` replaced with `SAMEORIGIN` + CSP `frame-ancestors 'self'` — Form Builder loads correctly inside the workspace
- **Scope-limited error handlers:** Security Center error handlers scoped to `VGT_WPDESK_PATH` — third-party plugin warnings can no longer trigger audit system failures
- **SQL identifier hardening:** Backtick-escaping applied to table names in the DB optimization routine
- **Recovery Center bugfix:** PHP fatal error resolved — deprecated `$this->get_user_settings()` replaced with static `WPDeskSettings` service call
- **Sandbox test transparency:** Auditor now displays a clear notice that a temporary isolated test file is created in the uploads directory during scan and deleted immediately after

#### File Headers

All files standardized with `// STATUS: 💠 DIAMANT VGT SUPREME` verification headers.

---

## ⚙️ Technical Specifications

| Metric | Specification |
|---|---|
| **Required WordPress** | 6.0+ |
| **Required PHP** | 8.1+ (Strict Types enforced) |
| **Frontend Frameworks** | None — 100% Vanilla JS (ES6+) / CSS Custom Variables |
| **Compilation Overhead** | Zero — no Node.js, no Vite, no TypeScript at runtime |
| **JS Architecture** | 9 isolated modules + 61-line orchestrator |
| **PHP Architecture** | Modular service classes (Settings / AppBuilder / Plugin) + lightweight bootstrapper |
| **Database Footprint** | `{prefix}vgt_desk_settings` (relational, delta-merge) + `{prefix}mcp_user_roles` |
| **Runtime External Calls** | Zero |
| **WAF Engine** | AEGIS Anomaly Scoring — Sentinel CE v1.7.0 / Sentinel V7 |
| **Session Hardening** | Throne Guard v2.6.0 |
| **Bypass Mechanism** | `?vgt_bypass=1` — classic view for session |
| **Default Mode** | Off — explicit per-user opt-in required |

---

## 🚀 Installation

```bash
# 1. Clone into WordPress plugins directory
cd /var/www/html/wp-content/plugins/
git clone https://github.com/visiongaiatechnology/vgtdesk

# 2. Activate in WordPress Admin
# Plugins → VGT WP-Desk → Activate

# 3. Opt-in to Desktop Mode
# Admin notice appears in classic backend → click to activate for your user
# Or: WP-Desk menu → Settings → Desktop as Default View

# 4. Initialize Throne Guard (Critical — do this first)
# Desktop → Master User Control app → set Superkey (12+ chars) + configure role hardening

# 5. Configure Sentinel CE / V7
# Desktop → Sentinel widget → verify AEGIS, CERBERUS, AIRLOCK, HADES, CHRONOS, GHOST TRAP active

# 6. Select Layout (Optional)
# Command Center → Display → Layout
# Or: Alt+Space → /layout [macos|windows|linux]

# 7. Emergency bypass (if locked out)
# Append ?vgt_bypass=1 to any admin URL
```

---

## 🔗 VGT Ecosystem

| Tool | Type | Purpose |
|---|---|---|
| 🖥️ **VGT WP-Desk** | **OS-Layer / UX** | Premium desktop interface for WordPress backend — you are here |
| 🏰 **VGT Throne Guard** | **Hardening** | Toxic capability stripping + Superkey vault — integrated |
| ⚔️ **[VGT Sentinel](https://github.com/visiongaiatechnology/sentinelcom)** | **WAF / IDS** | Zero-Trust WordPress WAF — integrated |
| 🛡️ **[VGT Myrmidon](https://github.com/visiongaiatechnology/vgtmyrmidon)** | **ZTNA** | Zero Trust device registry and cryptographic integrity verification |
| ⚡ **[VGT Auto-Punisher](https://github.com/visiongaiatechnology/vgt-auto-punisher)** | **IDS** | L4+L7 Hybrid IDS — attackers terminated before they even knock |
| 📊 **[VGT Dattrack](https://github.com/visiongaiatechnology/dattrack)** | **Analytics** | Sovereign analytics engine — your data, your server, no third parties |
| 🔐 **[VGT Omega Vault](https://github.com/visiongaiatechnology/vgt-omega-vault)** | **Encrypted Forms** | AES-256-GCM form vault with drag-and-drop builder — integrated via Build Center |
| 🌐 **[VGT Global Threat Sync](https://github.com/visiongaiatechnology/vgt-global-threat-sync)** | **Preventive** | Daily threat feed — block known attackers before arrival |

---

## 💰 Support the Project

[![Donate via PayPal](https://img.shields.io/badge/Donate-PayPal-00457C?style=for-the-badge&logo=paypal)](https://www.paypal.com/paypalme/dergoldenelotus)

| Method | Address |
|---|---|
| **PayPal** | [paypal.me/dergoldenelotus](https://www.paypal.com/paypalme/dergoldenelotus) |
| **Bitcoin** | `bc1q3ue5gq822tddmkdrek79adlkm36fatat3lz0dm` |
| **ETH** | `0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85` |
| **USDT (ERC-20)** | `0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85` |

---

## 🤝 Contributing

Pull requests are welcome. For major changes, open an issue first.

Licensed under **AGPLv3** — *"For Humans, not for SaaS Corporations."*

---

## 🏢 Built by VisionGaia Technology

[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

VisionGaia Technology builds enterprise-grade infrastructure — engineered to the DIAMANT VGT SUPREME standard.

> *"WP-Desk was built because WordPress administrators deserved a workspace that doesn't fragment their attention, doesn't phone home, and doesn't ask them to accept a degraded UX as the price of using the world's most popular CMS. Beta v4 raises the bar further: a modular PHP kernel, a consolidated Build Center, a Cyberpunk-grade security auditor and a full scaling overhaul — all still running on zero dependencies, zero build pipeline and zero external calls."*

---

*Version 1.0.0-Beta v4 — VGT WP-Desk // Modular PHP Kernel // 9-Engine Desktop // Multi-Layout (macOS/Windows/Linux) // Command Center // VGT Build Center // Sentinel Hardening Auditor // Folder Mode // Submenus // Aero Snap // RAM Hibernation // Dattrack Telemetry // Relational DB // Sentinel CE v1.7.0 // Throne Guard v2.6.0 // Zero-Overheat Architecture // AGPLv3*
