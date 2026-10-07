# Redirect Draft Content FAQ

[English](FAQ-English.md) · [Deutsch](FAQ-Deutsch.md)

## Getting started and daily use

### Does this permanently move content?

No. Redirects use HTTP 302. Search engines decide how to process temporary redirects; retaining an indexed URL is not guaranteed.

### Which content types are supported?

Posts, pages and publicly viewable custom post types, including hierarchical paths. Attachments and nonpublic types are excluded.

### Can editors preview drafts?

Yes. Users with edit_posts or permission to edit the requested draft are exempt. Preview, feed, REST, AJAX and write requests are excluded.

### Can I use an external URL?

Yes. An administrator can configure a complete HTTP or HTTPS URL for each content type. Credentials and unsafe protocols are rejected. Preview the destination before saving.

### How do I replace the existing snippet?

Disable the old snippet before activating this plugin. The existing rdc_targets option is read directly, including settings for temporarily inactive content types. Existing rows remain enabled until paused.

### Does it work on Multisite?

Yes. Configure destinations in Settings → Draft Redirect on each site. Network activation loads the helper across sites without copying settings or adding network-wide redirects. New sites start without destinations.

### What happens on uninstall?

Your rdc_targets settings and all content remain. Repository-specific updater caches are removed. Shared Library cleanup respects other installed hosts; its optional settings deletion only applies to the last host.
