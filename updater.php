<?php
/*                                                                              */

if ( ! class_exists( 'WP_Stream_Updater_0_1' ) ) {
	class WP_Stream_Updater_0_1 {

		const VERSION = 0.1;

		const API_URL = 'https://wp-steam.org/api';

		static $instance;

		public $plugins = array();

		public static function instance() {
			if ( empty( self::$instance ) ) {
				$class = get_called_class();
				self::$instance = new $class;
			}
			return self::$instance;
		}

		public function __construct() {
			$this->setup();
		}

		public function setup() {
			// Override requests for plugin information
			add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
			// Check for updates
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check' ), 20, 3 );
		}

		public function register( $plugin_file ) {
			$this->plugins[$plugin_file] = preg_match( '#([a-z\-]+).php#', $plugin_file, $match ) ? $match[1] : null;
		}

		public function info( $result, $action = null, $args = null ) {
			if ( $action != 'plugin_information' || ! in_array( $args->slug, $this->plugins )  ) {
				return $result;
			}

			$url      = apply_filters( 'wp-stream-update-api', self::API_URL, $action );
			$options  = array(
				'body' => array(
					'a' => $action,
					'slug'   => $args->slug,
				),
			);
			$response = wp_remote_get( $url, $options );

			if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
				wp_die( __( 'Could not connect to Stream update center.', 'stream-notifications' ) );
			}

			$body = wp_remote_retrieve_body( $response );
			do_action( 'wp-stream-update-api-response', $body, $response, $url, $options );

			$info = (object) json_decode( $body, true );
			return $info;
		}

		public function check( $transient ) {
			if ( empty( $transient->checked ) ) {
				return $transient;
			}
			$response = (array) $this->request( array_intersect_key( $transient->checked, $this->plugins ) );
			if ( $response ) {
				$transient->response = array_merge( $transient->response, $response );
			}
			return $transient;
		}

		public function request( $plugins ) {
			$action = 'update';

			$url      = apply_filters( 'wp-stream-update-api', self::API_URL, $action );
			$options  = array(
				'body' => array(
					'a'       => $action,
					'plugins' => $plugins,
					'name'    => get_bloginfo( 'name' ),
					'url'     => get_bloginfo( 'url' ),
					'license' => get_option( 'stream-license' ),
				),
			);

			$response = wp_remote_post( $url, $options );

			if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
				$error = __( 'Could not connect to Stream update center.', 'stream-notifications' );
				add_action( 'all_admin_notices', function() use ( $error ) { echo wp_kses_post( $error ); } );
				return;
			}

			$body = wp_remote_retrieve_body( $response );
			do_action( 'wp-stream-update-api-response', $body, $response, $url, $options );

			$body = json_decode( $body );

			if ( empty( $body ) ) {
				return;
			}

			return $body;
		}

	}
}

if ( ! class_exists( 'WP_Stream_Updater' ) ) {
	class WP_Stream_Updater {

		private static $versions = array();

		public static function instance() {
			$latest = max( array_keys( self::$versions ) );
			return new self::$versions[$latest];
		}

		public static function register( $class ) {
			self::$versions[ $class::VERSION ] = $class;
		}
	}
}

WP_Stream_Updater::register( 'WP_Stream_Updater_0_1' );
