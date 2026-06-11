# 🖥️ VGT WP-Desk — Premium Slim Desktop Layer

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0--Beta__v3-brightgreen?style=for-the-badge)](#)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Overheat_OS--Layer-blue?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Vanilla_JS_%2F_CSS3-orange?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-BETA__V3-yellow?style=for-the-badge)](#)
[![Security](https://img.shields.io/badge/Security-Diamant_VGT_Supreme-red?style=for-the-badge)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

> *"No frameworks. No build pipeline. No compromise on UX."*  
> *AGPLv3 — Open Source Core. Built for operators, not for SaaS dashboards.*

---

## ⚠️ DISCLAIMER: BETA SOFTWARE

This project is currently in **Beta v3** and part of ongoing development at VisionGaia Technology. It is **not** yet a finalized production release.

**Use at your own risk.** The software may contain bugs or unexpected behavior. Test thoroughly in a staging environment before deploying to live sites.

Found a bug or have an improvement? **Open an issue or contact us.**

---



<img width="2559" height="1227" alt="image" src="https://github.com/user-attachments/assets/565f660f-0c8a-4d98-bc22-cf45e9b149be" />


---

## 🔍 What is VGT WP-Desk?

VGT WP-Desk is a **modular, framework-free WordPress backend enhancement** that transforms the classic WordPress admin interface into a high-performance, OS-style desktop environment — hardened at the kernel level by an integrated security framework.

It is engineered under the **Zero-Overheat Doctrine**: maximum performance, absolute compatibility with WordPress Core and third-party hooks, and a strict refusal to rely on heavy frameworks or build pipelines.

Installed plugins are automatically detected, mapped as standalone desktop apps, and isolated within a **chromeless iframe architecture** — eliminating sidebar fragmentation and replacing it with a consistent, distraction-free multi-window workflow.

**Beta v3 marks a foundational shift:** VGT WP-Desk is no longer a pure UX layer. The full **VGT Sentinel CE WAF** and **VGT Throne Guard** system hardener are now integrated directly into the boot sequence, forming a unified, self-contained security and productivity enclave.

```
Classic WordPress Admin:
→ Fragmented sidebar navigation
→ Context-switching overhead
→ No persistent workspace state
→ Monotone, static interface
→ No integrated WAF or session hardening

VGT WP-Desk OS Layer (Beta v3):
→ IframeTransformer          — 100% hook-preserving CSS-Grid tile engine
→ Multi-Window Workspace     — drag, resize, minimize, focus
→ App Registry               — auto-maps installed plugins as desktop apps
→ Persistent Profiles        — per-user workspace state via wp_usermeta
→ AEGIS WAF                  — stream-based payload inspection (SQLi, XSS, RCE, LFI)
→ CERBERUS                   — brute-force shield + device fingerprinting
→ AIRLOCK                    — binary-level upload inspection
→ HADES                      — WordPress path obfuscation + route masking
→ CHRONOS                    — async filesystem integrity + malware scanner
→ GHOST TRAP                 — deception honeypot grid
→ Throne Guard               — toxic privilege stripping + Superkey vault
→ Anti-Phishing Origin Guard — deep-link SSRF prevention
→ CSRF Nonce Hardening       — cryptographic session coupling
→ DOM XSS Engine             — full HTML escaping before DOM injection
→ CSP Nonce Bridge           — automatic nonce propagation to all iframe assets
→ Glassmorphic UI Layer      — wallpaper engine, accent colors, blur modes
```

---

## 🏛️ Architecture

```
WordPress Admin Request
↓
Heuristic Session Detection (PHP Engine)
→ URL indicator: vgt_iframe=true
→ Modern HTTP security header: Sec-Fetch-Dest: iframe
→ HTTP Referer analysis on form submissions
→ Iframe context detected → strip menus, adminbar, footer
↓
SENTINEL CE BOOT SEQUENCE (Integrated in Beta v3)
→ AEGIS WAF activates on all HTTP methods (GET, POST, PUT, PATCH)
→ Input normalized: 5-layer URL decode, IIS Unicode, comment stripping
→ Scoring engine evaluates payload — threshold_block=12, threshold_challenge=5
→ CERBERUS enforces perimeter ban before WordPress user logic loads
→ AIRLOCK intercepts multipart/form-data file uploads
→ HADES masks wp-login, wp-admin, wp-content paths via rewrite engine
→ GHOST TRAP honeypot files deployed in root — access = instant IP ban
→ CHRONOS starts async filesystem scan (time-sliced, cron-safe)
↓
Throne Guard Capability Hardening & Session Gate
→ 14 toxic Administrator permissions stripped — moved exclusively to Master role
→ Zero-Trust session validation: IP & User-Agent SHA256 HMAC fingerprinting
→ Superkey verification required to unlock Master enclave (Argon2/Bcrypt)
→ Hardware Deactivation Lock prevents bypass via plugin disabling
↓
IframeTransformer (CSS-Grid Injection & UI Transformations)
→ Manipulates native WordPress admin list table DOM
→ display: grid !important on native tbody
→ Posts, pages, comments, plugins → responsive tiles
→ Custom dark-contrast styling: rgb(9 13 22) + white text
↓
CSP Nonce Bridge (NEW in Beta v3)
→ Throne Guard CSP nonce propagated to all IframeTransformer CSS transforms
→ All dynamically injected JavaScript modules receive current request nonce
→ Enclave operates correctly under strict Content-Security-Policy
↓
Deep-Link Origin Guard (Anti-Phishing / Anti-SSRF)
→ openDeepLink(rawUrl) validates URL.origin against window.location.origin
→ External origins blocked → redirect to welcome window
↓
Persistent Workspace Sync (wp_usermeta)
→ All state saved asynchronously via Non-Blocking AJAX
→ Per-user profiles — no global option overwrites
```

<img width="2558" height="1232" alt="image" src="https://github.com/user-attachments/assets/3c73df33-4695-4bbf-9d2c-0e073f1fa297" />


<img width="445" height="910" alt="image" src="https://github.com/user-attachments/assets/fc37497f-6f3d-4d72-a5ea-d6c9bdec5607" />


<img width="794" height="478" alt="image" src="https://github.com/user-attachments/assets/0d3c0ed1-7c58-4674-8136-12ababf28caa" />


<img width="1075" height="708" alt="image" src="https://github.com/user-attachments/assets/237c5c5a-1aac-44ad-91fc-a1f9c2de6e3f" />


<img width="702" height="576" alt="image" src="https://github.com/user-attachments/assets/41ab1782-1cf5-40bc-9184-003c8f971e32" />


---

## 🧩 Feature Matrix

### ⚡ 2.1 IframeTransformer — Hook-Preserving Tile Engine

The core innovation of VGT WP-Desk. Where other desktop-mode approaches rewrite native WordPress components (breaking all plugin hooks), the IframeTransformer takes a fundamentally different path.

| Parameter | Value |
|---|---|
| **Method** | CSS-Grid injection into native WordPress admin list table DOM |
| **Hook Preservation** | 100% — SEO columns, custom fields, all third-party hooks intact |
| **Transformed Views** | Posts, Pages, Comments, Plugins |
| **Layout Engine** | `display: grid !important` on native `tbody` element |
| **Responsive** | Yes — tiles adapt to workspace window dimensions |
| **Styling Override** | Plugins page styled with dark `rgb(9 13 22)` background and high-contrast `#ffffff` text |
| **CSP Compatibility** | Full — all injected assets receive Throne Guard nonce (Beta v3) |

<img width="1914" height="914" alt="Tile Engine Layout" src="https://github.com/user-attachments/assets/3f3df87a-45ef-4666-a879-8831f791a0e2" />

---

### 🛡️ 2.2 Sentinel CE Integration — Integrated WAF & IPS *(New in Beta v3)*

VGT Sentinel CE is now fully integrated into the WP-Desk boot sequence. The six security modules activate before WordPress user logic initializes, forming a perimeter defense layer beneath the desktop.

#### A. AEGIS — Stream-Based Payload WAF

| Parameter | Value |
|---|---|
| **Engine** | PCRE scoring matrix — ReDoS-resistant (Anomaly Scoring Architecture) |
| **Inspection Scope** | GET, POST, PUT, PATCH — all HTTP methods, JSON streams, query strings, headers |
| **Protected Vectors** | SQLi, XSS, RCE, LFI, malicious User-Agents, hostname spoofing, localhost bypass |
| **Normalization** | 5-layer pipeline: URL decode, HTML entity, Unicode escape, hex escape, IIS %uXXXX |
| **Scoring Thresholds** | `threshold_block=12` (hard block) · `threshold_challenge=5` (JS PoW challenge) |
| **Admin Exemption** | `edit_posts` / `manage_options` roles — SQLi/XSS weights zeroed; RCE/LFI remain active |
| **Contextual Exceptions** | WooCommerce checkout fields cleared before scan · Gutenberg REST thresholds raised to 50 |
| **Response** | Score < 5: pass · Score 5–12: JS PoW challenge · Score > 12: hard block |

The JS challenge page is responsive, served in corporate design with a CPU-bound mathematical challenge — bots without a JS engine fail silently.

#### B. CERBERUS — Brute-Force Shield & Access Control

- Pre-authentication IP validation fires at `hook_priority=1` — before any WordPress user logic loads
- Disk-based state tracking with exclusive `flock` file locking — database spared during mass attacks
- Device fingerprinting via SHA256 HMAC of IP + User-Agent — session hijacking neutralized
- Cloudflare CIDR verification for true-IP detection — X-Forwarded-For spoofing prevented

#### C. AIRLOCK — Binary Upload Inspection

- Strict allowlist of approved file extensions — no blacklist approach
- O(1) memory-safe scan: 4KB header/footer chunks for files >2MB
- Detects polyglot vectors: PHP wrappers, Base64 obfuscation, exec-patterns embedded in image payloads
- Magic byte analysis independent of file extension — real type validated at binary level

#### D. HADES — Route Masking & Path Obfuscation

| Original Path | Obfuscated |
|---|---|
| `wp-login.php` | Custom slug (configurable) |
| `wp-admin` | Custom slug (configurable) |
| `wp-content/plugins` | `content/lib` |
| `wp-content/uploads` | `storage` |
| `wp-includes` | `core` |

Direct requests to original paths return a standard 404 — automated WordPress scanners produce zero results.

**Desktop-Iframe Protection:** The Hades router enforces `vgt_iframe=true` on all internal iframe requests. A WordPress plugin cannot break out of the iframe context back into the classic backend.

#### E. CHRONOS — Async Filesystem Integrity Scanner

- SHA-256 baseline via `integrity_matrix.php` — mtime/size pre-filter before hash computation
- 40+ embedded malware signatures matched only on new/modified files — zero overhead on clean systems
- Time-sliced execution (max 20s per cron cycle) — PHP timeout-safe on any hosting environment
- Ghost Trap honeypot: `wp-admin-backup-restore.php` — HTTP access triggers instant IP hard-ban

#### F. GHOST TRAP — Deception Honeypot Grid

Decoy files (`.env.backup`, `wp-config.old.php`) deployed in the document root. On access:
1. Instant IP hard-ban via CERBERUS
2. Convincing fake PHP stack trace returned — attacker believes they found a real vulnerability
3. Alert logged and administrator notified

---

### 🔒 2.3 Throne Guard Integration — Kernel Hardening *(Expanded in Beta v3)*

Throne Guard operates as a privilege and session hardener at the WordPress user database level.

#### A. Administrator-Neutering — Toxic Privilege Stripping

14 high-risk capabilities removed from the standard `administrator` role and moved exclusively to the `master` role:

`activate_plugins` · `deactivate_plugins` · `edit_themes` · `install_plugins` · `install_themes` · `update_plugins` · `update_themes` · `update_core` · `create_users` · `delete_users` · `edit_users` · `promote_users` · `remove_users` · `unfiltered_upload`

Standard administrators retain full content management rights — but cannot touch the security layer.

#### B. Zero-Trust Session Lock — Superkey Vault

```
Standard WP Login → WordPress session established
         ↓
Master Enclave remains locked (regardless of role)
         ↓
Superkey entry required (12+ characters)
         ↓
Server-side validation: Argon2/Bcrypt hash comparison
         ↓
Session token generated: SHA256 HMAC(IP + User-Agent + secret)
         ↓
Master enclave unlocked — session bound to browser fingerprint
```

Cookie theft does not unlock the enclave. The HMAC token is non-transferable.

#### C. Hardware Deactivation Lock

Standard administrators cannot deactivate, edit, or modify the Throne Guard / Sentinel modules through the WordPress plugin interface. PHP-level bypass attempts are blocked at capability check.

#### D. Jailed Upload Vault — CDR Re-Encoding

Secure upload interface at `/mcp_vault/`:
- GD/Imagick physical image re-encoding — polyglot payloads destroyed at pixel level
- EXIF metadata fully stripped — no embedded code survives
- `umask(0077)` during write — minimal file permission exposure
- Files stored outside web root with `.vgt` extension + `.htaccess` execution block

---

### 🔗 2.4 System Synergies — Unified Desktop Enclave *(New in Beta v3)*

The integration of WP-Desk, Sentinel CE, and Throne Guard produces capabilities neither system could achieve independently.

#### Sentinel Live Desktop Widget

A glassmorphic widget rendered directly on the desktop wallpaper showing:
- Firewall kernel status (Active / Inactive)
- Real-time blocked IP count
- Last detected threat type + timestamp

The `Sentinel aktivieren / deaktivieren` button triggers an AJAX handshake — CSRF-nonce secured, `manage_options` capability verified — toggling the WAF live from the desktop without any page reload.

#### Cryptographic CSP Nonce Bridge

`MasterUserControlPlugin::get_csp_nonce()` (Throne Guard) is now linked directly to the WP-Desk asset loader. Every CSS injection by the IframeTransformer and every JS module loaded by the desktop receives the cryptographic nonce of the current request automatically. The enclave operates correctly under strict `Content-Security-Policy` headers — no `unsafe-inline` exceptions required.

#### Top-Bar Enclave Quick Access

The desktop top-bar includes dedicated quick-access menus:
- **Login-Design** → opens VGT Omega Login Engine dashboard in a dedicated portal window
- **System Settings** → direct access to Throne Guard and Sentinel CE dashboards in isolated iframe windows

#### Hades Iframe Continuity

The Hades route masker enforces `vgt_iframe=true` on all internal desktop navigation. Any WordPress plugin that would normally redirect to a full-page admin URL is caught by the Hades router and kept within the iframe workspace — users never lose their desktop context.

---

### 🖱️ 2.5 Multi-Window Workspace

A full OS-style window management system operating entirely within the WordPress backend.

| Feature | Detail |
|---|---|
| **8-Edge Window Resizing** | Eight independent invisible interaction zones on every window — scale in all directions |
| **Drag Threshold (8px)** | Micro-jitter filtered — drag engine only activates after intentional 8px movement |
| **Collision-Avoiding Grid Snapping** | Icons arrange column-wise; occupied grid cells detected via radius algorithm; auto-eject to next free cell |
| **Smart Dock & Taskbar** | Lower dock shows open/minimized window state via LED indicator dots in system accent color |
| **Task Switcher** | Single dock-icon click minimizes or focuses the corresponding window |
| **Iframe Isolation** | Each app window renders in a chromeless iframe — full WordPress functionality, no navigation bleed |
| **Hades Continuity** | All iframe navigation kept within desktop context — classic backend breakout structurally prevented |

<img width="1917" height="908" alt="Multi-Window Workspace" src="https://github.com/user-attachments/assets/1b9b8656-5866-4443-a9a8-ed2c7f7a724b" />

---

### 🔐 2.6 Security Architecture — Defense in Depth

Beyond the Sentinel and Throne Guard integration, the WP-Desk layer itself enforces independent security controls.

**A. AJAX Capability Guard**
```php
if (!current_user_can('read')) {
    wp_send_json_error('Unzureichende Berechtigungen.');
}
```
Explicit role and capability verification on every AJAX persistence endpoint — subscriber flooding and metadata injection prevented.

**B. Deep-Link Origin Guard (Anti-Phishing & SSRF)**
```javascript
const targetUrl = new URL(rawUrl, window.location.origin);
if (targetUrl.origin !== window.location.origin) {
    console.error("VGT Safety Guard: External Deep Links blockiert.");
    return this.openWindow('welcome');
}
```
All deep-link URLs validated against current server hostname before any window opens. External origins silently rejected.

**C. CSRF Nonce Hardening**
All activation, deactivation, and state-changing AJAX actions bound to cryptographically secure WordPress nonces tightly coupled to the user session.

**D. DOM XSS Prevention**
All dynamic window generation passes through a hardened escaping engine: `escapeHTML()` neutralizes `&`, `<`, `>`, `"`, `'`. `cleanUrl()` blocks `javascript:` protocol injections with `about:blank` fallback.

---

### 🎨 2.7 Glassmorphic UI & Wallpaper Engine

```
Wallpaper Engine:
→ Local, pre-optimized WebP resources (wallpapers/)
→ Local-first delivery — bundled assets only, zero third-party CDN requests
→ Preset collection + custom URL support

Accent Color System:
→ Indigo | Emerald | Cyan | Amber | Rose
→ Dynamically adjusts: badges, buttons, LED indicators, dock accents, Sentinel widget

Glassmorphism Mode:
→ Blur filter toggle (vgt_desk_blur)
→ Performance fallback for older GPUs via Boolean state

Widget Visibility Manager (Beta v3):
→ Individual widget toggle grid in Control Center
→ Clock / System Metrics / Notes / Sentinel status — each independently toggleable
```

> **Data sovereignty:** All UI rendering, wallpapers, scripts, and stylesheets are served from your own server. External requests are disabled by design. Zero GDPR/DSGVO exposure.

---

### 💾 2.8 Persistent Workspace Profiles

Per-user workspace state stored asynchronously via Non-Blocking AJAX in `wp_usermeta`. No global options overwritten. Every user maintains a fully independent workspace.

| Key | Type | Description |
|---|---|---|
| `vgt_desk_wallpaper` | URL | Desktop background (preset or custom) |
| `vgt_desk_accent_color` | CSS token | System accent (indigo / emerald / cyan / amber / rose) |
| `vgt_desk_blur` | Boolean | Glassmorphism blur filter state |
| `vgt_desk_icon_positions` | JSON Object | X/Y coordinates of all desktop icons |
| `vgt_desk_window_settings` | JSON Object | Width, height, left, top for all open application windows |

`JSON_FORCE_OBJECT` enforced on all complex metadata keys — prevents empty PHP arrays from serializing as `[]` and crashing JavaScript state on load.

---

## ⚙️ Technical Specifications

| Metric | Specification |
|---|---|
| **Required WordPress** | 6.0+ |
| **Required PHP** | 8.1+ (Strict Types enforced) |
| **Frontend Frameworks** | None — 100% Vanilla JS (ES6+) / CSS Custom Variables |
| **Compilation Overhead** | Zero — no Node.js, no Vite, no TypeScript at runtime |
| **Database Footprint** | 5 user-scoped meta keys (`wp_usermeta`) + `{prefix}mcp_user_roles` custom table |
| **Runtime External Calls** | Zero — strict local asset hosting |
| **WAF Engine** | AEGIS Anomaly Scoring (integrated Sentinel CE v1.7.0) |
| **Session Hardening** | Throne Guard v2.6.0 — Superkey vault, toxic privilege stripping |
| **CSP Compliance** | Full — Throne Guard nonce bridge propagates to all iframe assets |
| **Bypass Mechanism** | Cookie-based express state for emergency access (`?vgt_bypass=1`) |

---

## 🚀 Installation

```bash
# 1. Clone into WordPress plugins directory
cd /var/www/html/wp-content/plugins/
git clone https://github.com/visiongaiatechnology/vgtdesk

# 2. Activate in WordPress Admin
# Plugins → VGT WP-Desk → Activate

# 3. Open the Desktop
# Click "VGT WP-Desk" in the admin sidebar (top of menu)

# 4. Initialize Throne Guard (Critical — do this first)
# Open "Master User Control" app icon on the desktop
# Set your Superkey (12+ characters) and configure role hardening
# This locks the Master enclave and strips toxic privileges from standard admins

# 5. Configure Sentinel CE
# Open "Sentinel" widget on the desktop
# Verify AEGIS, CERBERUS, AIRLOCK, HADES, CHRONOS, GHOST TRAP are active
# Adjust WAF thresholds and admin exemptions for your stack

# 6. Emergency bypass (if locked out)
# Append ?vgt_bypass=1 to any admin URL — restores classic view for the session
```

> **Important:** Complete Step 4 (Throne Guard initialization) before Step 5. Sentinel CE is linked to the Throne Guard CSP nonce system — initializing in reverse order prevents nonce propagation.

---

## 📜 Changelog

### v1.0.0-Beta v3 *(Current)*

**Security Architecture — Sentinel CE Integration**
- Full integration of VGT Sentinel CE v1.7.0 into WP-Desk boot sequence — fires before WordPress user logic initializes
- AEGIS WAF upgraded to Anomaly Scoring architecture: per-rule score weights replace binary block decisions
- AEGIS: 5-layer normalization pipeline (URL decode, HTML entity, Unicode, hex, IIS %uXXXX)
- AEGIS: Contextual exemptions for WooCommerce checkout fields and Gutenberg REST API endpoints
- AEGIS: Admin/editor role exemptions — SQLi/XSS weights zeroed for `edit_posts` and `manage_options`; RCE/LFI remain active
- CERBERUS: Disk-based state tracking with `flock` file locking — database spared under mass attacks
- AIRLOCK: O(1) binary header/footer scan — polyglot file prevention
- HADES: WordPress path obfuscation fully integrated — all iframe navigation enforces `vgt_iframe=true` continuity
- CHRONOS: Async filesystem integrity scanner with 40+ malware signatures, time-sliced cron execution
- GHOST TRAP: Deception honeypot grid deployed — access triggers instant IP hard-ban + fake stack trace response

**Security Architecture — Throne Guard v2.6.0 Full Integration**
- 14 toxic capabilities stripped from `administrator` role — moved exclusively to `master` role
- Superkey session vault: Argon2/Bcrypt hashing, SHA256 HMAC session fingerprinting (IP + User-Agent)
- Hardware Deactivation Lock: standard admins cannot disable or bypass security modules via plugin interface
- Jailed Upload Vault (`/mcp_vault/`): GD/Imagick CDR re-encoding, EXIF metadata destruction, `umask(0077)` write hardening

**Desktop Synergies (New)**
- Sentinel Live Desktop Widget: real-time firewall status, blocked IP count, AJAX toggle (CSRF-nonce secured)
- Cryptographic CSP Nonce Bridge: Throne Guard nonce propagated to all IframeTransformer CSS injections and JS modules
- Top-Bar quick access: Login-Design portal window, Throne Guard and Sentinel CE dashboard shortcuts
- Hades Iframe Continuity: plugin redirect breakouts from iframe workspace structurally prevented

**UX & Stability**
- Control Center Toggle Fix: state variables strictly normalized to JavaScript booleans — string/boolean coercion desync eliminated
- Widget Visibility Manager: fully styled toggle grid in Control Center — Clock, System, Notes, Sentinel individually toggleable with immediate visual feedback
- VGT Omega Login Engine Module: standalone login engine integrated with settings matrix and live simulation in chromeless iframe
- Plugin Overview Styling: inline CSS overrides force `rgb(9 13 22)` background + `#ffffff` high-contrast text in iframe plugin list views

---

### v1.0.0-Beta v2

**Security**
- Security Hotfix (Hades Module): added `current_user_can('manage_options')` validation in `VGTS_Hades::__construct()` — prevents unauthenticated guests from triggering infinite `.htaccess` rewrites (DoS / file corruption vector closed)
- Throne Guard Integration: capability manager, Superkey session vault, and Jailed Upload Vault ported into core dashboard structure
- WAF (Aegis) Optimization: resolved false-positive "AEGIS: Critical Vector [rce]" blocks on administrative AJAX requests and `admin-post.php` form submissions
- Sentinel V7 Compatibility: automatic detection of Sentinel V7 schemas, combined ban statistics output, locked widget toggles when Sentinel V7 active

**UX**
- Premium Lock Screen: glassmorphism dark-mode lock screen for Superkey session decryption
- Contrast Adjustments: plugin page iframe rows forced to `rgb(9 13 22)` background + `#ffffff` high-contrast text
- Desktop Layout Refinements: re-layered widget overlay container — drag position tracking and overlap bugs resolved

---

### v1.0.0-Beta v1

- Initial beta release of VGT WP-Desk shell
- IframeTransformer, Wallpaper Engine, Start Menu, and Dock implemented
- Basic Multi-Window Workspace with drag and resize

---

## 🔗 VGT Ecosystem

| Tool | Type | Purpose |
|---|---|---|
| 🖥️ **VGT WP-Desk** | **OS-Layer / UX** | Premium desktop interface for WordPress backend — you are here |
| 🏰 **VGT Throne Guard** | **Hardening** | Toxic capability stripping + Superkey vault — integrated in WP-Desk Beta v3 |
| ⚔️ **[VGT Sentinel](https://github.com/visiongaiatechnology/sentinelcom)** | **WAF / IDS** | Zero-Trust WordPress WAF — integrated in WP-Desk Beta v3 |
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

> *"WP-Desk was built because WordPress administrators deserved a workspace that doesn't fragment their attention, doesn't phone home, and doesn't ask them to accept a degraded UX as the price of using the world's most popular CMS. Beta v3 adds what every such workspace also needs: a security layer that activates before the attacker reaches the door."*

---

*Version 1.0.0-Beta v3 — VGT WP-Desk // Premium Slim Desktop Layer // Zero-Overheat Architecture // Sentinel CE v1.7.0 Integrated // Throne Guard v2.6.0 Integrated // Hook-Preserving IframeTransformer // CSP Nonce Bridge // Glassmorphic OS UI // AGPLv3*
