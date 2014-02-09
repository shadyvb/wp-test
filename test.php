<?php
/*
Plugin Name: WP GitHub Releases Test
Description: Nothing yet
Plugin URI: http://
Author: Author
Author URI: http://
Version: 1.0
License: GPL2
*/

include dirname( __FILE__ ) . '/updater.php';
WP_Stream_Updater::instance()->register( plugin_basename( __FILE__ ) );