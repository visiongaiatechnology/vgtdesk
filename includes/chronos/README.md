# ⏱️ VGT Chronos Engine — High-Performance WordPress Countdown System

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.1.0-brightgreen?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-PLATIN-gold?style=for-the-badge)](#)
[![Target](https://img.shields.io/badge/Target-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php)](#)
[![Frontend](https://img.shields.io/badge/Frontend-Zero_Dependency-brightgreen?style=for-the-badge)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

> *AGPLv3 — For Humans, not for SaaS Corporations.*

---

## ⚠️ EXPERIMENTAL R&D PROJECT

This project is a **Proof of Concept (PoC)** and part of ongoing research and development at VisionGaia Technology. It is **not** a certified or production-ready product.

**Use at your own risk.** The software may contain security vulnerabilities, bugs, or unexpected behavior. It may break your environment if misconfigured or used improperly.

**Do not deploy in critical production environments** unless you have thoroughly audited the code and understand the implications. For enterprise-grade, verified protection, we recommend established and officially certified solutions.

Found a vulnerability or have an improvement? **Open an issue or contact us.**

---

## 🔍 What is VGT Chronos?

The VGT Chronos Engine is an uncompromising countdown system for WordPress, engineered for extreme performance and security. Moving away from the overhead of traditional page-builder modules, Chronos is built on a native Vanilla JS physics engine (60FPS Render Loop) and isolated backend instances.

No jQuery. No React. No external dependencies. Just hardware-accelerated, server-synchronized countdown precision.

<img width="1724" height="878" alt="VGT Chronos Dashboard" src="https://github.com/user-attachments/assets/13645832-db1f-4d51-a690-14ac80141706" />

---

## 📋 System Specifications

| Specification | Parameter |
|---|---|
| **Minimum PHP Version** | 8.1 (Enforces `strict_types=1`) |
| **Dependencies (Backend)** | WordPress Core Functions only |
| **Dependencies (Frontend)** | Zero-Dependency (No jQuery, No React/Vue) |
| **Architectural Pattern** | Singleton Bootstrapper, Namespaced Isolation (`VGT\Chronos`) |
| **Database Engine** | Custom Table via `dbDelta` (`wp_vgt_countdowns`) |
| **Version** | 1.1.0 |
| **License** | AGPLv3 |

---

## ⚡ Core Architecture & Performance

### 2.1 Rendering & Physics Engine

**60FPS Render Loop:** Client-side animations exclusively utilize `requestAnimationFrame` for hardware acceleration. Complete avoidance of I/O-blocking `setInterval` routines for the tick cycle — every frame is GPU-scheduled.

**Absolute Time Synchronization:** Eliminates JavaScript date-parsing errors entirely. Target time is pre-calculated on the PHP server in absolute Unix epoch milliseconds, eliminating cross-timezone desynchronization regardless of the visitor's locale or browser.

**Zero-Runtime Overhead:** Styles are processed as dynamic inline CSS via `wp_add_inline_style`. No blocking network requests for external stylesheets in the frontend — zero additional HTTP round-trips on page load.

**Evergreen Persistence:** Local state persistence for persistent evergreen timers via `localStorage` (`vgt_chronos_{id}_start`). Timers survive page reloads and browser restarts without server round-trips.

### 2.2 Security Kernel (Defense-in-Depth)

**Strict Whitelisting:** All UI parameters (Themes, Actions, Types) are validated server-side against constant arrays (`ALLOWED_THEMES`, `ALLOWED_ANIMATIONS`, etc.) using strict `in_array(..., ..., true)`. No user-supplied string reaches execution without whitelist verification.

**Input Sanitization:** Absolute data cleaning prior to DB insertion using WordPress core sanitation functions — `sanitize_text_field`, `absint`, `sanitize_hex_color`, `esc_url_raw`. Every data point is sanitized according to its expected type.

**Query Hardening:** Uncompromising use of Prepared Statements via `$wpdb->prepare` with typed formatting arrays (`%s`, `%d`). SQL injection is structurally impossible.

**Mutation Protection:** Every write and delete operation is secured via `current_user_can('manage_options')` capability check and cryptographic nonces (`wp_verify_nonce`). Unauthenticated or unauthorized state changes are rejected at the kernel level.

---

## 🗄️ Database Schema

**Table:** `{prefix}vgt_countdowns`

| Column | Type | Attributes | Function |
|---|---|---|---|
| `id` | `bigint(20) unsigned` | NOT NULL, AUTO_INCREMENT, PK | Unique System Identifier |
| `title` | `varchar(255)` | NOT NULL | Internal System Designation |
| `type` | `varchar(50)` | DEFAULT `'fixed'` | Logic: `fixed` or `evergreen` |
| `end_datetime` | `varchar(50)` | NULL | ISO 8601 Termination Timestamp |
| `duration_seconds` | `int(11) unsigned` | NULL | Runtime in seconds for Evergreen timers |
| `action_on_expire` | `varchar(50)` | DEFAULT `'hide'` | Post-Expiration Action (`hide`, `redirect`) |
| `redirect_url` | `varchar(2048)` | NULL | Target URL for Redirect Action |
| `design_settings` | `json` | NOT NULL | Scalable storage for UI payload |
| `created_at` | `datetime` | DEFAULT `CURRENT_TIMESTAMP` | Creation Timestamp |

The table is created via WordPress `dbDelta` on plugin activation — no manual SQL setup required. The `{prefix}` is dynamically resolved from the active WordPress installation.

---

## 🎨 UI/UX Configuration Matrix

### Visual Engines (Themes)

| Theme Key | Description |
|---|---|
| `blocks` | Solid Blocks — Default |
| `cyber` | Cyber Neon |
| `minimal` | Minimal Line |
| `matrix` | Digital Matrix |
| `glass` | Glassmorphism |
| `neon-pulse` | Neon Pulse |

### Tick Animations (GPU-Accelerated)

| Key | Effect | Technique |
|---|---|---|
| `none` | Zero Motion | Static render, no animation overhead |
| `pulse` | Cyber Pulse | CSS Scale + Glow keyframe |
| `flip` | 3D Mechanical Flip | Split-Flap System via CSS 3D transform |
| `slide` | Digital Slide | Vertical Translation transition |
| `glitch` | System Glitch | RGB-Split translation effect |

All animations are GPU-accelerated via CSS `transform` and `will-change` — no layout thrashing, no JavaScript animation loops.

### Post-Expiration Protocol

**`hide` (Purge):** Injects `.vgt-timer-hidden` class — enforces `display: none !important` via CSS. The countdown element is removed from the visual layout without a page reload.

**`redirect` (Force):** Executes `window.location.replace()` to overwrite the browser history stack. This prevents users from navigating "back" into the expired countdown state via the browser back button.

### Dashboard Engine

**Live Preview Canvas:** Reactive Vanilla JS DOM-binding synchronizes all inputs — colors, animations, languages — instantly in real-time without page reloads. What you configure is what you see, immediately.

**Tick Simulation:** An isolated tick interval in the admin area visualizes all animations independently of real time. Test the flip, glitch, or pulse animations without waiting for an actual countdown.

---

## 🚀 Installation

```bash
# 1. Clone or download the repository
git clone https://github.com/visiongaiatechnology/vgt-chronos.git

# 2. Place in WordPress plugins directory
cp -r vgt-chronos /var/www/html/wp-content/plugins/

# 3. Activate via WordPress Admin → Plugins
```

Or install directly via the WordPress admin panel by uploading the ZIP file.

**Requirements:**
- WordPress 6.x+
- PHP 8.1+

On activation, the plugin automatically creates the `{prefix}vgt_countdowns` table via `dbDelta`.

---

## 💎 Support the Project

[![Donate via PayPal](https://img.shields.io/badge/Donate-PayPal-00457C?style=for-the-badge&logo=paypal)](https://www.paypal.com/paypalme/dergoldenelotus)

| Method | Address |
|---|---|
| **PayPal** | [paypal.me/dergoldenelotus](https://www.paypal.com/paypalme/dergoldenelotus) |
| **Bitcoin** | `bc1q3ue5gq822tddmkdrek79adlkm36fatat3lz0dm` |
| **ETH** | `0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85` |
| **USDT (ERC-20)** | `0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85` |

---

## 🔗 VGT Ecosystem

| Tool | Type | Purpose |
|---|---|---|
| ⚔️ **[VGT Sentinel](https://github.com/visiongaiatechnology/sentinelcom)** | **WAF / IDS Framework** | Zero-Trust WordPress security suite |
| 🛡️ **[VGT Myrmidon](https://github.com/visiongaiatechnology/vgtmyrmidon)** | **ZTNA** | Zero Trust device registry and cryptographic integrity verification |
| ⚡ **[VGT Auto-Punisher](https://github.com/visiongaiatechnology/vgt-auto-punisher)** | **IDS** | L4+L7 Hybrid IDS — attackers terminated before they even knock |
| 📊 **[VGT Dattrack](https://github.com/visiongaiatechnology/dattrack)** | **Analytics** | Sovereign analytics engine — your data, your server, no third parties |
| 🌐 **[VGT Global Threat Sync](https://github.com/visiongaiatechnology/vgt-global-threat-sync)** | **Preventive** | Daily threat feed — block known attackers before they arrive |
| 🔥 **[VGT Windows Firewall Burner](https://github.com/visiongaiatechnology/vgt-windows-burner)** | **Windows** | 280,000+ APT IPs in native Windows Firewall |

---

## 🤝 Contributing

Pull requests are welcome. For major changes, open an issue first.

Licensed under **AGPLv3** — *"For Humans, not for SaaS Corporations."*

---

## 🏢 Built by VisionGaia Technology

[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

VisionGaia Technology is an R&D collective exploring experimental architectures, AI integration, and cybersecurity paradigms. We build to learn, we break things to understand them, and we share the results.

---

*VGT Chronos Engine v1.1.0 — High-Performance WordPress Countdown System // 60FPS Physics Engine // Zero-Dependency Frontend // Defense-in-Depth Security Kernel*
