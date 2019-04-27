<?php

/* Activation & Uninstall */

function arc_activate() {

	//Default params
	$options = array(
		"cache" 	 => 48,
		"defaultCountry" => "us",
		"defaultStars" 	 => 5,
		"defaultRecent"	 => 10
	);
	add_option("arc_options", $options);
	
	//Cache directory
	mkdir(APP_ARC_CACHE_DIR, 0777);
}

function arc_uninstall() {
	delete_option("arc_options");
}

/* Menu page */

//Shortcut to the Admin page
add_filter('plugin_action_links', 'arc_plugin_action_links', 10, 2);
	
function arc_plugin_action_links($links, $plugin_file) {
	static $plugin;

	if (!isset($plugin)) {
		$plugin = "appstore-reviews-viewer/appstore-review.php";
	}

	if ($plugin == $plugin_file) {
		$settings_link = '<a href="options-general.php?page=appstore-reviews-viewer/appstore-review-admin.php">Settings</a>';
        	array_unshift($links, $settings_link);
        }

	return $links;
}
	
//Menu
add_action('admin_init', 'arc_register_settings_and_fields');
add_action('admin_menu', 'arc_admin_page');

function arc_admin_page() {
	add_options_page('AppStore Reviews Posts Converter - Options', 'AppStore Reviews Posts Converter', 'manage_options', __FILE__, 'arc_render_admin_form');
}

function arc_register_settings_and_fields() {
	register_setting('arc_options','arc_options');
	add_settings_section('arc_plugin_main_section', 'Main Settings', 'arc_plugin_cb', __FILE__);
	add_settings_field('cache', 'Cache Time: ', 'arc_form_option_cache', __FILE__, 'arc_plugin_main_section');
	add_settings_field('defaultCountry', 'AppStore Country: ', 'arc_form_option_country', __FILE__, 'arc_plugin_main_section');
	add_settings_field('defaultStars', 'Retrieve reviews with at least: ', 'arc_form_option_stars', __FILE__, 'arc_plugin_main_section');
	add_settings_field('defaultRecent', 'Show only last X reviews: ', 'arc_form_option_recent', __FILE__, 'arc_plugin_main_section');
	add_settings_field('defaultPostType', 'Set default post type: ', 'arc_form_option_default_post_type', __FILE__, 'arc_plugin_main_section');
	add_settings_field('defaultPostStatus', 'Set default post status: ', 'arc_form_option_default_post_status', __FILE__, 'arc_plugin_main_section');
	add_settings_field('applicationId', 'Set AppStore application ID: ', 'arc_form_option_app_id', __FILE__, 'arc_plugin_main_section');
}

function arc_plugin_cb() {
	echo "Those are the default parameters for all the shortcodes you use on your site. You can override those parameters for any shortcode (except the cache).";
}

function arc_form_option_cache() {
	$options = get_option("arc_options");
	
	$caches = array(
		"0"   => "Don't cache",
		"1"   => "1 hour",
		"6"   => "6 hours",
		"12"  => "12 hours",
		"24"  => "1 day",
		"48"  => "2 days",
		"168" => "1 week"
	);
	
	echo "<select name='arc_options[cache]'>";
	foreach ($caches as $k => $v) {
		echo "<option value='" . $k . "'" . selected($k, $options['cache'], false) . ">" . $v . "</option>";
	}
	echo "</select>";
	echo "<span style='color:grey;margin-left:2px;'>This option determines how long before the plugin requests new data from Apple's servers.</span>";
}

function arc_form_option_country() {
	$options = get_option("arc_options");
	
	$countries = array(
		"AL" => "Albania",
		"DZ" => "Algeria",
		"AO" => "Angola",
		"AI" => "Anguilla",
		"AG" => "Antigua and Barbuda",
		"AR" => "Argentina",
		"AM" => "Armenia",
		"AU" => "Australia",
		"AT" => "Austria",
		"AZ" => "Azerbaijan",
		"BS" => "Bahamas",
		"BH" => "Bahrain",
		"BB" => "Barbados",
		"BY" => "Belarus",
		"BE" => "Belgium",
		"BZ" => "Belize",
		"BJ" => "Benin",
		"BM" => "Bermuda",
		"BT" => "Bhutan",
		"BO" => "Bolivia",
		"BW" => "Botswana",
		"BR" => "Brazil",
		"BN" => "Brunei Darussalam",
		"BG" => "Bulgaria",
		"BF" => "Burkina Faso",
		"KH" => "Cambodia",
		"CA" => "Canada",
		"CV" => "Cape Verde",
		"KY" => "Cayman Islands",
		"TD" => "Chad",
		"CL" => "Chile",
		"CN" => "China",
		"CO" => "Colombia",
		"CG" => "Congo, Republic of the",
		"CR" => "Costa Rica",
		"HR" => "Croatia",
		"CY" => "Cyprus",
		"CZ" => "Czech Republic",
		"DK" => "Denmark",
		"DM" => "Dominica",
		"DO" => "Dominican Republic",
		"EC" => "Ecuador",
		"EG" => "Egypt",
		"SV" => "El Salvador",
		"EE" => "Estonia",
		"FJ" => "Fiji",
		"FI" => "Finland",
		"FR" => "France",
		"GM" => "Gambia",
		"DE" => "Germany",
		"GH" => "Ghana",
		"GR" => "Greece",
		"GD" => "Grenada",
		"GT" => "Guatemala",
		"GW" => "Guinea-Bissau",
		"GY" => "Guyana",
		"HN" => "Honduras",
		"HK" => "Hong Kong",
		"HU" => "Hungary",
		"IS" => "Iceland",
		"IN" => "India",
		"ID" => "Indonesia",
		"IE" => "Ireland",
		"IL" => "Israel",
		"IT" => "Italy",
		"JM" => "Jamaica",
		"JP" => "Japan",
		"JO" => "Jordan",
		"KZ" => "Kazakhstan",
		"KE" => "Kenya",
		"KR" => "Korea, Republic Of",
		"KW" => "Kuwait",
		"KG" => "Kyrgyzstan",
		"LA" => "Lao, People's Democratic Republic",
		"LV" => "Latvia",
		"LB" => "Lebanon",
		"LR" => "Liberia",
		"LT" => "Lithuania",
		"LU" => "Luxembourg",
		"MO" => "Macau",
		"MK" => "Macedonia",
		"MG" => "Madagascar",
		"MW" => "Malawi",
		"MY" => "Malaysia",
		"ML" => "Mali",
		"MT" => "Malta",
		"MR" => "Mauritania",
		"MU" => "Mauritius",
		"MX" => "Mexico",
		"FM" => "Micronesia, Federated States of",
		"MD" => "Moldova",
		"MN" => "Mongolia",
		"MS" => "Montserrat",
		"MZ" => "Mozambique",
		"NA" => "Namibia",
		"NP" => "Nepal",
		"NL" => "Netherlands",
		"NZ" => "New Zealand",
		"NI" => "Nicaragua",
		"NE" => "Niger",
		"NG" => "Nigeria",
		"NO" => "Norway",
		"OM" => "Oman",
		"PK" => "Pakistan",
		"PW" => "Palau",
		"PA" => "Panama",
		"PG" => "Papua New Guinea",
		"PY" => "Paraguay",
		"PE" => "Peru",
		"PH" => "Philippines",
		"PL" => "Poland",
		"PT" => "Portugal",
		"QA" => "Qatar",
		"RO" => "Romania",
		"RU" => "Russia",
		"ST" => "São Tomé and Príncipe",
		"SA" => "Saudi Arabia",
		"SN" => "Senegal",
		"SC" => "Seychelles",
		"SL" => "Sierra Leone",
		"SG" => "Singapore",
		"SK" => "Slovakia",
		"SI" => "Slovenia",
		"SB" => "Solomon Islands",
		"ZA" => "South Africa",
		"ES" => "Spain",
		"LK" => "Sri Lanka",
		"KN" => "St. Kitts and Nevis",
		"LC" => "St. Lucia",
		"VC" => "St. Vincent and The Grenadines",
		"SR" => "Suriname",
		"SZ" => "Swaziland",
		"SE" => "Sweden",
		"CH" => "Switzerland",
		"TW" => "Taiwan",
		"TJ" => "Tajikistan",
		"TZ" => "Tanzania",
		"TH" => "Thailand",
		"TT" => "Trinidad and Tobago",
		"TN" => "Tunisia",
		"TR" => "Turkey",
		"TM" => "Turkmenistan",
		"TC" => "Turks and Caicos",
		"UG" => "Uganda",
		"GB" => "United Kingdom",
		"UA" => "Ukraine",
		"AE" => "United Arab Emirates",
		"UY" => "Uruguay",
		"US" => "USA",
		"UZ" => "Uzbekistan",
		"VE" => "Venezuela",
		"VN" => "Vietnam",
		"VG" => "Virgin Islands, British",
		"YE" => "Yemen",
		"ZW" => "Zimbabwe"
	);
	
	echo "<select name='arc_options[defaultCountry]'>";
	foreach ($countries as $k => $v) {
		echo "<option value='" . strtolower($k) . "'" . selected(strtolower($k), $options['defaultCountry'], false) . ">" . $v . "</option>";
	}
	echo "</select>";
	echo "<span style='color:grey;margin-left:2px;'>This option determines which AppStore country you want to download the reviews from.</span>";

}

function arc_form_option_stars() {
	$options = get_option("arc_options");
	
	$stars = array(
		"1" => "1 star",
		"2" => "2 stars",
		"3" => "3 stars",
		"4" => "4 stars",
		"5" => "5 stars"
	);
	
	echo "<select name='arc_options[defaultStars]'>";
	foreach ($stars as $k => $v) {
		echo "<option value='" . $k . "'" . selected($k, $options['defaultStars'], false) . ">" . $v . "</option>";
	}
	echo "</select>";
}

function arc_form_option_recent() {
	$options = get_option("arc_options");
	
	$recent = array(5, 10, 15, 20, 25, 100, 200, 500);
	
	echo "<select name='arc_options[defaultRecent]'>";
	foreach ($recent as $k) {
		echo "<option value='" . $k . "'" . selected($k, $options['defaultRecent'], false) . ">" . $k . "</option>";
	}
	echo "</select>";
}
function arc_form_option_default_post_type() {
	$options = get_option("arc_options");

	$postTypes = get_post_types();

	echo "<select name='arc_options[defaultPostType]'>";
	foreach ($postTypes as $k) {
		echo "<option value='" . $k . "'" . selected($k, $options['defaultPostType'], false) . ">" . $k . "</option>";
	}
	echo "</select>";
}

function arc_form_option_default_post_status() {
	$options = get_option("arc_options");

	$postTypes = ['draft', 'publish', 'pending', 'future', 'private'];

	echo "<select name='arc_options[defaultPostStatus]'>";
	foreach ($postTypes as $k) {
		echo "<option value='" . $k . "'" . selected($k, $options['defaultPostStatus'], false) . ">" . $k . "</option>";
	}
	echo "</select>";
}

function arc_form_option_app_id() {
	$options = get_option("arc_options");

	echo "<input name='arc_options[applicationId]' value='".$options['applicationId']."'>";

}

function arc_render_admin_form() {
?>
    <p>If you want to help this project you can donate below:</p>
    <p>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick" />
            <input type="hidden" name="hosted_button_id" value="SUZX9ZC8NR256" />
            <input type="image" src="https://www.paypalobjects.com/en_US/RU/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
            <img alt="" border="0" src="https://www.paypal.com/en_RU/i/scr/pixel.gif" width="1" height="1" />
        </form>
    </p>

	<br/>
	<form method="post" action="options.php">
		<?php
			settings_fields('arc_options');
			do_settings_sections(__FILE__);
		?>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
    <br />

    <h3>You can PULL reviews now</h3>
    <p>
        To use the plugin please manually press button "Pull reviews" below. If all fields are OK reviews will be saved as posts
    </p>
    <p><a href="<?= admin_url('admin-post.php') ?>?action=arc_update_reviews" class="button-primary">Pull reviews</a></p>

<?php
}