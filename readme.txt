=== Imageshop DAM Connector ===
Tags: media library, media cdn, DAM
Requires at least: 6.2
Requires PHP: 7.0
Tested up to: 6.8
Stable tag: 1.3.3
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cloud based DAM Solution

== Description ==

Imageshop is a cloud-based [http://www.imageshop.org Digital Asset Management system] (image bank /DAM system) that makes it easier than ever to organize, search, share and use your digital files, internally and with the outside world and partners.

Drag & drop uploading and ultra-efficient image tagging enable your files are always available in the DAM system when and where they are needed, in the right format and the best quality. Read more about Imageshop here: http://www.imageshop.org


== Frequently Asked Questions ==

= Can I use this plugin with multisite? =

The plugin will let you add it to any multisite, but each individual site will need to do their own configuration.

= What if I no longer wish to use Imageshop for my files? =

If you at any point wish to stop using Imageshop for your files, you can export any files that have been used in content on your site to your local media library again, before deactivating the plugin.

= What if I am already using plugins to improve my media library? =

The Imageshop DAM plugin replaces any need for media management plugins, as it provides a centralised storage location with categories and media compression built in. As such, any third party plugins that perform similar, or other manipulations of your media files may be incompatible and should be deactivated prior to activating the Imageshop plugin.

= I have an idea for an improvement or enhancement =

We welcome both suggestions, discussions, and code! Check out the project source at https://github.com/DekodeInteraktiv/imageshop

== Screenshots ==

1. The default WordPress media library, extended with Imageshop and showing results form your image bank.
2. Detailed information about one of your images, including rightss expiry, consent status, and more.
3. A view of the Imageshop advanced settings page.

== Changelog ==

= 1.3.3 (2025-10-08) =
* Bugfix: Fixed an issue where filenames with multiple periods in them would get urlencoded to and be represented as a `+` symbol, which would cause a 404 error for the images in question.
* Enhancement: Trigger a flush of the Imageshop media permalinks when changing file formats to ensure a faster switch to the new format instead of waiting for caches to clear.
* Documentation: Introduced FAQ sections covering important information and warnings when combining media manipulation plugins.

= 1.3.2 (2025-09-26) =
* Bugfix: Improved handling of original images used by third party plugins, such as SEO ones.
* Enhancement: Extend the core filter support for larger dropdown menus, previously hiding the category selector.

= 1.3.1 (2025-09-04) =
* Bugfix: Fixed an issue where only the preview would load for newly uploaded images, and not the full resolution image.
* Bugfix: Fixed a bug where file extensions would not always be applied to generic files, only images.
* Feature: Added support for fetching audio files within the media library from Imageshop.

= 1.3.0 (2025-08-26) =
* Feature: Support generic files (such as PDF's or Office documents) in the media manager.
* Feature: Support for video embeds from Imageshop.
* Enhancement: Added a compatibility layer to support synchronised attributes between WooCommerce products when using Polylang.
* Maintenance: Changed the minimum required WordPress and PHP version to 6.2 and 7.0 respectively.

= 1.2.0 (2025-06-27) =
* Feature: Pretty permalinks for Imageshop media items. Your media will now have a reader-friendly name in the URL.
* Feature: WebP Support. The plugin will now automatically serve up WebP images if you've enabled it, head on over to the settings page to enable it.
* Feature: Disable uploads to Imageshop. If you ever wanted to keep your WordPress media separate, you can now disable the automatic uploads to Imageshop, and only use the plugin to quickly access and insert media from Imageshop.
* Feature: Consent documentation. The plugin will now show if consent is given by individuals in an image, and what platforms they've given consent for their likeness to be used on.
* Enhancement: Rights and expiration date fields will now always be visible, even if no information is available, to give a more consistent user interface.
* Enhancement: Visual overhaul of the Imageshop settings page and onboarding experience.
* Bugfix: Fixed an issue where images with unexpected data values would prevent further browsing through paginated content.
* Bugfix: Fixed a case where the visual alt-text for an image would show the description text when updating Imageshop details, and then revert to its intended value when refreshing the page.
