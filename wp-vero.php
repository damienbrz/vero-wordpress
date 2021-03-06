<?php
  /*
  Plugin Name: WP Vero
  Plugin URI: http://www.getvero.com
  Description: Vero: Send emails based on what your customers do. Your customers are individuals. Track what each one does and send them relevant emails like never before.
  Author: Damien Brzoska
  Version: 1.0.0
  Author URI: http://www.damienrbz.com
  */

  class Vero {
    // Render Vero library
    public static function initialize($api_key, $ignore) {
      include('snippets/library.php');
    }
    // Render Vero identify
    public static function identify($identity) {
      if (!$identity) return;
      include('snippets/identify.php');
    }
    // Render Vero track
    public static function track($event, $event_data) {
      if (!$event) return;
      include('snippets/event.php');
    }
  }

  class WPVero {
    public function __construct() {
      if (is_admin()) {
        add_action('admin_menu', array($this, 'wpvero_admin_actions'));
      } else {

        add_action('wp_head', array($this, 'wpvero_head'));
        add_action('wp_footer', array($this, 'wpvero_footer'));
      }
    }

    /*
    ** Admin
    */
    public function wpvero_admin() {
      if (!current_user_can('manage_options')) {
        wp_die('Sorry, you don\'t have the permissions to access this page.');
      }
      include('wpvero_admin_config.php');
    }

    public function wpvero_admin_actions() {
      add_options_page("Vero Settings", "Vero Settings", "manage_options", "Vero_Settings", array($this, 'wpvero_admin'));
    }

    /*
    ** Snippets
    */
    public function wpvero_head() {
      $ignore = false;
      $api_key = get_option('wpvero_api_key', '');
      $user = wp_get_current_user();
      $ignore_user_level = get_option('wpvero_ignore_user', 11);
      if (($user->user_level >= $ignore_user_level)) $ignore = true;

      Vero::initialize($api_key, $ignore);
    }
    public function wpvero_footer() {
      $identity = $this->get_user_identity();
      if ($identity) Vero::identify($identity);

      $track = $this->get_page_track();
      if ($track) Vero::track($track['event'], $track['properties']);
    }

    /*
    ** User
    */
    public function get_user_identity() {
      $user = wp_get_current_user();
      $commenter = wp_get_current_commenter();

      if (is_user_logged_in() && $user) {
        $identity = array(
          'username' => $user->user_login,
          'email' => $user->user_email,
          'name' => $user->display_name,
          'firstName' => $user->user_firstname,
          'lastName' => $user->user_lastname,
          'url' => $user->user_url
        );
      }
      else if ($commenter) {
        $identity = array(
          'email' => $commenter['comment_author_email'],
          'name' => $commenter['comment_author'],
          'url' => $commenter['comment_author_url']
        );
      }
      else return false;

      return $identity;
    }

    /*
    ** Page track
    */
    public function get_page_track() {
      if (get_option('wpvero_track_posts', true)) {
        if (is_single() && !is_attachment()) {
          $track = array(
            'event'      => 'Viewed ' . ucfirst(get_post_type()),
            'properties' => array(
              'title' => single_post_title('', false)
            )
          );
        }
      }

      if (get_option('wpvero_track_pages', true)) {
        if (is_front_page()) {
          $track = array(
            'event' => 'Viewed Home Page',
            'properties' => array()
          );
        }
        // A normal WordPress page.
        else if (is_page()) {
          $track = array(
            'event' => 'Viewed ' . single_post_title('', false) . ' Page',
            'properties' => array()
          );
        }
      }

      if (get_option('wpvero_track_archives', true)) {
        // An author page
        if (is_author()) {
          $author = get_queried_object();
          $track = array(
            'event' => 'Viewed Author Page',
            'properties' => array(
              'author' => $author->display_name
            )
          );
        }
        // A tag page.
        else if (is_tag()) {
          $track = array(
            'event' => 'Viewed Tag Page',
            'properties' => array(
              'tag' => single_tag_title( '', false )
            )
          );
        }
        // A category page
        else if (is_category()) {
          $track = array(
            'event' => 'Viewed Category Page',
            'properties' => array(
              'category' => single_cat_title( '', false )
            )
          );
        }
        // A Date page
        else if (is_date()) {
          $track = array(
            'event' => 'Viewed Archive Page',
            'properties' => array(
              'date' => get_the_date('Y-m-d h:i:s T')
            )
          );
        }
      }

      if (get_option('wpvero_track_searches', true)) {
        if (is_search()) {
          $track = array(
            'event'      => 'Viewed Search Page',
            'properties' => array(
              'query' => get_query_var('s')
            )
          );
        }
      }

      if (!isset($track)) return false;

      return $track;
    }
  }

  $wpVero = new WPVero();
?>