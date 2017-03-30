# WordPress Ajax Filter Posts

## Description

A WordPress plugin to filter posts with taxonomies terms and load more posts via Ajax.
You can add posts and filters via a **shortcode** on any page.

```
[ajax_filter_posts post_type='recipe' tax="meal_type, food_type, diet_type"  posts_per_page="12"]

```

This plugins uses no dependencies.

## Parameters

- **post_type**
  Post type to show. Default post.

- **tax**
  A comma seperated list of taxonomies to filter the post by. Default post_term.

- **post_per_page**
  Numbers of maximum posts to retreive at a time.

## Motivation

I build a lot of sites that needed a functionality like this and decided to create a plugin for it. Although there are a lot of plugins doing something like this, they usually add a lot of bloat and is very user centered. This is for a developer easier to implement, update and keeps te code cleaner.

## Installation

Add this folder to your plugins or mu-plugins folder.

## License

GNU GENERAL PUBLIC LICENSE