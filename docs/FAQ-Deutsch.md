# Redirect Draft Content FAQ

[English](FAQ-English.md) · [Deutsch](FAQ-Deutsch.md)

## Einstieg und Alltag

### Werden Inhalte dauerhaft verschoben?

Nein. Die Weiterleitungen verwenden HTTP 302. Suchmaschinen entscheiden selbst, wie sie temporäre Weiterleitungen verarbeiten. Der Verbleib einer URL im Index ist nicht garantiert.

### Welche Inhaltstypen werden unterstützt?

Beiträge, Seiten und öffentlich aufrufbare eigene Inhaltstypen, einschließlich verschachtelter Pfade. Anhänge und nicht öffentliche Inhaltstypen sind ausgeschlossen.

### Können Redakteure Entwürfe ansehen?

Ja. Benutzer mit edit_posts oder der Berechtigung zum Bearbeiten des aufgerufenen Entwurfs sind ausgenommen. Vorschau, Feeds, REST, AJAX und schreibende Aufrufe bleiben ebenfalls ausgenommen.

### Kann ich eine externe URL verwenden?

Ja. Je Inhaltstyp lässt sich eine vollständige HTTP- oder HTTPS-URL einstellen. Zugangsdaten in URLs und unsichere Protokolle werden abgewiesen. Das Ziel lässt sich vor dem Speichern prüfen.

### Wie ersetze ich das bestehende Snippet?

Deaktiviere das alte Snippet vor der Plugin-Aktivierung. Die bestehende Option rdc_targets wird direkt übernommen, auch für vorübergehend inaktive Inhaltstypen. Vorhandene Regeln bleiben aktiv, bis sie pausiert werden.

### Funktioniert das Plugin in Multisite?

Ja. Ziele werden je Website unter Einstellungen → Entwurfsweiterleitung festgelegt. Die Netzwerkaktivierung kopiert keine Einstellungen und erzeugt keine netzwerkweiten Weiterleitungen. Neue Websites starten ohne Ziele.

### Was passiert bei der Deinstallation?

Die Einstellungen in rdc_targets und alle Inhalte bleiben erhalten. Repositorybezogene Update-Caches werden entfernt. Die gemeinsame Library berücksichtigt andere installierte Host-Plugins; ihre optionale Einstellungslöschung greift nur beim letzten Host.
