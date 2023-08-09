<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Kitchen_GlobalSettings {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_submenu_page('kitchen_menu', 'Global-Settings', 'Global Settings', 'manage_options', 'kitchen-settings.php', array($this, 'sa_settings'));
    }

    /**
     * Register admin JavaScript
     *
     * @param  [type] $hook [description]
     * @return [type]       [description]
     */
    public function enqueue_scripts($hook) {

        wp_enqueue_style('kitchen_admin_style', KITCHEN_ASSETS_URL . 'admin/css/admin.css', array(), NULL);
        wp_enqueue_script('kitchen_admin_js', KITCHEN_ASSETS_URL . 'admin/js/admin.js', array('jquery'), true);
    }

    public function sa_settings() {

        $settings = get_option('sa_settings');

        $args = array(
            'sa_packID' => FILTER_SANITIZE_STRING,
            'sa_sync' => FILTER_SANITIZE_STRING
        );

        $inputs = filter_input_array(INPUT_POST, $args);
        $shortcode = '';

        if (isset($inputs['sa_packID'])) {
            $shortcode = '[kitchen]';

            $settings[$inputs['sa_packID']] = array(
                'id' => $inputs['sa_packID'],
                'sa_sync' => $inputs['sa_sync'],
                'shortcode' => $shortcode
            );

            update_option('sa_settings', $settings);

            if ($inputs['sa_sync'] == 'Auto' && !wp_next_scheduled('auto_sync_kitchen')) {
                wp_schedule_event(strtotime('00:00:00'), 'daily', 'auto_sync_kitchen', $inputs['sa_packID']);
            }
            
            ?>

            <div class="updated"><p><strong><?php echo 'Settings updated Successfully.'; ?></strong></p></div>
            <?php
        }
        ?>
        <script type="text/javascript">

            /* <![CDATA[ */

            var ajaxurl = "<?php echo admin_url('admin-ajax.php', (is_ssl() ? 'https' : 'http')); ?>";

            /* ]]> */

        </script>
        <div class="tabs">

            <ul class="tab-links">
                <li class="active"><a href="#kitchen_setting_tabcontent" id="kitchen_settings_tab">Settings</a></li>
                <li><a href="#kitchen_list_tabcontent" id="kitchen_list_tab">List</a></li>
            </ul>
            <div class="tabcontent">

                <div id="kitchen_setting_tabcontent" class="tab active">

                    <form id="kitchen_settings" name="sa_settings" method="post" action="">

                        <table class="form-table">

                            <tr valign="top">
                            <h2><?php echo 'Kitchen Settings' ?></h2>
                            <th scope="row">Pack ID</th>
                            <td><input type="text" name="sa_packID" placeholder="Pack ID" required/></td>

                            </tr>
                            <tr valign="top">
                                <th scope="row">Sync</th>
                                <td><input type="radio" checked="checked" name="sa_sync" value="manual"/>Manual
                                    <input type="radio" name="sa_sync" value="Auto"/>Auto</td>
                            </tr>

                            <tr><td></td><td><input type="submit"  class="button-primary" value="Save and Generate Shortcode"/></td> </tr>
                            <tr><td></td><td><input type="text" readonly="readonly" value="<?php echo $shortcode ?>"/></td> </tr>
                        </table>
                    </form>
                </div>

                <div id="kitchen_list_tabcontent" class="tab">
                    <div id="loading" class="hidden">

                        <img id="loading-image" src="<?php echo KITCHEN_BASE_URL . '/images/loader.gif' ?>" alt="Loading..." />

                    </div>
                    <table class="form-table">

                        <tr valign="top">

                            <th scope="row">Pack ID</th>
                            <th scope="row">Shortcode</th>
                            <th scope="row">Actions</th>
                        </tr>
                        <?php
                        $nonce = wp_create_nonce('Kitchen_API_nonce');
                        if ($settings) {
                            foreach ($settings as $setting) {
                                ?>
                                <tr>
                                    <td><?php echo $setting['id'] ?></td>
                                    <td><?php echo $setting['shortcode'] ?></td>
                                    <td>
                                        <input type="button" class="button-primary" value="Sync" id="kitchen_sync_btn" data-packid="<?php echo $setting['id'] ?>" data-nonce="<?php echo $nonce ?>" data-action="sync_kitchen_listing"/>
                                        <input type="button" class="button-primary" value="Update Pack" id="pack_sync_btn" data-packid="<?php echo $setting['id'] ?>" data-nonce="<?php echo $nonce ?>" data-action="sync_pack_listing"/>

                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <div class="notice notice-warning is-dismissible">
                                <p><?php _e('No Pack ID has been setup. Please add Pack ID to get started', KITCHEN_LANG_DOMAIN); ?></p>
                            </div>
                            <?php
                        }
                        ?>
                    </table>

                </div>

            </div>
        </div>
        <?php
    }

}
