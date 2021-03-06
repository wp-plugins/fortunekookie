<?php
/*
Plugin Name: FortuneKookie
Description: This plugin adds a sidebar widget to display a random fortune cookie fortune. The database hosted on FortuneKookie.com has over 1500 unique fortunes and each fortune includes the front message, the back word(s), and the lucky numbers.
Author: Blendium
Author URI: http://www.fortunekookie.com/
Plugin URI: http://wordpress.fortunekookie.com/
Version: 1.0.1.0
License: GPL

This software comes without any warranty, express or otherwise.

Feature Requests:
1) View fortune within the blog via a really cool web 2.0 fortune cookie background
2) Internationalization priorities: German, Dutch, Spanish, French
3) Enable a php code snippet to allow FortuneKookie to be "manually" added to a template
4) Multiple fortunes added to sidebar
5) Different (better?) display of fortune components
6) eCommerce hook into spreadshirt shop: http://fortunekookie.spreadshirt.com/

Bugs to Correct:
1) Fortune Server does not error on positive security code and negative verification flag. Need to add check and return proper error codes.

Additional Notes:
In order to complete the setup for this widget you must register for your security code on http://wordpress.fortunekookie.com.
NOTE: The generic code `1234567890abcdef1234567890abcdef` is simply a place-holder and does NOT function...

*/

define('FORTUNEKOOKIE_VERSION', '1.0.1.0');
define('FK_INCLUDE_DIR', WP_CONTENT_DIR.'/plugins/'.dirname(plugin_basename(__FILE__)).'/includes/');
define('FK_IMAGE_DIR', WP_CONTENT_URL.'/plugins/'.dirname(plugin_basename(__FILE__)).'/images/');

//Must be set to '1'
define('FK_NBR', 1);

//Username is NULL (future functionality)
define('FK_USERNAME', NULL);

//Password is NULL (future functionality)
define('FK_PASSWORD', NULL);

//Required is the FortuneKookie API Class
//require "includes/fortunekookie.api.class.php";
require FK_INCLUDE_DIR."fortunekookie.api.class.php";

//This function is to retrieve the "front" and "back" data elements from the FortuneKookie XML file
function get_tag_contents($xmlcode,$tag) {
  $offset = 0;
  $xmlcode = trim($xmlcode);

    //find the next start tag
    $start_tag = strpos ($xmlcode,"<".$tag.">",$offset);
    $offset = $start_tag;

    //find the closing tag for the start tag you just found
    $end_tag = strpos ($xmlcode,"</".$tag.">",$offset);

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
    $start_tag = strpos($xmlcode,"<lucky_nbr ",$offset);
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

//This function is to retrieve the "forune cookie ID" data elements from the FortuneKookie XML file
function get_fortune_id($xmlcode) {
	$offset = 0;
	$xmlcode = trim($xmlcode);
	$cookie_id = "";

    //find the start tag
    $start_tag = strpos($xmlcode,"fk_id=",$offset);
    $start_tag_length = strlen("fk_id=");

	$x = ($start_tag + $start_tag_length + 1);
	$y = 10;

    //split off both tags from lucky number stream
    $cookie_id = intval(substr($xmlcode,$x,$y));

return $cookie_id;
}

//Alert admin to register a FortuneKookie security code
//function fortunekookie_activate() {
//	if (is_plugin_active('fortunekookie/fortunekookie.php')) {
//		function fortunekookie_alert() {
//			echo "<div id='fortunekookie-alert' class='updated fade'><p><strong>".__('FortuneKookie is almost ready.')."</strong> ".__('You must first <a href="http://wordpress.fortunekookie.com/#register" target="_blank">register and enter your FortuneKookie code</a> in the widget for it to work.')."</p></div>";
//		}
//		add_action('admin_notices', 'fortunekookie_alert');
//		return;
//	}
//}

//Main widget function
function widget_fortunekookie_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;

	//Proper name of widget
	$name = __('FortuneKookie');

	function widget_fortunekookie($args) {

		// "$args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys." - These are set up by the theme
		extract($args);

		// These are our own options
		$options 	= get_option('widget_fortunekookie');
		$fk_code 	= $options['fk_code'];  	// Your FortuneKookie 32-digit security code for WordPress Widget (register on http://wordpress.fortunekookie.com/#register for your code)
		$show_logo	= $options['show_logo'];  	// Option to show the FortuneKookie logo
		$show_link	= $options['show_link'];  	// Option to show a link to the graphical version of the fortune cookie
		$show_back  = $options['show_back'];  	// Option to show backside of the fortune
		$show_nbr   = $options['show_nbr'];  	// Option to show lucky numbers on the fortune
		$show_fk	= $options['show_fk'];  	// Option to show the Powered by FortuneKookie link
		$title   	= $options['title'];    	// Title in sidebar for widget
		
		//Instantiate the FortuneKookie class
		$fortune = new FortuneKookie(FK_USERNAME,FK_PASSWORD);
		
		//Retrieve the fortunes in xml format
		$fk_xml = $fortune->getFortunes(FK_NBR, $fk_code);
		
		//Parse the XML into fortune cookie fortune components of one (1) cookie ($fortune_back and $fortune_nbr are only retrieved if those options are selected.
		$fortune_id = get_fortune_id($fk_xml);
		$fortune_front = get_tag_contents($fk_xml,"front");

		//Display the random fortune cookie fortune and options
		$fortune_cookie = "<li>\n"; 
		if ($show_link)
			{
			$fortune_cookie .= "<a href=\"http://www.fortunekookie.com/view.php?qs=".$fortune_id."\" target=\"_blank\">".$fortune_front."</a>";
			}
		else
			{
			$fortune_cookie .= $fortune_front;
			}
		$fortune_cookie .= "</li>\n";

		if ($show_back)
			{
			//Parse the XML into fortune cookie fortune back word(s) then display if option has been selected
			$fortune_back = get_tag_contents($fk_xml,"back");
			$fortune_cookie .= "<li>\n";
				$fortune_cookie .= $fortune_back;
			$fortune_cookie .= "</li>\n";
			}

		if ($show_nbr)
			{
			//Parse the XML into fortune cookie fortune lucky numbers then display if option has been selected
			$fortune_nbr = get_lucky_nbr($fk_xml);
			$fortune_cookie .= "<li>\n";
				$fortune_cookie .= $fortune_nbr;
			$fortune_cookie .= "</li>\n";
			}
		
        // Output
		echo $before_widget;

		// start
		echo '<div id="fortunekookie_widget">'
              .$before_title.$title.$after_title . "\n";
 		if ($show_logo)
			{
			echo '<br /><img style="width:175px; display:block; margin-left:auto; margin-right:auto;" title="Save and Share your fortunes at FortuneKookie.com" src="'.FK_IMAGE_DIR.'fk_logo.png" />';
			}
        echo '<ul id="fortunekookie_spot">' . $fortune_cookie . '</ul>';
		if ($show_fk)
			{
			echo '<br /><h5>Powered by <a href="http://www.fortunekookie.com/" target="_blank">FortuneKookie.com</a></h5>';
			}
        echo '</div>';
		
		// echo widget closing tag
		echo $after_widget;
	}

	// Settings (configuration) form
	function widget_fortunekookie_control() {

		// Get options
		$options = get_option('widget_fortunekookie');
		// options exist? if not set defaults
		if ( !is_array($options) )
			$options = array(
							'fk_code' 	=> '1234567890abcdef1234567890abcdef',
							'show_logo'	=> '1',
							'show_link'	=> '1',
							'show_back' => '1',
							'show_nbr'	=> '1',
							'show_fk'	=> '1',
							'title'   	=> 'FortuneKookie Fortune'
						);

        // form posted?
		if ( $_POST['fortunekookie-submit'] ) {

			// Remember to sanitize and format use input appropriately.
			$options['fk_code'] 	= strip_tags(stripslashes(strtolower($_POST['fortunekookie-code'])));
			$options['show_logo'] 	= $_POST['fortunekookie-logo'];
			$options['show_link'] 	= $_POST['fortunekookie-link'];
			$options['show_back']   = $_POST['fortunekookie-back'];
			$options['show_nbr'] 	= $_POST['fortunekookie-nbr'];
			$options['show_fk'] 	= $_POST['fortunekookie-fk-show'];
			$options['title']   	= strip_tags(stripslashes(trim($_POST['fortunekookie-title'])));
			update_option('widget_fortunekookie', $options);
		}

		// Get options for form fields to show
		$fk_code	= htmlspecialchars($options['fk_code'], ENT_QUOTES);
		$show_logo	= intval($options['show_logo']);
		$show_link	= intval($options['show_link']);
		$show_back	= intval($options['show_back']);
		$show_nbr	= intval($options['show_nbr']);
		$show_fk	= intval($options['show_fk']);
		$title		= htmlspecialchars($options['title'], ENT_QUOTES);

		//Prepare defaults and values for radio buttons
		if($show_logo)
			{$show_logo_on = " checked=\"checked\"";}
		else
			{$show_logo_off = " checked=\"checked\"";}

		if($show_link)
			{$show_link_on = " checked=\"checked\"";}
		else
			{$show_link_off = " checked=\"checked\"";}

		if($show_back)
			{$show_back_on = " checked=\"checked\"";}
		else
			{$show_back_off = " checked=\"checked\"";}

		if($show_nbr)
			{$show_nbr_on = " checked=\"checked\"";}
		else
			{$show_nbr_off = " checked=\"checked\"";}

		if($show_fk)
			{$show_fk_on = " checked=\"checked\"";}
		else
			{$show_fk_off = " checked=\"checked\"";}


		// The form fields
		echo '<p style="text-align:left;">
				<label for="fortunekookie-title">' . __('Title:') . '
				<input style="width: 190px;" id="fortunekookie-title" name="fortunekookie-title" type="text" value="'.$title.'" />
				</label></p>';
		echo '<p style="text-align:left;">' . __('FortuneKookie logo:') . '
				<input id="fortunekookie-logo-on" name="fortunekookie-logo" type="radio" value="1"'.$show_logo_on.' /><label for="fortunekookie-logo-on">On</label>
				<input id="fortunekookie-logo-off" name="fortunekookie-logo" type="radio" value="0"'.$show_logo_off.' /><label for="fortunekookie-logo-off">Off</label>
				</label></p>';
		echo '<p style="text-align:left;">' . __('Link to cookie graphic:') . '
				<input id="fortunekookie-link-on" name="fortunekookie-link" type="radio" value="1"'.$show_link_on.' /><label for="fortunekookie-link-on">On</label>
				<input id="fortunekookie-link-off" name="fortunekookie-link" type="radio" value="0"'.$show_link_off.' /><label for="fortunekookie-link-off">Off</label>
				</label></p>';
		echo '<p style="text-align:left;">' . __('Show back of fortune:') . '
				<input id="fortunekookie-back-on" name="fortunekookie-back" type="radio" value="1"'.$show_back_on.' /><label for="fortunekookie-back-on">On</label>
				<input id="fortunekookie-back-off" name="fortunekookie-back" type="radio" value="0"'.$show_back_off.' /><label for="fortunekookie-back-off">Off</label>
				</label></p>';
		echo '<p style="text-align:left;">' . __('Show lucky numbers:') . '
				<input id="fortunekookie-nbr-on" name="fortunekookie-nbr" type="radio" value="1"'.$show_nbr_on.' /><label for="fortunekookie-nbr-on">On</label>
				<input id="fortunekookie-nbr-off" name="fortunekookie-nbr" type="radio" value="0"'.$show_nbr_off.' /><label for="fortunekookie-nbr-off">Off</label>
				</label></p>';
		echo '<p style="text-align:left;">' . __('Powered by FortuneKookie:') . '
				<input id="fortunekookie-fk-show-on" name="fortunekookie-fk-show" type="radio" value="1"'.$show_fk_on.' /><label for="fortunekookie-fk-show-on">On</label>
				<input id="fortunekookie-fk-show-off" name="fortunekookie-fk-show" type="radio" value="0"'.$show_fk_off.' /><label for="fortunekookie-fk-show-off">Off</label>
				</label></p>';
		echo '<p style="text-align:left;">
				<label for="fortunekookie-code">' . __('FortuneKookie 32-digit code:') . '
				<br /><input style="width: 230px;" id="fortunekookie-code" name="fortunekookie-code" type="text" maxlength=32 value="'.$fk_code.'" />
				</label></p>';
		echo '<input type="hidden" id="fortunekookie-submit" name="fortunekookie-submit" value="1" />';
	}

	$widget_ops = array(
	    'classname' => 'widget_fortunekookie',
	    'description' => __('Plugin adds a sidebar widget to display a random fortune cookie fortune.'));
	
	$control_ops = array(
	    'width' => 250,
	    'height' => 200,
	    'id_base' => 'widget_fortunekookie_control');

	// Register widget for use
	wp_register_sidebar_widget('widget_fortunekookie-1', $name, 'widget_fortunekookie', $widget_ops);

	// Register settings for use
	wp_register_widget_control('widget_fortunekookie-1', $name, 'widget_fortunekookie_control', $control_ops);
}

function fortunekookie_display($display_type) {

	//Pull options from the database
	$options = get_option('widget_fortunekookie');
	$fk_code = $options['fk_code'];

	//Instantiate the FortuneKookie class
	$fortune_inpost = new FortuneKookie(FK_USERNAME,FK_PASSWORD);
	
	//Retrieve the fortunes in xml format
	$fk_inpost_xml = $fortune_inpost->getFortunes(FK_NBR, $fk_code);
	
	//Parse the XML into fortune cookie fortune components of one (1) cookie.
	$fortune_inpost_id = get_fortune_id($fk_inpost_xml);
	$fortune_inpost_front = get_tag_contents($fk_inpost_xml,"front");
	$fortune_inpost_back = get_tag_contents($fk_inpost_xml,"back");
	$fortune_inpost_nbr = get_lucky_nbr($fk_inpost_xml);

	switch ($display_type) {
	    case 1:
			$inpost_fortune_cookie = $fortune_inpost_front."<br />";
			$inpost_fortune_cookie .= $fortune_inpost_back."<br />";
			$inpost_fortune_cookie .= $fortune_inpost_nbr."<br />";
	        break;
	    case 2:
			$inpost_fortune_cookie = $fortune_inpost_front."<br />";
			$inpost_fortune_cookie .= $fortune_inpost_nbr."<br />";
	        break;
	    case 3:
			$inpost_fortune_cookie = $fortune_inpost_front."<br />";
			$inpost_fortune_cookie .= $fortune_inpost_back."<br />";
	        break;
	    case 4:
			$inpost_fortune_cookie = $fortune_inpost_front."<br />";
	        break;
	    default:
			$inpost_fortune_cookie = "ERROR: Value must be between 1 and 4<br />Example: [fortunekookie|random=2]";
	}

//	$inpost_fortune_cookie .= "<h5>Powered by <a href=\"http://www.fortunekookie.com/\" target=\"_blank\">FortuneKookie.com</a></h5><br />";

	return $inpost_fortune_cookie;
}

function fortunekookie_inpost( $text ) {
	$fk_inpost_key = strpos($text,"[fortunekookie|random=");
 	if ($fk_inpost_key !== FALSE)
  		{
	    $text = preg_replace( "/\[fortunekookie\|random=(\d+)\]/ie", "fortunekookie_display('\\1')", $text );
		}
	return $text;
}

/**
 * On activation, alert the blog admin - Need user to register his / her own security code.
 */
//add_action('init', 'fortunekookie_activate');

/**
 * Allow random FortuneKookie fortunes to appear within a Page or a Post
 */
add_filter('the_content', 'fortunekookie_inpost', 7);

/**
 * Run code and initialize the widget
 */
add_action('widgets_init', 'widget_fortunekookie_init');

?>