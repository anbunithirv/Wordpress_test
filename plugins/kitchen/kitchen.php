<?php

/*
 * Plugin Name: Kitchen
 * Description: Custom plugin to fetch data from MockAPI and display via shortcode. See Readme for more.
 * Version:     1.0.0
 * Author:      Anbunithi Ramasamy Veerappan <anbunithirv@gmail.com>
 * Author URI:  http://localhost/wordpress/
 *
 * Text Domain: kitchen
 * Domain Path: /languages/
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!class_exists('Kitchen_Plugin')) {

    /**
     * Register the plugin.
     *
     * Display the administration panel, insert JavaScript etc.
     */
    class Kitchen_Plugin {

        /**
         * Hold plugin version
         * @var string
         */
        public $version = '1.0.0';

        /**
         * Hold an instance of Plugin class.
         *
         */
        protected static $instance = null;

        /**
         * Main Plugin instance.
         * @return Main instance.
         */
        public static function get_instance() {

            if (is_null(self::$instance)) {
                self::$instance = new Kitchen_Plugin;
            }

            return self::$instance;
        }

        /**
         * You cannot clone this class.
         */
        public function __clone() {
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'kitchen'), $this->version);
        }

        /**
         * You cannot unserialize instances of this class.
         */
        public function __wakeup() {
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'kitchen'), $this->version);
        }

        /**
         * Constructor
         */
        public function __construct() {

            $this->define_constants();
            $this->includes();
            $this->hooks();
            
        }

        /**
         * Define constants
         */
        private function define_constants() {
            define('KITCHEN_LANG_DOMAIN', 'kitchen');
            define('KITCHEN_VERSION', $this->version);
            define('KITCHEN_BASE_URL', trailingslashit(plugins_url('kitchen')));
            define('KITCHEN_ASSETS_URL', trailingslashit(KITCHEN_BASE_URL . 'assets'));
            define('KITCHEN_PATH', plugin_dir_path(__FILE__));
        }

        /**
         * Load required classes
         */
        private function includes() {

            //auto loader
            spl_autoload_register(array($this, 'autoloader'));
            if (is_admin()) {
                new Kitchen_GlobalSettings;
            }
        }

        /**
         * Autoload classes
         */
        public function autoloader($class) {
            $dir = KITCHEN_PATH . 'inc' . DIRECTORY_SEPARATOR;
            $class_file_name = 'class-' . str_replace(array('kitchen_', '_'), array('', '-'), strtolower($class)) . '.php';
            if (file_exists($dir . $class_file_name)) {
                require $dir . $class_file_name;
            }
        }

       

        /**
         * Hooks into WordPress
         */
        private function hooks() {

            // Common
            add_action('init', array($this, 'load_plugin_textdomain'));
            add_action('admin_menu', array($this, 'kitchen_admin_menu'), 10);
            add_action('init', array($this, 'register_post_type'));

            // AJAX Calls
            $Kitchen_AjaxHandler = new Kitchen_AjaxHandler;
            add_action('wp_ajax_sync_kitchen_listing', array($Kitchen_AjaxHandler, 'sync_kitchen_listing'));
            add_action('wp_ajax_delete_kitchen_listing', array($Kitchen_AjaxHandler, 'delete_kitchen_listing'));
            add_action('wp_ajax_sync_pack_listing', array($Kitchen_AjaxHandler, 'sync_pack_listing'));
            
            // Auto sync action
            add_action('auto_sync_kitchen', array($Kitchen_AjaxHandler, 'auto_sync_kitchen_listing'));
            add_action('auto_sync_pack_listing', array($Kitchen_AjaxHandler, 'auto_sync_pack_listing'));
            

            // Add sync type
            add_action('wp_ajax_add_sync_type', array($Kitchen_AjaxHandler, 'add_sync_type'));

            //Add additional information to post 
            add_action('save_post', array( $this,'save_addition_post'));
        }

        /**
         * Add addition information to Post
         */
        public function save_addition_post(){
            global $post;
            global $wpdb;
            $post_ID = $_POST['post_ID'];
            
            
            $meta = get_post_meta($post_ID);
            

            if(isset($_POST['sa_sync']) || isset($_POST['up_kitchen_period'])) {
                
            
             $wpdb->query($wpdb->prepare( "UPDATE ".$wpdb->get_blog_prefix()."posts set sa_sync = '".$_POST['sa_sync']."', sa_shortcode = '".$sa_shortcode."' WHERE ID =".$post_ID));
             update_post_meta( $post_ID, 'packID',  $_POST['packID']);
            }
    
        }
        /**
         * Initialise translations
         */
        public function load_plugin_textdomain() {
            load_plugin_textdomain(KITCHEN_LANG_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        /*
         * Admin menu to hold Kitchen pages
         */

        public function kitchen_admin_menu() {
            add_menu_page('Kitchen', 'Kitchen Details', 'administrator', 'kitchen_menu');
        }

        /**
         * Register post types
         */
        public function register_post_type() {

            $labels = array(
                'name' => __('Customers', KITCHEN_LANG_DOMAIN),
                'menu_name' => __('Customers', KITCHEN_LANG_DOMAIN),
                'singular_name' => __('Customers', KITCHEN_LANG_DOMAIN),
                'name_admin_bar' => _x('Customers', 'name admin bar', KITCHEN_LANG_DOMAIN),
                'all_items' => __('All Customer Details', KITCHEN_LANG_DOMAIN),
                'search_items' => __('Search Customer', KITCHEN_LANG_DOMAIN),
                'add_new' => _x('Add New', 'customer', KITCHEN_LANG_DOMAIN),
                'add_new_item' => __('Add New Customer', KITCHEN_LANG_DOMAIN),
                'new_item' => __('New Customer', KITCHEN_LANG_DOMAIN),
                'view_item' => __('View Customer', KITCHEN_LANG_DOMAIN),
                'edit_item' => __('Edit Customer', KITCHEN_LANG_DOMAIN),
                'not_found' => __('No Customer Found.', KITCHEN_LANG_DOMAIN),
                'not_found_in_trash' => __('Customer not found in Trash.', KITCHEN_LANG_DOMAIN),
                'parent_item_colon' => __('Parent Customer', KITCHEN_LANG_DOMAIN),
            );

            $args = array(
                'labels' => $labels,
                'description' => __('Holds the Customers and their ingredients.', KITCHEN_LANG_DOMAIN),
                'menu_position' => 6,
                'menu_icon' => 'dashicons-editor-help',
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'rewrite' => array('slug' => 'kitchen_customers'),
                'show_in_menu' => 'kitchen_menu',
                'query_var' => true,
                'capability_type' => 'post',
                'has_archive' => true,
                'hierarchical' => false,
                'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
            );

            register_post_type('kitchen_customers', $args);

           
        }
        
        public static function activate_plugin() {
            global $wpdb;
            add_post_meta('1', 'packID', '', true);
            $wpdb->query($wpdb->prepare( "ALTER TABLE ".$wpdb->get_blog_prefix()."posts ADD `sa_sync` VARCHAR(50) NOT NULL DEFAULT 'manual' AFTER `post_mime_type`, ADD `sa_shortcode` TEXT NULL AFTER `sa_sync`;"));
            // Don't activate on anything less than PHP 5.4.0 or WordPress 3.4
            if (version_compare(PHP_VERSION, '5.4.0', '<') || version_compare(get_bloginfo('version'), '3.4', '<') || !function_exists('spl_autoload_register')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                deactivate_plugins(basename(__FILE__));
                wp_die(__('Kitchen plugin requires PHP version 5.4.0 with spl extension or greater and WordPress 3.4 or greater.', KITCHEN_LANG_DOMAIN));
            }
        }

        public static function deactivate_plugin() {
            wp_clear_scheduled_hook('auto_sync_kitchen');
        }

    }

    /**
     * Main instance of Plugin.
     *
     * Returns the main instance of Plugin to prevent the need to use globals.
     *
     * @return Kitchen_Plugin
     */
    function kitchen() {
        return Kitchen_Plugin::get_instance();
    }

    function actions_meta_box_content()
    {
        global $post;
        global $wpdb;
        $gpost = get_post( $post->ID );
        $sync_period = $gpost->sa_sync;      
        $packID = $gpost->packID;       
        $nonce = wp_create_nonce('Kitchen_API_nonce');
        ?>
                <div id="loading" class="hidden">

        <img id="loading-image" src="<?php echo KITCHEN_BASE_URL . '/images/loader.gif' ?>" alt="Loading..." />

        </div>
        <br>
        <!-- <div id="post-formats-select">
		<fieldset>
			<legend class="screen-reader-text"><?php _e( 'Post Formats' ); ?></legend>
			<input type="radio" name="sa_sync" class="sync_period" id="sync_period" value="manual" <?php checked( $sync_period, 'manual' ); ?> /> <label>MANUAL</label>
			
			<br /><input type="radio" name="sa_sync" class="sync_period" id="sync_period_1" value="hourly" <?php checked( $sync_period, 'hourly' ); ?> /> <label>HOURLY</label>
            <br /><input type="radio" name="sa_sync" class="sync_period" id="sync_period_2" value="daily" <?php checked( $sync_period, 'daily' ); ?> /> <label>DAILY</label>
            <br /><input type="radio" name="sa_sync" class="sync_period" id="sync_period_3" value="weekly" <?php checked( $sync_period, 'weekly' ); ?> /> <label>WEEKLY</label>

		</fieldset>
	</div>
    <br> -->

      <input type="button" class="button-primary" <?php if($packID == ''){?> disabled="disabled"<?php } ?> value="Sync" id="kitchen_sync_btn" data-packid="<?php echo $packID ?>" data-nonce="<?php echo $nonce ?>" data-action="sync_kitchen_listing" data-postid="<?php echo $post->ID ?>"/>
       <input type="button" class="button-primary" <?php if($packID == ''){?> disabled="disabled"<?php } ?> value="Clear All" id="kitchen_delete_btn" data-postid="<?php echo $post->ID; ?>" data-packid="<?php echo $packID ?>" data-nonce="<?php echo $nonce ?>" data-action="delete_kitchen_listing" data-postid="<?php echo $post->ID ?>"/>
       <select name="sa_sync" class="sync_period" id="sync_period">
            <option value ="manual" <?php selected( $sync_period, 'manual' ); ?>>MANUAL</option>
            <option value ="hourly" <?php selected( $sync_period, 'hourly' ); ?>>HOURLY</option>
            <option value ="daily" <?php selected( $sync_period, 'daily' ); ?>>DAILY</option>
            <option value ="weekly" <?php selected( $sync_period, 'weekly' ); ?>>WEEKLY</option>
       </select>
       <?php
    }
    function wpc_meta_box_content()
    {
        global $post;
        $gpost = get_post( $post->ID );
        $packID = $gpost->packID;       
        $nonce = wp_create_nonce('Kitchen_API_nonce');
        ?>
        <br>
        

        <?php
        
    }
    function wpc_meta_box_content_all()
    {
        global $post;
        $gpost = get_post( $post->ID );
        $sync_period_all = $gpost->sync_period_all;      
        $packID = $gpost->packID;       
        $nonce = wp_create_nonce('Kitchen_API_nonce');
        ?>
        <br>
        <input type="button" class="button-primary" <?php if($packID == ''){?> disabled="disabled"<?php } ?> value="Sync & Update All" id="sync_all_btn" data-packid="<?php echo $packID ?>" data-nonce="<?php echo $nonce ?>" data-action="sync_all" data-postid="<?php echo $post->ID ?>"/>
        
        

        <?php
        
    }

    function customfield_meta_box_content()
    {
        global $post;
        $gpost = get_post( $post->ID );
        $sync_period = $gpost->sa_sync;      
        $packID = $gpost->packID; 
        $kitchen_update_url  =   $gpost->kitchen_update_url;  
        $nonce = wp_create_nonce('Kitchen_API_nonce');
        ?>
            <table class="form-table">

            <tr valign="top">
            <th scope="row">Pack ID</th>
            <td><input type="text" id="packID" style="width:100%" name="packID" value="<?php echo $packID; ?>" placeholder="Pack ID" required/></td>
            </tr>
            <tr valign="top">
            <th scope="row">kitchen UPDATE URL</th>
            <td><input type="text" id="kitchen_update_url" style="width:100%" name="kitchen_update_url" value="<?php echo $kitchen_update_url; ?>" placeholder="kitchen Update Url" /></td>
            </tr>
            </table>
        <?php
    
        }   
    function shortcode_meta_box_content()
    {
        global $post;
        $gpost = get_post( $post->ID );
        $shortcode = $gpost->sa_shortcode;
        echo $shortcode; 
    }
    
    function kitchen_meta_box(){
    add_meta_box("actions-meta-box", "Actions", "actions_meta_box_content", "kitchen", "side", "default", null);
    add_meta_box("wpc-meta-box", "Word Press Content", "wpc_meta_box_content", "kitchen", "side", "default", null);
    add_meta_box("wpc-meta-box_all", "Word Press Content", "wpc_meta_box_content_all", "kitchen", "side", "default", null);
    add_meta_box("shortcode-meta-box", "Shortcode", "shortcode_meta_box_content", "kitchen", "side", "default", null);
    add_meta_box("cf-meta-box", "Custom Fields", "customfield_meta_box_content", "kitchen", "normal", "high", null);
    
    }
    

    wp_enqueue_style('kitchen_admin_style', '/wp-content/plugins/kitchen/assets/admin/css/admin.css', array(), NULL);
    wp_enqueue_script('kitchen_admin_js',  '/wp-content/plugins/kitchen/assets/admin/js/admin.js', array('jquery'), true);
   
    //add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('add_meta_boxes', 'kitchen_meta_box');

    add_action('plugins_loaded', 'kitchen', 10);
    register_activation_hook(__FILE__, array('Kitchen_Plugin', 'activate_plugin'));

    register_deactivation_hook(__FILE__, array('Kitchen_Plugin', 'deactivate_plugin'));

}
