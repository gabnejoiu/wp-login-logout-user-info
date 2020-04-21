<?php
/**
 * Plugin Name: User Log Info
 * Description: Log user info:
 * - username and role,
 * - IP address from where the user is logging in or out,
 * - date and time when the event happened.
 * Plugin URI: http://www.art-web.ro
 * Author: Gabriel Nejoiu
 * Author URI: http://www.art-web.ro
 * Version: 1.0
 * License: GPL2
 *
 * @package GB
 */

define( 'USER_LOG_INFO', 1.0 );
define( 'TEXT_DOMAIN', 'user-log-info' );
define( 'USER_LOG_INFO_ASSETS_VER', '20180226.1530' );


/**
 * Class for CF Tax Rate Plugin
 */
class UserLogInfo
{
	
	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;
	/**
	 * @var string
	 */
	private static $message;
	public static $file_path;
	
	/**
	 * Get active object instance
	 *
	 * @access public
	 * @static
	 *
	 * @return object
	 */
	public static function get_instance()
	{
		
		if ( ! self::$instance ) {
			self::$instance = new UserLogInfo();
		}
		
		return self::$instance;
	}
	
	/**
	 * Class constructor, includes and init method.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function __construct()
	{
		self::$message = $this->update_info();
		$this->init();
		self::$file_path = get_option( 'file_path' );
	}
	
	
	/**
	 * Run action and filter hooks.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function init()
	{
		
		add_action( 'admin_menu', array ( get_called_class(), 'admin_menu' ) );
		
	}
	
	/**
	 * Add the new menu in settings section.
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function admin_menu()
	{
		
		add_submenu_page(
			'options-general.php',
			esc_html__( 'User Log Setup', TEXT_DOMAIN ),
			esc_html__( 'User Log Setup', TEXT_DOMAIN ),
			'manage_options',
			'user-log-settings',
			array ( get_called_class(), 'plugin_settings' )
		);
	}
	
	/**
	 * Save form data.
	 *
	 * @access public
	 * @static
	 *
	 * @return string
	 */
	public static function update_info()
	{
		if ( isset( $_REQUEST[ 'file_path' ] ) ) {
			if ( update_option( 'file_path', $_REQUEST[ 'file_path' ] ) ) {
				return 'Path was Updated';
			}
		}
		
		return '';
	}
	
	/**
	 * Returns a listing of all directories in the specified folder and all subdirectories up to 100 levels deep.
	 * The depth of the recursiveness can be controlled by the $levels param.
	 *
	 * @param string $folder Optional. Full path to folder. Default empty.
	 * @param int $levels Optional. Levels of folders to follow, Default 100 (PHP Loop limit).
	 * @param string[] $exclusions Optional. List of folders and files to skip.
	 *
	 * @return bool|string[] False on failure, else array of files.
	 * @since 2.6.0
	 * @since 4.9.0 Added the `$exclusions` parameter.
	 *
	 */
	static function user_list_directories( $folder = '', $levels = 100, $exclusions = array () )
	{
		if ( empty( $folder ) ) {
			return false;
		}
		
		$folder = trailingslashit( $folder );
		
		if ( ! $levels ) {
			return false;
		}
		
		$files = array ();
		
		$dir = @opendir( $folder );
		if ( $dir ) {
			while ( ( $file = readdir( $dir ) ) !== false ) {
				// Skip current and parent folder links.
				if (
					in_array( $file, array ( '.', '..' ), true ) OR
					strpos( $file, '.' ) !== false OR
					strpos( $file, 'plugins' ) !== false OR
					strpos( $file, 'upgrade' ) !== false
				) {
					continue;
				}
				
				// Skip hidden and excluded files.
				if ( '.' === $file[ 0 ] || in_array( $file, $exclusions, true ) ) {
					continue;
				}
				
				if ( is_dir( $folder . $file ) ) {
					$files2 = self::user_list_directories( $folder . $file, $levels - 1 );
					if ( $files2 ) {
						$files = array_merge( $files, $files2 );
					} else {
						$files[] = $folder . $file . '/';
					}
				} else {
					$files[] = $folder . $file;
				}
			}
			
			closedir( $dir );
		}
		
		return $files;
	}
	
	/**
	 * Output the custom plugin settings page content.
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function plugin_settings()
	{
		
		$files    = self::user_list_directories( ABSPATH . 'wp-content', $levels = 10 );
		$filepath = get_option( 'file_path' );
		?>
		
		<div id="" class="postbox ">
			<div class="hndle inside ui-sortable-handle">
				<h1><?php _e( 'Log Info Settings', TEXT_DOMAIN ); ?></h1>
			</div>
			<div class="inside">
				<div class="main">
					<?php
					if ( ! empty( self::$message ) ) {
						echo '<p>' . self::$message . '</p>';
					}
					?>
					
					<form action="/wp-admin/options-general.php?page=user-log-settings" method="POST">
						<p>
							<label> Save path: <br/>
								<select name="file_path">
									<?php
									if ( ! empty( $files ) ) {
										foreach ( $files as $file ) {
											$selected = '';
											if ( $filepath == $file ) {
												$selected = 'selected="selected"';
											}
											echo '<option value= "' . $file . '" ' . $selected . '>' . $file . '</option>';
										}
									}
									?>
								</select>
								<?php echo self::file_name(); ?>
							</label>
						</p>
						<p class="submit">
							<input type="submit" name="cf_submit_rates" id="submit" class="button button-primary"
							       value="Save Path">
						</p>
					</form>
				</div>
			</div>
		</div>
		
		<script>
			jQuery(document).ready(function () {

				jQuery('#cf_timezone').val(new Date().getTimezoneOffset() * 60 * (-1));

				jQuery('.datepicker').datetimepicker({
					format: 'Y-m-d H:i',
					formatTime: 'H:i',
					formatDate: 'Y-m-d',
					startDate: new Date(),
				});
			});
		</script>
		
		<?php
	}
	
	public static function file_name()
	{
		// Set file name.
		return 'user-log-file.txt';
	}
	
	public static function get_the_user_ip()
	{
		if ( ! empty( $_SERVER[ 'HTTP_CLIENT_IP' ] ) ) {
			//check ip from share internet
			$ip = $_SERVER[ 'HTTP_CLIENT_IP' ];
		} elseif ( ! empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
			//to check ip is pass from proxy
			$ip = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
		} else {
			$ip = $_SERVER[ 'REMOTE_ADDR' ];
		}
		
		return apply_filters( 'wpb_get_ip', $ip );
	}
	
	/**
	 * Log user info on login.
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function log_user_info_login( $login )
	{
		$user     = get_user_by( 'login', $login );
		$filepath = self::$file_path . self::file_name();
		
		$data = date( "Y-m-d H:i:s" ) . PHP_EOL;
		$data .= ' - Action < LOGIN > ' . PHP_EOL;
		$data .= ' - User Login < ' . $user->data->user_login . ' >  ' . PHP_EOL;
		$data .= ' - ID < ' . $user->data->ID . ' > ' . PHP_EOL;
		$data .= ' - Role < ' . $user->roles[ 0 ] . ' > ' . PHP_EOL;
		$data .= ' - IP < ' . self::get_the_user_ip() . ' > ' . PHP_EOL;
		$file = fopen( $filepath, 'a' );
		fwrite( $file, $data );
		fclose( $file );
	}
	
	/**
	 * Log user info on logout.
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function log_user_info_logout()
	{
		
		$filepath = self::$file_path . self::file_name();
		$user     = wp_get_current_user();
		
		$data = date( "Y-m-d H:i:s" ) . PHP_EOL;
		$data .= ' - Action < LOGOUT > ' . PHP_EOL;
		$data .= ' - User Login < ' . $user->data->user_login . ' >  ' . PHP_EOL;
		$data .= ' - ID < ' . $user->data->ID . ' > ' . PHP_EOL;
		$data .= ' - Role < ' . $user->roles[ 0 ] . ' > ' . PHP_EOL;
		$data .= ' - IP < ' . self::get_the_user_ip() . ' > ' . PHP_EOL;
		$file = fopen( $filepath, 'a' );
		fwrite( $file, $data );
		fclose( $file );
		
	}
	
}

// Instantiate the class.
UserLogInfo::get_instance();

// Log user data.
add_action( 'wp_login', array ( 'UserLogInfo', 'log_user_info_login' ) );
add_action( 'clear_auth_cookie', array ( 'UserLogInfo', 'log_user_info_logout' ) );
