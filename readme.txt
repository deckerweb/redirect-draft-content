=== Redirect Draft Content ===
Contributors: deckerweb
Tags: redirects, drafts, content-management
Requires at least: 7.1.2
Tested up to: 7.1.2
Requires PHP: 8.2
Stable tag: 0.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Redirect Draft Content temporarily redirects draft requests to a published destination or a custom URL. This small Manage Content helper preserves editor previews and provides individual targets per content type.

== Description ==
- Temporary HTTP 302 redirects.
- Individual destinations and pause switches per content type.
- Published-target search, 30 results per page.
- Custom HTTP/HTTPS URLs and live previews.
- Exact route matching and protection against known draft loops.
- English, German and formal German.
- deckerweb Library 0.6.2 and deckerweb Updater 2.1.0.

== Installation ==
1. Disable the existing Redirect Draft Content snippet.
2. Upload the test ZIP through Plugins → Add Plugin → Upload Plugin and activate it.
3. Open Settings → Draft Redirect, review the retained destinations and save.
4. Test a draft URL while logged out. Live previews also work before saving.

An existing plugin installation can be replaced using the ZIP. The `rdc_targets` option remains intact.

== Frequently Asked Questions ==
= Does this permanently move content? =
No. Redirects use HTTP 302. Search engines decide how to process temporary redirects; retaining an indexed URL is not guaranteed.

= Which content types are supported? =
Posts, pages and publicly viewable custom post types, including hierarchical paths. Attachments and nonpublic types are excluded.

= Can editors preview drafts? =
Yes. Users with edit_posts or permission to edit the requested draft are exempt. Preview, feed, REST, AJAX and write requests are excluded.

= Can I use an external URL? =
Yes. An administrator can configure a complete HTTP or HTTPS URL for each content type. Credentials and unsafe protocols are rejected. Preview the destination before saving.

= How do I replace the existing snippet? =
Disable the old snippet before activating this plugin. The existing rdc_targets option is read directly, including settings for temporarily inactive content types. Existing rows remain enabled until paused.

= Does it work on Multisite? =
Yes. Configure destinations in Settings → Draft Redirect on each site. Network activation loads the helper across sites without copying settings or adding network-wide redirects. New sites start without destinations.

= What happens on uninstall? =
Your rdc_targets settings and all content remain. Repository-specific updater caches are removed. Shared Library cleanup respects other installed hosts; its optional settings deletion only applies to the last host.

== Changelog ==
= 0.9.0 =
* New: Temporary draft redirects for posts, pages and publicly viewable custom post types, with individual destinations and live previews.
* Improved: Search published targets, pause each content type and keep existing site settings.
* Fixed: Exact route matching, validated targets and protection against self-redirects.
