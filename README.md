# 🖥️ VGT WP-Desk — Operator OS for WordPress

> *"WordPress stays WordPress. The operator gets a hardened OS above it."*  
> *AGPLv3 — Local-first, framework-free and built for operators, not SaaS dashboards.*

---

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-2.0.0-brightgreen?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-V2.0.0_Beta_Testsystem_(Stable)-yellow?style=for-the-badge)](#)
[![Security Core](https://img.shields.io/badge/Security_Core-GeDefense_v8.0.0-00f0ff?style=for-the-badge)](#-security-center--gedefense-v800-integration)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Overheat_OS--Layer-blue?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Vanilla_JS_%2F_CSS3-orange?style=for-the-badge)](#)
[![Design](https://img.shields.io/badge/Design-Unified_Design_System-indigo?style=for-the-badge)](#-design-system)
[![Frame](https://img.shields.io/badge/Frame-Portal_Hardening-purple?style=for-the-badge)](#-portal--iframe-hardening)
[![VGT](https://img.shields.io/badge/VGT-VisionGaiaTechnology-red?style=for-the-badge)](https://visiongaiatechnology.de)
[![Security Review](https://img.shields.io/badge/Security_Posture-v8_Master_Review-brightgreen?style=for-the-badge)](#-security-posture)

---

## ⚠️ V2 BETA TESTSYSTEM // STABLE RELEASE (v2.0.0)

**VGT WP-Desk v2.0.0** is an Operator OS for WordPress powered by the **GeDefense v8.0.0 Sovereign Security Fabric**. This release represents the **V2 Beta Test System** marked as stable and engineered to the **DIAMANT VGT SUPREME** standard.

Found a bug or have an improvement suggestion? **Open an issue or contact our team.**

---

## 🔐 Security Posture

VGT WP-Desk v2.0.0 has undergone an exhaustive security posture and integrity review covering:
- The multi-window desktop runtime & RAM hibernation layers
- Native **GeDefense v8.0.0** 19-module kernel integration
- ThroneGuard Master role & capability isolation
- LoginPager sovereign authentication surfaces
- AJAX typed exception hierarchies (`SecurityException`, `ValidationException`, `StorageException`)
- Single-owner Frame Policy (`WPDeskFramePolicy`) preventing clickjacking and header stacking
- Zero external CDN dependencies & 100% same-origin isolation

Within the reviewed scope, no exploitable vulnerabilities were identified during the performed tests and review.

See: [SECURITY_POSTURE.md](SECURITY_POSTURE.md)

---

<img width="2560" height="1229" alt="VGT WP-Desk Operator OS" src="https://github.com/user-attachments/assets/ba5a557f-f1e9-4154-8b71-5ce0df2218a0" />

---

## 🔍 What is VGT WP-Desk?

VGT WP-Desk is a **modular, zero-dependency WordPress Operator OS** — an OS-style desktop layer that sits above WordPress and provides a unified control plane, hardened portal, multi-window workspace, and sovereign security matrix across every WordPress admin surface.

WordPress remains WordPress. Core and third-party plugin interfaces are untouched. WP-Desk provides the operating layer on top: multi-window workspace, persistent per-user desktop state, same-origin iframe isolation, unified Security Center, Build Center, Design System, and a hardened Frame Policy — all running without CDN calls, without build pipelines, without external runtimes.

Engineered under the **Zero-Overheat Doctrine**: vanilla JavaScript, PHP, and CSS only, served locally from the WordPress installation.

```text
Classic WordPress Admin:
→ Fragmented sidebar navigation
→ Context-switching overhead
→ No persistent workspace state
→ Limited operational visibility
→ Inconsistent UI across modules
→ Vulnerable admin surfaces

VGT WP-Desk V2 Operator OS:
→ OS-style multi-window desktop for WordPress
→ Powered by GeDefense v8.0.0 Sovereign Security Kernel (19 Modules)
→ ThroneGuard Master boundary (14 toxic capabilities stripped)
→ LoginPager Cyberpunk auth surface & live preview cockpit
→ Per-user opt-in with classic admin fallback (?vgt_bypass=1)
→ Hook-preserving iframe workspace (IframeTransformer)
→ Automatic plugin-to-app mapping & Submenu popups
→ Folder Mode, layouts, widgets, and persistent state
→ Command Center for real-time diagnostics and runtime operations
→ VGT Build Center unifying Omega Vault, Chronos, and Book Reader
→ Unified Design System across all admin surfaces
→ Frame Policy + Portal Hardening (single-owner SAMEORIGIN / DENY)
→ Recovery Control Plane outside desktop
→ Classic Mode for incompatible screens (Customizer, Page Builders)
→ Sovereign telemetry without third-party tracking (Dattrack)
→ Local AI reasoning via VGTAstra
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
GEDEFENSE v8.0.0 OPEN CORE SECURITY KERNEL (19 Modules)
→ ZEUS: Early pre-boot environment & execution shield
→ AEGIS WAF: 5-layer input normalization, stream inspection & anomaly scoring
  • Admin/editor exemption: SQLi/XSS weights zeroed; RCE/LFI active
  • WAF exception: edit_posts + post.php / edit.php / wp-json REST endpoints
→ CERBERUS: L0/L1 perimeter ban matrix before WordPress core boot
→ PROMETHEUS AI: Heuristic threat correlation & classification
→ TRINITY GRID: Cryptographic integrity verification & defense mesh
→ MORPHEUS RASP: Runtime application self-protection (SQL, code execution, path jail)
→ NEMESIS DECEPTION: Active honeypot decoys & tarpit engines
→ TITAN HARDENING: Strict security headers (XFO, CSP, COOP, COEP, CORP, HSTS) & 0700/0600 file perms
→ HADES: Path obfuscation, login cloaking & iframe continuity enforcement
→ AIRLOCK: Multipart/form-data binary & upload inspection (MIME finfo, SVG vector scan, traversal checks)
→ GHOST TRAP: Tarpit honeypots for bots & automated scanners (instant hard-ban)
→ STYX: Outbound egress shield & data exfiltration prevention
→ CHRONOS: Malware scanning engine & file integrity quarantine store
→ KEY VAULT: Argon2/Bcrypt superkey & cryptographic token vault
→ ORACLE AUDIT: Real-time forensic logging & audit trail
→ MODULE REGISTRY: Sovereign module manifest validation
→ THRONEGUARD MASTER: 14 toxic capabilities stripped from Administrator → Master exclusive
→ LOGINPAGER GATEWAY: Zero-dependency Cyberpunk login customization & live preview
↓
V2 Control Plane
→ WPDeskFramePolicy     ← single-owner X-Frame-Options, admin/embed → SAMEORIGIN, frontend → DENY
→ WPDeskIframePolicy    ← per-app classic vs. iframe routing (Builder/Customizer → classic tab)
→ WPDeskWidgetLayout    ← widget position validation + normalization (full-replace + localStorage)
→ WPDeskDesignSystem    ← shared tokens + components + compat across all admin surfaces
→ Module Registry       ← clean boot of integrated modules (GeDefense, Omega Vault, Chronos, Dattrack, Astra)
→ Pure Test Suite       ← control plane tests without full WP boot (frame / widgets / design / harden)
↓
Modular PHP Kernel
→ desktop.php             ← lightweight bootstrapper / loader only
→ WPDeskSettings          ← DB schema, settings tables; widget_positions as full-replace
→ WPDeskAppBuilder        ← admin portal URLs enforced; front-URLs → new tab
→ WPDeskPlugin            ← central controller: hooks, assets, AJAX dispatch, iframe rules
↓
Per-User Opt-in Check
→ Desktop mode off by default
→ Admin notice in classic backend → explicit per-user activation
→ vgt_bypass=1 cookie → classic view for session
↓
IframeTransformer + CSP Nonce Bridge
→ CSS-Grid injection into native WordPress list tables (Posts, Pages, Comments, Plugins)
→ 100% hook preservation (SEO columns, custom fields intact)
→ Portal Card Layout v2 + filemtime cache-bust
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
→ widget_positions: full-replace + localStorage backup (no delta-merge zombie states)
```

---

<img width="2560" height="1229" alt="Sicherheits-Zentrale Overview" src="https://github.com/user-attachments/assets/90970ebf-8247-46e4-a0b7-d5aacb94998a" />

<img width="2560" height="1229" alt="GeDefense Suite Dashboard" src="https://github.com/user-attachments/assets/e73f52ef-5561-43f3-83da-7f9f61be304f" />

## 🛡️ Security Center & GeDefense v8.0.0 Integration

VGT WP-Desk v2.0.0 features a completely overhauled **Sicherheits-Center** ([`VGTSecurityCenter`](includes/dashboard/class-vgt-security-center.php)) wired directly to the **GeDefense v8.0.0** engine.

| Security Tab | Description |
|---|---|
| **Übersicht & Vitals** | 4-Card HUD: Security Core Status, ThroneGuard Master State, LoginPager Gateway, and Cerberus Ban Count with integrated live 14-point invariant audit. |
| **GeDefense Suite (19 Module)** | Full access to the 19-module GeDefense v8 dashboard (`VIS_Dashboard_View` / `?page=vgt-suite`). |
| **ThroneGuard Master** | Sovereign Master cockpit: Superkey vault, capability stripping, and hardware deactivation lock. |
| **LoginPager Gateway** | Cyberpunk login customization with 2-column live preview cockpit and 5 color presets. |
| **Dattrack Analytics** | Sovereign local analytics and privacy rollups. |
| **Recovery Center** | Autonomous safe-mode controls outside the desktop workspace. |

---

<img width="2560" height="1229" alt="ThroneGuard Cockpit" src="https://github.com/user-attachments/assets/959d7af9-d7dd-4fc8-8807-a0cc7cea5b7d" />

## 👑 ThroneGuard Master Boundary

ThroneGuard enforces a strict cryptographic boundary between ordinary WordPress Administrators and the **Master Sovereign User**:

* **14 Toxic Capabilities Stripped from Administrator:** `edit_plugins`, `activate_plugins`, `delete_plugins`, `install_plugins`, `update_plugins`, `edit_themes`, `install_themes`, `switch_themes`, `delete_themes`, `update_themes`, `edit_users`, `delete_users`, `create_users`, `promote_users`.
* **Hardware Deactivation Lock:** Prevents disabling security plugins from the standard admin panel.
* **Superkey Verification:** Argon2/Bcrypt hashed master password required to unlock the Master Enclave.
* **SHA256 HMAC Session Fingerprinting:** IP and User-Agent cryptographically bound to the active session.

---

<img width="2560" height="1229" alt="LoginPager Gateway" src="https://github.com/user-attachments/assets/2b7e6e50-afa8-4d05-be7f-46b021dc13ae" />

## 🎨 LoginPager Gateway

A modern, zero-dependency login customization engine embedded directly into GeDefense v8.0.0:

* **Cyberpunk Glassmorphism UI:** Frosted glass login card, glowing focus borders, cybernetic button styling.
* **2-Column Live Preview Cockpit:** Interactive settings pane on the left, real-time simulated browser mockup on the right.
* **5 Color Presets:** *Cyber Cyan, Emerald Matrix, Purple Haze, Apex Gold, Crimson Core*, plus custom hex color pickers.
* **Zero External Dependencies:** Built with pure CSS3 and Vanilla JS.

---

## 🧩 VGT Studio & Ops Modules

VGT WP-Desk integrates 5 sovereign tools directly into the Operator OS:

1. **🔐 VGT Omega Vault:** AES-256-GCM encrypted form builder with drag-and-drop field designer and secure Com-Link vault.
2. **📖 VGT Book Reader:** Embedded, zero-dependency PDF and digital book reader engine.
3. **⏳ VGT Chronos:** Dynamic campaign timing, countdown builder, and scheduled page triggers.
4. **📊 VGT Dattrack:** Privacy-focused local analytics engine with zero cloud telemetry.
5. **🧠 VGTAstra AI:** Zero-dependency WordPress AI assistant with Groq reasoning pipelines for Gutenberg and backend assistance.

---

## 🎨 Design System

A unified, token-based design system across all 12 modules and admin surfaces:

```
assets/css/design-system/
├── tokens/        ← brand colors, spacing, radii, shadows
├── base/          ← resets, typography, scrollbars
├── components/    ← buttons, cards, badges, panels, tabs
└── compat/        ← WordPress admin override shims
```

**Coverage — all modules on shared tokens:**
Security Center, GeDefense Suite, ThroneGuard, LoginPager, Recovery, Dattrack, Omega Vault, Book Reader, Chronos Builder, VGTAstra, Desktop Shell, and Portal Iframe.

---

## 🖼️ Portal & Iframe Hardening

* **Frame Policy (`WPDeskFramePolicy`):** Single-owner XFO policy (`SAMEORIGIN` for admin/embed, `DENY` for public frontend). Eliminates header stacking conflicts.
* **Iframe Policy (`WPDeskIframePolicy`):** Automatic classic-tab routing for incompatible tools (Customizer, Page Builders).
* **Portal URL Resolution:** Enforces admin portal URLs; external/front links open in a new tab.
* **Portal Card Layout v2:** Cache-busted via `filemtime`. Badge shown only on list screens (Posts, Pages, Comments, Plugins).
* **Full-Bleed App Windows:** 100vh sidebar, zero admin-bar 32px offset overlap.

---

## 🔒 Recovery Control Plane

Accessible outside the desktop shell via `?vgt_bypass=1` or the Tools menu:

| Feature | Detail |
|---|---|
| **Force Classic Mode** | Bypass desktop workspace for the current session |
| **Desktop Settings UI** | Access and modify settings without desktop loading |
| **Redirect Off** | Disable auto-redirect to prevent redirect loops |
| **Diagnostics** | Inspect system metrics, active bans, and module states |

---

## 🧩 Feature Matrix

### ⚡ 2.1 IframeTransformer — Hook-Preserving Tile Engine
* **Method:** CSS-Grid injection into native WordPress admin list table DOM (`tbody`).
* **Hook Preservation:** 100% — SEO columns, custom fields, and third-party action hooks remain fully intact.
* **Transformed Views:** Posts, Pages, Comments, Plugins.
* **CSP Nonce Bridge:** ThroneGuard nonces automatically propagated to all injected assets.

---

### 🖱️ 2.2 Multi-Window Workspace
* **8-Edge Resizing:** Eight invisible edge/corner zones on every window.
* **Drag Threshold (4px):** Micro-jitter filtered — double-click isolation preserved.
* **Double-Click Maximize:** Header double-click toggles maximize; drag-to-restore on maximized window.
* **Aero Snap:** Drag to top = maximize preview; drag to left/right edge = half-screen snap.
* **Window Bounds Guard:** Drag capped at `top: 0` — windows cannot slide under top bar.
* **RAM Hibernation:** Minimized windows suspend iframe to `about:blank` (rehydrated on restore).

---

### 📁 2.3 Desktop Folder Mode
* **Creating Folders:** Right-click desktop → **📁 New Folder**.
* **Drag-and-Drop Grouping:** Drag app icons onto folders with bounding-box collision detection.
* **Dedicated Folder Windows:** Launch grouped apps directly or restore icons to the desktop with the `×` button.
* **Database Persistence:** Full folder hierarchies stored in `{prefix}vgt_desk_settings`.

<img width="2560" height="1229" alt="Folder Mode" src="https://github.com/user-attachments/assets/9311ba87-c628-4436-8167-bfb5ca8f9753" />

---

### 🖼️ 2.4 Multi-Layout Workspace
Switchable at runtime via Command Center or Spotlight CLI:

| Layout | Style | Dock Position | Maximize Behavior |
|---|---|---|---|
| **macOS Cupertino** | Menu bar top, floating centered dock | Bottom | Full workspace bounds |
| **Windows Redmond** | Full-width bottom taskbar, Windows 11 dock | Bottom bar | `height: calc(100% - 48px)` |
| **Linux Tux** | Vertical sidebar dock | Left side | `left: 80px; width: calc(100% - 80px)` |

**Spotlight CLI:** Press `Alt+Space` → type `/layout [macos|windows|linux]` for instant layout switching.

---

### ⚙️ 2.5 Command Center
* **Real-Time Diagnostics:** Live CPU load, RAM usage, active GeDefense state, database table footprint.
* **IP Ban Manager:** Directly inspect and manage Cerberus L0/L1 perimeter bans.
* **Superkey Management:** Update ThroneGuard master credentials.
* **Personalization:** UI scaling slider (10px–24px), wallpaper manager (same-origin enforced), accent themes, and custom shortcut mapper.

---

## 📜 Changelog

### v2.0.0 — Sovereign Operator OS & GeDefense v8.0.0 Integration *(Current)*

* **GeDefense v8.0.0 Sovereign Security:** Replaced legacy Sentinel CE with the complete 19-module GeDefense Open Core Engine.
* **Native ThroneGuard Master:** Integrated capability stripping (14 admin capabilities restricted to Master role), Superkey vault, and hardware lock.
* **Native LoginPager Gateway:** Integrated Cyberpunk glassmorphism login customization with 2-column live preview cockpit.
* **Full V2 Control Plane:**
  * `WPDeskFramePolicy` — Single-owner XFO (`SAMEORIGIN` / `DENY`), `.htaccess` scrub.
  * `WPDeskIframePolicy` — Automatic classic routing for Customizer and Page Builders.
  * `WPDeskWidgetLayout` — Full-replace widget coordinate persistence with `localStorage` backup.
  * `WPDeskDesignSystem` — Shared design tokens and components across 12 modules.
* **Unified Security Center:** Rewired all tabs (Overview HUD, GeDefense Suite, ThroneGuard, LoginPager, Audit, Dattrack, Recovery) directly to GeDefense v8 controllers.
* **Legacy Purge:** Deleted obsolete Sentinel CE files, old scanner, old views, and standalone ThroneGuard/LoginPager files.
* **Automated Regression Suite:** 100% test pass on VGT Desk test suite and GeDefense regression suites.

---

### v2.0.0-beta.1 — Control Plane & Design System *(archived)*
* Introduced V2 Control Plane (`WPDeskFramePolicy`, `WPDeskIframePolicy`, `WPDeskWidgetLayout`, `WPDeskDesignSystem`).
* Unified Design System across 12 modules.
* Portal Card Layout v2 with `filemtime` cache-busting.
* Recovery Control Plane outside the desktop shell.

---

## ⚙️ Technical Specifications

| Metric | Specification |
|---|---|
| **Required WordPress** | 6.0+ (Tested up to 6.9) |
| **Required PHP** | 8.1+ (Strict Types enforced) |
| **Frontend Frameworks** | None — 100% Vanilla JS (ES6+) / CSS Custom Variables |
| **Compilation Overhead** | Zero — no Node.js, no Vite, no TypeScript at runtime |
| **Security Engine** | GeDefense v8.0.0 (19 Defense Modules) |
| **Master Protection** | ThroneGuard Master Enclave (14 Toxic Capabilities Stripped) |
| **Login Surface** | LoginPager Cyberpunk Live Preview Gateway |
| **Studio Engines** | Omega Vault, Book Reader, Chronos, Dattrack, VGTAstra |
| **Frame Policy** | `WPDeskFramePolicy` — single-owner XFO, no stacking |
| **Bypass Mechanism** | `?vgt_bypass=1` — classic view for session |
| **Default Mode** | Off — explicit per-user opt-in required |

---

## 🚀 Installation

```bash
# 1. Clone into WordPress plugins directory
cd /var/www/html/wp-content/plugins/
git clone https://github.com/visiongaiatechnology/vgtdesk.git desktop

# 2. Activate in WordPress Admin
# Plugins → VGT WP-Desk → Activate

# 3. Opt-in to Desktop Mode
# Admin notice → click to activate for your user
# Or: WP-Desk → Settings → Desktop as Default View

# 4. Initialize ThroneGuard Master (Critical — do this first)
# Security Center → ThroneGuard Master → set Superkey (12+ chars) + configure role hardening

# 5. Verify GeDefense v8.0.0 Core
# Security Center → Overview & Vitals → verify all 19 defense modules active

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
| 🛡️ **[GeDefense WP](https://github.com/visiongaiatechnology/gedefensewp)** | **Security Core** | Sovereign 19-module security kernel (WAF, RASP, Deception, ThroneGuard) — integrated |
| 🏰 **VGT ThroneGuard** | **Hardening** | Toxic capability stripping + Superkey vault — integrated via GeDefense |
| 🎨 **VGT LoginPager** | **Auth Surface** | Zero-dependency Cyberpunk login customizer — integrated via GeDefense |
| 📊 **[VGT Dattrack](https://github.com/visiongaiatechnology/dattrack)** | **Analytics** | Sovereign analytics engine — your data, your server, no third parties |
| 🔐 **[VGT Omega Vault](https://github.com/visiongaiatechnology/vgt-omega-vault)** | **Encrypted Forms** | AES-256-GCM form vault with drag-and-drop builder — integrated |
| 🧠 **[VGT AETHEL / Astra](https://github.com/visiongaiatechnology/vgt-aethel)** | **Sovereign AI** | Local AI reasoning pipelines for WordPress — integrated |

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

VisionGaia Technology builds sovereign, high-performance infrastructure — engineered to the **DIAMANT VGT SUPREME** standard.

---

*Version 2.0.0 — VGT WP-Desk // Operator OS // GeDefense v8.0.0 // ThroneGuard Master // LoginPager Gateway // V2 Control Plane // Unified Design System // Frame & Portal Hardening // Recovery Control Plane // Classic Mode // 9-Engine Desktop // Multi-Layout (macOS/Windows/Linux) // Command Center // VGT Build Center // Omega Vault // Chronos // Dattrack // VGTAstra // Zero-Overheat Architecture // AGPLv3*
