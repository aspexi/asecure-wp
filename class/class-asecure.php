<?php
/**
 * ASecure
 *
 * @package ASecure
 */

namespace ASecure;

require_once ABSPATH . '/wp-admin/includes/file.php';
require_once ABSPATH . '/wp-admin/includes/plugin.php';
require_once ABSPATH . '/wp-admin/includes/plugin-install.php';

/**
 * Class ASecure
 *
 * Manage all ASecure features.
 */
class ASecure {

	public static $version = '1.0';

	public static $key_size = 8;

	public $plugin_slug;

	public function __construct( $plugin_file ) {

		$this->plugin_slug = plugin_basename( $plugin_file );

		add_action( 'plugin_action_links_asecure-wp/asecure-me.php', array( &$this, 'plugin_settings_link' ) );
		add_action( 'admin_menu', array( &$this, 'asecure_add_menu' ) );

		add_action('wp_ajax_nopriv_get_wordpress_info', array( &$this, 'asecure_get_info') );
	}

	public function asecure_get_info() {
		$key = get_option('asecure_key');

		if (isset($_POST['function']) && self::decrypt($_POST['function'], $key) === 'asecure_get_info') {

			$site_info = $this->get_siteinfo();
			$plugins_info = $this->get_plugins();
			$latest_wp_version = self::get_latest_wordpress_version();

			$complete_info = array(
				'SiteInfo' => $site_info,
				'PluginsInfo' => $plugins_info,
				'LatestWpVersion' => $latest_wp_version
			);

			header('Content-Type: application/json');
			echo json_encode(array('response' => self::encrypt($complete_info, $key)));
			exit;
		}
	}

	public function asecure_add_menu() {
	    add_submenu_page(
	        'options-general.php',
	        'ASecure.me Plugin Options',
	        'ASecure.me',
	        'manage_options',
	        'asecure_tab',
	        array( &$this, 'asecure_options_page' )
	    );
	}

	public function asecure_options_page() {
		$key = get_option('asecure_key');
	    ?>
	    <div class="wrap">
	        <h2>ASecure.me WordPress Plugin</h2>
		    <p>Your connect key: <span style="color: green; font-size: 20px; font-weight: bold;"><?php echo esc_attr( $key ); ?></span></p>
		    <p>Note: This is preview version of the ASecure.me WordPress plugin which allows you to monitor your WordPress website status from ASecure.me dashboard, mobile application or Telegram bot. Please visit our website <a href="https://asecure.me" target="_blank">asecure.me</a> or <a href="https://t.me/ASecureMe" target="_blank">Telegram Group</a> for more information and updates.</p>
		    <p>You can also start using our Telegram Bot to monitor your websites: <a href="https://t.me/ASecureMeBot" target="_blank">open</a>.</p>
	    </div>
	    <?php
	}

	public function deactivation() {

		$to_delete   = array(
			'asecure_key'
		);

		foreach ( $to_delete as $delete ) {
			if ( get_option( $delete ) ) {
				delete_option( $delete );
				wp_cache_delete( $delete, 'options' );
			}
		}
	}

	public function activation() {

		$to_delete = array(
			'asecure_key',
		);
		foreach ( $to_delete as $delete ) {
			if ( get_option( $delete ) ) {
				delete_option( $delete );
			}
		}

		// Generate the key
		$new_key = self::random_string(self::$key_size);

		self::update_option('asecure_key', $new_key);
	}

	public function plugin_settings_link( $actions ) {
		$href          = admin_url( 'options-general.php?page=asecure_tab' );
		$settings_link = '<a href="' . $href . '">' . __( 'Settings' ) . '</a>';
		array_unshift( $actions, $settings_link );

		return $actions;
	}

	public function get_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins_list = get_plugins();
		$active_plugins = get_option('active_plugins', array());
        $return_list = array();
        $i = 0;
		foreach($plugins_list as $k=>$v) {
            $return_list[$i] = $v;
			$is_active = in_array($k, $active_plugins) ? 'true' : 'false';
			$return_list[$i]['Status'] = $is_active;

			$latest_version = self::get_plugin_latest_version($k);
			$return_list[$i]['LatestVersion'] = $latest_version;

			if (version_compare($v['Version'], $latest_version, '<')) {
	            $return_list[$i]['NeedsUpdate'] = 'true';
	        } else {
	            $return_list[$i]['NeedsUpdate'] = 'false';
	        }

            $i++;
		}

		return $return_list;
	}

	public function get_siteinfo() {
		global $wp_version;

		$blog = get_bloginfo( 'name' );
		$site = get_bloginfo( 'url' ) . '/';

		return array(
			$wp_version,
			$blog,
			$site
		);
	}

	public static function random_string($length, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
		$str   = '';
		$count = strlen( $charset );
		while ($length--) {
			$str .= $charset[ mt_rand( 0, $count - 1 ) ];
		}
		return $str;
	}

	public static function update_option( $option_name, $option_value, $autoload = 'no' ) {
		$updated = add_option( $option_name, $option_value, '', $autoload );
		if ( ! $updated ) {
			$updated = update_option( $option_name, $option_value );
		}
		return $updated;
	}

	public static function encrypt($data, $key) {
        $iv = str_pad($key, 16);
        $key = str_pad($key, 32);

        $encrypted = openssl_encrypt(json_encode($data), 'aes-256-cbc', $key, 0, $iv);
        return bin2hex($iv) . $encrypted;
	}

	public static function decrypt($encryptedData, $key) {
        $iv = hex2bin(substr($encryptedData, 0, 32));
        $key = str_pad($key, 32);
        $encryptedData = (substr($encryptedData, 32));

        return json_decode(openssl_decrypt($encryptedData, 'aes-256-cbc', $key, 0, $iv), true);
	}

	public static function get_plugin_latest_version($plugin_path) {
	    $plugin_slug = basename($plugin_path, '.php');
	    $plugin_info = plugins_api('plugin_information', array('slug' => $plugin_slug));

	    if (is_object($plugin_info) && isset($plugin_info->version)) {
	        return $plugin_info->version;
	    }

	    return '';
	}

	public static function get_latest_wordpress_version() {
	    $response = wp_remote_get('https://api.wordpress.org/core/version-check/1.7/');
	    if (!is_wp_error($response) && isset($response['body'])) {
	        $data = json_decode($response['body']);
	        if (isset($data->offers[0]->current)) {
	            return $data->offers[0]->current;
	        }
	    }
	    return '';
	}
}
