<?php
/*
Plugin Name: AppStore Reviews To Posts Converter
Version: 1.0.0
Plugin URI: https://github.com/lexus65/wp_appstore_to_posts
Description: Plugin converts all reviews from your applications to custom post type.
Author: Slava Reshetnyakov
Author URI: https://github.com/lexus65
*/

/*
Copyright 2019 Slava Reshetnyakov (email: reshetnyakov.slava@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Version
define( "APP_ARC_VERSION", "1.0.0" );

// URLs
define( "APP_ARC_DIRECTORY", WP_PLUGIN_DIR . "/" . basename( dirname( __FILE__ ) ) );
define( "APP_ARC_CACHE_DIR", APP_ARC_DIRECTORY . "/cache/" );

// Admin
include_once( "appstore-review-admin.php" );
register_activation_hook( __FILE__, 'arc_activate' );
register_uninstall_hook( __FILE__, 'arc_uninstall' );

// AppStore review URL
define( 'APP_ARC_APPSTORE_URL', 'http://itunes.apple.com/{country}/rss/customerreviews/id={id}/json' );

//AppStore app URL
define( 'APP_ARC_APPSTORE_APP_URL', 'https://itunes.apple.com/lookup?id={id}&entity=software' );

/* Scripts and styles */

add_action( 'wp_enqueue_scripts', 'arc_enqueue_sripts_and_styles' );

function arc_enqueue_sripts_and_styles() {
	wp_enqueue_script( 'jquery' );
}

add_action( 'admin_post_arc_update_reviews', 'appstore_review' );
function appstore_review() {
    $options = get_option( "arc_options" );

	$atts = [
		"id"              => $options['applicationId'],
		"country"         => $options['defaultCountry'],
		"minstar"         => $options['defaultStars'],
		"defaultPostType" => $options['defaultPostType'],
		"recent"          => $options['defaultRecent'],
		"defaultPostStatus"=> $options['defaultPostStatus'],
	];

	//Don't do anything if the ID is blank
	if ( $atts['id'] == "" ) {
		return;
	}

	//Lowercase the country code
	$atts['country'] = strtolower( $atts['country'] );

	/* APP */
	$urlApp       = arc_make_store_app_url( $atts );
	$cacheFileApp = APP_ARC_CACHE_DIR . $atts['id'] . "_app.appstore";
	$jsonApp      = arc_fetch_data( $urlApp, $cacheFileApp );

	/* REVIEWS */
	$urlReviews       = arc_make_store_url( $atts );
	$cacheFileReviews = APP_ARC_CACHE_DIR . $atts['id'] . $atts['country'] . "_reviews.appstore";
	$jsonReviews      = arc_fetch_data( $urlReviews, $cacheFileReviews );

	//Display data
	return arc_review_output( $jsonApp, $jsonReviews, $atts['minstar'], $atts['recent'], $atts );
}

/* Fetch data methods */

function arc_fetch_data( $url, $cacheFile ) {
	if ( ! file_exists( APP_ARC_CACHE_DIR ) ) {
		mkdir( APP_ARC_CACHE_DIR, 0755 );
	}

	//First, check if the data in cache is not too old
	$cacheTime = get_option( "arc_options" )["cache"] * 60 * 60;
	if ( is_readable( $cacheFile ) && ( time() - $cacheTime < filemtime( $cacheFile ) ) ) {
		$json_data = json_decode( file_get_contents( $cacheFile ) );
	} //Otherwise, we download fresh data and store them
	else {

		//Call the URL 4 times as it may not work the first time (API error?)
		$json_data = null;
		for ( $i = 0; $i < 4; $i ++ ) {

			if ( function_exists( 'file_get_contents' ) && ini_get( 'allow_url_fopen' ) ) {
				$json_data = arc_fetch_data_fopen( $url );
			} else if ( function_exists( 'curl_exec' ) ) {
				$json_data = arc_fetch_data_curl( $url );
			} else {
				wp_die( '<h1>You must have either file_get_contents() or curl_exec() enabled on your web server.</h1>' );
			}

			//Store JSON in its original state.
			if ( ! empty( $json_data ) ) {
				file_put_contents( $cacheFile, json_encode( $json_data ) );

				//Don't need to try to download anymore
				break;
			}
		}

		//If no data returned from Apple (error?), we just update the modification time of the file and load the data from the cache.
		if ( $json_data == null ) {
			if ( is_readable( $cacheFile ) ) {
				touch( $cacheFile );
			}
		}
	}

	return $json_data;
}

function arc_make_store_url( $atts ) {
	$url = str_replace( array( "{country}", "{id}" ), array( $atts['country'], $atts['id'] ), APP_ARC_APPSTORE_URL );
	$url .= "?p" . rand() . "=" . rand();

	return $url;
}

function arc_make_store_app_url( $atts ) {
	$url = str_replace( "{id}", $atts['id'], APP_ARC_APPSTORE_APP_URL );
	return $url;
}

function arc_fetch_data_fopen( $url ) {
	$data = file_get_contents( $url );
	return json_decode( $data );
}

function arc_fetch_data_curl( $url ) {
	$output = wp_remote_get($url);
	return json_decode( $output );
}

/* Display data methods */

function arc_review_output( $jsonApp, $jsonReviews, $minStar, $nbToDisplay, $atts ) {
	if ( $jsonApp->results == null ) {
		return;
	}
	//Parse App info
	$appInfo     = $jsonApp->results[0];
	$app['name'] = $appInfo->trackName;
	$app['icon'] = $appInfo->artworkUrl100;
	$app['url']  = $appInfo->trackViewUrl;

	//Parse App reviews and get only the last X based on the number of stars
	$reviews = array();
	foreach ( $jsonReviews->feed->entry as $review ) {

		//Parse the review and store it if it has the minimum required amount of stars
		$r = arc_get_review( $review, $minStar );
		if ( $r ) {
			$reviews[] = $r;
			if ( count( $reviews ) == $nbToDisplay ) {
				break;
			}
		}
	}

	//Save posts
	if ( count( $reviews ) > 0 ) {
		return arc_save_as_posts( $app, $reviews, $atts );
	}
}

function arc_get_review( $data, $minStar ) {
	$review = array();

	$review["id"] = $data->id->label;
	$review["author"]  = $data->author->name->label;
	$review["rating"]  = $data->{'im:rating'}->label;
	$review["version"] = $data->{'im:version'}->label;
	$review["title"]   = $data->title->label;
	$review["content"] = $data->content->label;

	if ( $review["rating"] >= $minStar ) {
		return $review;
	}
}

function arc_transform_rating( $rating ) {
	$s1 = "<span style='color:gold'>" . str_repeat( "★", $rating ) . "</span>";
	$s2 = "<span style='color:#eee'>" . str_repeat( "★", 5 - $rating ) . "</span>";

	return $s1 . $s2;
}

function arc_save_as_posts( $app, $reviews, $atts ) {

	/**
	 *    $atts = [
	 * "id"        => $options['applicationId'],
	 * "country"    => $options['defaultCountry'],
	 * "minstar"    => $options['defaultStars'],
	 * "defaultPostType" => $options['defaultPostType'],
	 * "recent"    => $options['defaultRecent'],
	 * "defaultPostStatus"    => $options['defaultPostStatus'],
	 * ];
	 */

	foreach ( $reviews as $review ) {
		if ( post_exists( $review['title'] ) )
		    continue;

	    $postId = $review['id'];
	    $post = [];
		$postStatus = get_post($postId);
		if(!$postStatus){
			$post['import_id'] = (int) $postId;
        }else{
			$post['ID'] = (int) $postId;
        }

		$post = [
			'post_content' => $review['content'],
			'post_name'    => $review['title'],
			'post_status'  => $atts['defaultPostStatus'],
			'post_title'   => $review['title'],
			'post_type'    => $atts['defaultPostType'],
			'meta_input'   => [
			    'country' => $atts['country'],
                'star' => $review['rating'],
                'app_icon' => $app['icon'],
                'app_url' => $app['url'],
                'version' => $review['version']
			],
		];

		wp_insert_post( $post );

	}

    return wp_redirect(admin_url() . 'edit.php?post_type='.$atts['defaultPostType']);
}

