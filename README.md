# WordPress Ajax Filter Posts

## Description

A WordPress plugin to filter posts with taxonomies terms and load more posts via Ajax.
You can add posts and filters via a **shortcode** on any page.

```
[ajax_filter_posts post_type='recipe' tax="meal_type, food_type, diet_type"  posts_per_page="12"]
```

This plugins uses no dependencies, is translatable and WPML ready.

## Parameters

- **post_type**
  Post type to show. Default post.

- **tax**
  A comma seperated list of taxonomies to filter the post by. Default post_term.

- **post_per_page**
  Numbers of maximum posts to retreive at a time. Default 12.

## Overwriting template files

To easily overwrite template files you can copy one or more of the files in the templates folder to your own theme. Create a folder `ajax-filter-posts` in the root of your theme directory and copy the files in that newly created folder. Keep in mind that you have to keep the folder structure intact. For example: If you want a custom version of `loop.php`, you copy the file to `<<your-public-folder>>/wp-content/themes/<<your-theme>>/ajax-filter-posts/partials`.

## Motivation

I build a lot of sites that needed a functionality like this and decided to create a plugin for it. Although there are a lot of plugins doing something like this, they usually add a lot of bloat and is very user centered. This is for a developer easier to implement, adept, update and keeps te code cleaner.

## Installation

Clone this repo to your plugins or mu-plugins folder.

## License

GNU GENERAL PUBLIC LICENSE