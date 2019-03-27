<?php
/*
Plugin Name: Mobile-URL-Redirect
Description: Point Users To Mobile URL from Home
Author: Kenneth Cremeans
Version: 1.0
Author URI: https://keybase.io/kcseb
*/

/*	Copyright 2018 Kenneth Cremeans (email : kcrem@hfhosting.us)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

*/

$ios_mobile_redirect = new IOS_Mobile_Redirect();

register_uninstall_hook( __FILE__, 'uninstall_mobile_redirect' );
function uninstall_mobile_redirect() {
	delete_option( 'mobileredirecturl' );
	delete_option( 'mobileredirecttoggle' );
	delete_option( 'mobileredirectmode' );
	delete_option( 'mobileredirecthome' );
}

class IOS_Mobile_Redirect{

	function __construct() { //Initialise Function
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'template_redirect', array( &$this, 'template_redirect' ) );
		if ( get_option( 'mobileredirecttoggle' ) == 'true' )
			update_option( 'mobileredirecttoggle', true );
	}

	function admin_init() {
		add_filter( 'plugin_action_links_'. plugin_basename( __FILE__ ), array( &$this, 'plugin_action_links' ), 10, 4 );
	}

	function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		if ( is_plugin_active( $plugin_file ) )
			$actions[] = '<a href="' . admin_url('options-general.php?page=mobile-url-redirect/mobile-redirect.php') . '">Configure</a>';
		return $actions;
	}

	function admin_menu() {
		add_submenu_page( 'options-general.php', __( 'Mobile Redirect', 'mobile-redirect' ), __( 'Mobile Redirect', 'mobile-redirect' ), 'administrator', __FILE__, array( &$this, 'page' ) );
	}

	function page() { //Admin Options

		if ( isset( $_POST['mobileurl'] ) ) {
			update_option( 'mobileredirecturl', esc_url_raw( $_POST['mobileurl'] ) );
			update_option( 'mobileredirecttoggle', isset( $_POST['mobiletoggle'] ) ? true : false );
			update_option( 'mobileredirectmode', intval( $_POST['mobilemode'] ) );
			update_option( 'mobileredirecthome', isset( $_POST['mobileredirecthome'] ) );
			update_option( 'mobileredirectonce', isset( $_POST['mobileredirectonce'] ) ? true : false );
			update_option( 'mobileredirectoncedays', intval( $_POST['mobileredirectoncedays'] ) );

			echo '<div class="updated"><p>' . __( 'Updated', 'mobile-redirect' ) . '</p></div>';
		}	?>
		<div class="wrap"><h2><?php _e( 'Mobile Redirect', 'mobile-redirect' ); ?></h2>
		<p>
			<?php _e( 'If the checkbox is checked, and a valid URL is inputted, this site will redirect to the specified URL when visited by a mobile device.', 'mobile-redirect' ); ?>
		</p>
		<form method="post">
		<p>
			<input type="checkbox" value="1" name="mobiletoggle" id="mobiletoggle" <?php checked( get_option('mobileredirecttoggle', ''), 1 ); ?> />
			<label for="mobiletoggle"><?php _e( ' <strong>Enable Redirect</strong>', 'mobile-redirect' ); ?></label>
		</p>
		<p>
			<label for="mobileurl"><?php _e( '<strong>Redirect URL:</strong>', 'mobile-redirect' ); ?><br/>
			<input type="text" name="mobileurl" id="mobileurl" value="<?php echo esc_url( get_option('mobileredirecturl', '') ); ?>" /></label>
		</p>
		<p>
			<label for="mobilemode"><?php _e( ' <strong>Redirect Mode</strong>', 'mobile-redirect' ); ?>
			</label><br/>
			<select id="mobilemode" name="mobilemode">
				<option value="301" <?php selected( get_option('mobileredirectmode', 301 ), 301 ); ?>>301</option>
				<option value="302" <?php selected( get_option('mobileredirectmode'), 302 ); ?>>302</option>
			</select>
		</p>
		<p>
			<input type="checkbox" value="1" name="mobileredirecthome" id="mobileredirecthome" <?php checked( get_option('mobileredirecthome', ''), 1 ); ?> />
			<label for="mobileredirecthome"><?php _e( ' <strong>Only Redirect Homepage</strong>', 'mobile-home' ); ?>
			</label>
		</p>
		<p>
			<input type="checkbox" value="1" name="mobileredirectonce" id="mobileredirectonce" <?php checked( get_option('mobileredirectonce', ''), 1 ); ?> />
			<label for="mobileredirectonce"><?php _e( ' <strong>Redirect Once</strong>', 'mobile-redirect' ); ?>
			</label>
		</p>
		<p>
			<label for="mobileredirectoncedays"><?php _e( '<strong>Redirect Once Cookie Expiry:</strong>', 'mobile-redirect' ); ?><br/>
			<input type="text" size="5" maxlength="7" name="mobileredirectoncedays" id="mobileredirectoncedays" value="<?php echo esc_attr( get_option('mobileredirectoncedays', 7 ) ); ?>" /> days.</label><br/>
			<span class="description">If <em>Redirect Once</em> is checked, a cookie will be set for the user to prevent them from being continually redirected to the same page. This cookie will expire by default after 7 days. Setting to zero or less is effectively the same as unchecking Redirect Once</span>
		</p>
			<?php submit_button(); ?>
		</form>
		</div>

		<div class="copyFooter">Plugin written by <a href="https://keybase.io/kcseb">Kenneth C.</a>.</div>
		<?php
	} //Page Function End

	function template_redirect() {
		if ( ! wp_is_mobile() )
				return;

		if( get_option('mobileredirecthome') == 1){
			if( ! is_front_page() )	return;
		}

		//Disabled / Not Enabled
		if ( ! get_option('mobileredirecttoggle') )
			return;

		$mr_url = esc_url( get_option('mobileredirecturl', '') );
		//URL Empty
		if ( empty( $mr_url ) )
			return;

		$cur_url = esc_url("http://". $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] );
		$cookiedays = intval( get_option( 'mobileredirectoncedays', 7 ) );
		//Expire cookie if negative number - Or just uncheck redirect once. 
		if ( $cookiedays <= 0 || ! get_option( 'mobileredirectonce' ) ) {
			setcookie( 'mobile_single_redirect', true, time()-(60*60), '/' );
			unset($_COOKIE['mobile_single_redirect']);
		}

		//Dont self-redirect
		if ( $mr_url != $cur_url ) {
			if ( isset( $_COOKIE['mobile_single_redirect'] ) ) return;

			if ( get_option( 'mobileredirectonce', '' ) )
				setcookie( 'mobile_single_redirect', true, time()+(60*60*24*$cookiedays ), '/' );
			header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate"); //from amclin
			wp_redirect( $mr_url, get_option('mobileredirectmode', '301' ) );
			exit;
		}

	}

}
//That's it... it's the end. Go away now. 
