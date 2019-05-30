<?php
/**
 * Plugin Name: Simple Facebook Auth Plugin
 * Description: Adds Facebook authentication to wordpress
 * Version:     1.0
 * Author:      scodx.com
 * Author URI:  https://www.scodx.com/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 *  Define the users that initially are going to be
 *  admin in this array
 */
define('FB_ADMIN', array(
  array(
    'name' => 'Oscar Sanchez',
    'email' => 'oscar.exe@gmail.com',
  ),
));
define('FB_AUTH_TABLE_ADMINS', 'scodx_fb_auth_admins');

/**
 * Adds the FB button to the login form, the removal of the other
 * fields would need to be trough js/css
 */
function scodx_fb_auth_add_fb() {
  // removing fields from login form. I know, it must be a
  // better way, but couldn't find it.
  // Please note that this is also happening in the JS code
  ?>
  <style>
    form#loginform label, p.forgetmenot, p.submit {
      display: none;
      visibility: hidden;
    }
  </style>
  <fb:login-button
    data-size="large"
    scope="public_profile,email"
    onlogin="checkLoginState();">
  </fb:login-button>
  <?php
}

/**
 * Ajax response to the fb auth, start all the validation
 * for the already logged in fb user
 */
function scodx_fb_auth_ajax_init() {
  if ( isset( $_POST['user_email'] ) ) {
    wp_send_json(scodx_fb_auth_process_auth($_POST));
  }
}

/**
 * General function that processes the validation for the fb user
 *
 * @param $user_data
 * @return array
 */
function scodx_fb_auth_process_auth($user_data) {
  $user_email = $user_data['user_email'];
  $user_name = $user_data['user_name'];
  $user_fb_id = $user_data['user_id'];
  $is_fb_admin = !empty(scodx_fb_auth_validate_admin($user_email));
  $email_exist = email_exists($user_email);

  $create_and_login = function ($email_exist, $role) use ($user_name, $user_email) {
    if ($email_exist) {
      // if exist, $email_exist is the user id
      $user_id = $email_exist;
    } else {
      // create user
      $user_id = wp_create_user(sanitize_user($user_name, true), wp_generate_password(), $user_email );
    }
    // process role
    scodx_fb_auth_process_roles($user_id, $role);
    // sets auth cookie
    wp_set_auth_cookie($user_id);

    return $user_id;
  };

  $role = $is_fb_admin ? 'administrator' : 'subscriber';
  $user_id = $create_and_login($email_exist, $role);

  return [
    'user_id' => $user_id,
    'admin_url' => admin_url(),
  ];
}

/**
 * Adds the specified role to the user if doesn't have it
 *
 * @param $user_id
 * @param $role
 */
function scodx_fb_auth_process_roles($user_id, $role) {
  $user = get_userdata( $user_id );
  $user_roles = $user->roles;
  if ( !in_array( $role, $user_roles, true ) ) {
    $user->add_role($role);
    // If you want to remove all other roles then use this line:
    // $user->set_role($role);
    // otherwise with ->add_role will just add the role and keep the previous ones
  }
}

/**
 * Validates if an email is in the list of the fb admins
 *
 * @param $email
 * @return array|object|void|null
 */
function scodx_fb_auth_validate_admin($email) {
  global $wpdb;
  $table_name = $wpdb->prefix . FB_AUTH_TABLE_ADMINS;
  $query = $wpdb->prepare("SELECT id, name, email from {$table_name} WHERE email = %s", [$email]);
  return $wpdb->get_row($query);
}

/**
 * inserts the js scripts into the login page, also enables
 * backend var into frontend
 */
function scodx_fb_auth_enqueue_script() {
  wp_enqueue_script( 'scodx-fb-auth-js', plugins_url( 'assets/js/fb-auth.js', __FILE__ ),  array('jquery'));
  wp_localize_script( 'scodx-fb-auth-js', 'fb_auth', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

/**
 * Creates the fb admins table
 */
function scodx_fb_auth_install() {
  global $wpdb;

  $table_name = $wpdb->prefix . FB_AUTH_TABLE_ADMINS;

  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name tinytext NOT NULL,
		email text NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}

/**
 * Inserts the default fb admins
 */
function scodx_fb_auth_install_data() {
  global $wpdb;

  $table_name = $wpdb->prefix . FB_AUTH_TABLE_ADMINS;

  foreach (FB_ADMIN as $admins) {
    $wpdb->insert(
      $table_name,
      array(
        'name' => $admins['name'],
        'email' => $admins['email'],
      )
    );
  }
}

// hooks for SQL creating and insertion of data at plugin activation
register_activation_hook( __FILE__, 'scodx_fb_auth_install' );
register_activation_hook( __FILE__, 'scodx_fb_auth_install_data' );
// injecting custom scripts into login page
add_action('login_enqueue_scripts', 'scodx_fb_auth_enqueue_script', 1);
// hook to insert the fb login button
add_action('login_form', 'scodx_fb_auth_add_fb');
// enables ajax responses
add_action('wp_ajax_fb_auth_init', 'scodx_fb_auth_ajax_init');
add_action('wp_ajax_nopriv_fb_auth_init', 'scodx_fb_auth_ajax_init');