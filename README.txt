=== CC-TOC ===
Contributors: ClearcodeHQ, PiotrPress
Tags: toc, table of contents, index, indexes, navigation, nav, toc shortcode, shortcode
Requires PHP: 7.0
Requires at least: 4.9.1
Tested up to: 5.4.0
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

This plugin automatically creates a table of contents based on html headings in content.

== Description ==

The WordPress CC-TOC plugin automatically creates a table of contents (TOC) for posts/pages (and/or custom post types) based on headings in content. This plugin can also output a listing of child pages (INDEX).

The TOC by default appears before, while the INDEX appears after the content post/page.

= Tips & Tricks =

1. You can disable support for TOC and/or INDEX displaying in specific post/page edit page manually.
2. You can also use shortcode (by default `toc` and/or `index` - you can change it in the settings):
`[toc]` and/or `[index]`

== Installation ==

= From your WordPress Dashboard =

1. Go to 'Plugins > Add New'
2. Search for 'CC-TOC'
3. Activate the plugin from the Plugin section in your WordPress Dashboard.

= From WordPress.org =

1. Download 'CC-TOC'.
2. Upload the 'cc-toc' directory to your `/wp-content/plugins/` directory using your favorite method (ftp, sftp, scp, etc...)
3. Activate the plugin from the Plugin section in your WordPress Dashboard.

= Once Activated =

Visit 'Settings > Writing' to configure the plugin.

== Screenshots ==

1. **WordPress Writing Settings** - Configure the plugin.
2. **WordPress Post Edit** - Enable/Disable TOC/INDEX for specific post.

== Changelog ==

= 1.1.1 =
*Release date: 03.04.2020*

* Added support for excluding H1 headers.

= 1.1.0 =
*Release date: 22.11.2019*

* Added support for custom `id` attribute.

= 1.0.0 =
*Release date: 01.12.2017*

* First stable version of the plugin.