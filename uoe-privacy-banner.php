<?php
/**
 * Plugin Name: UoE Privacy Banner
 * Description: Adds a cookie acceptance interface at the top of each page, based on the EdGEL cookie consent system.
 * Author: DLAM Applications Development Team
 * Version: 0.9
 *
 * @package WordPress
 * @subpackage uoe-privacy-banner
 * @since 0.9
 * @version 0.9
 **/

/**
 * Add in EdGEL Cookie CSS, JavaScript
 *
 * @return void
 */
function enqueue_edgel_cookie_js() {
	wp_enqueue_script( 'edgel-cookie-js', plugin_dir_url( __FILE__ ) . 'js/edgel-cookie.js', false, '1.0', false );
	wp_enqueue_style( 'edgel-cookie-stylesheet', plugin_dir_url( __FILE__ ) . 'css/edgel-cookie.min.css', false, '1.0', 'all' );
}


/**
 * Get in front of the the_content() output.
 *
 * @param string $content the_content() in a post.
 * @return string
 */
function rewrite_iframe_output( $content ) {
	// Locate and extract all occurances of an iframe (added to array[0][x] (the full iframe match) and array[1][x] (the src contents).
	preg_match_all( '/.*iframe.*? src="(.*?)".*<\/iframe>/', $content, $iframe_matches );
	$modified_content_output = $content;
	$index = 0;
	foreach ( $iframe_matches[0] as $iframe_element ) {
		$modified_content_output = str_replace( $iframe_element, '<iframe src="about:blank" data-uoe-cookie-src="' . $iframe_matches[1][ $index ] . '" width="560" height="315" frameborder="0" allowfullscreen></iframe>', $modified_content_output );
		$index++;
	}
	return $modified_content_output;
}


/**
 * Outputs Google Analytics tracking code information, and respects the user privacy choices made in the EdGEL cookie jar.
 *
 * @return string
 */
function output_google_analytics_blocking_code() {
	$analytics_tracking_code = '';
	$output = '';
	if ( '' !== get_site_option( 'ed_cookie_public_google_analytics_code' ) ) {
		$analytics_tracking_code = get_site_option( 'ed_cookie_public_google_analytics_code' );
	}
	$output .= '
		<script type="text/javascript">
			/**
				* Disable Google Analytics according to cookie settings.
			*/
			(function () {
				\'use strict\';
				// Update some settings
				EdGel.cookieSettings.subscribe(function () {
					if ( !EdGel.cookieSettings.allowed( \'performance\' ) ) {
	';
	if ( '' !== $analytics_tracking_code ) {
		$output .= 'window[\'ga-disable-' . $analytics_tracking_code . '\'] = true;
		';
	}
	$output .= '
					}
				});
			})();
		</script>
	 ';
	echo $output;
}


/**
 * Creates the plug-in admin page and builds it interface.
 *
 * @return void
 */
add_action( 'admin_menu', 'register_ed_public_cookies_admin_page' );
function register_ed_public_cookies_admin_page(){
	// Check that the user has the appropriate permissions to view the settings page.
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
    add_menu_page( 'EdGEL banner', 'EdGEL banner', 'manage_options', 'ed-public-cookies-menu', 'cookie_banner_menu_output', 'dashicons-slides' );
   	add_action( 'admin_init', 'cookie_banner_register_settings' );
}


/**
 * Registers plugin settings.
 *
 * @return void
 */
function cookie_banner_register_settings() {
	$analytics_args = array(
		'type' => 'string',
		'default' => NULL
	);
	register_setting( 'ed-public-cookies-group', 'ed_public_cookies_ga_property', $analytics_args );

	$wrap_iframes_args = array(
		'type' => 'boolean',
		'default' => TRUE
	);
	register_setting( 'ed-public-cookies-group', 'ed_public_cookies_wrap_iframes', $wrap_iframes_args );
}

function cookie_banner_menu_output() { ?>
	
	<div class="wrap">
		<h2>EdGEL privacy banner settings</h2>
		<form method="post" action="options.php">
			<input type='hidden' name='action' value='save' />
			<?php
				settings_fields( 'ed-public-cookies-group' );
				do_settings_sections( 'ed-public-cookies-group' );
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="ed_public_cookies_ga_property">Your Google Analytics tracking ID</label>
					</th>
					<td>
						<input type="text" name="ed_public_cookies_ga_property" id="ed_public_cookies_ga_property" value="<?php echo esc_attr( get_option( 'ed_public_cookies_ga_property' ) ); ?>" class="regular-text input-box" aria-describedby="ed_public_cookies_ga_property_description" required />
						<p class="description" id="ed_public_cookies_ga_property_description"><strong>This field is required for this plugin to operate properly.</strong> <br/>You can find your tracking ID in the Google Analytics admin area, and going to "Property settings" (usually formatted as UA-123456789-1).</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						Iframe blocking
					</th>
					<td>
						<label for="ed_public_cookies_wrap_iframes">
							<input name="ed_public_cookies_wrap_iframes" type="checkbox" id="ed_public_cookies_wrap_iframes" <?php echo get_option( 'ed_public_cookies_wrap_iframes' ) ? 'checked' : ''; ?> aria-describedby="ed_public_cookies_wrap_iframes_description">
							Block iframe loading <span style="color: #777;">(recommended)</span>
						</label>
						<p class="description" id="ed_public_cookies_wrap_iframes_description">Prevent iframe elements from loading their content until a visitor interacts with them.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Save changes' ); ?>
		</form>
	</div>
	<?php
}

add_action( 'wp_enqueue_scripts', 'enqueue_edgel_cookie_js' );

if ( get_option( 'ed_public_cookies_wrap_iframes' ) == 1 ) {
	add_filter( 'the_content', 'rewrite_iframe_output' );
}

add_action( 'wp_footer', 'output_google_analytics_blocking_code', 0 );
