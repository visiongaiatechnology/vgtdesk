# 📖 VGT Book Reader Engine — Secure PDF Delivery for WordPress

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.1_BETA-orange?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-BETA-orange?style=for-the-badge)](#)
[![Target](https://img.shields.io/badge/Target-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php)](#)
[![Frontend](https://img.shields.io/badge/Frontend-Zero_Dependency-brightgreen?style=for-the-badge)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

> *AGPLv3 — For Humans, not for SaaS Corporations.*

---

## ⚠️ DISCLAIMER: EXPERIMENTAL R&D PROJECT

This project is a **Proof of Concept (PoC)** and part of ongoing research and development at VisionGaia Technology. It is **not** a certified or production-ready product.

**Use at your own risk.** The software may contain security vulnerabilities, bugs, or unexpected behavior. It may break your environment if misconfigured or used improperly.

**Do not deploy in critical production environments** unless you have thoroughly audited the code and understand the implications. For enterprise-grade, verified protection, we recommend established and officially certified solutions.

Found a vulnerability or have an improvement? **Open an issue or contact us.**

---

## 🔍 What is VGT Book Reader?

The VGT Book Reader Engine is a high-performance, asynchronous WordPress plugin for the secure and aesthetically precise delivery of PDF documents. It fully decouples itself from the native, inefficient WordPress UI and establishes its own isolated backend dashboard alongside an overlay-based frontend rendering engine.

<img width="1733" height="901" alt="{BCA5827F-A13F-4F3C-A049-4C0624F7361D}" src="https://github.com/user-attachments/assets/6f3d9f91-b273-4d02-8062-60ce5917530d" />


**Core Directives:**
- **Zero-Trust DOM:** Absolute control over all render processes — no `innerHTML`, no unvalidated injection.
- **Hermetic Data Layer:** Custom Post Type (`vgt_book`) hidden from global WordPress queries and REST API exposure.
- **State-of-the-Art UX:** Dark-mode interfaces with hardware-accelerated animations and glassmorphism design language.

---

## 📋 System Specifications

| Specification | Parameter |
|---|---|
| **Version** | 1.0.1 BETA |
| **Namespace** | `VGT\BookReader` |
| **Minimum PHP Version** | 8.1+ (`strict_types=1` enforced) |
| **Dependencies (Backend)** | WordPress Core Functions only |
| **Dependencies (Frontend)** | Zero-Dependency (Vanilla JS ESNext, CSS3) |
| **Architecture** | PHP 8.1+, Vanilla JS (ESNext), CSS3 (Hardware Accelerated) |
| **License** | AGPLv3 |

---

<img width="1735" height="905" alt="{4CE8527B-24AD-46F9-A650-376FA4298C7A}" src="https://github.com/user-attachments/assets/e78ac4fa-5c89-4010-9c9d-c11597844ef4" />


## 🏛️ Architecture Topology

| Component | Technology | Function | Complexity |
|---|---|---|---|
| **Bootstrapper** | PHP (Singleton) | Prevents memory leaks via multi-instantiation. Secures the boot process. | O(1) |
| **Data Layer (CPT)** | PHP (WP Core) | Native WP database abstraction. Full deactivation of native UI and REST API for maximum isolation. | O(1) |
| **API Controller** | PHP (AJAX) | Type-safe endpoints for all CRUD operations. | O(1) per request |
| **Dashboard UI** | Vanilla JS / CSS Grid | Fluid-layout backend with asynchronous state management. Eliminates page reloads entirely. | O(n) DOM iteration |
| **Frontend Engine** | Vanilla JS / CSS3 | Chromium-compatible PDF rendering, event delegation, scroll-locking. | O(1) event bindings |

---

## 🔴 Threat Modeling & Security Vectors (Red Team Audit)

The system has been hardened against the OWASP Top 10 and extended attack vectors. All vectors are eliminated through systemic barriers.

### 3.1 Cross-Site Scripting (XSS)

**Attack Vector:** Injection of malicious JavaScript via book titles or manipulated URLs.

**VGT Mitigation:**
- **Backend:** Strict sanitization via `sanitize_text_field()` and `esc_url_raw()`
- **Frontend (PHP):** Output escaping via `esc_html()`, `esc_attr()`, `esc_url()`
- **Frontend (JS):** Zero-Trust DOM injection — complete rejection of `innerHTML`. DOM construction exclusively via `document.createElement()` with value assignment via `textContent`. Injection is structurally impossible.

---

### 3.2 Cross-Site Request Forgery (CSRF) & Blind IDOR

**Attack Vector:** Unauthorized modification or deletion of records via manipulated requests or guessed IDs.

**VGT Mitigation:**
- Every request requires a cryptographic nonce (`wp_create_nonce`)
- Strict capability verification (`current_user_can('manage_options')`)
- **Hermetic Entity Check:** Before every update or delete, the system mandatorily verifies that the targeted record both exists AND is of type `vgt_book` (`get_post_type($post_id) === 'vgt_book'`). Cross-entity overrides are mathematically impossible.

---

### 3.3 Type Juggling & Array Injection (PHP 8 Fatal Errors)

**Attack Vector:** Submission of arrays in POST requests where strings are expected — used to provoke runtime errors or bypass type checks.

**VGT Mitigation:**
- Consistent use of `declare(strict_types=1)` across all PHP files
- Isolated abstraction layer: `get_post_string()` — validates input for existence AND for the primitive type `string` before it leaves the request scope. Arrays never reach the execution context.

---

### 3.4 Malicious Payload Rendering (SSRF / Iframe Hijacking)

**Attack Vector:** Injection of a malicious URL (e.g. phishing page) in place of a PDF, executed inside the user's iframe context.

**VGT Mitigation (Defense-in-Depth):**

- **Check 1:** `wp_http_validate_url()` verifies the general URL schema
- **Check 2 (Target Enforcement):** Strict extension enforcement — the URL path is analyzed via `parse_url` and `pathinfo`. Only the `.pdf` file extension is accepted. No exceptions.

**Architectural Note on Iframe Sandbox:**
A restrictive HTML `sandbox` attribute is intentionally omitted. This is an architectural necessity to allow internal browser PDF engines (such as the Chromium PDF Viewer) to render the payload. Security is fully guaranteed by the backend-side path enforcement (Check 2). Execution of malicious JavaScript via a verified `.pdf` payload is isolated and non-exploitable within the native browser rendering context.

---

<img width="1912" height="939" alt="{072CF8AD-F9DD-401C-8171-7213B38862C8}" src="https://github.com/user-attachments/assets/a422e1de-bdd5-4c7e-9b26-31ade4df6f0c" />


## ⚡ Performance & UI/UX Metrics

**DOM Overhead:** Minimal. The frontend overlay rests in the DOM (`display: none` / `opacity: 0`) and is activated on-demand exclusively via hardware-accelerated CSS transitions (`transform`, `opacity`). Zero reflow on activation.

**Iframe Memory Management:** When the overlay is closed, the `src` attribute of the iframe is cleared (`this.iframe.src = ''`), immediately releasing browser RAM. No memory accumulation across multiple sessions.

**Design Language (VGT State of the Art):**
- CSS Variables (`--vgt-accent`) for O(1) theme-switching
- Glassmorphism (`backdrop-filter: blur()`) with solid fallbacks for non-supporting browsers
- Z-Index Dominance (`999999`) ensures the reader overlay layers above any existing WordPress theme without destroying the stacking context

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
| ⏱️ **[VGT Chronos](https://github.com/visiongaiatechnology/chronos)** | **Countdown** | High-performance WordPress countdown engine — 60FPS, Zero-Dependency |
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

*VGT Book Reader Engine v1.0.1 BETA — Secure PDF Delivery for WordPress // Zero-Trust DOM // Defense-in-Depth Security // Hardware-Accelerated Frontend*
