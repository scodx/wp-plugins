<?php
/**
 * Plugin Name: Simple Testimonials Plugin
 * Description: Adds a custom post type and a form for testimonials submissions
 * Version:     1.0
 * Author:      scodx.com
 * Author URI:  https://www.scodx.com/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

session_start();

/**
 * Register custom post type
 */
function scodx_testimonials_create_post_types() {

  /**
   * Post Type: Testimonials.
   */
  $labels = array(
    "name" => __( "Testimonials", "storefront" ),
    "singular_name" => __( "Testimonial", "storefront" ),
  );

  $args = array(
    "label" => __( "Testimonials", "storefront" ),
    "labels" => $labels,
    "description" => "Describes a Testimonial",
    "public" => true,
    "publicly_queryable" => true,
    "show_ui" => true,
    "delete_with_user" => false,
    "show_in_rest" => true,
    "rest_base" => "",
    "rest_controller_class" => "WP_REST_Posts_Controller",
    "has_archive" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "exclude_from_search" => false,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "rewrite" => array( "slug" => "testimonials", "with_front" => true ),
    "query_var" => true,
    "supports" => array( "title", "custom-fields" ),
  );

  register_post_type( "testimonials", $args );
}

function scodx_testimonials_process_errors() {
  $errors = '';
  $testimonials_session = $_SESSION['testimonials'];
  if (isset($testimonials_session['errors']) && is_wp_error($testimonials_session['errors'])) {
    $errors .= "<ul class='testimonials-errors'><li>" .
      implode( '</li><li>', $testimonials_session['errors']->get_error_messages() ) . '</li>' .
      '</ul>';
  }
  return $errors;
}

function testimonials_form_subheader() {
  $sub_header = '';
  if (isset($_SESSION['testimonials'])) {
    $errors = scodx_testimonials_process_errors();
    if ($errors === '') {
      $sub_header = '<h3>Testimonial submitted, please wait for an administrator to publish your content.</h3>';
    } else {
      $sub_header = '<h3>There are error in your submission, please correct them</h3>'. $errors;
    }
  }

  return $sub_header;
}

function testimonials_form_predata() {
  $data = [
    'testimonial-title' => '',
    'testimonial-author' => '',
    'testimonial-body' => '',
  ];
  // if there are errors
  if (isset($_SESSION['testimonials']['errors'])) {
    return $_SESSION['testimonials']['data'];
  }
  return $data;
}

/**
 * Renders the public form to submit the testimonial
 */
function scodx_testimonials_form() {
  $action_url = esc_url( admin_url('admin-post.php') );

  $sub_header = testimonials_form_subheader();
  $pre_data = testimonials_form_predata();

  $html = <<<TESTIMONIALFORM
    {$sub_header}
    <form action="{$action_url}" method="post">
    <input type="hidden" name="action" value="scodx_testimonial_form">
      <p>Title (required) <br>
        <input type="text" required="" name="testimonial-title" pattern="[a-zA-Z0-9 ]+" value="{$pre_data['testimonial-title']}" size="80">
      </p>
      <p>Author (required) <br>
        <input type="text" required="" name="testimonial-author" pattern="[a-zA-Z0-9 ]+" value="{$pre_data['testimonial-author']}" size="80">
      </p>
      <p>Testimonial (required) <br>
        <textarea required="" rows="8" cols="80" name="testimonial-body">{$pre_data['testimonial-body']}</textarea>
      </p>
      <p><input type="submit" name="testimonial-submit" value="Send"></p>
    </form>
TESTIMONIALFORM;

  echo $html;

  unset($_SESSION['testimonials']);
}

function scodx_testimonials_validate_form($form_data) {

  // Instantiate the WP_Error object
  $errors = new WP_Error();

  $hasData = function ($input) {
    return !empty(trim($input));
  };

  if (!isset($form_data['testimonial-title']) || !$hasData('testimonial-title')){
    $errors->add('empty', 'The title is a required field');
  }
  if (!isset($form_data['testimonial-author']) || !$hasData('testimonial-author')){
    $errors->add('empty', 'The author is a required field');
  }
  if (!isset($form_data['testimonial-body']) || !$hasData('testimonial-body')){
    $errors->add('empty', 'The testimonial body is a required field');
  }

  return !empty($errors->get_error_codes()) ? $errors : true;
}

function scodx_testimonials_process_form() {

  $errors = scodx_testimonials_validate_form($_POST);

  // If form submitted then insert testimonial
  if ( isset($_POST['testimonial-submit']) && !is_wp_error( $errors )) {

    // sanitize form values
    $title = sanitize_text_field( $_POST['testimonial-title'] );

    $custom_fields = [
      // testimonial's author
      'field_5ce9d82ff6eaa' => sanitize_text_field( $_POST['testimonial-author'] ),
      // testimonial's body
      'field_5ce9d7f8f6ea9' => esc_textarea( $_POST['testimonial-body'] ),
    ];

    $testimonial_id = wp_insert_post(
      array(
        'comment_status'	=>	'closed',
        'ping_status'		=>	'closed',
        'post_title'		=>	$title,
        'post_status'		=>	'pending',
        'post_type'		=>	'testimonials'
      )
    );

    if ($testimonial_id) {
      // populating custom fields
      foreach ($custom_fields as $field_name => $value) {
        update_field($field_name, $value, $testimonial_id);
      }
    }

  } else {
    // Placing errors on session, couldn't figured out another simple solution to flash messages :/
    $_SESSION['testimonials']['errors'] = $errors;
  }

  $_SESSION['testimonials']['data'] = $_POST;

  // redirecting to form
  wp_redirect($_SERVER['HTTP_REFERER']);
}

function scodx_testimonials_form_shortcode () {
  ob_start();
  scodx_testimonials_form();
  return ob_get_clean();
}


// registering 'testimonial' post type
add_action( 'init', 'scodx_testimonials_create_post_types' );
// registering shortcode to be able to render the form
add_shortcode( 'simple_testimonials_form', 'scodx_testimonials_form_shortcode' );
// setting form processing for either anonymous or logged in users
add_action( 'admin_post_nopriv_scodx_testimonial_form', 'scodx_testimonials_process_form' );
add_action( 'admin_post_scodx_testimonial_form', 'scodx_testimonials_process_form' );
