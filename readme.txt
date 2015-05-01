=== markdownR ===
Contributors: jianhong.ou
Tags: add, content, post.
Requires at least: 3.6
Tested up to: 4.2
Stable tag: 1.0
License: GPLv2
 
A powerful plugin to write blog in rmd format.

== Description ==

A powerful plugin to write blog in rmd format. The plugin can convert it into html in background and save the result into the server. This is excellent plugin for R/Bioconductor coder to record or share codes in wordpress.

This plugin is not easy to install. You need R installed and many the packages pre-installed. And you also need to install pandoc(need the version can handle markdown files).

At last, if you like our this plugin then show us your love with rating us 5 stars.

== Installation ==

1. Upload `markdownR` to your `/wp-content/plugins/` directory or download through the Plugins page.

1. install R and packages knitr, stringr, markdown
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. set the R path and pandoc path in setting menu.
1. Now simply create a new rmd file in the editor. The first line should be ==Rmd== (see screen short 2)
1. post the rmd.
1. wait to see the result.

== Frequently Asked Questions ==

= What does this do? =
run knitr after you post and before it insert into the database.

== Screenshots ==

1. markdownR Option and R log file page
2. editor page
3. post page

== Changelog ==

= 1.0 =
* Initial launch.

== Upgrade Notice ==
