=== Plugin Name ===
Contributors: intoxstudio
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=KPZHE6A72LEN4&lc=US&item_name=WordPress%20Plugin%3a%20Content%20Aware%20Sidebars&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: sidebar, widget, content aware, post type, taxonomy, term, archive, singular, seo
Requires at least: 3.1
Tested up to: 3.3
Stable tag: 0.7

Manage and show sidebars according to the content being viewed.

== Description ==

Manage an infinite number of sidebars. Make your WordPress site even more dynamic and boost SEO by controlling what content the sidebars should be displayed with. Creating flexible, dynamic sidebars has never been easier, and no code is needed at all as everything is easily done in the administration.
No extra database tables or table columns will be added.

= Features =

* Show sidebars with:
	* Specific singulars - e.g. specific posts or pages
	* Specific (custom) post types
	* Specific page templates
	* Singulars containing specific taxonomies or taxonomy terms
	* Singulars made by specific authors
	* Specific post type archives, author archives, taxonomy archives or taxonomy term archives
	* Search results, 404 page and front page
	* Any combination of the above
* Merge new sidebars with others, replace them or simply add them to your theme manually with a template tag
* Create complex content with nested sidebars
* Private sidebars only for members
* Schedule sidebars for later publishing

= Translations =

Content Aware Sidebars can now be translated. If you want to help, please contact jv[at]intox.dk.

= Contact =

www.intox.dk

== Installation ==

1. Upload the full plugin directory to your `/wp-content/plugins/` directory or install the plugin through `Plugins` in the administration 
1. Activate the plugin through `Plugins` in the administration
1. Have fun creating your first sidebar
1. Optional: Insert `<?php display_ca_sidebar(); ?>` in a template if you have a special spot for the new, manual handled, sidebars.

== Frequently Asked Questions ==

If you have any questions not answered here, feel free to contact jv[at]intox.dk.

= How do I use `display_ca_sidebar()`? =

This function handles all sidebars that are set to be handled manually. It can be inserted anywhere on your site in any quantity, either as it is, or with the following parameters:

`include` (array|string)
Insert IDs of sidebars. By using this, the function will only handle the sidebars whose IDs are included. Default is `null`.

`before` (string)
Change the html to be displayed before the sidebar. Default is `<div id="sidebar" class="widget-area"><ul class="xoxo">`.

`after` (string)
Change the html to be displayed after the sidebar. Default is `</ul></div>`.

The function accepts URL-style strings as parameters too, like the standard WordPress Template Tags.

== Screenshots ==

1. Add a new Content Aware Sidebar to be displayed with All Posts and Another Page. It replaces `Main Sidebar`
2. Simple overview of all created Content Aware Sidebars
3. Add widgets to the newly added sidebar
4. Viewing front page of site. `Main Sidebar` is displayed
5. Viewing Another Page. The Content Aware Sidebar has replaced `Main Sidebar`

== Changelog ==

= 0.7 =

* Added: sidebars will be displayed even if empty (i.e. hidden)
* Added: author rules on singulars and archives
* Added: page template rules
* Added: javascript handling for disabling/enabling specific input on editor page
* Fixed: minor tweak for full compatibility with wp3.3
* Fixed: function for meta boxes is called only on editor page
* Fixed: proper column sorting in administration
* Fixed: specific post type label not supported in WP3.1.x
* Fixed: type (array) not supported as post_status in get_posts() in WP3.1.x
* Fixed: code cleanup

= 0.6.3 =

* Added: scheduled and private singulars are selectable in sidebar editor
* Added: combined cache for manual and automatically handled sidebars
* Added: display_ca_sidebar accepts specific ids to be included
* Fixed: only a limited amount of sidebars were present in widgets area
* Fixed: better caching in sidebar editor
* Fixed: page list in sidebar editor could behave incorrectly if some pages were static

= 0.6.2 =

* Fixed: array_flip triggered type mismatch errors in some cases

= 0.6.1 =

* Fixed: an image caused headers already sent error

= 0.6 =

* Added: sidebars can be set with specific singulars
* Added: sidebars can be set with specific post formats
* Added: updated gui
* Fixed: draft sidebars save meta

= 0.5 =

* Added: search, 404, front page rules now supported
* Fixed: custom tax and terms are now supported properly (again)

= 0.4 =

* Added: post type archives, taxonomy archives and taxonomy terms archives now supported
* Added: taxonomy rules
* Added: removable donation button
* Fixed: faster!

= 0.3 =

* Added: sidebars can now be private
* Fixed: taxonomy terms are now supported by template function
* Fixed: faster rule recognition and handling
* Fixed: custom taxonomies are now supported properly
* Fixed: error if several sidebars had taxonomy terms rules

= 0.2 =

* Added: taxonomy terms rules
* Added: optional description for sidebars
* Added: display_ca_sidebar also accepts URL-style string as parameter
* Fixed: saving meta now only kicks in with sidebar types
* Fixed: archives are not singulars and will not be treated like them

= 0.1 =

* First stable release

== Upgrade Notice ==

= 0.5 =

* Note that the plugin now requires at least WordPress 3.1 because of post type archives.

= 0.4 =

* All current custom sidebars have to be updated after plugin upgrade due to the new archive rules

= 0.1 =

* Hello World

