# 🖥️ VGT WP-Desk — Strategic Development Roadmap

Dieses Dokument beschreibt die verbleibenden Meilensteine, Architekturoptimierungen und Sicherheitserweiterungen für **VGT WP-Desk**. Die Planung folgt konsequent der **Zero-Overheat-Doktrin** (maximale Performance, minimaler Fußabdruck) und entspricht den strengen Kriterien des **DIAMANT VGT SUPREME** Standards.

---

## 📅 Aktive Roadmap (Zukünftige Phasen)

Die Entwicklung konzentriert sich nun auf Ausfallsicherheit, intelligente Analyse und revisionssichere Audits.

### 1. Safe Mode / Recovery Center (Notfallmodus)
Ein absolut idiotensicherer, externer Rettungsmodus für Administratoren außerhalb des Desktops.
*   **Funktionsumfang:**
    *   **WP-Desk Bypass:** WP-Desk per DB-Flag oder URL-Parameter komplett de-/aktivieren.
    *   **Sentinel Security Control:** Sentinel Firewall temporär aus-/einschalten.
    *   **Throne Guard Override:** locked/unlocked Status einsehen und Notfall-Entsperrung.
    *   **Classic Restore:** WordPress-Standardadministration (Klassisches Admin) mit einem Klick wiederherstellen.
*   **Zugang:** Ein durch den Throne Guard Superkey geschützter Login/Bypass-Link.

### 2. Plugin Compatibility Profiler
Automatisierte Erkennung der Iframe-Kompatibilität von Drittanbieter-Plugins.
*   **Funktionsumfang:**
    *   **Kompatibilitäts-Scanner:** Erkennt, ob ein Plugin sauber im Iframe läuft, ausbricht (z.B. durch `X-Frame-Options` Header) oder den Classic Mode benötigt.
    *   **Status-Matrix:** Klassifizierung in *Sauber* (läuft im Iframe), *Teilweise* (leichte Einschränkungen), *Classic-Erforderlich* (muss im klassischen Admin geladen werden).
    *   **Erkennung:** Passives Monitoring über `postMessage` und Fehlerabfang.

### 3. Audit Timeline (Sicherheits- & Aktivitätsprotokoll)
Lückenlose, revisionssichere Aufzeichnung aller administrativen und sicherheitsrelevanten Aktionen.
*   **Protokollierte Ereignisse:**
    *   Wer hat Sentinel aktiviert/deaktiviert (IP, Zeitstempel, Benutzer).
    *   Wer hat den Superkey verwendet oder geändert.
    *   Wer hat Systemeinstellungen (Wallpaper, Akzentfarbe, Layouts, Presets) verändert.
    *   Brute-Force-Versuche und automatische IP-Sperren durch Sentinel.
*   **UI:** Eigener interaktiver Audit-Tab im Command Center mit Filterung nach Benutzer, Aktion und Modul.

### 4. Iframe Memory Suspend (Hibernation Engine)
Client-seitige RAM-Schonung für geöffnete, aber minimierte App-Fenster.
*   **Funktionsumfang:** Minimierte Fenster werden temporär "eingefroren" (Inhalt entladen), um Browser-RAM freizugeben. Bei Fokusierung erfolgt ein nahtloser Restore.

---

## ✅ Erledigte Meilensteine

Folgende Kernkomponenten wurden bereits erfolgreich implementiert und gehärtet:

*   **Custom Relational Database Migration:** Migration aller serialisierten Benutzerdaten aus `wp_usermeta` in die dedizierte, hochperformante Tabelle `{prefix}vgt_desk_settings`.
*   **Modularisierung der Engine:** Umstellung von einer monolithischen JS-Engine auf ES6-Module (`desktop-core.js`, `desktop-windows.js`, `desktop-icons.js` etc.).
*   **Multi-Layout Workspace Customization:** Vollständig responsive Implementierung von macOS-, Windows- und Linux-Desktop-Layouts inklusive dynamischer Desktop-Icon-Gitteranordnung und Snap-Tiling.
*   **Gold Accent Theme (Easter Egg):** Spezieller Premium-Gold-Modus, der bei Aktivierung den gesamten Desktop (Borders, Halo, Topbar, Dock, Widgets und Scrollbars) in ein glänzendes, metallisches Gold-Design hüllt.
*   **Workspace Presets:** Ein-Klick Profile zur schnellen Systemrekonfiguration: *Publisher Mode*, *Security Mode*, *Developer Mode* und *Minimal Mode*.
*   **First-Run Wizard (Interaktives Onboarding):** Geführter Einrichtungs-Assistent für neue Administratoren. Ermöglicht Layout-Auswahl, Akzentfarben-Zuweisung, Superkey-Initialisierung mit Stärkemessung, Sentinel WAF-Aktivierung und Auto-Redirect-Konfiguration in einem interaktiven, 6-schrittigen glassmorphic Interface.
