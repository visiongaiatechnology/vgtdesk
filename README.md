# 🖥️ VGT WP-Desk — Premium Slim Desktop Layer

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0--Beta__v3__Hardened-brightgreen?style=for-the-badge)](#)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Overheat_OS--Layer-blue?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Vanilla_JS_%2F_CSS3-orange?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-BETA__V3__HARDENED-yellow?style=for-the-badge)](#)
[![Security](https://img.shields.io/badge/Security-Diamant_VGT_Supreme-red?style=for-the-badge)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

> *"No frameworks. No build pipeline. No compromise on UX."*  
> *AGPLv3 — Open Source Core. Built for operators, not for SaaS dashboards.*

---

## ⚠️ DISCLAIMER: BETA SOFTWARE

This project is currently in **Beta v3 (Hardened Edition)** and part of ongoing development at VisionGaia Technology. It is **not** yet a finalized production release.

**Use at your own risk.** Test thoroughly in a staging environment before deploying to live sites.

Found a bug or have an improvement? **Open an issue or contact us.**

---

<img width="2560" height="1232" alt="image" src="https://github.com/user-attachments/assets/336383e1-653c-4353-aaea-7414d909b3bf" />


---

## 🔍 What is VGT WP-Desk?

VGT WP-Desk is a **modular, framework-free WordPress backend enhancement** that transforms the classic WordPress admin interface into a high-performance, OS-style desktop environment — hardened at the kernel level by an integrated security framework.

It is engineered under the **Zero-Overheat Doctrine**: maximum performance, absolute compatibility with WordPress Core and third-party hooks, and a strict refusal to rely on heavy frameworks or build pipelines.

Installed plugins are automatically detected, mapped as standalone desktop apps, and isolated within a **chromeless iframe architecture** — eliminating sidebar fragmentation and replacing it with a consistent, distraction-free multi-window workflow.

**Beta v3 Hardened Edition** introduces three completed feature phases alongside a complete security audit: desktop engine modularization (3,250+ lines split into 9 isolated modules), multi-layout workspace customization (macOS / Windows / Linux), and a centralized Command Center administration panel — all under continuous DIAMANT VGT SUPREME hardening.

```
Classic WordPress Admin:
→ Fragmented sidebar navigation
→ Context-switching overhead
→ No persistent workspace state
→ Monotone, static interface
→ No integrated WAF or session hardening

VGT WP-Desk OS Layer (Beta v3 Hardened):
→ IframeTransformer          — 100% hook-preserving CSS-Grid tile engine
→ Multi-Window Workspace     — drag, resize, minimize, focus, Aero Snap
→ App Registry               — auto-maps installed plugins as desktop apps
→ Submenu Popups             — glassmorphic per-app submenu dropdowns
→ Folder Mode                — drag-and-drop app grouping on desktop
→ Multi-Layout Engine        — macOS Cupertino / Windows Redmond / Linux Tux
→ Command Center             — unified admin panel (diagnostics, security, shortcuts)
→ RAM Hibernation            — iframe suspend/rehydrate on minimize/restore
→ Custom DB Layer            — relational settings table with delta-merge
→ Per-User Opt-in            — desktop mode off by default, per-user activation
→ AEGIS WAF                  — anomaly-scoring stream WAF (SQLi, XSS, RCE, LFI)
→ CERBERUS                   — brute-force shield + device fingerprinting
→ AIRLOCK                    — binary-level upload inspection
→ HADES                      — WordPress path obfuscation + route masking
→ CHRONOS                    — async filesystem integrity + malware scanner
→ GHOST TRAP                 — deception honeypot grid
→ Throne Guard               — toxic privilege stripping + Superkey vault
→ CSP Nonce Bridge           — automatic nonce propagation to all iframe assets
→ DOM XSS Engine             — full HTML escaping before DOM injection
→ Glassmorphic UI            — wallpaper engine, accent colors, blur, custom modals
```

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

<img width="1914" height="911" alt="VGT Architecture Layer" src="https://github.com/user-attachments/assets/10f41efb-4b27-4e33-ac6d-dd9d3b127797" />

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

### 📁 2.3 Desktop Folder Mode *(New in Beta v3 Hardened)*

App grouping directly on the desktop workspace — no page reload required.

**Creating & Managing Folders:**
- Right-click empty desktop area → context menu → **📁 Neuer Ordner** → glassmorphic name prompt
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

### 🔤 2.4 WordPress Submenus *(New in Beta v3 Hardened)*

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

### 🖼️ 2.5 Multi-Layout Workspace *(New in Beta v3 Hardened)*

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

### ⚙️ 2.6 Command Center *(Phase 3 — New in Beta v3 Hardened)*

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
- Font size slider (10px–24px) — updates `--vgt-font-size` on `:root` + DB sync
- Layout switcher (macOS / Windows / Linux)
- Wallpaper selector + custom URL input
- HSL accent color theme matrix

**Keyboard Shortcuts Mapper:**
- Click "Aufzeichnen" → captures modifier key (Ctrl/Alt/Shift/Meta) + `e.code`
- Saved to user settings — persisted in `vgt_desk_settings`
- Global `initShortcuts()` listener matches recorded keys to desktop window actions

---

### 🔒 2.7 Security Architecture — Defense in Depth

**Integrated Sentinel CE (v1.7.0):**

AEGIS, CERBERUS, AIRLOCK, HADES, CHRONOS, and GHOST TRAP integrated into boot sequence. See [Sentinel CE README](https://github.com/visiongaiatechnology/sentinelcom) for full module documentation.

**AEGIS WAF Exceptions (Beta v3 Hardened):**
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

**CSP Admin Fix:**
- Nonce removed from `Content-Security-Policy` header during `is_admin()` — WordPress Core inline scripts (Heartbeat, inline-edit) no longer blocked by overly strict CSP

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

### 💡 2.8 Desktop Engine Modularization *(New in Beta v3 Hardened)*

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

### 💾 2.9 Performance & Persistence

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
- `Settings → Desktop als Standard-Ansicht` toggle for persistent preference
- `?vgt_bypass=1` → classic view for session; `auto_redirect` set to `false` in DB — redirect loops permanently prevented

**Cookie Hardening:**
- `setcookie()` corrected to PHP 7.3+ `$cookie_options` array signature — fatal error on "Zur klassischen Ansicht" button eliminated

---

### 🎨 2.10 Glassmorphic UI & Custom Modals

**Wallpaper Engine:** Local WebP assets, preset collection + custom URL, zero third-party CDN requests.

**Accent Colors:** Indigo / Emerald / Cyan / Amber / Rose — dynamically applied to badges, buttons, dock LEDs, Sentinel widget, snap preview borders.

**Custom Modal Dialogues (`showModal`):**
- Replaces all native `prompt()` and `confirm()` calls: folder create, folder rename, folder delete, settings reset
- Glassmorphic overlay, styled to match desktop theme
- Used in: `createNewFolder()`, `renameFolder()`, `deleteFolder()`, `resetAllSettings()`

**Widget Visibility Manager:** Individual toggle grid in Control Center — Clock, System Metrics, Notes, Sentinel each independently togglable with immediate visual feedback.

---

## ⚙️ Technical Specifications

| Metric | Specification |
|---|---|
| **Required WordPress** | 6.0+ |
| **Required PHP** | 8.1+ (Strict Types enforced) |
| **Frontend Frameworks** | None — 100% Vanilla JS (ES6+) / CSS Custom Variables |
| **Compilation Overhead** | Zero — no Node.js, no Vite, no TypeScript at runtime |
| **JS Architecture** | 9 isolated modules + 61-line orchestrator |
| **DB Layer** | `{prefix}vgt_desk_settings` (relational, delta-merge) + `{prefix}mcp_user_roles` |
| **Runtime External Calls** | Zero |
| **WAF Engine** | AEGIS Anomaly Scoring — Sentinel CE v1.7.0 |
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
# Or: WP-Desk menu → Settings → Desktop als Standard-Ansicht

# 4. Initialize Throne Guard (Critical — do this first)
# Desktop → Master User Control app → set Superkey (12+ chars) + configure role hardening

# 5. Configure Sentinel CE
# Desktop → Sentinel widget → verify AEGIS, CERBERUS, AIRLOCK, HADES, CHRONOS, GHOST TRAP active

# 6. Select Layout (Optional)
# Command Center → Display → Layout
# Or: Alt+Space → /layout [macos|windows|linux]

# 7. Emergency bypass (if locked out)
# Append ?vgt_bypass=1 to any admin URL
```

---

## 📜 Changelog

### v1.0.0-Beta v3 — Hardened Edition *(Current)*

**Security Audit & Hardening**
- Eliminated potential RCE in scanner engine: `integrity_matrix.php` migrated from PHP `include` to `file_get_contents` + `json_decode` with PHP exit guard header
- Path jail boundary hardened with trailing slash enforcement in `ajax_scan_plugin` — directory shadowing attack closed
- DOM XSS in mock developer console fixed: regex HTML entity escaping applied to all terminal input before render
- Login settings CSS variable injection fixed: `preg_replace` sanitization on background image + logo URL at save and render
- AEGIS WAF Quick Edit exception: `edit_posts` / `manage_options` users — all pattern weights zeroed on post-save endpoints; RCE/LFI remain active
- CSP Admin fix: nonce removed from `is_admin()` CSP header — WordPress Core inline scripts no longer blocked
- Antibot module: typed exception hierarchy (AppException, ValidationException, SecurityException, StorageException) — PATTERN 1.5.A
- Full DIAMANT VGT SUPREME audit: Aegis, Airlock, Cerberus, Hades, Chronos, Ghost Trap, Throne Guard — all modules verified
- Stray `</form>` tag removed from `class-vis-dashboard-view.php` — DOM structure stabilized

**Desktop Engine Modularization (Phase 0)**
- `desktop.js` (3,250+ lines) decomposed into 9 isolated modules under `assets/js/modules/`
- Main orchestrator reduced to 61 lines
- WordPress dependency chain guarantees load order — no race conditions
- `window.VGTDeskEngine` singleton pattern via `Object.assign` — zero build pipeline overhead

**New Features: Folder Mode**
- Drag-and-drop app grouping on desktop workspace
- Bounding box overlap detection triggers grouping
- Folder windows with launch, remove, rename, delete
- Right-click desktop icon context menu: position reset, rename (folders), delete (folders)
- Full persistence in `vgt_desk_settings` via delta-merge AJAX

**New Features: WordPress Submenus**
- Glassmorphic dropdown popup on app icon click for apps with WordPress submenus
- Capability-checked via `current_user_can` — users see only accessible submenus
- XSS-safe DOM construction: `document.createElement` + `textContent` — zero innerHTML injection
- Window title updates: `Parent Title › Submenu Title`

**New Features: Multi-Layout Workspace (Phase 2)**
- Three complete OS styles: macOS Cupertino, Windows Redmond, Linux Tux
- Windows 10-style Start Menu: left sidebar strip, center A–Z app list, right 3-column pinned tiles
- Spotlight CLI: `/layout [macos|windows|linux]`
- Layout-aware Aero Snap preview zones and maximize bounds
- Reactive icon recalculation on layout change (immediate + post-CSS-transition)
- SVG protocol fix: `esc_attr` for `data:` URIs — WordPress no longer strips SVG base64 protocols
- Start Menu: pinned vs. all-apps sections with search-driven section auto-hide

**New Features: Command Center (Phase 3)**
- Split glassmorphic admin panel: left nav tabs + scrollable right content
- Real-Time Diagnostics: CPU, RAM, Sentinel/Throne Guard state, DB footprint, terminal event log
- Enclave Security Center: IP Ban Manager, Superkey update, active threat overview
- Display controls: font size slider, layout switcher, wallpaper, accent colors
- Keyboard Shortcuts Mapper: click-to-capture recording, global key event monitor

**Performance: Phase 1 Optimizations**
- RAM Hibernation: minimized/closed windows suspend iframe to `about:blank` — memory freed; rehydration on restore
- Custom relational DB: `{prefix}vgt_desk_settings` with `UNIQUE(user_id, setting_key)` and delta-merge AJAX
- wp_usermeta auto-migration on first load
- Per-User Opt-in: desktop mode off by default; admin notice for activation; `?vgt_bypass=1` redirect loop fix
- `setcookie()` PHP 7.3+ signature fix — fatal error on classic view toggle eliminated

**UX & Window Management**
- Aero Snap: drag-to-top maximize, drag-to-edge half-screen snap with live preview outlines
- Double-click header: toggle maximize; drag-to-restore on maximized windows
- Window drag capped at `top: 0`; resize capped at top boundary
- Custom glassmorphic `showModal` replaces all native `prompt()` / `confirm()` dialogs
- Welcome window automatic popup removed — clean workspace start
- Login-Design button in top bar: opens VGT Omega Login Engine in dedicated portal window
- Dark mode injected into Media, Themes, Menus pages inside iframes — consistent contrast throughout

**Bug Fixes**
- Clickability fix: `.vgt-context-menu` and `.vgt-submenu-popup` given `z-index: 999999 !important` + appended to `#vgt-shell-root` + `stopPropagation()` — menus no longer intercepted by workspace layer
- Windows Redmond grid: `repeat(3, minmax(0, 1fr))` — long tile labels no longer overflow Start Menu
- Theme grid overlap fix: Flexbox on `.theme-browser .themes` — "Add New Theme" no longer renders on top of active theme
- Control Center toggles: state normalized to JavaScript booleans — string/boolean coercion desync eliminated

---

### v1.0.0-Beta v3

- Sentinel CE v1.7.0 + Throne Guard v2.6.0 integrated into boot sequence
- AEGIS Anomaly Scoring WAF (replaces binary block decisions)
- AEGIS contextual WooCommerce + Gutenberg exceptions
- CSP Nonce Bridge: Throne Guard nonce propagated to IframeTransformer
- Sentinel Live Desktop Widget with AJAX WAF toggle
- Top-bar quick access: Login-Design + Throne Guard/Sentinel dashboards
- Hades Iframe Continuity: plugin breakouts from workspace prevented
- Widget Visibility Manager in Control Center
- VGT Omega Login Engine Module integrated
- Plugin page dark-mode overrides (rgb(9 13 22) + white text)
- Control Center toggle desync fixed (string/boolean normalization)

---

### v1.0.0-Beta v2

- Security Hotfix (Hades Module): `current_user_can('manage_options')` added — unauthenticated `.htaccess` rewrite DoS closed
- Throne Guard partial integration: capability manager, Superkey vault, Jailed Upload Vault
- Premium Lock Screen: glassmorphic Superkey entry UI
- WAF false positive fix: AEGIS no longer blocks admin AJAX and `admin-post.php` forms
- Sentinel V7 compatibility: combined ban statistics, locked widget toggles
- Desktop Layout: widget overlay container re-layered — drag tracking and overlap bugs fixed

---

### v1.0.0-Beta v1

- Initial release: IframeTransformer, Wallpaper Engine, Start Menu, Dock
- Basic Multi-Window Workspace (drag + resize)

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

> *"WP-Desk was built because WordPress administrators deserved a workspace that doesn't fragment their attention, doesn't phone home, and doesn't ask them to accept a degraded UX as the price of using the world's most popular CMS. Beta v3 Hardened adds what every professional workspace also needs: a security layer that activates before the attacker reaches the door, and a desktop engine that doesn't fall apart when you actually use it at scale."*

---

*Version 1.0.0-Beta v3 Hardened Edition — VGT WP-Desk // Modular 9-Engine Desktop // Multi-Layout (macOS/Windows/Linux) // Command Center // Folder Mode // Submenus // Aero Snap // RAM Hibernation // Relational DB // Sentinel CE v1.7.0 // Throne Guard v2.6.0 // Zero-Overheat Architecture // AGPLv3*
