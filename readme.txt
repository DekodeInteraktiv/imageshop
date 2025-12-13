=== Imageshop DAM Connector ===
Tags: media library, media cdn, DAM
Requires at least: 6.2
Requires PHP: 7.0
Tested up to: 6.9
Stable tag: 1.4.0
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

= 1.5.0 (TBD) =
* Media: Change the default setting for media uploads to not synchronize to Imageshop automatically, separating responsibilities.
* Media: Made WebP conversion the default value for new installations.
* Media: When deactivating the Imageshop integration, hide attachment references from the media library to create a better user experience.
* Media: Add filter options for media types to the media library screen.
* Onboarding: Add notice to onboarding flow about the potential conflicts multiple media management plugins can have with each other.

= 1.4.0 (2025-11-28) =
* Feature: Added support for SVG vector images.
* Bugfix: Fixed an issue where you might not get a status response when testing a new API key in the settings panel.
