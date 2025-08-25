=== Imageshop DAM Connector ===
Tags: media library, media cdn, DAM
Requires at least: 6.2
Requires PHP: 7.0
Tested up to: 6.8
Stable tag: 1.2.0
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

= I have an idea for an improvement or enhancement =

We welcome both suggestions, discussions, and code! Check out the project source at https://github.com/DekodeInteraktiv/imageshop

== Screenshots ==

1. The default WordPress media library, extended with Imageshop and showing results form your image bank.
2. Detailed information about one of your images, including rightss expiry, consent status, and more.
3. A view of the Imageshop advanced settings page.

== Changelog ==

= 1.2.0 (2025-06-27) =
* Feature: Pretty permalinks for Imageshop media items. Your media will now have a reader-friendly name in the URL.
* Feature: WebP Support. The plugin will now automatically serve up WebP images if you've enabled it, head on over to the settings page to enable it.
* Feature: Disable uploads to Imageshop. If you ever wanted to keep your WordPress media separate, you can now disable the automatic uploads to Imageshop, and only use the plugin to quickly access and insert media from Imageshop.
* Feature: Consent documentation. The plugin will now show if consent is given by individuals in an image, and what platforms they've given consent for their likeness to be used on.
* Enhancement: Rights and expiration date fields will now always be visible, even if no information is available, to give a more consistent user interface.
* Enhancement: Visual overhaul of the Imageshop settings page and onboarding experience.
* Bugfix: Fixed an issue where images with unexpected data values would prevent further browsing through paginated content.
* Bugfix: Fixed a case where the visual alt-text for an image would show the description text when updating Imageshop details, and then revert to its intended value when refreshing the page.

= 1.1.2 (2025-04-04) =
* Media filter: Ensure filters are always rendered after the error fixes in 1.1.1
* Settings: Fixed error preventing feedback messages from rendering.
* Onboarding: Removed import options form the onboarding wizard, the feature exists within the settings page, but removes potential confusion during setup.
* Compatibility: Properly flush caches when the plugin is uninstalled.
* Compatibility: Ensure the plugin is compatible with WordPress 6.8.

= 1.1.1 (2025-02-21) =
* Media filer: Fix a fatal error when the available interfaces list is empty.
* Attachments: Import the new `AltText` attribute from the Imageshop API.
* Translations: Updated when translations are loaded, to follow the best practice since WordPress 6.7
* General: Updated tested with attributes.
