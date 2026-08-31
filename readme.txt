=== VGT WP-Desk — Operator OS for WordPress ===
Contributors: visiongaiatechnology
Donate link: https://visiongaiatechnology.de
Tags: desktop, operator-os, security, firewall, waf, dashboard, ui, admin
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 2.0.0
License: AGPLv3
License URI: https://www.gnu.org/licenses/agpl-3.0.html

VGT WP-Desk — Premium Operator OS and Multi-Window Desktop layer for WordPress, featuring native GeDefense v8.0.0 Sovereign Security, Unified Design System, and Frame Policy.

== Description ==

VGT WP-Desk is a modular, zero-dependency WordPress Operator OS. It sits above WordPress to provide a unified control plane, multi-window workspace, hardened portal, and coherent design system across every admin surface.

WordPress remains WordPress. Core and third-party plugin interfaces remain untouched while WP-Desk provides an operating layer on top:
- Multi-window workspace with Aero Snap and 8-edge resizing
- 3 runtime-switchable desktop layouts (macOS Cupertino, Windows Redmond, Linux Tux)
- Integrated GeDefense v8.0.0 Security Core (19 defense modules including Aegis WAF, ThroneGuard Master, LoginPager, Morpheus RASP, and Chronos Malware Scanner)
- Unified Design System across all security and studio modules
- Hardened Frame Policy (single-owner X-Frame-Options)
- Recovery Control Plane outside the desktop shell
- Integrated VGT Studio modules: Omega Vault, Book Reader, Chronos Campaign Engine, Dattrack Analytics, and VGTAstra AI

== Installation ==

1. Upload the `desktop` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Opt-in to Desktop Mode via the admin notice or under WP-Desk Settings.
4. Access the Security Center to manage GeDefense v8.0.0, ThroneGuard, and LoginPager.

== Changelog ==

= 2.0.0 =
* Major Release: Upgraded to full GeDefense v8.0.0 Open Core Engine with 19 defense modules.
* Native ThroneGuard Master and LoginPager subsystems integrated into GeDefense core.
* Full V2 Control Plane: WPDeskFramePolicy, WPDeskIframePolicy, WPDeskWidgetLayout, WPDeskDesignSystem.
* Unified Security Center with live Vitals HUD, GeDefense Suite, ThroneGuard cockpit, and LoginPager preview.
* Purged legacy Sentinel CE and standalone modules.