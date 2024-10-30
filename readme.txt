=== Bilingual Linker ===
Contributors: jackdewey
Donate link: https://ylefebvre.github.io/wordpress-plugins/bilingual-linker/
Tags: translation, link, bilingual
Requires at least: 3.0
Tested up to: 6.4.1
Stable tag: 2.4

The purpose of this plugin is to allow users to add a link to a translation version of a page or post in the admin and print this link on their blog, on a single post or or a page.

== Description ==

The purpose of this plugin is to allow users to add a link to a translation version of a page or post in the admin and print this link on their blog, on a single post or or a page.

You can try it out in a temporary copy of WordPress [here](https://demo.tastewp.com/bilingual-linker).

* [Changelog](https://wordpress.org/plugins/bilingual-linker/changelog/)
* [Support Forum](https://wordpress.org/support/plugin/bilingual-linker)

== Installation ==

1. Download the plugin
1. Upload entire bilingual-linker folder to the /wp-content/plugins/ directory
1. Activate the plugin in the Wordpress Admin
1. Add links to posts or pages in the Wordpress editor
1. Use the OutputBilingualLink function in the loop to display a link to the item translation.

OutputBilingualLink($post_id, $linktext, $beforelink, $afterlink);

When using in The Loop in any template, you can use $post->ID as the first argument to pass the current post ID being processed.

== Changelog ==

= 2.4 =
* Added new navigation block to support FSE themes

= 2.3.7 =
* Modification so header links always have trailing slash

= 2.3.6 =
* Fix to output proper hreflang for the current page language. E.g. Was previously outputting de_DE instead of only de for German

= 2.3.5 =
* Added output to page header to show alternate link for current page language

= 2.3.4 =
* Add new option to be able to specify a custom coded condition to not display translation link

= 2.3.3 =
* Change to address missing scripts in admin pages

= 2.3.2 =
* Adds language selection option in menu builder to be able to create menu items for multiple languages
* Adds text name to menu items in menu builder to facilitate managing multiple Bilingual Linker menu items

= 2.3.1 =
* Fixed issue with adding Bilingual Linker menu in Menu editor

= 2.3 =
* Added alternate language tags to page header based on user suggestion

= 2.2.4 =
* Correction in donation link

= 2.2.3 =
* Added support to display translation link when posts page is not front page

= 2.2.2 =
* Code fix to use bilingual link set in page editor if page is used as site front page

= 2.2.1 =
* Fix for Bilingual Linker Menu item

= 2.2 =
* Added support for custom post type archive pages

= 2.1.2 =
* Fix for potential XSS vulnerability

= 2.1.1 =
* Fix for menu items all showing translation link

= 2.1 =
* Added new item in WordPress menu builder to be able to easily add Bilingual Linker link to menu

= 2.0.8 =
* Modified the_bilingual_linker function so it can accept arguments as an array
* Added new option url_only that only echoes or returns the translation URL. This option is only available when sending options as an array.

= 2.0.7 =
* Corrected issue preventing users from specifying HTML in link test, before and after fields in admin panel

= 2.0.6 =
* Corrected issue with new menu suppression options

= 2.0.5 =
* Added options to hide translation link on front page, search page, archive pages and category pages

= 2.0.4 =
* Added option to be able to hide translation links on pages that don't have a translation

= 2.0.3 =
* Added shortcode [the-bilingual-link]
* Added field to configure hreflang

= 2.0.2 =
* Corrected PHP Warnings

= 2.0.1 =
* Corrected problem with category meta table creation code

= 2.0 =
* Added support for multiple languages
* Added ability to assign translation links to categories
* Translation display link now works on all page types (front page, archives, search results, categories, tag)
* Created new display function (the_bilingual_link)

= 1.2.3 =
* Added option to specify whether the link should be echoed or sent as a function return value

= 1.2.2 =
* Added option to OutputBilingualLink to be able to provide a default URL to display if no translation link is found

= 1.2.1 =
* Fixed problem with posts extra field getting deleted

= 1.2 =
* Updated Bilingual Linker to support network installations
* Changed data storage method to use post meta data instead of custom table

= 1.1 =
* Added code to display Bilingual Linker on all post types, not only on posts and pages

= 1.0 =
* Initial functionality
* Ability to add custom link for translated text in post and page editors
* Ability to query this address from Wordpress theme

== Frequently Asked Questions ==

There are currently no FAQs

== Screenshots ==

There are currently no screenshots available
