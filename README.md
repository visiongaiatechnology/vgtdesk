# 🖥️ VGT WP-Desk — Operator OS for WordPress

> *"WordPress stays WordPress. The operator gets a hardened OS above it."*
> *AGPLv3 — Local-first, framework-free and built for operators, not SaaS dashboards.*

---

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-2.0.0--beta.1-brightgreen?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-V2.0_Beta_v1-yellow?style=for-the-badge)](#)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Overheat_OS--Layer-blue?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Vanilla_JS_%2F_CSS3-orange?style=for-the-badge)](#)
[![Design](https://img.shields.io/badge/Design-Unified_Design_System-indigo?style=for-the-badge)](#-design-system)
[![Frame](https://img.shields.io/badge/Frame-Portal_Hardening-purple?style=for-the-badge)](#-portal--iframe-hardening)
[![VGT](https://img.shields.io/badge/VGT-VisionGaiaTechnology-red?style=for-the-badge)](https://visiongaiatechnology.de)
[![Security Review](https://img.shields.io/badge/Security_Posture-v4_Internal_Review-brightgreen?style=for-the-badge)](#security-posture)

---

## ⚠️ DISCLAIMER: BETA SOFTWARE

This project is currently in **V2.0 Beta v1** and part of ongoing development at VisionGaia Technology. It is **not** yet a finalized production release.

**Use at your own risk.** Test thoroughly in a staging environment before deploying to live sites.

Found a bug or have an improvement? **Open an issue or contact us.**

---

## 🔐 Security Posture

VGT WP-Desk v4 Stable has undergone an internal security posture review covering the desktop runtime, Security Center integrations, AJAX control layer, local telemetry, upload workflow and same-origin iframe workspace.

Within the reviewed scope, no exploitable vulnerabilities were identified.

See: [SECURITY_POSTURE.md](SECURITY_POSTURE.md)

---

<img width="2560" height="1229" alt="image" src="https://github.com/user-attachments/assets/a3120a9e-49fd-4ce8-947f-a62bceddf2cb" />

---

## 🔍 What is VGT WP-Desk?

VGT WP-Desk is a **modular, zero-dependency WordPress Operator OS** — an OS-style layer that sits above WordPress and gives the operator a unified control plane, hardened portal, and coherent design system across every admin surface.

WordPress remains WordPress. Core and third-party plugin interfaces are untouched. WP-Desk provides the operating layer on top: multi-window workspace, persistent per-user desktop state, same-origin iframe isolation, Security Center, Build Center, Design System and a hardened Frame Policy — all running without CDN calls, without build pipelines, without external runtimes.

Engineered under the **Zero-Overheat Doctrine**: vanilla JavaScript, PHP and CSS only, served locally from the WordPress installation.

```text
Classic WordPress Admin:
→ Fragmented sidebar navigation
→ Context-switching overhead
→ No persistent workspace state
→ Limited operational visibility
→ No unified security control layer
→ Inconsistent UI across modules

VGT WP-Desk V2 Operator OS:
→ OS-style multi-window desktop for WordPress
→ Per-user opt-in with classic admin fallback
→ Hook-preserving iframe workspace
→ Automatic plugin-to-app mapping
→ Folder Mode, layouts, widgets and persistent state
→ Command Center for diagnostics and runtime operations
→ Security Center integrating Sentinel, Throne Guard and Dattrack
→ VGT Build Center unifying Form Builder, Chronos and Book Reader
→ Sentinel Hardening Auditor with Cyberpunk scoring UI
→ Unified Design System across all admin surfaces (V2)
→ Frame Policy + Portal Hardening (V2)
→ Recovery Control Plane outside desktop (V2)
→ Classic Mode for incompatible screens (V2)
→ Local telemetry without third-party tracking
→ Same-origin deep-link protection
→ CSP-aware asset loading and DOM XSS hardening
→ Zero-CDN, zero-build, zero-framework runtime
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
→ CSP Admin Exception: nonce removed from admin CSP
↓
V2 Control Plane (New in V2.0)
→ WPDeskFramePolicy     ← single-owner X-Frame-Options, admin/embed → SAMEORIGIN, frontend → DENY
→ WPDeskIframePolicy    ← per-app classic vs. iframe routing (Builder/Customizer → classic tab)
→ WPDeskWidgetLayout    ← widget position validation + normalization (empty id / oversized reject)
→ WPDeskDesignSystem    ← shared tokens + components + compat across all admin surfaces
→ Module Registry       ← clean boot of integrated modules (Transformer, Security Center, …)
→ Pure Test Suite       ← control plane tests without full WP boot (frame / widgets / design / harden)
↓
Modular PHP Kernel (v4 baseline, retained)
→ desktop.php             ← lightweight bootstrapper / loader only
→ WPDeskSettings          ← DB schema, settings tables; widget_positions as full-replace (V2)
→ WPDeskAppBuilder        ← admin portal URLs enforced; front-URLs → new tab (V2)
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
→ Portal Card Layout v2 + filemtime cache-bust (V2)
→ Portal badge only on list screens — hidden in Security Center / Astra / Full-Apps (V2)
↓
Desktop Engine (9 Modules, Zero-Overheat)
→ core → windows → draggable → icons → menus → widgets → spotlight → modals → folders
→ VGTDeskEngine singleton — modules extend via Object.assign
→ WordPress dependency chain guarantees load order
↓
RAM Hibernation Layer
→ Minimized windows: iframe suspended to about:blank (memory freed)
→ Restore: last URL rehydrated from data-suspendedUrl
↓
Persistent Settings (Relational DB)
→ {prefix}vgt_desk_settings — UNIQUE(user_id, setting_key)
→ widget_positions: full-replace + localStorage backup (V2, no delta-merge zombie)
→ Migration: wp_usermeta data imported on first load
```



---

## 🎨 Design System *(New in V2.0)*

V2.0 introduces a unified design system that replaces the per-module isolated styles of v4 (cyan Sentinel, gold Vault, mint Chronos, cyan Astra, inline Recovery) with a coherent shared layer.

```
assets/css/design-system/
├── tokens/        ← brand colors, spacing, radii, shadows
├── base/          ← resets, typography, scrollbars
├── components/    ← buttons, cards, badges, panels, tabs
└── compat/        ← WordPress admin override shims
```

**Brand:** Indigo primary (matches desktop shell), optional themes for security surfaces and premium modules.

**Coverage — modules now on shared tokens:**

| Module | Status |
|---|---|
| Security Center | ✅ Design System |
| Sentinel Tabs | ✅ Design System |
| Throne Guard | ✅ Design System |
| Recovery | ✅ Design System |
| Dattrack | ✅ Design System |
| Login Engine | ✅ Design System |
| Omega Vault | ✅ Design System |
| Book Reader | ✅ Design System |
| Chronos Builder | ✅ Design System |
| VGTAstra | ✅ Design System |
| Desktop Shell | ✅ Design System |
| Portal / Iframe | ✅ Design System |

**Fixes in V2:**
- MU-deployer CSS bug resolved (`mudeployer` → `mu-deployer`)
- Astra CLOSE buttons no longer squashed
- Welcome popup updated to celebrate V2 (Design System, Portal Hardening, Zero-Overheat, Security Plane)

---

## 🖼️ Portal & Iframe Hardening *(New in V2.0)*

The biggest operational improvement in V2. v4 had partial iframe fixes — V2 hardens the entire frame and portal layer.

### Frame Policy (`WPDeskFramePolicy`)

Single-owner X-Frame-Options policy — no more DENY + SAMEORIGIN stacking conflicts:

| Context | Policy |
|---|---|
| Admin / Embedded (WP-Desk iframe) | `SAMEORIGIN` |
| Frontend / Public | `DENY` |

`.htaccess` scrub removes any host-level XFO headers that could stack. Titan no longer emits a conflicting XFO header.

> Note: If `.htaccess` is not writable (some Apache hardened configs), host-level XFO may still stack. Recovery mode provides a manual override path.

### Iframe Policy (`WPDeskIframePolicy`)

Per-app routing: apps that are incompatible with iframe rendering (Customizer, Page Builders) are routed to **Classic Tab mode** instead of opening inside the desktop workspace.

### Portal URL Resolution

- `resolvePortalUrl` enforces admin portal URLs — frontend URLs are never registered as portal apps
- Frontend link clicks open in a new tab instead of inside the workspace iframe
- Rescue / Classic fallback path if resolution fails

### Portal Card Layout v2

- `filemtime`-based cache-busting on portal card assets
- Portal badge visible only on list screens (Posts, Pages, Comments, Plugins)
- Badge hidden automatically in: Security Center, VGTAstra, Full-App windows

### Full-App Rendering

- Full-bleed sidebar: `100vh`, no admin-bar 32px offset overlap
- Content scroll inside iframe — no overflow bleed to workspace
- No padding/sidebar collision on Security Center or Astra windows

---

## 🔒 Recovery Control Plane *(New in V2.0)*

Recovery lives **outside the desktop** — accessible even when the workspace is broken or inaccessible.

| Feature | Detail |
|---|---|
| **Force Classic Mode** | Bypass desktop entirely for current session |
| **Desktop Settings UI** | Access settings without desktop loading |
| **Redirect Off** | Disable auto-redirect to desktop (prevents redirect loops) |
| **Diagnostics** | Read system state, settings, module status without desktop context |

Access: append `?vgt_bypass=1` to any admin URL, or use the Recovery entry point in the classic admin menu.

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
| **Cache Busting** | `filemtime`-based asset cache-bust (V2) |

<img width="2560" height="1229" alt="image" src="https://github.com/user-attachments/assets/c51ff077-c5f8-4caf-91b6-cbb4b233fb9f" />


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
| **Full-Bleed Apps** | Security Center / Astra / Full-Apps: sidebar 100vh, no admin-bar offset (V2) |
| **Custom Scrollbars** | Global slim translucent scrollbars matching glassmorphic style |

<img width="1917" height="908" alt="Multi-Window Workspace" src="https://github.com/user-attachments/assets/1b9b8656-5866-4443-a9a8-ed2c7f7a724b" />

---

<img width="2560" height="1229" alt="image" src="https://github.com/user-attachments/assets/9311ba87-c628-4436-8167-bfb5ca8f9753" />


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
- Full folder structure (names, contents, positions) persisted in `vgt_desk_settings`
- Survives page reload — state rehydrated from DB on load

---

### 🔤 2.4 WordPress Submenus

Native WordPress submenus surfaced as glassmorphic dropdown popups — no navigation away from the desktop required.

**Backend (PHP):**
- `build_dynamic_plugin_apps()` parses the global `$submenu` array
- Capability check via `current_user_can` — users only see submenus they have access to
- Admin portal URLs enforced — front-URLs never registered as apps (V2)

**Frontend (XSS-Safe):**
- All submenu items built via `document.createElement` + `textContent` — zero innerHTML injection
- Clicking a submenu item loads the child page in the parent window's iframe (`vgt_iframe=true`)
- Window title bar updates: `Parent Title › Submenu Title`

---

### 🖼️ 2.5 Multi-Layout Workspace

Three complete OS aesthetic styles, switchable at runtime via Control Center or Spotlight CLI.

| Layout | Style | Dock Position | Maximize Behavior |
|---|---|---|---|
| **macOS Cupertino** | Menu bar top, floating centered dock | Bottom | Full workspace bounds |
| **Windows Redmond** | Bottom taskbar (full width), Windows 11 dock inside | Bottom bar | `height: calc(100% - 48px)` |
| **Linux Tux** | Vertical sidebar dock (left) | Left side | `left: 80px; width: calc(100% - 80px)` |

**Spotlight CLI:** `/layout [macos|windows|linux]` — switches layout with sound feedback and icon recalculation.

**Aero Snap Layout-Awareness:** Snap preview zones and half-screen drop targets shift to clear the active layout's taskbar/sidebar boundaries.

---

### ⚙️ 2.6 Command Center

Split glassmorphic layout with left navigation tabs and scrollable right content.

**Real-Time Diagnostics:**
- CPU load + RAM usage with live progress bars (red highlight above 80%)
- Active Sentinel WAF / Throne Guard state
- Database footprint across all 6 VGT metadata/log tables
- Embedded terminal console with real-time system event log stream
- 5-second polling loop — active only when Command Center is open

**Enclave Security Center:**
- Active IP Ban Manager — queries both Sentinel CE and Sentinel V7 tables
- Superkey update: `ajax_update_superkey()` — enforces 12-character minimum

**Display & Personalization:**
- Resolution scaling slider (10px–24px) — updates `--vgt-font-size` on `:root`
- Layout switcher (macOS / Windows / Linux)
- Wallpaper selector + custom URL (same-origin enforced)
- HSL accent color theme matrix

**Keyboard Shortcuts Mapper:**
- Click "Record" → captures modifier key (Ctrl/Alt/Shift/Meta) + `e.code`
- Persisted in `vgt_desk_settings`

---

### 🛡️ 2.7 Sentinel Hardening Auditor

Deeply integrated into the VGT Security Center, replacing the previous standalone view.

**Sentinel Auto-Awareness:** detects Sentinel CE or V7, checks running defense modules (Airlock, Cerberus, Titan) and reflects their state in the security index automatically.

**Cyberpunk Scoring UI:** Cyber-corner brackets, animated neon scan line, angled clip-path start button, cockpit-style score display, interactive tier grid (DIAMANT / PLATIN GOLD / VGT SECURED / CRITICAL RISK), red warning row highlighting on unresolved items.

**Score Index:** `ScoreMax` dynamically counts actual test vectors (29) — 27 passed = 93%.

---

### 🔒 2.8 Security Architecture — Defense in Depth

**Integrated Sentinel CE (v1.7.1):** AEGIS, CERBERUS, AIRLOCK, HADES, CHRONOS, GHOST TRAP in boot sequence.

**AEGIS WAF Exceptions:** `edit_posts` / `manage_options` users: all pattern weights zeroed on post-save endpoints; RCE/LFI remain active. WooCommerce + Gutenberg exceptions active.

**Airlock Upload Protection:**
- Real file size validation via `filesize()` — client-supplied claims not trusted
- MIME detection via `finfo`
- Image type cross-check via `IMAGETYPE_*`
- SVG files blocked at upload
- ZIP/Office: traversal paths and executable content scan
- SVG vector scan: script tags, event handlers, `javascript:` URIs, `foreignObject`, iframe/object/embed, external/data href vectors

**Network & IP Hardening:**
- Cloudflare IPv4/IPv6 CIDR ranges in IP resolution
- Configurable trusted proxy CIDRs
- `X-Forwarded-For`, `X-Real-IP`, `CF-Connecting-IP` evaluated only in trusted proxy context

**Titan Security Headers:** `X-Content-Type-Options`, `X-Frame-Options` (via Frame Policy in V2), `Referrer-Policy`, `Permissions-Policy`, `HSTS`, `COOP`, `COEP`, `CORP`, optional CSP baseline.

**AJAX Exception Hardening:** Five major AJAX actions catch typed exception hierarchies (`SecurityException`, `ValidationException`, `StorageException`, `\Throwable`) — opaque responses to client, full tracebacks to `error_log` only.

**Vault & Scanner Hardening:** directories `0700`, files `0600`, writes use `LOCK_EX`. Manifest files written with restrictive permissions.

**Throne Guard (v2.6.0):** 14 toxic capabilities stripped, SHA256 HMAC session fingerprinting, Superkey vault (Argon2/Bcrypt), Hardware Deactivation Lock, Jailed Upload Vault (`/mcp_vault/`).

---

### 💡 2.9 Desktop Engine Modularization

`desktop.js` (3,250+ lines) decomposed into 9 isolated modules.

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

**Main Orchestrator (`desktop.js`):** 61 lines. Triggers module init routines after `DOMContentLoaded`.

---

### 💾 2.10 Performance & Persistence

**RAM Hibernation:** minimized windows suspend iframe to `about:blank` — rehydrated on restore.

**Widget Position Persistence (V2 hardened):**
- `widget_positions` now saved as full-replace — no delta-merge zombie states
- `localStorage` backup for immediate client-side restoration before DB response

**Custom Relational DB Layer:**
- `{prefix}vgt_desk_settings` — `UNIQUE(user_id, setting_key)`
- Delta-merge via `array_replace_recursive` for JSON objects (folders, window positions, icon coordinates)
- Single-query reads per settings fetch — no N+1 on startup

**Per-User Opt-in:** desktop off by default. Admin notice → explicit activation. `?vgt_bypass=1` → classic view.

**Viewport Dimension Auto-Fit:** `#vgt-shell-root` recalculated on resize. Parent containers forced to `overflow: hidden !important`. Right-half widgets saved as relative `right` coordinates.

---

### 🎨 2.11 Glassmorphic UI & Custom Modals

**Wallpaper Engine:** Local WebP assets, preset collection + custom URL (same-origin enforced), zero CDN.

**Accent Colors:** Indigo / Emerald / Cyan / Amber / Rose — applied via Design System tokens (V2).

**Custom Modal Dialogues:** Replaces all native `prompt()` / `confirm()` — glassmorphic overlay, styled to match desktop theme.

**Widget Visibility Manager:** Individual toggle in Control Center — Clock, System Metrics, Notes, Sentinel.

---

## 📜 Changelog

### v2.0.0-beta.1 — Operator OS: Design System, Frame Policy, Control Plane *(Current)*

> V2.0 turns the v4 operator workspace into a hardened Operator OS: a unified Frame/Portal Policy, a Design System across all security and studio surfaces, robust widget/settings persistence, Classic Mode for builders, and Recovery outside the desktop — preserving the Zero-Overheat desktop DNA and Security Center (Sentinel · Throne · Dattrack).

#### Operator OS Positioning

- Version label updated system-wide: `VGT_WPDESK_VERSION` / `VERSION_LABEL` → `2.0.0-beta.1`
- Welcome popup updated to celebrate V2 milestones (Design System, Portal Hardening, Zero-Overheat, Security Plane)
- Product editions documented in `PRODUCT.md`: Core / Secure / Studio / Ops

#### V2 Control Plane (New architecture layer)

Six new service classes sit between the Security boot sequence and the PHP kernel:

- **`WPDeskFramePolicy`** — single-owner X-Frame-Options: admin/embed → `SAMEORIGIN`, frontend → `DENY`; `.htaccess` scrub; Titan XFO header stack removed
- **`WPDeskIframePolicy`** — per-app classic vs. iframe routing; Builder/Customizer/Themes → classic tab
- **`WPDeskWidgetLayout`** — widget position validation and normalization; empty id / oversized rejected before save
- **`WPDeskDesignSystem`** — shared token and component loader across all admin surfaces
- **Module Registry** — clean ordered boot of integrated modules (IframeTransformer, Security Center, Build Center, …)
- **Pure Test Suite** — control plane tests without full WordPress boot (frame, widgets, design system, harden)

#### Unified Design System

- `assets/css/design-system/` — tokens / base / components / compat
- Brand: Indigo primary, optional security and premium themes
- All 12 modules migrated to shared tokens (Security Center, Sentinel, Throne, Recovery, Dattrack, Login, Vault, Book, Chronos, Astra, Desktop, Portal)
- MU-deployer CSS bug fixed (`mudeployer` → `mu-deployer`)
- Astra CLOSE buttons: squash layout bug fixed

#### Portal & Iframe Hardening

- `resolvePortalUrl` — admin portal URLs enforced; front-URLs → new tab; rescue/classic fallback on resolution failure
- Portal Card Layout v2 — `filemtime` cache-bust on portal assets
- Portal badge: visible only on list screens (Posts, Pages, Comments, Plugins); hidden in Security Center, Astra, full-app windows
- Full-bleed full-apps: sidebar `100vh`, content scroll inside iframe, no admin-bar 32px overlap

#### Widget & Settings Persistence (V2 hardened)

- `widget_positions` saved as full-replace — delta-merge zombie states eliminated
- `localStorage` backup for immediate client-side restore before DB response
- Widget position normalization on load: empty IDs and oversized coordinates rejected

#### App Builder — URL Policy

- Admin portal URLs enforced on registration; frontend URLs not registered as portal apps
- Front-URL clicks route to new browser tab
- Classic-mode routing for incompatible screens (Customizer, Page Builders)

#### Recovery Control Plane

- Recovery accessible outside desktop: Force Classic Mode, Desktop Settings UI, Redirect Off, Diagnostics
- `?vgt_bypass=1` remains the emergency escape hatch for full lockouts

---

### v1.0.0-Beta v4.1 — Patch Release *(archived)*

Sentinel CE → v1.7.1. Black screen after Quarantine/Genesis accept fixed. Airlock expanded: `filesize()`, `finfo`, `IMAGETYPE_*`, SVG blocker, ZIP/Office traversal scan, SVG vector scan. Cloudflare CIDR IP hardening. Trusted proxy CIDRs. `X-XSS-Protection` removed from Titan. Styx Lite outbound policy. PHP 7.4 compatibility restored. UTF-8 BOM fatal in `class-vis-network.php` fixed.

---

### v1.0.0-Beta v4 — Stability, Scaling & Security *(archived)*

Modular PHP kernel (Settings / AppBuilder / Plugin). VGT Build Center consolidation. Sentinel Hardening Auditor with Cyberpunk UI. Dattrack telemetry integration. V7 class collision fix. JS compiler fix (`zoom` identifier redeclaration). Viewport auto-fit. AJAX exception hardening. Wallpaper same-origin enforcement. SAMEORIGIN iframe fix. Scope-limited error handlers. SQL identifier hardening. All files: `// STATUS: 💠 DIAMANT VGT SUPREME`.

---

## ⚙️ Technical Specifications

| Metric | Specification |
|---|---|
| **Required WordPress** | 6.0+ |
| **Required PHP** | 8.1+ (Strict Types enforced) |
| **Frontend Frameworks** | None — 100% Vanilla JS (ES6+) / CSS Custom Variables |
| **Compilation Overhead** | Zero — no Node.js, no Vite, no TypeScript at runtime |
| **JS Architecture** | 9 isolated modules + 61-line orchestrator |
| **PHP Architecture** | Modular service classes + V2 Control Plane (6 policy classes) |
| **Design System** | Unified token/component layer across 12 modules |
| **Database Footprint** | `{prefix}vgt_desk_settings` (relational) + `{prefix}mcp_user_roles` |
| **Runtime External Calls** | Zero |
| **WAF Engine** | AEGIS Anomaly Scoring — Sentinel CE v1.7.1 / Sentinel V7 |
| **Session Hardening** | Throne Guard v2.6.0 |
| **Frame Policy** | `WPDeskFramePolicy` — single-owner XFO, no stacking |
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
# Admin notice → click to activate for your user
# Or: WP-Desk → Settings → Desktop as Default View

# 4. Initialize Throne Guard (Critical — do this first)
# Desktop → Master User Control → set Superkey (12+ chars) + configure role hardening

# 5. Configure Sentinel CE / V7
# Desktop → Sentinel widget → verify AEGIS, CERBERUS, AIRLOCK, HADES, CHRONOS, GHOST TRAP active

# 6. Select Layout (Optional)
# Command Center → Display → Layout
# Or: Alt+Space → /layout [macos|windows|linux]

# 7. Emergency bypass (if locked out)
# Append ?vgt_bypass=1 to any admin URL
# Or: use Recovery Control Plane from classic admin menu
```

---

## 🔗 VGT Ecosystem

| Tool | Type | Purpose |
|---|---|---|
| 🖥️ **VGT WP-Desk** | **Operator OS** | Hardened OS layer for WordPress backend — you are here |
| 🏰 **VGT Throne Guard** | **Hardening** | Toxic capability stripping + Superkey vault — integrated |
| ⚔️ **[VGT Sentinel](https://github.com/visiongaiatechnology/sentinelcom)** | **WAF / IDS** | Zero-Trust WordPress WAF — integrated |
| 🛡️ **[VGT Myrmidon](https://github.com/visiongaiatechnology/vgtmyrmidon)** | **ZTNA** | Zero Trust device registry and cryptographic integrity verification |
| ⚡ **[VGT Auto-Punisher](https://github.com/visiongaiatechnology/vgt-auto-punisher)** | **IDS** | L4+L7 Hybrid IDS — attackers terminated before they even knock |
| 📊 **[VGT Dattrack](https://github.com/visiongaiatechnology/dattrack)** | **Analytics** | Sovereign analytics engine — your data, your server, no third parties |
| 🔐 **[VGT Omega Vault](https://github.com/visiongaiatechnology/vgt-omega-vault)** | **Encrypted Forms** | AES-256-GCM form vault with drag-and-drop builder — integrated via Build Center |
| 🧠 **[VGT AETHEL](https://github.com/visiongaiatechnology/vgt-aethel)** | **Sovereign AI Kernel** | Local AI agent runtime — Astra cockpit integrated in Build Center |
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

> *"WP-Desk V2 was built because a hardened operator workspace needs more than feature additions — it needs a control plane. One Frame Policy. One Design System. One Recovery path. Zero overheating."*

---

*Version 2.0.0-beta.1 — VGT WP-Desk // Operator OS // V2 Control Plane // Unified Design System // Frame & Portal Hardening // Recovery Control Plane // Classic Mode // 9-Engine Desktop // Multi-Layout (macOS/Windows/Linux) // Command Center // VGT Build Center // Sentinel Hardening Auditor // Folder Mode // Aero Snap // RAM Hibernation // Sentinel CE v1.7.1 // Throne Guard v2.6.0 // Zero-Overheat Architecture // AGPLv3*
