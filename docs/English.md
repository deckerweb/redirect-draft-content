# Redirect Draft Content

Redirect Draft Content temporarily redirects draft requests to a published destination or a custom URL. This small Manage Content helper preserves editor previews and provides individual targets per content type.

[Deutsch](../README-de.md)

## Getting started

1. Disable the existing Redirect Draft Content snippet.
2. Upload the test ZIP through Plugins → Add Plugin → Upload Plugin and activate it.
3. Open Settings → Draft Redirect, review the retained destinations and save.
4. Test a draft URL while logged out. Live previews also work before saving.

An existing plugin installation can be replaced using the ZIP. The `rdc_targets` option remains intact.

## Features

- Temporary HTTP 302 redirects.
- Individual destinations and pause switches per content type.
- Published-target search, 30 results per page.
- Custom HTTP/HTTPS URLs and live previews.
- Exact route matching and protection against known draft loops.
- English, German and formal German.
- deckerweb Library 0.6.2 and deckerweb Updater 2.1.0.
