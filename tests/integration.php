<?php
/**
 * Run against a disposable WordPress database:
 * php tests/integration.php C:/path/to/wordpress [admin]
 * Fixture writes are rolled back. No mail is sent.
 */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) {
	exit( "Usage: php tests/integration.php <wordpress-path> [admin]\n" );
}
$source = dirname( __DIR__ );
define( 'WP_PLUGIN_DIR', dirname( $source ) );
if ( isset( $argv[2] ) && 'admin' === $argv[2] ) {
	define( 'WP_ADMIN', true );
	$_GET['page'] = 'cherry-services-options';
}
$GLOBALS['wp_filter']['option_active_plugins'][10][] = array(
	'function' => static function () use ( $source ) { return array( basename( $source ) . '/cherry-services-list.php' ); },
	'accepted_args' => 1,
);
$GLOBALS['wp_filter']['enable_wp_debug_mode_checks'][10][] = array(
	'function' => static function () { return false; },
	'accepted_args' => 1,
);
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array(
	'function' => static function () { return true; },
	'accepted_args' => 1,
);
if ( getenv( 'CHERRY_TEST_ELEMENTOR' ) ) {
	$GLOBALS['wp_filter']['muplugins_loaded'][10][] = array(
		'function' => static function () { require getenv( 'CHERRY_TEST_ELEMENTOR' ); },
		'accepted_args' => 0,
	);
}
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) use ( $source ) {
	if ( str_starts_with( str_replace( '\\', '/', $file ), str_replace( '\\', '/', $source ) . '/' ) ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
	return false;
} );
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';
function cherry_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}
function cherry_test_json( $callback ) {
	$handler = static function () {
		return static function () { throw new RuntimeException( 'cherry-json-end' ); };
	};
	add_filter( 'wp_die_ajax_handler', $handler );
	ob_start();
	try {
		$callback();
	} catch ( RuntimeException $error ) {
		if ( 'cherry-json-end' !== $error->getMessage() ) {
			throw $error;
		}
	} finally {
		$json = ob_get_clean();
		remove_filter( 'wp_die_ajax_handler', $handler );
	}
	$result = json_decode( $json, true );
	cherry_test_assert( is_array( $result ), 'Invalid AJAX JSON: ' . $json );
	return $result;
}
global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
	$old_options = array_merge( cherry_services_list()->default_options, array( 'archive-columns' => 2 ) );
	update_option( 'cherry-services', $old_options );
	cherry_services_list()->activation();
	cherry_test_assert( get_option( 'cherry-services' ) === $old_options, 'Upgrade overwrote settings' );
	cherry_test_assert( post_type_exists( 'cherry-services' ), 'Post type missing' );
	cherry_test_assert( get_post_type_object( 'cherry-services' )->show_in_rest, 'Block editor unavailable' );
	$term = wp_insert_term( 'Cherry compatibility test', 'cherry-services_category', array( 'slug' => 'cherry-compat-test' ) );
	cherry_test_assert( ! is_wp_error( $term ), 'Cannot create category' );
	$ids = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		$id = wp_insert_post( array( 'post_type' => 'cherry-services', 'post_status' => 'publish', 'post_title' => 'Compatibility service ' . $i, 'post_content' => 'Service description for PHP compatibility testing.' ), true );
		cherry_test_assert( ! is_wp_error( $id ), 'Cannot create service' );
		$ids[] = $id;
		wp_set_object_terms( $id, array( $term['term_id'] ), 'cherry-services_category' );
	}
	$html = do_shortcode( '[cherry_services posts_per_page="2" more="false" pagination="true" show_filters="true"]' );
	cherry_test_assert( 2 === substr_count( $html, '<div id="services-' ), 'Listing limit incorrect' );
	cherry_test_assert( str_contains( $html, 'services-ajax-pager' ), 'Pagination missing' );
	cherry_test_assert( str_contains( $html, 'cherry-compat-test' ), 'Category filter missing' );
	$html = do_shortcode( '[cherry_services id="' . $ids[0] . '" more="false"]' );
	cherry_test_assert( str_contains( $html, 'Compatibility service 1' ), 'ID selection failed' );
	$data = new Cherry_Services_List_Data( array( 'category' => 'does-not-exist', 'echo' => false ) );
	$query = $data->get_services();
	cherry_test_assert( $query instanceof WP_Query && ! $query->have_posts(), 'Empty query must remain a WP_Query' );
	cherry_test_assert( '' === $data->get_services_loop( $query, array() ), 'Empty query rendering failed' );
	$data->get_services( array( 'category' => '', 'id' => array( 0, $ids[1] ) ) );
	cherry_test_assert( $data->get_services( array( 'id' => array( $ids[1] ) ) )->posts[0]->ID === $ids[1], 'Array IDs failed' );
	cherry_test_assert( str_contains( cherry_services_templater()->get_template_by_name( '../wp-config.php' ), '%%' ), 'Unsafe template fallback failed' );
	if ( getenv( 'CHERRY_TEST_ELEMENTOR' ) ) {
		$widget = \Elementor\Plugin::instance()->widgets_manager->get_widget_types( 'cherry_services' );
		cherry_test_assert( $widget instanceof Cherry_Services_Elementor_Widget, 'Elementor widget not registered' );
		$controls = $widget->get_controls();
		cherry_test_assert( isset( $controls['posts_per_page'] ), 'Elementor controls missing' );
		$widget = \Elementor\Plugin::instance()->elements_manager->create_element_instance( array( 'id' => 'compat-widget', 'elType' => 'widget', 'widgetType' => 'cherry_services', 'settings' => array() ) );
		ob_start();
		$widget->render_content();
		$elementor_html = ob_get_clean();
		cherry_test_assert( str_contains( $elementor_html, 'Compatibility service' ), 'Elementor widget failed to render' );
	}
	global $post;
	$post = get_post( $ids[0] );
	setup_postdata( $post );
	$fields = array( 'item-0' => array( 'name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => 'yes' ) );
	update_post_meta( $post->ID, 'cherry-services-cta-form', $fields );
	update_post_meta( $post->ID, 'cherry-services-form-message', 'Thank you' );
	$callbacks = new Cherry_Services_List_Template_Callbacks( array() );
	cherry_test_assert( str_contains( $callbacks->get_cta_form(), 'cherry-services-nonce' ), 'Form nonce missing' );
	$mail = array();
	$mail_stub = static function ( $result, $attributes ) use ( &$mail ) { $mail[] = $attributes; return true; };
	add_filter( 'pre_wp_mail', $mail_stub, 10, 2 );
	$_POST = array( 'cherry-services-form' => $post->ID, 'service-cta' => array( 'email' => 'test@example.invalid' ) );
	cherry_services_list_form()->maybe_process_cta_form();
	cherry_test_assert( ! $mail, 'Unverified form sent mail' );
	$_POST['cherry-services-nonce'] = wp_create_nonce( 'cherry-services-form-' . $post->ID );
	cherry_services_list_form()->maybe_process_cta_form();
	cherry_test_assert( 1 === count( $mail ) && str_contains( $mail[0]['message'], 'E-mail: test@example.invalid' ), 'Valid form failed' );
	cherry_test_assert( PHP_SESSION_ACTIVE !== session_status(), 'Form kept the session locked' );
	cherry_test_assert( 'Thank you' === cherry_services_list_form()->get_message( $post->ID ), 'Form confirmation missing' );
	cherry_test_assert( empty( cherry_services_list_form()->get_message( $post->ID ) ), 'Form confirmation repeated' );
	$_POST['service-cta']['email'] = array( 'invalid' );
	cherry_services_list_form()->maybe_process_cta_form();
	cherry_test_assert( 1 === count( $mail ), 'Malformed field sent mail' );
	remove_filter( 'pre_wp_mail', $mail_stub, 10 );
	$_POST = array();
	if ( is_admin() ) {
		$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
		wp_set_current_user( $admin[0]->ID );
		require_once ABSPATH . 'wp-admin/includes/admin.php';
		set_current_screen( 'cherry-services' );
		do_action( 'admin_init' );
		do_action( 'add_meta_boxes', 'cherry-services', get_post( $ids[0] ) );
		ob_start();
		do_meta_boxes( 'cherry-services', 'normal', get_post( $ids[0] ) );
		$meta = ob_get_clean();
		cherry_test_assert( str_contains( $meta, 'cherry-services' ), 'Metaboxes missing' );
		ob_start();
		Cherry_Services_Options_Page::get_instance()->options_page();
		$settings = ob_get_clean();
		cherry_test_assert( str_contains( $settings, 'posts-per-page' ), 'Settings missing' );
		$meta_object = Cherry_Services_List_Meta::get_instance();
		update_post_meta( $ids[0], 'compat-nested', array( 'quote' => "Client's service", 'path' => 'C:\\test' ) );
		$_REQUEST = array( 'action' => 'cherry_services_clone_post', 'post' => $ids[0], '_wpnonce' => wp_create_nonce( 'cherry_services_clone_' . $ids[0] ) );
		$clone_id = 0;
		$redirect = static function ( $url ) use ( &$clone_id ) {
			parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $params );
			$clone_id = (int) $params['post'];
			throw new RuntimeException( 'cherry-clone-end' );
		};
		add_filter( 'wp_redirect', $redirect );
		try {
			$meta_object->duplicate_post_as_draft();
		} catch ( RuntimeException $error ) {
			if ( 'cherry-clone-end' !== $error->getMessage() ) { throw $error; }
		} finally {
			remove_filter( 'wp_redirect', $redirect );
		}
		cherry_test_assert( 'draft' === get_post_status( $clone_id ), 'Clone was not a draft' );
		cherry_test_assert( get_post_meta( $ids[0], 'compat-nested', true ) === get_post_meta( $clone_id, 'compat-nested', true ), 'Clone metadata changed' );
		cherry_test_assert( has_term( $term['term_id'], 'cherry-services_category', $clone_id ), 'Clone category missing' );
	}
	define( 'DOING_AJAX', true );
	require_once $source . '/admin/includes/class-cherry-services-ajax.php';
	$ajax = Cherry_Services_Ajax::get_instance();
	$_POST = array( 'cat' => 'no-matching-category', 'atts' => array() );
	$result = cherry_test_json( array( $ajax, 'filter_posts' ) );
	cherry_test_assert( $result['success'] && '' === $result['data']['result'] && 0 === $result['data']['pages'], 'Empty AJAX filter failed' );
	$_POST = array( 'page' => 1, 'atts' => array( 'limit' => 2, 'more' => false, 'pager' => true ) );
	$result = cherry_test_json( array( $ajax, 'more_posts' ) );
	cherry_test_assert( 1 === substr_count( $result['data']['result'], '<div id="services-' ), 'AJAX More failed' );
	$_POST = array( 'page' => 99, 'atts' => array( 'limit' => 2 ) );
	$result = cherry_test_json( array( $ajax, 'pager' ) );
	cherry_test_assert( '' === $result['data']['result'], 'Empty AJAX page failed' );
	if ( is_admin() ) {
		$options_object = Cherry_Services_Options_Page::get_instance();
		$_POST = array( 'nonce' => wp_create_nonce( 'cherry_ajax_nonce' ), 'type' => 'save', 'post_array' => array( 'archive-columns' => 3, 'posts-per-page' => 6 ) );
		$result = cherry_test_json( array( $options_object, 'process_options' ) );
		cherry_test_assert( 'success-notice' === $result['type'] && 6 === get_option( 'cherry-services' )['posts-per-page'], 'Settings save failed' );
	}
	echo 'PASS PHP ' . PHP_VERSION . ' / WordPress ' . get_bloginfo( 'version' ) . ( is_admin() ? ' admin' : ' frontend' ) . PHP_EOL;
} finally {
	$wpdb->query( 'ROLLBACK' );
	wp_cache_flush();
}
