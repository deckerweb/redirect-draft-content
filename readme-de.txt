=== Redirect Draft Content ===
Contributors: deckerweb
Tags: redirects, drafts, content-management
Requires at least: 7.1.2
Tested up to: 7.1.2
Requires PHP: 8.2
Stable tag: 0.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Redirect Draft Content leitet Aufrufe von Entwürfen vorübergehend auf ein veröffentlichtes Ziel oder eine eigene URL weiter. Das kleine Helfer-Plugin aus der Serie Manage Content erhält die Vorschau für Redakteure und bietet individuelle Ziele je Inhaltstyp.

== Description ==
- Echte temporäre Weiterleitungen mit HTTP 302.
- Ziele und Pause-Schalter je Inhaltstyp.
- Veröffentlichte Inhalte suchen, 30 Ergebnisse je Seite.
- Eigene HTTP-/HTTPS-URLs und Live-Vorschau.
- Exakter Pfadabgleich und Schutz vor bekannten Entwurfsschleifen.
- Englisch, Deutsch und Deutsch (Sie).
- deckerweb Library 0.6.2 und deckerweb Updater 2.1.0.

== Installation ==
1. Deaktiviere das bisherige Redirect-Draft-Content-Snippet.
2. Lade das Test-ZIP unter Plugins → Installieren → Plugin hochladen hoch und aktiviere es.
3. Öffne Einstellungen → Entwurfsweiterleitung, prüfe die übernommenen Ziele und speichere.
4. Teste eine Entwurfs-URL ausgeloggt. Die Live-Vorschau prüft das Ziel auch vor dem Speichern.

Bei einer bestehenden Plugin-Installation kann das ZIP die bisherige Fassung ersetzen. Die Option `rdc_targets` bleibt erhalten.

== Frequently Asked Questions ==
= Werden Inhalte dauerhaft verschoben? =
Nein. Die Weiterleitungen verwenden HTTP 302. Suchmaschinen entscheiden selbst, wie sie temporäre Weiterleitungen verarbeiten. Der Verbleib einer URL im Index ist nicht garantiert.

= Welche Inhaltstypen werden unterstützt? =
Beiträge, Seiten und öffentlich aufrufbare eigene Inhaltstypen, einschließlich verschachtelter Pfade. Anhänge und nicht öffentliche Inhaltstypen sind ausgeschlossen.

= Können Redakteure Entwürfe ansehen? =
Ja. Benutzer mit edit_posts oder der Berechtigung zum Bearbeiten des aufgerufenen Entwurfs sind ausgenommen. Vorschau, Feeds, REST, AJAX und schreibende Aufrufe bleiben ebenfalls ausgenommen.

= Kann ich eine externe URL verwenden? =
Ja. Je Inhaltstyp lässt sich eine vollständige HTTP- oder HTTPS-URL einstellen. Zugangsdaten in URLs und unsichere Protokolle werden abgewiesen. Das Ziel lässt sich vor dem Speichern prüfen.

= Wie ersetze ich das bestehende Snippet? =
Deaktiviere das alte Snippet vor der Plugin-Aktivierung. Die bestehende Option rdc_targets wird direkt übernommen, auch für vorübergehend inaktive Inhaltstypen. Vorhandene Regeln bleiben aktiv, bis sie pausiert werden.

= Funktioniert das Plugin in Multisite? =
Ja. Ziele werden je Website unter Einstellungen → Entwurfsweiterleitung festgelegt. Die Netzwerkaktivierung kopiert keine Einstellungen und erzeugt keine netzwerkweiten Weiterleitungen. Neue Websites starten ohne Ziele.

= Was passiert bei der Deinstallation? =
Die Einstellungen in rdc_targets und alle Inhalte bleiben erhalten. Repositorybezogene Update-Caches werden entfernt. Die gemeinsame Library berücksichtigt andere installierte Host-Plugins; ihre optionale Einstellungslöschung greift nur beim letzten Host.

== Changelog ==
= 0.9.0 =
* Neu: Temporäre Entwurfsweiterleitungen für Beiträge, Seiten und öffentlich aufrufbare eigene Inhaltstypen, mit individuellen Zielen und Live-Vorschau.
* Verbessert: Veröffentlichte Ziele suchen, Weiterleitungen je Inhaltstyp pausieren und bestehende Website-Einstellungen behalten.
* Behoben: Exakter URL-Abgleich, geprüfte Ziele und Schutz vor Weiterleitungen auf dieselbe URL.
