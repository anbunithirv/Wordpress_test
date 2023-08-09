<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Kitchen_AjaxHandler {

    private $settings;
    private $curl_var;
    private $packID;

    public function __construct() {
        
        set_time_limit(300);

    }

    public function sync_kitchen_listing() {
        global $wpdb;
        if (FALSE == wp_verify_nonce($_POST['nonce'], 'Kitchen_API_nonce')) {
            wp_send_json_error('Not a valid request', 400);
            wp_die(); // this is required to terminate immediately and return a proper response
        } else {
            $postdata = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $this->packID = $postdata['packID'];

            $this->curl_var = curl_init();
            curl_setopt($this->curl_var, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->curl_var, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->curl_var, CURLOPT_RETURNTRANSFER, true);

            $api_response = $this->RetrieveWebActive();
            if ($api_response === false) {
                curl_close($this->curl_var);
                echo wp_send_json_error(curl_error($this->curl_var));
                die;
            } else {
            }
            curl_close($this->curl_var);

            //$wpdb->query($wpdb->prepare( "UPDATE ".$wpdb->get_blog_prefix()."posts set sa_sync = '".$_POST['sync_period']."' WHERE ID =".$_POST['post_id']));
            wp_send_json('Sync completed');
            wp_die(); // this is required to terminate immediately and return a proper response
        }
    }

    private function RetrieveWebActive() {
        $apiUrl = 'https://6466e9a7ba7110b663ab51f2.mockapi.io/api/v1/' . $this->packID;
        curl_setopt($this->curl_var, CURLOPT_URL, $apiUrl);
        $result = curl_exec($this->curl_var);
        //echo '<pre>';print_r($result);exit;
        if ($result !== false) {
            $fp = fopen(KITCHEN_PATH . 'saved_kitchen_details/' . $this->packID, 'w');
            fwrite($fp, $result);
            fclose($fp);
            
        }
        return $result;
    }

    public function sync_pack_listing() {
        global $wpdb;
        if (FALSE == wp_verify_nonce($_POST['nonce'], 'Kitchen_API_nonce')) {
            wp_send_json_error('Not a valid request', 400);
            wp_die(); // this is required to terminate immediately and return a proper response
        } else {
            $postdata = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $this->packID = $postdata['packID'];
            if (file_exists(KITCHEN_PATH . 'saved_kitchen_details/' . $this->packID)) {
                $data = file_get_contents(KITCHEN_PATH . 'saved_kitchen_details/' . $this->packID);
                $data = json_decode($data, TRUE);
                
                foreach ($data as $customers) {
                    $customers_meta = $this->get_post_meta($customers);
                    $customers_meta['packID'] = $this->packID;
                    $customers_post_arr = array(
                        'ID' => $customers['customer_id'],
                        'import_id' => $customers['customer_id'],
                        'post_type' => 'kitchen_customers',
                        'post_title' => $customers['customer_id'],
                        'post_content' => 'test content',
                        'post_status' => 'publish',
                        'comment_status' => 'closed', // if you prefer
                        'ping_status' => 'closed', // if you prefer
                        'meta_input' => $customers_meta
                    );

                    if (get_post_type($customers['customer_id']) == 'kitchen_customers') {
                        unset($customers_post_arr['import_id']);
                    } else {
                        unset($customers_post_arr['ID']);
                    }
                    $customers_id = wp_insert_post($customers_post_arr);
                    
                }
                wp_send_json('Sync completed');
                wp_die(); // this is required to terminate immediately and return a proper response
            } else {
                wp_send_json_error("Error: No Data File Found.", 200);
                //wp_send_json_error("Error: No Data File Found.", 400);
                wp_die(); // this is required to terminate immediately and return a proper response
            }
        }
    }

    
    
   
   
   

    private function get_post_meta($data) {
        $post_meta = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $post_meta[$key] = $value;
        }
        return $post_meta;
    }

    public function delete_kitchen_listing() {
        global $wpdb;
        global $post;
        print_r($_POST);exit;
        if (FALSE == wp_verify_nonce($_POST['nonce'], 'Kitchen_API_nonce')) {
            wp_send_json_error('Not a valid request', 400);
            wp_die(); // this is required to terminate immediately and return a proper response
        } else {
            $postdata = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $meta = get_post_meta($_POST['post_id']);
            $wpdb->query($wpdb->prepare( "UPDATE ".$wpdb->get_blog_prefix()."posts set post_status = 'draft' WHERE ID =".$_POST['post_id']));
            wp_send_json('Moved to Draft successfully');
            wp_die(); // this is required to terminate immediately and return a proper response
        }
    }
}
