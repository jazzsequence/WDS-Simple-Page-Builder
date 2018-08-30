# SPB2 (Simple Page Builder)

![SPB2 (Simple Page Builder)](https://raw.githubusercontent.com/WebDevStudios/WDS-Simple-Page-Builder/master/assets/banner-1544x500.png)

Contributors: [jazzsequence](https://github.com/jazzsequence), [modemlooper](https://github.com/modemlooper), [gregrickaby](https://github.com/gregrickaby), [aubreypwd](https://github.com/aubreypwd), [jtsternberg](https://github.com/jtsternberg), [bradp](https://github.com/bradp), [jaywood](https://github.com/jaywood), [dustyf](https://github.com/dustyf), [blobaugh](https://github.com/blobaugh)

Uses existing template parts in the theme to dynamically build a custom page layout, *per page*. An options page allows you to define your template part directory (if you wanted to keep these template parts separate from other template parts) and the template part prefix you are using.

Questions? [Check out the wiki!](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki)

## Usage

To use this plugin, your theme template files must have the following `do_action` wherever you want the template parts to load:

`<?php do_action( 'spb2_load_parts' ); ?>`

This will take care of loading the correct template parts in the order you specified. You can also specify a specific saved layout by passing the layout name to the `do_action` as a second parameter, like this:

`<?php do_action( 'spb2_load_parts', 'my-saved-layout' ); ?>`

**Note:** With saved layouts, the name you pass to the do_action must match *exactly* the way it is saved on the options page. So, if your layout was instead named "my saved layout", you would need to pass it to the `do_action` with the spaces intact.

## Page vs Global Parts vs Saved Layouts

The page builder will, by default, use the template parts that were set on the page when you set them on the Edit page screen. However, if no template parts were defined on the individual page, you can also set Global Template Parts that will load on all pages that don't have their own, individual template parts defined.

You can leave the Global setting to "- No Template Parts -" to not define any global template parts if individual page-specific template parts weren't set.

Saved layouts are used when there is no layout set for that page (or post) with Global layouts used as a generic fallback. You can set a saved layout to be the default layout for all posts of a type *or* you can call them specifically when you add the `do_action` to your theme template files.

## Screenshots

Dynamically add template parts within the Edit Page screen and reorder those components.
![page builder ui](https://cldup.com/epETzuW4Dx.gif)

The results are saved to post meta on the page and visible as soon as you save the page.
![page builder front-end](https://cldup.com/djUNBYKcEd.gif)

Options page
![options page](https://cldup.com/gmB327JMaG.png)

## Changelog

**1.6**
* added new Page Builder "Areas" feature ([documentation](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki/Page-Builder-Areas))
* CMB2 takes care of figuring out which version to run internally, so don't check CMB2_LOADED
* fixed a bug where saved layouts were getting deleted when the options were registered
* fixed an issue where a saved layout wouldn't display when layouts were displayed if registered layouts existed
* fixed an issue where the global layouts didn't display the templates dropdown if no global layout was saved
* added filters for template-specific fields, users can now use a filter of `spb2_fields_{$part_slug}` to allow fields to show when a user selects that template part ([Issue #19](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/issues/19))
* added template tags for getting part-specific data, `spb2_get_this_part_data( $meta_key )` and `spb2_get_part_data( $part_slug, $meta_key )` respectively.  The former can be used in the part itself, the latter can be used anywhere within the site.

**1.5**
* fixed a bug that prevented options from being saved with an empty saved layout name (removed the name requirement) ([issue](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/issues/3))
* added a new `page_builder_class` function ([issue](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/issues/11) | [documentation](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki/Template-Tags#page_builder_class-class---))
* added a new function that will initialize page builder with a wrapping container around it ([issue](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/issues/13) | [documentation](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki/Template-Tags#spb2_wrap-container---class---layout---))
* added a new function to initialize the page builder options and set those initialized options as either hidden or visible but uneditable ([issue](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/issues/13) | [documentation](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki/Template-Tags#wds_register_page_builder_options-args--array-))
* added the ability to register Page Builder as a theme feature (using `add_theme_support( 'simple-page-builder' )`) and a helper function to initialize the Page Builder options ([documentation](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki/Adding-Theme-Support))

**1.4.2**
* added `saved_page_builder_layout_exists` function

**1.4.1**
* fixed empty templates showing up after options save in Saved Layouts ([fixes #5](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/issues/5))

**1.4**
* added actions and filters for plugin developers to hook into. See [Hooks documentation](https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki/Hooks)
* removed some unused functions
* deprecated `wds_template_part_prefix` and `wds_template_parts_dir` and replaced them with `spb2_template_part_prefix` and `spb2_template_parts_dir`, respectively
* added `unregister_page_builder_layout` to unregister a single registered layout (or all of them if `'all'` is passed)

**1.3**
* added new template tags -- `spb2_load_parts` for loading an array of specific template parts and `spb2_load_part` for loading a single template part
* added new feature to programmatically register a new layout

**1.2**
* added saved layouts feature. Now you can save layouts and set those saved layouts as the defaults for post types. Or you can define a specific layout in the `do_action`, e.g. `do_action( 'spb2_load_parts', 'my named layout' )`
* added a check for the existence of a template part before loading it -- prevents accidental blowing up of the page if parts are changed and not found

**1.1**
* added post type support beyond just pages. Options page now allows you to check which post types you want to use the page builder on, and the page builder metabox will appear on the Add New/Edit page for those post types.

**1.0.1**
* switched to using get_queried_object instead of get_the_ID to get a post id when checking the existence of post meta for cases when a loop is not being used or the action is fired outside the loop.
