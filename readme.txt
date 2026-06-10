=== VGT Sentinel CE ===
Contributors: visiongaiatechnology

Donate link: https://visiongaiatechnology.de

Tags: security, firewall, waf, malware scanner, anti-spam

Requires at least: 6.0

Tested up to: 6.9

Requires PHP: 7.4

Stable tag: 1.5.0

License: AGPLv3

License URI: https://www.gnu.org/licenses/agpl-3.0.html

VGT Sentinel Community Edition (CE) – a high-performance security kernel engineered specifically for modern WordPress environments.

== Description ==

VGT Sentinel (Community Edition) brings enterprise-grade security architecture to the open-source world. Unlike conventional security plugins, which are often sluggish and resource-heavy, Sentinel is built on the VGT Omega Doctrine: Maximum hardening with a minimal footprint.

The kernel is strictly written using declare(strict_types=1); and utilizes mathematically optimized inference logic to neutralize threats in real-time.

Core Modules of the Community Edition:

AEGIS (Application Firewall): A high-performance stream-inspection engine. Utilizing Chunk-Boundary-Safe technology, Aegis intercepts payloads attempting to bypass firewalls through fragmentation or splitting.

HADES (Stealth Protocol): Conceals your WordPress identity. It renames login URLs and obfuscates tell-tale paths like /wp-admin/ or /wp-login.php on the frontend.

CERBERUS (Perimeter Shield): The central enforcer. An intelligent IP-banning system that blocks malicious actors at the TCP level before they can stress the application core.

GHOST TRAP (Honeypot): A cognitive trap for automated bots and scrapers. Those blindly searching for sensitive files are instantly routed into a tarpit.

Why VGT Sentinel?

Zero-Overhead: Optimized for shared hosting environments. Stream inspection requires only a few kilobytes of RAM, regardless of payload size.

Modern Engineering: Full PHP 8.x support and JIT-optimized regex signatures.

No Tracking: We respect your digital sovereignty. No cloud connections, no tracking, and no "phone home" telemetry in the CE version.

== Installation ==

Upload the sentinel folder to the /wp-content/plugins/ directory.

Activate the plugin through the 'Plugins' menu in WordPress.

Navigate to the new Sentinel menu item to configure your defensive shields.

Important: If you activate the HADES module, resave your permalinks under Settings > Permalinks to generate the necessary rewrite rules.

== Frequently Asked Questions ==

= Does Sentinel slow down my website? = No. On the contrary. Through the efficient pre-filtering of Cerberus and Aegis, malicious requests are blocked before WordPress has to execute complex database queries. This saves CPU cycles for your legitimate visitors.

= Is Sentinel compatible with page builders like Elementor? = Yes. Sentinel includes dedicated compatibility bridges for Elementor to prevent false positives during the editing process.

= What is the difference to the V7 Full Version? = The V7 Full Version is our commercial enterprise solution. It additionally offers the Pre-Boot Zeus Kernel (WAF execution prior to WordPress boot), Prometheus, Nemesis, and the Groq-powered Oracle AI for semantic zero-day defense.

== Screenshots ==

The VGT Sentinel Dashboard – Total control over the security matrix.

== Changelog ==

= 1.5.0 =

Added Antibot as a new module.

AEGIS regex expansion and atomization.

Increased core stability.

= 1.1.1 =

Core bug fixes.

Integration of Aegis Platinum Rewrite with improved regex signatures.

Overhauled the Cerberus Auto-Ban protocol.

== Privacy Policy ==

VGT Sentinel CE operates locally on your server. No personal data is transmitted to external VisionGaiaTechnology servers or third-party providers. All log data remains within your local WordPress database.