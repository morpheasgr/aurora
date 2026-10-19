=== Aurora for Blocks ===
Contributors: aurora
Tags: animation, text animation, gradient, blocks, full site editing
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Aurora's text animations, animated inner blocks and gradients for the block editor and the Site Editor.

== Description ==

Aurora for Blocks adds an "Aurora" panel to the settings sidebar of core blocks, in posts, pages, templates and template parts.

* **Text animation** (headings, paragraphs, buttons, site title, post title, pullquotes, verse): 53 effects that split text into characters, words or lines.
* **Animate inner blocks** (group, columns, column, buttons, gallery, list, social icons, media & text, cover, post template): staggered entrances, hover and proximity effects for the blocks inside.
* **Gradient** (group, columns, column, cover, media & text as background; headings, paragraphs, buttons, site and post titles as text): multi-stop gradients, flow and hue animation, WebGL mesh styles.

How it works:

* The options are saved in the block's settings. The block's own HTML is never changed, so switching the plugin off leaves every block valid.
* The data-attributes Aurora reads are added when the page renders, and each module's script loads only on pages that use it.
* Animations play on the published page and in Preview, not inside the editor.
* Every module respects the visitor's "reduce motion" setting.

Text animation uses Anime.js v4; everything else uses native CSS, the Web Animations API or WebGL. No GSAP.

Developers can offer modules on more blocks, or change the per-block defaults, with the `aurora_blocks_targets` filter (see includes/class-block-targets.php).

== Installation ==

1. Upload the zip from Plugins → Add New → Upload Plugin, then activate.
2. Select a heading, a group or another supported block and open the "Aurora" panel in the block settings.

To build the zip from the Aurora repository: `npm install`, then `npm run pack:blocks` (writes build/aurora-for-blocks.zip).

== Changelog ==

= 0.1.0 =
* First version: text, children and gradient modules on core blocks.
