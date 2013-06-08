<?php 
/*
Plugin Name: Custom Facebook Feed
Plugin URI: http://smashballoon.com/custom-facebook-feed
Description: Add a completely customizable Facebook feed to your WordPress site
Version: 1.0
Author: Smash Balloon
Author URI: http://smashballoon.com/
License: GPLv2 or later
*/

/* 
Copyright 2013  Smash Balloon (email : hey@smashballoon.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Include admin
include dirname( __FILE__ ) .'/custom-facebook-feed-admin.php';

error_reporting(0);

// Add shortcodes
add_shortcode('custom-facebook-feed', 'display_cff');
function display_cff($atts) {
    //Pass in shortcode attrbutes
    $atts = shortcode_atts(
        array(
            'id' => get_option('cff_page_id'),
            'show' => get_option('cff_num_show')
        ), $atts);

    //Assign the Access Token and Page ID variables
    $access_token = get_option('cff_access_token');
    $page_id = $atts['id'];

    //Check whether the Access Token is present and valid
    if ($access_token == '') {
        echo 'Please enter a valid Access Token. You can do this in the plugin settings (Settings > Custom Facebook Feed).<br /><br />';
        return false;
    }

    //Check whether a Page ID has been defined
    if ($page_id == '') {
        echo "Please enter the Page ID of the Facebook feed you'd like to display.  You can do this in either the plugin settings (Settings > Custom Facebook Feed) or in the shortcode itself. For example [custom_facebook_feed id=<b>YOUR_PAGE_ID</b>].<br /><br />";
        return false;
    }

    //Get the contents of a Facebook page
    $FBpage = @file_get_contents('https://graph.facebook.com/' . $page_id . '/posts?access_token=' . $access_token . '&limit=' . $atts['show']);

    //Interpret data with JSON
    $FBdata = json_decode($FBpage);

    //Create HTML
    $content = '<div id="cff">';
    foreach ($FBdata->data as $news ) {

        //Explode News and Page ID's into 2 values
        $StatusID = explode("_", $news->id);

        //Start the container
        $content .= '<div class="cff-item">';

        //Text/title/description/date
        if (!empty($news->story)) { $content .= '<h4>' . $news->story . '</h4>'; }
        if (!empty($news->message)) { $content .= '<h4>' . $news->message . '</h4>'; }
        if (!empty($news->description)) { $content .= '<p>' . $news->description . '</p>'; }

        $content .= '<p class="cff-date">Posted '. timeSince(strtotime($news->created_time)) . ' ago</p>';


        //Check whether it's a shared link
        if ($news->type == 'link') {
            $content .= '<a href="'.$news->link.'"><img src="'. $picture_b .'" border="0" style="padding-right:10px;" /></a>';  

            //Display link name and description
            if (!empty($news->description)) {
                $content .= '<a href="'.$news->link.'">'. '<b>' . $news->name . '</b></a>';
            }
        }


        if (!empty($news->link)) {
            $link = $news->link;

            //Check whether it links to facebook or somewhere else
            $facebook_str = 'facebook.com';

            if(stripos($link, $facebook_str) !== false) {
                $link_text = 'View on Facebook';
            } else {
                $link_text = 'View Link';
            }
            $content .= '<a class="cff-viewpost" href="' . $link . '" title="' . $link_text . '">' . $link_text . '</a>';
        }

        $content .= '</div> <!-- end .cff-item -->';

    };


    //Add the Like Box
    $content .= '<div class="cff-likebox"><script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script><fb:like-box href="http://www.facebook.com/' . $page_id . '" width="200" show_faces="false" stream="false" header="true"></fb:like-box></div>';
    $content .= '</div> <!-- end .Custom Facebook Feed -->';

    //Return our feed HTML to display
    return $content;

}





//Time stamp function

function timeSince($original) {

    // Array of time period
    $chunks = array(
        array(60 * 60 * 24 * 365 , 'year'),
        array(60 * 60 * 24 * 30 , 'month'),
        array(60 * 60 * 24 * 7, 'week'),
        array(60 * 60 * 24 , 'day'),
        array(60 * 60 , 'hour'),
        array(60 , 'minute'),
    );

    // Current time
    $today = time();   
    $since = $today - $original;

    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];

        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }

    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";

    if ($i + 1 < $j) {
        // now getting the second item
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];

        // add second item if it's greater than 0
        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
            $print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
        }
    }
    return $print;
}



//Enqueue stylesheet
add_action( 'wp_enqueue_scripts', 'cff_add_my_stylesheet' );
function cff_add_my_stylesheet() {
    // Respects SSL, Style.css is relative to the current file
    wp_register_style( 'cff', plugins_url('css/style.css', __FILE__) );
    wp_enqueue_style( 'cff' );
}

//Allows shortcodes in sidebar of theme
add_filter('widget_text', 'do_shortcode'); 

//Uninstall
function cff_uninstall()
{
    if ( ! current_user_can( 'activate_plugins' ) )
        return;

    delete_option( 'cff_access_token' );
    delete_option( 'cff_page_id' );
    delete_option( 'cff_num_show' );
}
register_uninstall_hook( __FILE__, 'cff_uninstall' );
 
?>