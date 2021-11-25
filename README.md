# WordPress Ajax Filter Posts

## Description

A WordPress plugin to filter posts with taxonomies terms and load more posts via Ajax.
You can add posts and filters via a **shortcode** on any page.

```
[ajax_filter_posts post_type="recipe" tax="meal_type, food_type, diet_type"  posts_per_page="12"]
```

This plugins uses no dependencies, is translatable and WPML ready.

## Parameters

- **tax**
  A comma seperated list of taxonomies to filter the post by. Default `post_term`.

- **post_type**
  A comma seperated list of post types to show. Default `post`.

- **post_status**
  A comma seperated list of post status to show. Default `publish`.

- **post_per_page**
  Numbers of maximum posts to retreive at a time. Default 12.

- **orderby**
  Value to order the posts by. Supports `ID`, `author`, `title`, `name`, `type`, `date`, `modified`, `parent`, `rand`, `comment_count`, `relevance`, and `menu_order`.
  Does **not** support `meta_value`, `meta_value_num`, `post_name__in`, `post_parent__in` `post_parent__in` because additionals arguments needs to be set with these orderby values. You can [add your own query arguments via a filter hook](#ajax_filter_posts_query_args) if you need that support. Defaults to `date`.

  Check the [WordPress documentation on Query arguments](https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters) for more information.

- **order**
  Order the posts ascending or descendings. Support `ASC` (1, 2, 3; a, b, c) and `DESC` (3, 2, 1; c, b, a). Defaults to `DESC`.

- **multiselect**
  Allow one or more active filters per taxonomy. Defaults to `true`: allow more active filters

- **id**
  Usefull for custom styling or to target specific instances of the shortcode in the filter hooks. Default not set.

## Overwriting template files

To easily overwrite template files you can copy one or more of the files in the templates folder to your own theme. Create a folder `ajax-filter-posts` in the root of your theme directory and copy the files in that newly created folder. Keep in mind that you have to keep the folder structure intact. For example: If you want a custom version of `loop.php`, you copy the file to `<<your-public-folder>>/wp-content/themes/<<your-theme>>/ajax-filter-posts/partials`.

You can also [set a custom template path](#ajax_filter_posts_template_name).

## Motivation

I build a lot of sites that needed a functionality like this and decided to create a plugin for it. Although there are a lot of plugins doing something like this, they usually add a lot of bloat and are not developer friendly. This plugin is for a developer easier to implement, easier to edit and keeps te codebase cleaner.

## Installation

Clone this repo to your plugins or mu-plugins folder. When you load it in your mu-plugins folder, you have to call the plugin via a file that is directly in the `mu-plugins` folder. See [this article](https://www.sitepoint.com/wordpress-mu-plugins/) for more information.

## Requirements
Wordpress 5.7.0 or higer

## Filters hooks
As a developer you can overwrite functionality with WordPress hooks

### `ajax_filter_posts_query_args`

#### Description
With the filter `ajax_filter_posts_query_args` you can pass or alter query arguments to all post queries made by this plugin.

#### Arguments
`array $query_args` - query arguments set by the plugin Ajax Filter posts
`array $shortcode_attributes` - all shortcode attributes

#### Example
For example you can add an extra taxonomy query.

```php
/**
 * Add the diet term on all the queries made with the shortcode ajax_filter_posts
 *
 * @param array $query_args 			query arguments set by the plugin Ajax Filter posts
 * @param array $shortcode_attributes 	all shortcode attributes
 *
 * @return array a updated list of query arguments
 */
function my_site_set_additional_term_for_ajax_filter_posts($query_args, $shortcode_attributes) {

	// Only show posts with the term vegan in the diet taxonomy
	$diet_tax_query_args = [
		[
			'taxonomy' => 'diet',
			'field'    => 'slug',
			'terms'    => 'vegan',
		],
	];

	// If there are already tax queries args set, merge my query args with the set args
	if ( !empty( $query_args['tax_query'] ) ) {
		$prev_set_tax_args = $query_args['tax_query'];
		$query_args['tax_query'] = [
			// Set the relationship to AND: we want only post with my term and the set terms by the user
      		// Also see https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters
			'relation' => 'AND',
			$diet_tax_query_args,
			$prev_set_tax_args
		];
		return $query_args;
	}

	// If there are no tax queries args already set, just add it
	$query_args['tax_query'] = $diet_tax_query_args;
	return $query_args;
}
add_filter('ajax_filter_posts_query_args', 'my_site_set_additional_term_for_ajax_filter_posts', 10, 2);
```
### `ajax_filter_posts_is_post_type_viewable`

By default only post types that are publicly queryable are allowed as shortcode parameters.
This prevents that for example a custom private can be viewed when the wrong parameters are set or when a visitor manipulates the AJAX-request.

For built-in post types such as posts and pages, the 'public' value will be evaluated. For all others, the 'publicly_queryable' value will be used.

You can overwrite this check with this hook

#### Arguments
`boolean $is_publicly_queryable` - Default return value, esult of checking all set post types against Wordpress' *is_post_type_viewable* function

`array $shortcode_attributes` - all shortcode attributes, including the *post_type* attribute

### `ajax_filter_posts_is_post_status_viewable`

By default only post status that are publicly queryable are allowed as shortcode parameters.
This prevents that for example private or trashed posts can be viewed when the wrong parameters are set or when a visitor manipulates the AJAX-request.

For built-in post statuses such as publish and private, the ‘public’ value will be evaluted. For all others, the ‘publicly_queryable’ value will be used.

You can overwrite this check with this hook

#### Arguments
`boolean $is_publicly_queryable` - Default return value, result of checking all set post status against Wordpress' *is_post_status_viewable* function

`array $shortcode_attributes` - all shortcode attributes, including the *post_status* attribute

### `ajax_filter_posts_template_name`

This package searches for the the template files [in the active theme folder and in this plugin folder](#overwriting-template-files). If that doesn't fit your needs, you can overwrite the template path.

#### Arguments
`string $template` - The current retrieved template path. Empty if no path could be found.

`string $template_name` - The name of the current template to retrieve, with exentsion and subpath (e.g. base.php, partials/filters.php). See the template folder of this package for the used template files.

#### Arguments
`boolean $is_publicly_queryable` - Default return value, result of checking all set post status against Wordpress' *is_post_status_viewable* function

`array $shortcode_attributes` - all shortcode attributes, including the *post_status* attribute


## License

GNU GENERAL PUBLIC LICENSE
