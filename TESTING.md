# Test coverage

[Deutsch](#deutsch)

Version 0.9.1 was checked in isolated SQLite-backed WordPress installations. Routing, saved settings, destination validation, translations and administration were exercised on WordPress 7.1.2 with PHP 8.2.29 and 8.4.5, and on WordPress 6.9.10, 7.0.7 and 7.1.3 with PHP 8.4.5. The supported minimum remains WordPress 7.1.2 and PHP 8.2; exploratory tests on older WordPress series do not change that requirement.

Coverage includes dated and hierarchical routes, published-content preservation, per-type pauses, editors and previews, external URLs, single-site and network activation, new sites, uninstall preservation, candidate identity/version/platform checks, and a native local update from 0.9.0 to 0.9.1. The native update used the final local ZIP; it did not publish or download a 0.9.1 GitHub release.

Browser checks cover the structured changelog, German interface language, Escape, native closing, focus return, conflicting dialog styles, overridden instance methods and usable documentation links when the controller is missing or opening fails. Plugin Check 2.1.0 is run on the installation package in static and explicitly enabled runtime modes. Context-specific findings are reviewed individually; this does not imply that Plugin Check certifies security or compatibility with every third-party plugin.

External redirect chains, third-party URL rewriting, production caches and FTP/SSH filesystem transports require installation-specific testing. The package contains local runtime artwork; documentation screenshots remain outside the plugin ZIP.

## Deutsch

Version 0.9.1 wurde in isolierten WordPress-Installationen mit SQLite geprüft. Weiterleitungen, gespeicherte Ziele, Validierung, Übersetzungen und Administration wurden mit WordPress 7.1.2 unter PHP 8.2.29 und 8.4.5 sowie mit WordPress 6.9.10, 7.0.7 und 7.1.3 unter PHP 8.4.5 getestet. Die Mindestanforderungen bleiben WordPress 7.1.2 und PHP 8.2; ergänzende Tests älterer WordPress-Serien ändern diese Voraussetzungen nicht.

Die Prüfungen umfassen datierte und hierarchische URLs, unveränderte veröffentlichte Inhalte, Pausen je Inhaltstyp, Redakteure und Vorschauen, externe URLs, Einzel- und Netzwerkaktivierung, neue Websites, Datenerhalt bei Deinstallation, Paketidentität, Version und Plattformanforderungen sowie ein natives lokales Update von 0.9.0 auf 0.9.1. Dieses Update verwendete das finale lokale ZIP; ein GitHub-Release 0.9.1 wurde weder veröffentlicht noch heruntergeladen.

Im Browser wurden strukturierter Änderungsverlauf, deutsche Oberflächensprache, Escape, natives Schließen, Fokusrückgabe, störende Dialog-Styles, überschriebene Instanzmethoden und nutzbare Dokumentationslinks bei fehlendem Controller oder Öffnungsfehler geprüft. Plugin Check 2.1.0 wird am Installationspaket statisch und mit ausdrücklich aktiviertem Laufzeitmodus ausgeführt. Kontextabhängige Befunde werden einzeln bewertet; daraus folgt keine Sicherheitsgarantie oder Zusage für jedes Fremdplugin.

Externe Weiterleitungsketten, URL-Eingriffe anderer Plugins, produktive Caches und FTP-/SSH-Dateisysteme benötigen Tests auf der jeweiligen Installation. Das Paket enthält lokale Laufzeitgrafiken; Dokumentations-Screenshots bleiben außerhalb des Plugin-ZIPs.
