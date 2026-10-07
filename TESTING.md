# Test coverage

[Deutsch](#deutsch)

The 0.9.0 installation package was tested with WordPress 7.1.2 and PHP 8.2.29 and 8.4.5 using disposable SQLite-backed sites. Coverage includes 39 routing, validation, migration, translation and administration checks, three date-permalink checks, nine Multisite checks, seven updater package-validation checks and four uninstall preservation checks.

Browser checks cover paged target searches, safe previews, native settings saves, Escape dismissal and focus return. HTTP requests confirm 302, Location, X-Redirect-By and no-store cache headers; unrelated URLs remain 404. PHP source passes syntax validation on PHP 8.2.

Tested migration retains the original rdc_targets schema. No published GitHub release exists yet, so a complete real release-to-release GitHub download/update remains a follow-up check. External redirect chains and third-party URL rewriting require site-specific testing. Artwork remains a selection of three drafts.

## Deutsch

Das Installationspaket 0.9.0 wurde mit WordPress 7.1.2 sowie PHP 8.2.29 und 8.4.5 auf isolierten SQLite-Testwebsites geprüft: 39 Prüfungen für Weiterleitungen, Validierung, Einstellungsübernahme, Übersetzungen und Administration, drei Prüfungen für Datums-Permalinks, neun Multisite-Prüfungen, sieben Prüfungen der Update-Paketvalidierung und vier Prüfungen zur Deinstallation.

Die Browserprüfungen umfassen die paginierte Zielsuche, sichere Vorschau, das Speichern, Schließen mit Escape und die Fokusrückgabe. HTTP-Aufrufe bestätigen 302, Location, X-Redirect-By und Cache-Sperren; unbekannte URLs bleiben 404. Die PHP-Dateien bestehen die Syntaxprüfung mit PHP 8.2.

Die Einstellungsübernahme erhält das ursprüngliche Schema rdc_targets. Ein echter GitHub-Update-Durchlauf zwischen veröffentlichten Releases folgt später, da es noch keinen veröffentlichten Release gibt. Externe Weiterleitungsketten und URL-Anpassungen anderer Plugins müssen auf der jeweiligen Website geprüft werden. Die Grafik liegt in drei Entwürfen zur Auswahl vor.
