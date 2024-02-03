<?php
/*
 * Plugin Name: WP-Golf-Score
 * Plugin URI: https://www.smitka.net/wp-golf-score
 * Description: JavaScript init elements by Time
 * Version: 1.0
 * Author: Ivan Smitka
 * Author URI: http://www.smitka.net
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

class WP_Golf_Score
{

    public static function init()
    {
        // Scripts
        if (!is_admin()) { // show only in public area
            add_action('wp_enqueue_scripts', [
                'WP_Golf_Score',
                'enqueue_scripts'
            ]);
        }
    }

    public static function enqueue_scripts()
    {
        foreach (scandir(__DIR__ . "/dist/assets") as $path) {
            $pathInfo = pathinfo($path);
            if (strpos($pathInfo["filename"], "index") === 0) {
                wp_enqueue_script('wp-time', plugins_url("/dist/assets/{$path}", __FILE__), ['jquery']);
                wp_enqueue_style('wp-time', plugins_url('/static/golf-score.css', __FILE__));
            }
        }
    }
}

add_action('plugins_loaded', array(
    'WP_Golf_Score',
    'init'), 100);
