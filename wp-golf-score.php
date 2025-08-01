<?php
/*
 * Plugin Name: Golf Score
 * Plugin URI: https://www.smitka.net/wp-golf-score
 * Update URI: https://www.smitka.net/wp-plugin/wp-golf-score
 * Description: Calculate Golf Feeling Score depends on weather forecast and Season. Script periodically sets css class for elements with attribute data-day
 * Version: 1.3
 * Author: Ivan Smitka
 * Author URI: https://www.smitka.net
 * License: The MIT License
 *
 *
 * Copyright 2024 Web4People Ivan Smitka <ivan at stimulus dot cz>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 *
 */

class WP_Golf_Score {

	const UPDATE_URI = "https://www.smitka.net/wp-plugin/wp-golf-score";

	public static function init() {
		// Scripts
		if ( ! is_admin() ) { // show only in public area
			add_action( 'wp_enqueue_scripts', [ 'WP_Golf_Score', 'enqueue_scripts' ] );
			add_shortcode( 'wp-golf-score', [ 'WP_Golf_Score', 'html' ] );
		} else {
			/* SelfHosted Updater Section */
			add_filter( 'http_request_host_is_external', '__return_true' );
			add_filter( 'update_plugins_www.smitka.net', function ( $update, $plugin_data, $plugin_file, $locales ) {
				if ( $plugin_file === plugin_basename( __FILE__ ) ) {
					return self::getUpdate( $plugin_data['UpdateURI'] );
				}

				return false;
			}, 10, 4 );
			add_filter( 'plugins_api', static function ( $res, $action, $args ) {
				if ( plugin_basename( __DIR__ ) !== $args->slug ) {
					return $res;
				}

				if ( $action !== 'plugin_information' ) {
					return $res;
				}

				$res                = self::getUpdate( self::UPDATE_URI );
				$res->download_link = $res->package;

				return $res;

			}, 9999, 3 );
			/* End of SelfHosted Updater Section */
		}
	}

	/**
	 * @param $update_URI
	 *
	 * @return mixed
	 */
	private static function getUpdate( $update_URI ): mixed {
		try {
			$request = wp_remote_get( $update_URI, [
				'timeout' => 10,
				'headers' => [
					'Accept' => 'application/json'
				]
			] );
			if (
				is_wp_error( $request )
				|| wp_remote_retrieve_response_code( $request ) !== 200
				|| empty( $request_body = wp_remote_retrieve_body( $request ) )
			) {
				return false;
			}

			$update = json_decode( $request_body, false );
			if ( ! is_array( $update->sections ) && is_object( $update->sections ) ) {
				$update->sections = (array) $update->sections;
			}

			return $update;
		} catch ( Throwable $e ) {
			return false;
		}
	}

	public static function enqueue_scripts() {
		foreach ( scandir( __DIR__ . "/dist/assets" ) as $path ) {
			$pathInfo = pathinfo( $path );
			if ( strpos( $pathInfo["filename"], "index" ) === 0 ) {
				wp_enqueue_script( 'wp-golf-score', plugins_url( "/dist/assets/{$path}", __FILE__ ), [ 'jquery' ] );
				wp_enqueue_style( 'wp-golf-score', plugins_url( '/static/golf-score.css', __FILE__ ) );
				break;
			}
		}
	}

	private static function is_true( $val, $return_null = false ) {
		$boolval = ( is_string( $val ) ? filter_var( $val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : (bool) $val );

		return ( $boolval === null && ! $return_null ? false : $boolval );
	}

	public static function html( $args = [] ) {
		$lat   = (float) array_key_exists( "lat", $args ) ? $args["lat"] : 0;
		$lon   = (float) array_key_exists( "lon", $args ) ? $args["lon"] : 0;
		$days  = (int) array_key_exists( "days", $args ) ? $args["days"] : 0;
		$date  = self::is_true( $args["date"] ?? "true" );
		$attrs = array_filter( $args, function ( $key ) {
			return ! in_array( $key, [ "lat", "lon", "days" ] );
		}, ARRAY_FILTER_USE_KEY );
		$tag   = "span";
		if ( array_key_exists( "href", $attrs ) ) {
			$tag = "a";
		}

		if ( $days < 0 ) {
			$days = 0;
		} else if ( $days > 3 ) {
			$days = 3;
		}
		ob_start();
		$attrs["data-golf-score"] = json_encode( [ "lat" => $lat, "lon" => $lon ] );
		if ( $date ) {
			$attrs["data-date-element"] = "1";
		}
		print "<{$tag} " . implode( " ", array_map( function ( $key ) use ( $attrs ) {
				return "{$key}='{$attrs[$key]}'";
			}, array_keys( $attrs ) ) ) . ">";
		for ( $i = 0; $i < $days; $i ++ ) {
			print "<span data-day='{$i}'></span>";
		}
		print "</{$tag}>";

		return ob_get_clean();
	}

}

add_action( 'plugins_loaded', [
	'WP_Golf_Score',
	'init'
], 100 );
