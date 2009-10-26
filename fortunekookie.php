<?php
/*
Plugin Name: FortuneKookie
Description: This plugin adds a sidebar widget to display a random fortune cookie fortune. The database hosted on FortuneKookie.com has over 1500 unique fortunes and each fortune includes the front message, the back word(s), and the lucky numbers.
Author: Blendium
Author URI: http://www.fortunekookie.com/
Plugin URI: http://wordpress.fortunekookie.com/
Version: 0.9.0.0
License: GPL

This software comes without any warranty, express or otherwise.

*/

//Required is the FortuneKookie API Class
require "includes/fortunekookie.api.class.php";

//This function is to retrieve the "front" and "back" data elements from the FortuneKookie XML file
function get_tag_contents($xmlcode,$tag) {
  $offset=0;
  $xmlcode = trim($xmlcode);

    //find the next start tag
    $start_tag=strpos ($xmlcode,"<".$tag.">",$offset);
    $offset = $start_tag;

    //find the closing tag for the start tag you just found
    $end_tag=strpos ($xmlcode,"</".$tag.">",$offset);

    //split off <$tag>... as a string, leaving the </$tag> 
    $fortune_tag = substr ($xmlcode,$start_tag,($end_tag-$start_tag));
    $start_tag_length = strlen("<".$tag.">");
    if (substr($fortune_tag,0,$start_tag_length)=="<".$tag.">"){
      //strip off stray start tags from the beginning
      $fortune_tag = substr ($fortune_tag,$start_tag_length);
    }
return $fortune_tag;
}

//This function is to retrieve the "lucky number" data elements from the FortuneKookie XML file
function get_lucky_nbr($xmlcode) {
	$offset = 0;
	$xmlcode = trim($xmlcode);
	$lucky_nbr = "";

    //find the start tag
    $start_tag=strpos($xmlcode,"<lucky_nbr ",$offset);
    $start_tag_length = strlen("<lucky_nbr ");

    //find the closing tag
    $end_tag=strpos($xmlcode,"></lucky_nbr>");

	$x = ($start_tag + $start_tag_length);
	$y = ($end_tag - $x);

    //split off both tags from lucky number stream
    $fortune_tag = substr($xmlcode,$x,$y);

	//Strip out the lucky numbers
	for ($i = 1; $i <= 6; $i++)
		{
		$nbr_pos_start = strpos($fortune_tag,"\"") + 1;
		$nbr_pos_end = strpos($fortune_tag,"\"",$nbr_pos_start);
		$nbr = substr($fortune_tag,$nbr_pos_start,($nbr_pos_end - $nbr_pos_start));
		if ($i <> 6)
			{
			$lucky_nbr .= $nbr." - ";
			$nbr_length = strlen($fortune_tag);
			$fortune_tag = substr($fortune_tag,($nbr_pos_end + 2));
			}
		else
			{
			$lucky_nbr .= $nbr;
			}
		}

return $lucky_nbr;
}

//Main widget function
function widget_fortunekookie_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_fortunekookie($args) {

		// "$args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys." - These are set up by the theme
		extract($args);

		// These are our own options
		$options 	= get_option('widget_fortunekookie');
		$fk_code 	= $options['fk_code'];  	// Your FortuneKookie 10-digit security code for WordPress Widget (default value: 1022200909)
		$show_back  = $options['show_back'];  	// Option to show backside of the fortune
		$show_nbr   = $options['show_nbr'];  	// Option to show lucky numbers on the fortune
		$title   	= $options['title'];    	// Title in sidebar for widget

		//The number of random fortunes requested 1 <= n <= 10
		$nbr = 1;
		
		//Username is NULL (future functionality)
		$username = NULL;
		
		//Password is NULL (future functionality)
		$password = NULL;
		
		//Instantiate the FortuneKookie class
		$fortune = new FortuneKookie($username,$password);
		
		//Retrieve the fortunes in xml format
		$xml = $fortune->getFortunes($nbr, $fk_code);
		
		//Sample of code to display the data components of one (1) fortune cookie fortune
		$fortune_front = get_tag_contents($xml,"front");
		$fortune_back = get_tag_contents($xml,"back");
		$fortune_nbr = get_lucky_nbr($xml);
		
		$fortune_cookie .= "<li>\n"; 
			$fortune_cookie .= $fortune_front;
		$fortune_cookie .= "</li>\n";

		if ($show_back)
			{
			$fortune_cookie .= "<li>\n";
				$fortune_cookie .= $fortune_back;
			$fortune_cookie .= "</li>\n";
			}

		if ($show_nbr)
			{
			$fortune_cookie .= "<li>\n";
				$fortune_cookie .= $fortune_nbr;
			$fortune_cookie .= "</li>\n";
			}
		
        // Output
		echo $before_widget;

		// start
		echo '<div id="fortunekookie_widget">'
              .$before_title.$title.$after_title . "\n";
        echo '<ul id="fortunekookie_spot">' . $fortune_cookie . '</ul>';
        echo '</div>';
		
		// echo widget closing tag
		echo $after_widget;
	}

	// Settings form
	function widget_fortunekookie_control() {

		// Get options
		$options = get_option('widget_fortunekookie');
		// options exist? if not set defaults
		if ( !is_array($options) )
			$options = array(
							'fk_code' 	=> 'dbc6f4b1aa48acc5c8ceb9dae38a91af',
							'show_back' => '1',
							'show_nbr'	=> '1',
							'title'   	=> 'FortuneKookie Fortune'
						);

        // form posted?
		if ( $_POST['fortunekookie-submit'] ) {

			// Remember to sanitize and format use input appropriately.
			$options['fk_code'] 	= strip_tags(stripslashes($_POST['fortunekookie-code']));
			$options['show_back']   = strip_tags(stripslashes($_POST['fortunekookie-back']));
			$options['show_nbr'] 	= strip_tags(stripslashes($_POST['fortunekookie-nbr']));
			$options['title']   	= strip_tags(stripslashes($_POST['fortunekookie-title']));
			update_option('widget_fortunekookie', $options);
		}

		// Get options for form fields to show
		$fk_code	= htmlspecialchars($options['fk_code'], ENT_QUOTES);
		$show_back	= intval(htmlspecialchars($options['show_back'], ENT_QUOTES));
		$show_nbr	= intval(htmlspecialchars($options['show_nbr'], ENT_QUOTES));
		$title		= htmlspecialchars($options['title'], ENT_QUOTES);

		//Prepare defaults for radio buttons
		$show_back_on = "";
		$show_back_off = "";
		$show_nbr_on = "";
		$show_nbr_off = "";
		if(isset($show_back) && $show_back == 1){$show_back_on = " checked=\"checked\"";}
		if(isset($show_back) && $show_back == 0){$show_back_off = " checked=\"checked\"";}
		if(isset($show_nbr) && $show_nbr == 1){$show_nbr_on = " checked=\"checked\"";}
		if(isset($show_nbr) && $show_nbr == 0){$show_nbr_off = " checked=\"checked\"";}

		// The form fields
		echo '<p style="text-align:left;">
				<label for="fortunekookie-title">' . __('Title:') . '
				<input style="width: 190px;" id="fortunekookie-title" name="fortunekookie-title" type="text" value="'.$title.'" />
				</label></p>';
		echo '<p style="text-align:left;">
				<label for="fortunekookie-code">' . __('FortuneKookie 32-digit Code:') . '
				<br /><input style="width: 230px;" id="fortunekookie-code" name="fortunekookie-code" type="text" maxlength=32 value="'.$fk_code.'" />
				</label></p>';
		echo '<p style="text-align:left;">' . __('Show back:') . '
				<input id="fortunekookie-back-on" name="fortunekookie-back" type="radio" value="1"'.$show_back_on.' /><label for="fortunekookie-back-on">On</label>
				<input id="fortunekookie-back-off" name="fortunekookie-back" type="radio" value="0"'.$show_back_off.' /><label for="fortunekookie-back-off">Off</label>
				</label></p>';
		echo '<p style="text-align:left;">' . __('Show lucky numbers:') . '
				<input id="fortunekookie-nbr-on" name="fortunekookie-nbr" type="radio" value="1"'.$show_nbr_on.' /><label for="fortunekookie-nbr-on">On</label>
				<input id="fortunekookie-nbr-off" name="fortunekookie-nbr" type="radio" value="0"'.$show_nbr_off.' /><label for="fortunekookie-nbr-off">Off</label>
				</label></p>';
		echo '<input type="hidden" id="fortunekookie-submit" name="fortunekookie-submit" value="1" />';
	}


	// Register widget for use
	register_sidebar_widget(array('FortuneKookie', 'widgets'), 'widget_fortunekookie');

	// Register settings for use, 300x200 pixel form
	register_widget_control(array('FortuneKookie', 'widgets'), 'widget_fortunekookie_control', 250, 200);
}

// Run code and init
add_action('widgets_init', 'widget_fortunekookie_init');

?>
