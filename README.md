# ⚔️ VGT Sentinel — Community Edition (Silber Status)

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.7.0-brightgreen?style=for-the-badge)](#)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Trust_WAF-red?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Deterministic_DFA-orange?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-STABLE-brightgreen?style=for-the-badge)](#)
[![WP Marketplace](https://img.shields.io/badge/WordPress-Marketplace_Ready-21759B?style=for-the-badge&logo=wordpress)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

> *"No external libraries. No blind trust. No compromise."*
> *AGPLv3 — Open Source Core. Built for humans, not for SaaS margins.*

---

## ⚠️ DISCLAIMER: EXPERIMENTAL R&D PROJECT

This project is a **Proof of Concept (PoC)** and part of ongoing research and development at VisionGaia Technology. It is **not** a certified or production-ready product.

**Use at your own risk.** The software may contain security vulnerabilities, bugs, or unexpected behavior. It may break your environment if misconfigured or used improperly.

**Do not deploy in critical production environments** unless you have thoroughly audited the code and understand the implications. For enterprise-grade, verified protection, we recommend established and officially certified solutions.

Found a vulnerability or have an improvement? **Open an issue or contact us.**

---


## 📋 Changelog — V1.7.0

### 🚀 Major Upgrade: AEGIS WAF Engine Reworked

VGT Sentinel 1.7.0 introduces the largest overhaul of the AEGIS engine since the Community Edition launch. The WAF now uses a multi-stage anomaly-scoring architecture with cryptographic browser verification, dramatically reducing false positives while improving detection of heavily obfuscated attacks.

#### 🧠 Anomaly Scoring Engine

* Replaced immediate regex-based blocking with weighted anomaly scoring.
* Every signature now contributes a configurable threat score.
* Requests are blocked only after exceeding the configured `threshold_block` value.
* Example:

  * `sqli` = 5 points
  * `xss` = 5 points
  * `rce` = 100 points

**Benefit:** Benign content containing technical keywords no longer triggers immediate visitor lockouts.

---

#### 🔐 Cryptographic JavaScript Challenge

* Added browser verification layer for medium-confidence detections.
* Requests exceeding `threshold_challenge` receive a JavaScript challenge page.
* Successful browsers receive a signed trust cookie (24h validity).
* Low and medium severity detections are automatically relaxed for verified browsers.

**Benefit:** Headless bots and scrapers fail automatically while legitimate users experience only a one-time verification step.

---

#### 👨‍💻 Administrator & Editor Relaxation

* Logged-in users with:

  * `edit_posts`
  * `manage_options`

  automatically bypass low-risk signatures.

* Weights reduced to zero for:

  * SQLi heuristics
  * XSS heuristics
  * Recon probes
  * Direct DB references
  * GraphQL reconnaissance

* Critical exploit classes remain enforced:

  * RCE
  * LFI
  * Command Injection

**Benefit:** Content editors can safely work with code snippets, HTML fragments and technical content.

---

#### 🛒 Context-Aware WooCommerce & Gutenberg Protection

WooCommerce:

* Sensitive checkout fields are excluded from inspection:

  * billing_company
  * shipping_company
  * related business fields

Gutenberg:

* Post editing and REST save operations automatically increase challenge/block thresholds.
* Applies to:

  * `/wp-json/wp/v2/posts`
  * `post.php`

**Benefit:** Eliminates false positives during checkout and content publishing.

---

#### 🔬 Enhanced Payload Normalization

Normalization pipeline upgraded:

* Up to 5 recursive URL decode passes
* IIS Unicode `%uXXXX` support
* Improved nested encoding detection

**Benefit:** Detects deeply obfuscated payloads and multi-stage encoding bypass attempts.

---

#### ✂️ Intelligent SQL Comment Reconstruction

New lookaround-based comment processing:

* `sel/**/ect` → `select`
* `select/**/1` → `select 1`

**Benefit:** Closes classic SQL comment evasion techniques without breaking syntax reconstruction.

---

#### ⚡ Atomic Signature Refactoring

Large monolithic patterns were split into dedicated detection classes:

Examples:

* `rce_eval`
* `rce_callbacks`
* `rce_backticks`
* `rce_jndi_env`
* `sqli_union`
* `sqli_select`
* `xss_script_tags`
* `xss_event_handlers`

**Benefits:**

* Faster matching
* Better logging
* Lower ReDoS exposure
* Improved diagnostics

---

#### 🏗️ Two-Stage Detection Pipeline

Detection workflow redesigned:

Stage 1:

* Atomic kill signatures
* Immediate critical exploit detection

Stage 2:

* Heuristic scoring
* AI delegation hooks
* Nexus pattern analysis

**Benefit:** Critical attacks are terminated instantly while reducing CPU usage for normal traffic.

---

#### 🛡️ Hardened Proxy Spoofing Detection

* Added RFC-compliant private and loopback validation.
* Local sockets automatically bypass spoofing checks.
* Improved compatibility with reverse proxies and development environments.

**Benefit:** Eliminates false positives caused by internal infrastructure.

---

#### 🎨 Improved Challenge UX

New browser verification interface includes:

* Responsive layout
* Corporate branding
* Progress animations
* Mathematical CPU challenge

**Benefit:** Visitors receive clear feedback instead of a generic HTTP 403 response.


---

## 🔍 What is VGT Sentinel?

VGT Sentinel Community Edition is a **modular, zero-dependency WordPress security framework** engineered to neutralize deterministic attack vectors without sacrificing performance.

It is the open-source core of the VGT Sentinel suite — a battle-hardened, multi-layered defense system built on a **Zero-Trust architecture**. Every request is inspected, every header hardened, every upload analyzed, every file hashed and signature-matched, and every bot challenged.

<img width="1749" height="906" alt="{5F2676BC-C375-4830-A497-B98D228ED23E}" src="https://github.com/user-attachments/assets/468784d3-4022-4fed-b563-9165f2bc4001" />

```
Traditional WordPress Security:
→ Single plugin = single point of failure
→ Shared hosting overhead
→ No outbound control
→ No filesystem integrity monitoring

VGT Sentinel ZTNA Security Stack:
→ Stream-based WAF (AEGIS)             — SQLi, XSS, RCE, LFI neutralized
→ Kernel Hardening (TITAN)             — Server fingerprint masked
→ Stealth Engine (HADES)               — WordPress architecture obfuscated
→ Access Guard (CERBERUS)              — IP-validated brute-force prevention
→ Outbound Control (STYX LITE)         — Data exfiltration blocked
→ Payload Sanitizer (AIRLOCK)          — Binary upload inspection
→ Integrity + Malware Scanner (CHRONOS) — SHA-256 diff + 40+ signatures
→ Anti-Bot Engine (VGT SHIELD)         — Zero-UI PoW bot defense
```

---

## 🏛️ Architecture

```
Incoming HTTP Request
        ↓
CERBERUS (Pre-Auth IP Validation)
→ Cloudflare CIDR verification
→ X-Forwarded-For spoofing prevention
→ Brute-force state via RAM/Object Cache
→ Hook Priority 1 — fires before WP user logic
        ↓
AEGIS WAF (Stream Inspection)
→ php://input scanned in 4KB binary chunks
→ Overlap-buffer for boundary-spanning patterns
→ 512KB scan limit (Memory Exhaustion prevention)
→ Tarpit: Socket-Drop + Connection: Close on critical hit
→ HARDENED V1.6.0: 4-layer payload normalization
        ↓
TITAN (Kernel Hardening)
→ Security headers injected
→ X-Powered-By camouflage (Laravel / Drupal / Django)
→ XML-RPC blocked, REST API locked to auth sessions
→ .env / wp-config.php / .git access denied at .htaccess level
        ↓
HADES (Stealth Engine)
→ URL rewrites mask WordPress directory structure
→ Custom slugs for wp-admin and wp-login.php
        ↓
VGT SHIELD (Anti-Bot / PoW Engine)
→ SHA-256 cryptographic challenge issued by PHP server
→ Web Worker mines proof-of-work in isolated browser thread
→ X-VGT-Shield-PoW header injected into form submissions
→ Server validates hash in <10ms — replay protection (TTL 1800s)
        ↓
AIRLOCK (Upload Inspection)
→ Magic Byte analysis on 4KB header/footer chunks
→ PHP wrapper, Base64 and exec-pattern detection
→ Polyglot file prevention
        ↓
CHRONOS (Integrity + Malware Scanner)  ← UPGRADED IN V1.6.0
→ SHA-256 against integrity_matrix.php baseline
→ mtime + size pre-filter before hash computation
→ NEW: 40+ malware signatures matched on NEW/MODIFIED files only
→ Ghost Trap honeypot triggers IP blacklisting on access
→ Cron-sliced execution (max 20s) — PHP timeout safe
        ↓
STYX LITE (Outbound Control)
→ Telemetry Kill Switch for api.wordpress.org
→ Supply-chain exfiltration blocked
```

---

## 🧩 Module Matrix

### ⚡ 2.1 AEGIS — Web Application Firewall (Hardened V1.6.0)

<img width="1747" height="908" alt="{71C52BDB-CA2F-4A57-9919-18D402E53F60}" src="https://github.com/user-attachments/assets/ba2da2ab-b835-44d4-899d-9401818d701b" />

Stream-based WAF for real-time payload inspection.

| Parameter | Value |
|---|---|
| **Engine** | Deterministic Regex Pattern Matching (Hardened V1.6.0) |
| **Scan Limit** | 512 KB (Memory Exhaustion prevention) |
| **Read Strategy** | `php://input` binary stream in 4KB chunks with overlap buffer |
| **Protected Vectors** | SQLi, XSS, RCE, LFI, Malicious User Agents |
| **Threat Response** | Immediate socket-drop (`Connection: Close`) before header send |
| **Normalization Layers** | URL, HTML Entity, Unicode Escape, Hex Escape |
| **Failure Mode** | Fail-Closed PCRE — ReDoS attempts trigger immediate block |

**V1.6.0 Hardening Summary:**

The pattern set was extended to close gaps that gave manual attackers an evasion path. The new normalizer decodes HTML entities (`&#x6A;avascript:`), Unicode escapes (`\u0073ystem`), and hex escapes (`\x73ystem`) before pattern matching — closing four previously distinct evasion vectors at the input layer. The XSS pattern now matches any `on*` event handler via wildcard instead of a hardcoded list of five. The SQLi pattern accepts non-whitespace separators between `OR`/`AND` and operands, closing payloads like `1/OR/1=1` that bypassed the previous whitespace requirement.

---

### 🔩 2.2 TITAN — Kernel Hardening

Application-layer hardening and server signature masking.

<img width="1750" height="905" alt="{93DD0E21-02EB-4C5E-BC91-6DE083326321}" src="https://github.com/user-attachments/assets/047a7845-bc9b-4892-90f5-1847e86d1f71" />

```
Headers Enforced:
→ X-XSS-Protection
→ X-Frame-Options: SAMEORIGIN
→ X-Content-Type-Options: nosniff
→ Referrer-Policy
→ Permissions-Policy

Camouflage Engine:
→ X-Powered-By spoofed to: Laravel | Drupal | Django

API Lockdown:
→ XML-RPC:     BLOCKED (full)
→ REST API:    Auth-only sessions
→ RSS/Atom:    DISABLED

Protected Paths (.htaccess):
→ .env  |  .git  |  wp-config.php  |  composer.json  |  Vault directories
```

---

### 👻 2.3 HADES — Stealth Engine

Architecture obfuscation to prevent automated WordPress fingerprinting.

<img width="1748" height="910" alt="{612F5CF2-053A-4A04-8153-F23CBC83E0D8}" src="https://github.com/user-attachments/assets/9fcec577-5213-4734-9933-c53ad008de8a" />

**URL Rewrite Map:**

| Original Path | Masked Path |
|---|---|
| `wp-content/themes` | `content/ui` |
| `wp-content/plugins` | `content/lib` |
| `wp-content/uploads` | `storage` |
| `wp-includes` | `core` |
| `wp-admin` | *(Custom Slug)* |
| `wp-login.php` | *(Custom Slug)* |

**Webserver Support:** Apache (auto via `.htaccess`) · Nginx (static rule injection) · LiteSpeed

---

### 🐕 2.4 CERBERUS — Access Guard

Pre-authentication IP validation and brute-force defense.

<img width="1753" height="909" alt="{87791C5E-509B-49DB-9AF6-63A6148C5214}" src="https://github.com/user-attachments/assets/3c0e0556-51d0-4ad5-bea2-0d0c85d6fb14" />

| Feature | Detail |
|---|---|
| **True-IP Detection** | Native Cloudflare CIDR validation — prevents X-Forwarded-For spoofing |
| **Fail-State Tracking** | RAM/Object Cache via WordPress Transients |
| **Hook Priority** | `1` on `authenticate` — fires before any WP user logic loads |

---

### 🌑 2.5 STYX LITE — Outbound Control

Network-layer control against data exfiltration and supply-chain attacks.

<img width="1751" height="908" alt="{03D0FA24-4E7B-47B9-8CD9-5A38C9D9F66F}" src="https://github.com/user-attachments/assets/22acc9aa-beef-4895-91ed-a12d32fed1da" />

```
Telemetry Kill Switch — Blocked Domains:
→ api.wordpress.org
→ downloads.wordpress.org
→ s.w.org

Supply-Chain Protection:
→ Blocks unintended external communication from compromised plugins
```

---

### 🔒 2.6 AIRLOCK — Payload Sanitizer

Binary-level analysis of all file uploads (`multipart/form-data`).

<img width="1750" height="912" alt="{F202F832-6642-4595-8F6B-DD5EA5F54B4D}" src="https://github.com/user-attachments/assets/96b4cacf-726d-45fd-9027-5aed572369e3" />

| Feature | Detail |
|---|---|
| **File Policy** | Strict allowlist — only pre-approved safe formats |
| **Large File Strategy** | Memory-safe chunked read — 4KB header/footer scan for files >2MB |
| **Magic Byte Inspection** | Detects real file type regardless of extension |
| **Polyglot Prevention** | Blocks PHP wrappers, Base64 obfuscation, exec-patterns in image/document payloads |

---

### 🕰️ 2.7 CHRONOS — Integrity + Malware Scanner *(Upgraded V1.6.0)*

Asynchronous filesystem integrity monitoring with embedded malware signature engine and honeypot tripwire.

```
Two-Stage Scan Architecture:

Stage 1: Integrity Pre-Filter (existing)
→ mtime + size compared against integrity_matrix.php baseline
→ Unchanged files reuse cached SHA-256 hash — zero re-hashing
→ Changed files proceed to Stage 2

Stage 2: Signature Scan (NEW V1.6.0)
→ 40+ embedded malware signatures matched on file content
→ 5MB hard size limit per file (DoS prevention)
→ PCRE fail-closed — ReDoS-resistant matching
→ Runs ONLY on NEW or MODIFIED files
→ Zero overhead on clean systems

Ghost Trap (existing):
→ Honeypot file: wp-admin-backup-restore.php
→ HTTP access = immediate IP blacklisting

Execution Safety:
→ Async State Machine — max 20s Cron-Slice
→ No PHP timeout risk on large installations
```

**Embedded Malware Signature Categories (40+ patterns):**

| Category | Coverage |
|---|---|
| **WordPress malware families** | wp_vcd injector + markers, pharma hack, WP filemanager backdoor |
| **Generic obfuscation** | eval+base64, eval+gzinflate, eval+str_rot13, pack hex, chr concat, goto-obfuscation |
| **Known webshells** | C99, R57, PHPSpy, WeBaCoo, b374k, WSO, FilesMan, ALFA, Marijuana Shell |
| **Polyglot files** | GIF/JPG/PNG headers + embedded PHP code |
| **Direct shell access** | shell_exec/passthru/system with `$_GET`/`$_POST`/`$_REQUEST` arguments |
| **Remote file inclusion** | `https://`, `data://`, `php://input`, `php://filter` includes |
| **Backdoor patterns** | `create_function` with user input, `assert($_POST)`, `$auth_pass` MD5 markers |
| **Cryptominers** | Coinhive, Cryptonight, Monero/XMR, xmrig, cpuminer, minerd |
| **Header manipulation** | Base64-decoded redirects, `wp_set_current_user` with user input |

**Performance characteristics:**

On a clean WordPress installation, Stage 2 is never invoked — every file matches its baseline mtime/size and reuses the cached hash. The signature database lives in PHP OPcache as a class constant after first load, with zero file I/O cost. When a file changes legitimately (plugin update, theme modification), it gets one signature scan and one hash computation. When malware is introduced, it gets flagged with a `MALWARE` change type which escalates the report status to `critical`.

---

### 🤖 2.8 VGT SHIELD — Anti-Bot / Proof-of-Work Engine

A high-performance, DSGVO-compliant reCAPTCHA alternative for WordPress. Eliminates bot interactions through a server-validated Proof-of-Work engine that operates entirely without user interaction and without external data transfers (Zero-Cloud).

No checkbox. No "I'm not a robot". No Google requests. No cookies. Instead: invisible, mathematical bot defense directly in the browser.

| Feature | Description |
|---|---|
| **Zero-UI Bot Defense** | End users see no captchas or checkboxes — security operates invisibly in the background |
| **SHA-256 PoW Engine** | Cryptographic challenge-response via bitwise hashing, isolated in a Web Worker |
| **100% DSGVO-compliant** | No third-party requests, no cookies, no tracking |
| **Zero-Cloud** | Fully server-side — no external APIs, no CDNs |
| **Replay Protection** | Every hash is valid exactly once — TTL: 1800 seconds |
| **Deep Plugin Scanner** | AST-regex parsing detects installed form plugins and integrates them automatically |
| **Network Layer Hijacking** | Automatic interception of network requests for PoW header injection |
| **<10ms Server Validation** | Minimal latency on server-side hash verification |
| **Dark/Light Mode** | Neural Aesthetics admin dashboard with full theme support |

**Native integrations:** WooCommerce · Contact Form 7 · WPForms · Gravity Forms · WordPress Core Comments

---

## 🔴 Red Team Validation — Community Testing Scripts

The repository includes **3 Python-based red team test scripts** for independent validation of Sentinel CE.

> ⚠️ **Only use against your own servers.** Running these scripts against third-party systems without explicit authorization is illegal.

| Script | Module Tested | Technique |
|---|---|---|
| `Redteamtest1.py` | AEGIS WAF | The script tests whether the AEGIS WAF detects and blocks attack payloads for SQLi, XSS, LFI, and RCE, or whether they can be bypassed |
| `Redteamtest2.py` | AEGIS WAF | The script tests whether the AEGIS WAF detects and blocks JSON‑obfuscated attack payloads (XSS, SQLi, LFI, and malformed JSON evasions), or whether they can be bypassed |
| `redteamtest3.py` | AEGIS WAF | The script tests whether the AEGIS WAF detects and blocks polymorphically mutated attack payloads (SQLi, XSS, LFI, RCE) across multiple HTTP methods (GET, POST forms, JSON, multipart, header injection) – including WordPress‑specific vectors such as polyglot uploads, REST API evasions, and Shellshock/JNDI probes – or whether they can be bypassed |
| `redteam4.py` | AEGIS WAF | The script tests whether the AEGIS WAF detects and blocks deeply obfuscated, polymorphic attack payloads (advanced SQLi, XSS, LFI, RCE) using techniques such as double URL encoding, comment‑based slicing, HTML entity scrambling, JSON Unicode obfuscation, HTTP header smuggling, and parameter pollution – or whether they can be bypassed |

---

## ⚙️ Performance Design

> **Zero performance tax. Maximum coverage.**

| Optimization | Mechanism |
|---|---|
| **Fast-Path Routing** | Static assets bypass WAF inspection entirely — saves >90% CPU cycles |
| **Stream Chunking** | Payload inspection via chunked reads — low, stable RAM footprint |
| **Two-Stage Integrity Scan** | Signature scanning skipped entirely on clean files |
| **OPcache-Resident Signatures** | Malware database loaded once, lives in OPcache — zero file I/O at runtime |
| **Async Scheduling** | CHRONOS runs in time-sliced cron — never blocks request handling |
| **Web Worker Isolation** | VGT SHIELD PoW mining runs in isolated thread — zero UI blocking |
| **Zero Dependencies** | No external libraries — no supply chain risk, no overhead |

---

## 🔌 Ecosystem Compatibility

| Component | Detail |
|---|---|
| **PHP** | 7.4+ (Recommended: 8.1+) |
| **Webserver** | Apache (auto), Nginx (manual rule injection), LiteSpeed |
| **Page Builders** | Bridge Manager auto-disables conflicting DOM/header interventions for Elementor, Divi, Oxygen |
| **VGT Ecosystem** | Native VisionLegalPro support via Shadow-Net Asset Routing |
| **VGT Myrmidon** | AEGIS Co-op Mode — whitelists Myrmidon ZTNA API endpoints automatically |
| **WordPress Marketplace** | Fully compliant with WordPress plugin guidelines |

---

## ⚠️ System Boundaries — Silber vs. Platin

> **DISCLAIMER:** The Community Edition (Silber Status) operates on a deterministic rule engine. It provides a robust shield against standardized, automated botnets, scrapers, and known attack vectors.

The following capabilities are **exclusive to VGT Sentinel Pro / Platin Status:**

| Capability | Silber | Platin |
|---|---|---|
| **ORACLE AI** — Polymorphic Zero-Day Detection | ❌ | ✅ |
| **PROMETHEUS** — Dynamic Behavioral Profiling | ❌ | ✅ |
| **NEMESIS** — Deception-Engine | ❌ | ✅ |
| **ZEUS** — Pre-Boot WAF via `auto_prepend_file` | ❌ | ✅ |
| **MORPHEUS** — Hypervisor for Plugins | ❌ | ✅ |
| **GORGON** — Global Swarm Intelligence Threat Feed | ❌ | ✅ |
| **NEXUS Live Signature Updates** — Hot-loadable threat patterns | ❌ | ✅ |
| **API CRYPTO VAULT** — AES-256-GCM Database Payload Encryption | ❌ | ✅ |
| Deterministic WAF (AEGIS Lite) | ✅ | ✅ |
| Kernel Hardening (TITAN Lite) | ✅ | ✅ |
| Stealth Engine (HADES Lite) | ✅ | ✅ |
| Access Guard (CERBERUS) | ✅ | ✅ |
| Outbound Control (STYX LITE) | ✅ | ✅ |
| Payload Sanitizer (AIRLOCK Lite) | ✅ | ✅ |
| Integrity + Malware Scanner (CHRONOS) | ✅ | ✅ |
| Anti-Bot PoW Engine (VGT SHIELD) | ✅ | ✅ |

---

## 🚀 Installation

```bash
# 1. Clone into WordPress plugins directory
cd /var/www/html/wp-content/plugins/
git clone https://github.com/visiongaiatechnology/sentinelcom

# 2. Activate in WordPress Admin
# Plugins → VGT Sentinel Community Edition → Activate

# 3. HADES: Configure custom login slug
# Settings → Sentinel → Stealth Engine

# 4. CHRONOS: Generate initial integrity manifest + signature scan
# Settings → Sentinel → Integrity Monitor → Generate Baseline

# 5. VGT SHIELD: Activate Anti-Bot PoW
# Settings → Sentinel → Shield → Enable
```

> **MU-Deployer Note:** One-click MU deployment is no longer available (WordPress policy). After activating Sentinel, navigate to **Settings → Sentinel → MU-Deployer** to generate the deployment script, then upload it manually to `wp-content/mu-plugins/` via FTP.

On first activation, Sentinel automatically:

```
→ Injects AEGIS WAF into the request lifecycle
→ Applies TITAN security headers
→ Activates HADES URL rewrites (.htaccess / Nginx rules)
→ Initializes CERBERUS fail-state cache
→ Generates CHRONOS integrity_matrix.php baseline + signature scan
→ Deploys Ghost Trap honeypot
→ Activates STYX outbound kill switch
→ Registers VGT SHIELD challenge endpoint (/wp-json/vgt-shield/v1/challenge)
```

<img width="1749" height="906" alt="{9D2A7C57-9EC6-4183-9E36-04120AA9419A}" src="https://github.com/user-attachments/assets/1199d85a-c9f6-40ad-b596-12dea0e77964" />

<img width="1750" height="911" alt="{9A9F9703-E90B-4591-A717-C5D406B6FEAA}" src="https://github.com/user-attachments/assets/0d0f7459-7a50-49ba-8ecc-2c4acd803fcd" />

<img width="1749" height="904" alt="{7C042814-E8E4-484D-A698-5CE6C5E90889}" src="https://github.com/user-attachments/assets/8bc0d18f-be99-414f-9935-22cef04d2964" />

---

## 🔗 VGT Ecosystem

| Tool | Type | Purpose |
|---|---|---|
| ⚔️ **VGT Sentinel** | **WAF / IDS Framework** | Zero-Trust WordPress security suite — you are here |
| 🏰 **[Throne Guard](https://github.com/visiongaiatechnology/throne-guard)** | **Capability Hardening** | Strips toxic capabilities from Administrator role |
| 🛡️ **[VGT Myrmidon](https://github.com/visiongaiatechnology/vgtmyrmidon)** | **ZTNA** | Zero Trust device registry and cryptographic integrity verification |
| ⚡ **[VGT Auto-Punisher](https://github.com/visiongaiatechnology/vgt-auto-punisher)** | **IDS** | L4+L7 Hybrid IDS — attackers terminated before they even knock |
| 📊 **[VGT Dattrack](https://github.com/visiongaiatechnology/dattrack)** | **Analytics** | Sovereign analytics engine — your data, your server, no third parties |
| 🌐 **[VGT Global Threat Sync](https://github.com/visiongaiatechnology/vgt-global-threat-sync)** | **Preventive** | Daily threat feed — block known attackers before they arrive |
| 🔥 **[VGT Windows Firewall Burner](https://github.com/visiongaiatechnology/vgt-windows-burner)** | **Windows** | 280,000+ APT IPs in native Windows Firewall |

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

VisionGaia Technology builds enterprise-grade security infrastructure — engineered to the DIAMANT VGT SUPREME standard.

> *"Sentinel was built because WordPress deserved a security framework that doesn't phone home, doesn't bloat your stack, and doesn't ask you to trust a SaaS dashboard with your attack surface."*

---

*Version 1.6.0 — VGT Sentinel Community Edition // Zero-Trust WAF Framework // Deterministic DFA Engine // Integrated Malware Scanner // Hardened Pattern Set // WordPress Marketplace Ready // AGPLv3*
