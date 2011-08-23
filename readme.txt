=== Plugin Name ===
Contributors: intoxstudio
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=KPZHE6A72LEN4&lc=US&item_name=WordPress%20Plugin%3a%20Content%20Aware%20Sidebars&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: sidebar, widget, content aware, post type, taxonomy, term, archive
Requires at least: 3.1
Tested up to: 3.2.1
Stable tag: 0.5

Manage and show sidebars according to the content being viewed.

== Description ==

Manage an infinite number of sidebars. Each with different rules for which content they should be visible with. Creating flexible sidebars has never been easier, and no code is needed at all as everything is done in the administration.
No extra database tables or table columns will be added.

= Features =

* Show sidebars with specific post types
* Show sidebars with posts containing specific taxonomies or taxonomy terms
* Show sidebars with specific post type archives, taxonomy archives or taxonomy term archives
* Show sidebars with search, 404 page and front page
* Merge new sidebars with others, replace them or simply add them to your theme manually
* Create complex content with nested sidebars
* Private sidebars only for members
* Schedule sidebars for later publishing

= Contact =

www.intox.dk

== Installation ==

Easy install:

1. Upload the full plugin directory to your `/wp-content/plugins/` directory or install the plugin through `Plugins` in the administration 
1. Activate the plugin through `Plugins` in the administration
1. Have fun creating your first sidebar
1. Optional: Insert `<?php display_ca_sidebar(); ?>` in a template if you have a special spot for the new, manual handled, sidebars.

== Frequently Asked Questions ==

= Who's great? =

You are.

== Screenshots ==

1. Add a new sidebar `For Pages` visible on all pages, replacing `Main Sidebar`
2. Add widgets to `For Pages`
3. Viewing home page. `Main Sidebar` is visible
4. Viewing a page. `For Pages` has replaced `Main Sidebar`

== Changelog ==

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

== Translations ==

None yet. Might come in the future. Do you want to contribute? Send a mail to jv[at]intox.dk