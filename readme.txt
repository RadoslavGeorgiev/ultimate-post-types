=== Ultimate Post Types ===
Contributors: RadoGeorgiev
Donate link: https://www.ultimate-fields.com/pro/
Tags: cpt, post type, taxonomy, custom fields, custom templates, repeater, ultimate fields
Requires at least: 4.8
Tested up to: 4.9.5
Stable tag: 3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.4

Manage your Custom Post Types (CPT) and Custom Taxonomies, their templates and fields, without touching a line of code!

== Description ==

Ultimate Post Types provides an easy interface for custom Post Types and Taxonomies management through the admin. It works fully with the WordPress post type/taxonomy API and covers the functionality from creating a post type, through adding taxonomies and custom fields, to the template which the post type uses.

It is a logical extension of the [Ultimate Fields](https://wordpress.org/plugins/ultimate-fields/ "Easy and powerful custom fields management: Post Meta, Options Pages, Repeaters and many field types!") plugin and will not work if the latter is not installed.

= Post Types & Taxonomies =

You can create unlimited custom post types and custom taxonomies and set up every one of their details, as it would be possible through code.

= Custom Fields =

When creating a post type or taxonomy, you can directly assign all of Ultimate Fields' custom fields!

= Templates =

With Ultimate Post Types you can select which template from the active theme should the post type use for it's singular pages.

Additionally, you can define content that will appear before and after the standard content of the post type. That content may include custom fields, associated with the post type. This way you can create a custom template for your custom post type and display custom fields accordingly, without needing to add them to each separate post.

= Export =

You can export post types and taxonomies as stand-alone PHP code! When you add the code to your theme or plugin, the code
is self-sustaining and does not need Ultimate Post Types to be installed.

You would only need Ultimate Fields or Ultimate Fields Premium in order to enable custom fields.

== Installation ==

1. Upload `ultimate-post-types` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You are ready to create your first post type. To do this, choose "Add New" from the "Post Types" section in the Ultimate Fields menu.
4. Go to the new section in the admin with your post type and enter data.

== Screenshots ==

1. This screenshot shows the main page where you can set a post type up.
2. The second screenshot shows how the Fields interface looks.
3. The third screenshot shows how you can modify the template of the post type.

== Changelog ==

= 3.0 =
A rewrite of the plugin, compatible with Ultimate Fields 3

= 0.3 =
Version 0.3 changes the way singular templates are rewritten to ensure better compatibility with most themes.

= 0.2 =
This version brings two changes:

1. Rewrite rules are now flushed when a new post type or taxonomy is created. No more 404 errors.
2. Post Types & Taxonomies can be exported to PHP code now!

= 0.1 =
Beta 1
