# 🖥️ VGT WP-Desk — Premium Slim Desktop Layer

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0--Beta-brightgreen?style=for-the-badge)](#)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Overheat_OS--Layer-blue?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Vanilla_JS_%2F_CSS3-orange?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-BETA-yellow?style=for-the-badge)](#)
[![Security](https://img.shields.io/badge/Security-Diamant_VGT_Supreme-red?style=for-the-badge)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

> *"No frameworks. No build pipeline. No compromise on UX."*  
> *AGPLv3 — Open Source Core. Built for operators, not for SaaS dashboards.*

---

## ⚠️ DISCLAIMER: BETA SOFTWARE

This project is currently in **Beta** and part of ongoing development at VisionGaia Technology. It is **not** yet a finalized production release.

**Use at your own risk.** The software may contain bugs or unexpected behavior. Test thoroughly in a staging environment before deploying to live sites.

Found a bug or have an improvement? **Open an issue or contact us.**

---



https://github.com/user-attachments/assets/c7c1bde7-a178-4dff-8d09-a3ace3f77c14



<img width="1916" height="908" alt="VGT WP-Desk Main Desktop" src="https://github.com/user-attachments/assets/b0776d5f-4dcc-4939-bf0f-215de8d87cd7" />

## 🔍 What is VGT WP-Desk?

VGT WP-Desk is a **modular, framework-free WordPress backend enhancement** that transforms the classic WordPress admin interface into a high-performance, OS-style desktop environment.

It is engineered under the **Zero-Overheat Doctrine**: maximum performance, absolute compatibility with WordPress Core and third-party hooks, and a strict refusal to rely on heavy frameworks or build pipelines.

Installed plugins are automatically detected, mapped as standalone desktop apps, and isolated within a **chromeless iframe architecture** — eliminating sidebar fragmentation and replacing it with a consistent, distraction-free multi-window workflow.

<img width="1917" height="908" alt="Multi-Window Workspace" src="https://github.com/user-attachments/assets/1b9b8656-5866-4443-a9a8-ed2c7f7a724b" />

```
Classic WordPress Admin:
→ Fragmented sidebar navigation
→ Context-switching overhead
→ No persistent workspace state
→ Monotone, static interface
VGT WP-Desk OS Layer:
→ IframeTransformer          — 100% hook-preserving CSS-Grid tile engine
→ Multi-Window Workspace     — drag, resize, minimize, focus
→ App Registry               — auto-maps installed plugins as desktop apps
→ Persistent Profiles        — per-user workspace state via wp_usermeta
→ Anti-Phishing Origin Guard — deep-link SSRF prevention
→ CSRF Nonce Hardening       — cryptographic session coupling
→ DOM XSS Engine             — full HTML escaping before DOM injection
→ Glassmorphic UI Layer      — wallpaper engine, accent colors, blur modes

```


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
IframeTransformer (CSS-Grid Injection)
→ Manipulates native WordPress admin list table DOM
→ display: grid !important on native tbody
→ Posts, pages, comments, plugins → responsive tiles
→ 100% of WordPress and third-party hooks preserved
↓
Deep-Link Origin Guard (Anti-Phishing / Anti-SSRF)
→ openDeepLink(rawUrl) intercepts all deep-link targets
→ URL.origin validated against window.location.origin
→ External origins blocked → redirect to welcome window
↓
CSRF Nonce Validation
→ Activation / deactivation bound to wp_verify_nonce()
→ AJAX actions verified via check_ajax_referer()
→ Replay / CSRF attacks neutralized at session level
↓
DOM XSS Protection Layer
→ escapeHTML() neutralizes all special chars before DOM injection
→ cleanUrl() blocks javascript: injections → fallback: about:blank
→ Covers plugin titles, URL parameters, deep-link window generation
↓
Persistent Workspace Sync (wp_usermeta)
→ All state saved asynchronously via Non-Blocking AJAX
→ Per-user profiles — no global option overwrites
→ JSON_FORCE_OBJECT enforcement prevents array/object corruption
```

<img width="1914" height="911" alt="VGT Architecture Layer" src="https://github.com/user-attachments/assets/10f41efb-4b27-4e33-ac6d-dd9d3b127797" />

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
| **Competitor Approach** | Automattic prototype rewrites `edit.php` via `<wpd-table>` — breaks all hooks |

<img width="1914" height="914" alt="Tile Engine Layout" src="https://github.com/user-attachments/assets/3f3df87a-45ef-4666-a879-8831f791a0e2" />

---

### 🔒 2.2 Security Architecture — Defense in Depth

Engineered on the principle of minimal attack surface with multiple independent guardrails.

**A. AJAX Capability Guard (Privilege Escalation Prevention)**

The server-side AJAX persistence endpoint enforces explicit role and capability verification — not just user ID matching. Subscribers and low-privilege users cannot flood the database with oversized metadata payloads.

```php
if (!current_user_can('read')) {
    wp_send_json_error('Unzureichende Berechtigungen.');
}
```

**B. Deep-Link Origin Guard (Anti-Phishing & SSRF)**

All deep-link URLs are instantiated and validated against the current server hostname before any window is opened. External origins are rigidly rejected and redirected to the welcome window.

```javascript
const targetUrl = new URL(rawUrl, window.location.origin);
if (targetUrl.origin !== window.location.origin) {
    console.error("VGT Safety Guard: External Deep Links blockiert.");
    return this.openWindow('welcome');
}
```

**C. CSRF Nonce Hardening**

Activation, deactivation, and all state-changing AJAX actions are bound to cryptographically secure WordPress nonces tightly coupled to the user session. Manipulated image links and CSRF emails cannot silently toggle the desktop shell.

**D. DOM-based XSS Prevention**

All dynamic window generation passes parameters through a hardened escaping engine before DOM injection. `escapeHTML()` neutralizes `&`, `<`, `>`, `"`, `'`. `cleanUrl()` blocks `javascript:` protocol injections with `about:blank` fallback.

**E. CSS Injection Defense (Wallpaper URL)**

Custom wallpaper URLs are sanitized server-side via `esc_url_raw()` and verified client-side by `VGTDeskEngine.cleanUrl()` before assignment to any DOM style attribute.

---

### 🖱️ 2.3 Multi-Window Workspace

A full OS-style window management system operating entirely within the WordPress backend.

| Feature | Detail |
|---|---|
| **8-Edge Window Resizing** | Eight independent invisible interaction zones on every window — scale in all directions |
| **Drag Threshold (8px)** | Micro-jitter filtered — drag engine only activates after intentional 8px movement; click events fire instantly |
| **Collision-Avoiding Grid Snapping** | Icons arrange column-wise; occupied grid cells detected in advance via radius algorithm; auto-eject to next free cell |
| **Smart Dock & Taskbar** | Lower dock shows open/minimized window state via LED indicator dots in system accent color |
| **Task Switcher** | Single dock-icon click minimizes or focuses the corresponding window |
| **Iframe Isolation** | Each app window renders in a chromeless iframe — full WordPress functionality, no navigation bleed |

---

### 🎨 2.4 Glassmorphic UI & Wallpaper Engine
Wallpaper Engine:
→ Local, pre-optimized WebP resources (wallpapers/)
→ Local-first delivery — bundled assets only, no third-party image CDNs
→ Preset collection + custom URL support
Accent Color System:
→ Indigo | Emerald | Cyan | Amber | Rose
→ Dynamically adjusts: badges, buttons, LED indicators, dock accents
Glassmorphism Mode:
→ Blur filter toggle (vgt_desk_blur)
→ Performance fallback for older GPUs via Boolean state

> **Data sovereignty note:** Core UI rendering, wallpapers, scripts and stylesheets are served from your own server. The Beta build currently enqueues Plus Jakarta Sans via Google Fonts CDN for typography consistency — this is the only external request and will be made fully local-only in v1.1 for strict DSGVO compliance.

---

### 💾 2.5 Persistent Workspace Profiles

Per-user workspace state stored asynchronously via Non-Blocking AJAX in `wp_usermeta`. No global options are overwritten. Every administrator, editor, and author maintains a fully independent workspace.

```sql
SELECT meta_key, meta_value
FROM wp_usermeta
WHERE user_id = [CURRENT_USER_ID]
AND meta_key LIKE 'vgt_desk_%';
```

**Synchronized Profile Parameters:**

| Key | Type | Description |
|---|---|---|
| `vgt_desk_wallpaper` | URL | Desktop background (preset or custom) |
| `vgt_desk_accent_color` | CSS token | System accent color (indigo / emerald / cyan / amber / rose) |
| `vgt_desk_blur` | Boolean | Glassmorphism blur filter state |
| `vgt_desk_icon_positions` | JSON Object | X/Y coordinates of all desktop icons |
| `vgt_desk_window_settings` | JSON Object | Width, height, left, top for all open application windows |

**JSON Integrity Protection:**

To prevent empty PHP arrays from being serialized as unusable `[]` values (which crash JavaScript state on load), the storage engine enforces strict object formatting via `JSON_FORCE_OBJECT` on all complex metadata keys.

---

## ⚙️ Technical Specifications

| Metric | Specification |
|---|---|
| **Required WordPress** | 6.0+ |
| **Required PHP** | 8.1+ (Strict Types enforced) |
| **Frontend Frameworks** | None — 100% Vanilla JS (ES6+) / CSS Custom Variables |
| **Compilation Overhead** | Zero — no Node.js, no Vite, no TypeScript at runtime |
| **Database Footprint** | 5 user-scoped meta keys (`wp_usermeta`) |
| **Runtime External Calls** | Zero — uses WordPress-native system font stack |
| **Typography** | System font stack — renders as native UI on macOS, Windows, Linux |
| **Asset Enqueue Priority** | Head-priority (prevents race conditions with iframes) |
| **Bypass Mechanism** | Cookie-based express state for emergency access |
| **Canvas Workspace** | HTML5 / CSS-Grid hybrid |

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

# 4. Customize workspace
# Use the in-app Settings window to change Wallpaper / Accent Color / Blur

# 5. Emergency bypass (if needed)
# Append ?vgt_bypass=1 to any admin URL — restores classic view for the session
```

> **Non-Destructive Design:** VGT WP-Desk does not modify your WordPress core, theme, or any other plugin. Deactivate the plugin at any time to fully restore the classic WordPress admin experience.

---

## 🔧 Developer Guidelines (VGT Core Contract)

- **Design Changes:** All visual modifications must use CSS Custom Properties in `desktop.css` only — ensures global color synchronization across all components.
- **Iframe Navigation:** All internal links and forms within the iframe context must carry the `&vgt_iframe=true` parameter. The JavaScript module `interceptIframeNavigations()` monitors and auto-corrects this on all dynamic clicks and form submissions.
- **State Management:** Never write to global WordPress options — all state belongs in `wp_usermeta` under the `vgt_desk_` prefix.
- **Runtime External Requests:** Forbidden by policy — with the single exception of the typography CDN (slated for removal in v1.1). All other assets must remain local.

---

Inspired by [WP Desktop Mode from Automattic](https://github.com/WordPress/desktop-mode) — but without the dependency stack.

## 🔗 VGT Ecosystem

| Tool | Type | Purpose |
|---|---|---|
| 🖥️ **VGT WP-Desk** | **OS-Layer / UX** | Premium desktop interface for WordPress backend — you are here |
| ⚔️ **[VGT Sentinel](https://github.com/visiongaiatechnology/sentinelcom)** | **WAF / IDS Framework** | Zero-Trust WordPress security suite |
| 🏰 **[Throne Guard](https://github.com/visiongaiatechnology/throne-guard)** | **Capability Hardening** | Strips toxic capabilities from Administrator role |
| 🛡️ **[VGT Myrmidon](https://github.com/visiongaiatechnology/vgtmyrmidon)** | **ZTNA** | Zero Trust device registry and cryptographic integrity verification |
| ⚡ **[VGT Auto-Punisher](https://github.com/visiongaiatechnology/vgt-auto-punisher)** | **IDS** | L4+L7 Hybrid IDS — attackers terminated before they even knock |
| 📊 **[VGT Dattrack](https://github.com/visiongaiatechnology/dattrack)** | **Analytics** | Sovereign analytics engine — your data, your server, no third parties |
| 🌐 **[VGT Global Threat Sync](https://github.com/visiongaiatechnology/vgt-global-threat-sync)** | **Preventive** | Daily threat feed — block known attackers before they arrive |

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

> *"WP-Desk was built because WordPress administrators deserved a workspace that doesn't fragment their attention, doesn't phone home, and doesn't ask them to accept a degraded UX as the price of using the world's most popular CMS."*

---

*Version 1.0.0-Beta — VGT WP-Desk // Premium Slim Desktop Layer // Zero-Overheat Architecture // Hook-Preserving IframeTransformer // Glassmorphic OS UI // Diamant VGT Supreme // AGPLv3*
