=== Cherry Services List ===

Contributors: TemplateMonster 2002, artismiezitis
Tags: custom post type, services, service, cherry-framework, elementor
Requires at least: 6.0
Tested up to: 7.1.4
Requires PHP: 8.0
Stable tag: 1.5.1-cit
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Display company services as individual pages and configurable lists. PHP 8+ compatibility edition maintained by Creative IT (cit), based on the original Zemez / Cherry Team plugin.

== Description ==

Create services with a title, content, featured image, categories and additional service options. Display them through the Services archive, the [cherry_services] shortcode or the optional Cherry Services Elementor widget.

Starting from WordPress 6.0 and PHP 8.0. Tested on WordPress 6.0 and 7.1.4 with PHP 8.0, 8.1, 8.2, 8.3, 8.4 and 8.5. The latest WordPress version tested for this release is 7.1.4. Future WordPress and PHP versions require new testing.

Supports the WordPress block editor and classic or block themes. Elementor is optional; widget registration, controls and output have been tested with Elementor 4.3.6 on WordPress 7.1.4. Older third-party Cherry add-ons and individual theme overrides require their own compatibility testing.

Source and releases: https://github.com/artismiezitis/cherry-services-list
Original plugin authors: Zemez / Cherry Team. PHP 8 compatibility maintenance: Creative IT.

== Installation ==

1. Download the complete cherry-services-list-1.5.1-cit.zip release package.
2. Open Plugins > Add New Plugin > Upload Plugin in WordPress.
3. Select the ZIP, click Install Now, then Activate Plugin.
4. Open Services in the left administration menu.

The ZIP includes the patched Cherry Framework. A separate framework plugin, Composer or Git submodule download is not required. The installed folder must be wp-content/plugins/cherry-services-list/.

== Updating an existing installation ==

1. Back up the website files and database. Copy any changes made directly inside the old plugin folder; replacing a plugin replaces those files.
2. Upload the Creative IT release ZIP through Plugins > Add New Plugin > Upload Plugin.
3. When WordPress reports that the plugin is already installed, choose Replace current with uploaded. Keep the folder name cherry-services-list.
4. Ensure the plugin is active. Open Settings > Permalinks and click Save Changes, especially if the services archive URL has changed.
5. Check a service page, service list, filters and any custom theme templates.

Existing services, categories, service metadata, shortcodes and the cherry-services / cherry-services_default options are retained. No data migration or database table creation is required. Deleting this plugin through WordPress does not run a custom data-removal routine, but replacement is the recommended update workflow.

The Update URI identifies this Creative IT fork and prevents an unrelated WordPress.org package from overwriting it. It does not provide automatic GitHub updates. Install future releases by uploading their ZIP packages.

== Usage ==

= Add services =

Open Services > Add New. Enter the service title and content, choose a featured image and category, then click Publish. Service Options below the editor provides the slogan, description, icon, features and optional call to action. Testimonials require the separate Cherry Testimonials plugin.

= Display services on a page =

Create or edit a WordPress page. Add a Shortcode block and paste:

[cherry_services posts_per_page="6" columns="3" columns_laptop="3" columns_tablet="2" columns_phone="1" more="false" pagination="true"]

To add category filters, use:

[cherry_services posts_per_page="6" show_filters="true" more="false" pagination="true"]

To use a Load More button instead of numbered pagination:

[cherry_services posts_per_page="3" more="true" ajax_more="true" more_text="Load more"]

To display one category, supply its slug, for example category="consulting". To display a specific service, use id="123". Use template="media-icon" for the icon-based listing or template="default" for the standard listing.

= Configure the archive =

Open Services > Settings to set the archive page, items per page, columns, image sizes and templates. Save stores the current options; Define as default stores your preferred defaults; Restore reloads those defaults.

Without a selected archive page, the default archive is /cherry-services/ when pretty permalinks are enabled. If an archive page is selected, its slug becomes the services archive slug. Save Settings > Permalinks after changing it. Choose Selected archive page content when you want the page's own content or shortcode to control the listing.

= Optional request form =

In a service's Call to Action options, enable the CTA, select the form type, add the desired fields, configure the recipient and success message, then update the service. The single-service template displays the form. Delivery uses WordPress wp_mail(); the website must have working email delivery. A valid submission nonce and required fields are checked. A success message is stored only when wp_mail() accepts the message; actual delivery depends on the mail server.

= Optional Elementor widget =

If Elementor is installed, add Cherry Services from the Cherry Addons category in its editor. Elementor is not needed for the standard WordPress Shortcode block or services pages.

= Custom templates =

Store theme overrides under your child theme's cherry-services/ directory. Item templates use cherry-services/tmpl/*.tmpl. Changes directly inside this plugin are overwritten during replacement.

== Changelog ==

= 1.5.1-cit =

* Creative IT PHP 8.0-8.5 compatibility edition; WordPress minimum 6.0, tested through 7.1.4.
* Bundle the patched Cherry Framework 1.5.11 directly in the repository and release package.
* Fix deprecated parameter declarations, dynamic properties and PHP 8.5 numeric casts.
* Support the block editor, REST exposure of services/categories and block-theme service templates.
* Correct empty AJAX results, page selection, ID lists, query reuse and published-service filtering.
* Correct JavaScript initialization, script dependencies and multiple service listings on one page.
* Update Elementor widget registration, controls and rendering APIs.
* Validate settings, template filenames, form fields and form/clone nonces.
* Preserve service categories and structured metadata when cloning a service.
* Avoid opening or holding PHP sessions on ordinary visits or throughout a request.
* Add repeatable compatibility tests and a complete release ZIP builder.

== Upgrade Notice ==

= 1.5.1-cit =
Requires WordPress 6.0+ and PHP 8.0+. Upload this release ZIP to replace the existing cherry-services-list plugin. Back up any modifications made directly inside the old plugin directory first.
