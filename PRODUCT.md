# VGT Desk — Product Packaging (Operator OS)

## Nordstern

**VGT Desk is the Operator OS for WordPress.**

WordPress stays WordPress. The operator gets a local-first workspace above it: multi-window productivity, optional security control plane, and recovery that works when the desktop shell does not.

Primary positioning:

> *WordPress Operator Workspace + local-first Security*  
> Not “another WAF”. Not “another Mac admin theme”.

---

## Editions matrix

| Edition | Includes | Intent |
|---------|----------|--------|
| **Core** | Desktop shell, layouts, folders, spotlight, settings persistence, recovery entry | Daily operator UX only |
| **Secure** | Core + Sentinel CE (or V7 coexistence) + Throne Guard + Security Center | Hardened control plane |
| **Studio** | Core + Build Center (Omega Vault forms, Chronos, Book Reader) | Campaign / content tooling |
| **Ops** | Secure + Dattrack telemetry + Command Center diagnostics / task manager | Observability and ops trust |

Notes:

- Modules remain optional flags under one install; editions describe **product packaging**, not separate monorepos.
- Astra is an **assistant add-on** inside the cockpit (read-only and content-assist first), not the product center.

---

## Near-term priority stack (Production Hardening)

1. **Recovery outside the desktop** — force classic, disable auto-redirect, capability + CSRF; reachable without the multi-window shell.
2. **Iframe compatibility** — profiler + classic-mode per app (agency daily-driver gate).
3. **Audit / ops trust** — revision-safe activity trail for security and desk control actions; honest metrics (no fake CPU).

Supporting tracks: lazy module boundaries, pure control-plane tests, unified versioning.

---

## Explicit non-goals (this packaging cycle)

Spaces, full Notification Center, Multisite fleet, Client Mode product UI, Policy-as-Code, Staging Diff, Build Center 2.0 redesign, cloud-required AI.

---

## Audience

Agencies, power-admins, publishers — people who live in `wp-admin` all day and need speed without lockout risk.
