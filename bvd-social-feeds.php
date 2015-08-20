<?php
/*
  Plugin Name: BVD Easy Social Feeds & Images
  Plugin URI:  https://balcom-vetillo.com/products/wordpress-social-feed-plugin/
  Description: Displays Instagram, Twitter and Facebook Feeds
  Author: Balcom-Vetillo Design, Inc.
  Version: 1.0.0
  Author URI: https://www.balcom-vetillo.com
  
 */
define("SFR_URL", "https://balcom-vetillo.com/social-feeds-redirect/index.php");
define("SFR_URL_FACEBOOK", "https://balcom-vetillo.com/social-feeds-redirect/facebook.php");
define("SFR_URL_TWITTER", "https://balcom-vetillo.com/social-feeds-redirect/twitter.php");
define("SFR_URL_INSTAGRAM", "https://balcom-vetillo.com/social-feeds-redirect/instagram.php");

class bvdSocialFeeds {

    private $instagram_client_id = "";
    private $instagram_client_secret = "";
    public $uuid; //site unique ID
    public $secret; //site unique ID
    private $submit_success = 0;
    private $license_key_error = false;
    private $bvd_sf_license_key_secret = "";
    private $bvd_sf_license_key_server_url = "https://www.balcom-vetillo.com";
    private $bvd_sf_license_key_item_reference = "BVD Social Feeds WordPress Plugin";
    private $bvd_var_dump = '';

    function __construct() {

        //add_action( 'wp', array($this, 'setup_schedule')); //setup initial schedule
        //add_action( 'bvd_instagram_hourly', array($this, 'cron_hourly')); //run cron

        add_action('init', array($this, 'process_post'));
        add_action('admin_menu', array($this, 'setup_admin_menu'));
        add_action('template_redirect', array($this, "callback"));
        add_action('admin_notices', array($this, 'showAdminMessages'));

        wp_register_style('socialFeedsPluginUserStylesheet', plugins_url('bvd-social-feeds-user-style.css', __FILE__));
        wp_enqueue_style('socialFeedsPluginUserStylesheet');

        add_shortcode('bvd-instagram-feed', array($this, "instagram_feed_display"));
        add_shortcode('bvd-facebook-feed', array($this, "facebook_feed_display"));
        add_shortcode('bvd-twitter-feed', array($this, "twitter_feed_display"));
    }

    public function process_post() {
        $this->uuid = get_option("bvads_social_feed_uuid");
        if (!$this->uuid) {
            update_option("bvads_social_feed_uuid", $this->guid());
            $this->uuid = get_option("bvads_social_feed_uuid");
        }

        $this->secret = get_option("bvads_social_feed_secret");
        if (!$this->secret) {
            $resp = json_decode(file_get_contents(SFR_URL . "?sfr_uuid=" . $this->uuid . "&sfr_register=1&callback_url=" . $this->callback_url()));
            update_option("bvads_social_feed_secret", $resp->secret);
            $this->secret = get_option("bvads_social_feed_secret");
        }

        if ($_REQUEST['instagram_auth'] === "auth") {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.instagram.com/oauth/access_token");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                'client_id' => $this->instagram_client_id,
                'client_secret' => $this->instagram_client_secret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => 'https://balcom-vetillo.com/social-feeds-redirect/instagram.php/?instagram_auth=auth&bvd_referer=' . $_SERVER['HTTP_HOST'],
                'code' => $_REQUEST['code']
            )));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            curl_close($ch);
            $resp = json_decode($server_output, true);

            update_option("bvads_social_feed_instagram_access_token", $resp['access_token']);
            update_option("bvads_social_feed_instagram_user_id", $resp['user']['id']);
            update_option("bvads_social_feed_instagram_username", $resp['user']['username']);

            wp_redirect(get_admin_url(null, 'admin.php?page=bvd-social-feeds-instagram&tab=basic-settings'));
            exit;
        }
        
        if(isset($_REQUEST['disconnect_account'])) {
            switch($_REQUEST['disconnect_account']) {
                case 'instagram' :
                    update_option("bvads_social_feed_instagram_access_token", false);
                    update_option("bvads_social_feed_instagram_user_id", false);
                    update_option("bvads_social_feed_instagram_username", false);
                    break;
                
                case 'facebook' :
                    update_option("bvads_facebook_oauth_token", false);
                    update_option("bvads_facebook_user_id", false);
                    
                    $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=disconnect_account";
                    $data = file_get_contents($request);
                    //$data = json_decode($data);
                    break;
                
                case 'twitter' :
                    update_option("bvads_twitter_oauth_token", false);
                    update_option("bvads_twitter_oauth_secret", false);
                    update_option("bvads_twitter_screenname", false);
                    update_option("bvads_twitter_user_id", false);
                    
                    $request = SFR_URL_TWITTER . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=disconnect_account";
                    $data = file_get_contents($request);
                    break;
            }
        }

        if (isset($_REQUEST['bvd-post-action'])) {
            switch ($_REQUEST['bvd-post-action']) {
                case 'set-instagram-api-settings' :
                    $search = strtolower($_REQUEST['user-name']);
                    $access_token = get_option("bvads_social_feed_instagram_access_token");

                    $url = 'https://api.instagram.com/v1/users/search?q=' . $search . '&access_token=' . $access_token;
                    $resp = json_decode(file_get_contents($url), true);

                    foreach ($resp['data'] as $user) {
                        if (strtolower($user['username']) == $search) {
                            $user_id = $user['id'];
                            $user_name = $user['username'];

                            break;
                        }
                    }

                    update_option("bvads_social_feed_instagram_user_id", $user_id);
                    update_option("bvads_social_feed_instagram_username", $user_name);

                    update_option("bvads_instagram_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-instagram-display-options' :
                    update_option("bvads_social_feed_instagram_number_photos", $_REQUEST['number-display']);

                    update_option("bvads_social_feed_instagram_number_columns", $_REQUEST['number-columns']);

                    if (strpos($_REQUEST['padding-around'], 'px') === false) {
                        update_option("bvads_social_feed_instagram_padding_around", $_REQUEST['padding-around']);
                    } else {
                        update_option("bvads_social_feed_instagram_padding_around", str_replace('px', '', $_REQUEST['padding-around']));
                    }

                    update_option("bvads_social_feed_instagram_user_tag", $_REQUEST['user-tag']);

                    if (isset($_REQUEST['show-header'])) {
                        update_option("bvads_social_feed_instagram_show_header", 1);
                    } else {
                        update_option("bvads_social_feed_instagram_show_header", 0);
                    }

                    update_option("bvads_instagram_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-facebook-api-settings' :
                    $fb_page = $_REQUEST['page-id'];

                    $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=get_page_id&sfr_page=" . $fb_page;
                    $data = file_get_contents($request);
                    $data = json_decode($data);

                    update_option("bvads_social_feed_facebook_page_id", $data->id);
                    update_option("bvads_social_feed_facebook_page_name", $data->name);

                    update_option("bvads_facebook_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-facebook-display-options' :
                    update_option("bvads_social_feed_facebook_number_items", $_REQUEST['number-display']);
                    if (isset($_REQUEST['show-header'])) {
                        update_option("bvads_social_feed_facebook_show_header", 1);
                    } else {
                        update_option("bvads_social_feed_facebook_show_header", 0);
                    }

                    update_option("bvads_facebook_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-twitter-api-settings' :
                    update_option("bvads_twitter_screenname", $_REQUEST['page-id']);

                    update_option("bvads_twitter_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-twitter-display-options' :
                    update_option("bvads_social_feed_twitter_number_items", $_REQUEST['number-display']);
                    if (isset($_REQUEST['show-header'])) {
                        update_option("bvads_social_feed_twitter_show_header", 1);
                    } else {
                        update_option("bvads_social_feed_twitter_show_header", 0);
                    }

                    update_option("bvads_twitter_settings_change", 1);

                    $this->submit_success = 1;
                    break;

                case 'set-pro-key' :
                    $this->license_key_activation($_REQUEST['pro-key']);
                    break;
                
                case 'deactivate-pro-key' :
                    $this->license_key_deactivation($_REQUEST['pro-key']);
                    break;
            }
        }
    }

    private function guid() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = chr(123)// "{"
                    . substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12)
                    . chr(125); // "}"
            return $uuid;
        }
    }

    public static function callback_url() {
        return urlencode(home_url());
    }

    public function setup_admin_menu() {
        $my_page = add_menu_page("BVD Social Feeds", "BVD Social Feeds", "manage_options", "bvd-social-feeds", array($this, "admin_page"), false, '81.3');
        $my_page_2 = add_submenu_page("bvd-social-feeds", "Social Feeds Instagram", "Instagram", "manage_options", "bvd-social-feeds-instagram", array($this, "admin_page_instagram"));
        $my_page_3 = add_submenu_page("bvd-social-feeds", "Social Feeds Facebook", "Facebook", "manage_options", "bvd-social-feeds-facebook", array($this, "admin_page_facebook"));
        $my_page_4 = add_submenu_page("bvd-social-feeds", "Social Feeds Twitter", "Twitter", "manage_options", "bvd-social-feeds-twitter", array($this, "admin_page_twitter"));

        add_action('load-' . $my_page, array($this, "social_feeds_load_styles"));
        add_action('load-' . $my_page_2, array($this, "social_feeds_load_styles"));
        add_action('load-' . $my_page_3, array($this, "social_feeds_load_styles"));
        add_action('load-' . $my_page_4, array($this, "social_feeds_load_styles"));
    }

    public function social_feeds_load_styles() {
        add_action('admin_enqueue_scripts', array($this, "social_feeds_enqueue"));
    }

    public function social_feeds_enqueue() {
        wp_register_style('socialFeedsPluginStylesheet', plugins_url('bvd-social-feeds-style.css', __FILE__));
        wp_enqueue_style('socialFeedsPluginStylesheet');
    }
    
    public function license_key_activation($license_key) {
        // API query parameters
        $api_params = array(
            'slm_action' => 'slm_activate',
            'secret_key' => $this->bvd_sf_license_key_secret,
            'license_key' => $license_key,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference' => urlencode($this->bvd_sf_license_key_item_reference),
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->bvd_sf_license_key_server_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($api_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        $resp = json_decode($server_output);
        
        if($resp->result == 'success'){//Success was returned for the license activation
            
            //Uncomment the followng line to see the message that returned from the license server
            //echo '<br />The following message was returned from the server: '.$license_data->message;
            
            //Save the license key in the options table
            //update_option('sample_license_key', $license_key); 
            
            $request = SFR_URL . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&sfr_add_license=1&sfr_secret=" . get_option("bvads_social_feed_secret") . "&sfr_license_key=" . $license_key;
            $data = file_get_contents($request);
            $data = json_decode($data);
            if ($data->ACK == 'SUCCESS') {
                $this->submit_success = 1;
            }
        } else {
            //Show error to the user. Probably entered incorrect license key.
            
            //Uncomment the followng line to see the message that returned from the license server
            //echo '<br />The following message was returned from the server: '.$license_data->message;
            
            $this->license_key_error = $resp->message;
        }
    }
    
    public function license_key_deactivation($license_key) {
        // API query parameters
        $api_params = array(
            'slm_action' => 'slm_deactivate',
            'secret_key' => $this->bvd_sf_license_key_secret,
            'license_key' => $license_key,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference' => urlencode($this->bvd_sf_license_key_item_reference),
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->bvd_sf_license_key_server_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($api_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        $resp = json_decode($server_output);
        
        if($resp->result == 'success'){//Success was returned for the license activation
            
            //Uncomment the followng line to see the message that returned from the license server
            //echo '<br />The following message was returned from the server: '.$license_data->message;
            
            //Save the license key in the options table
            //update_option('sample_license_key', $license_key); 
            
            $request = SFR_URL . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&sfr_deactivate_license=1&sfr_secret=" . get_option("bvads_social_feed_secret");
            $data = file_get_contents($request);
            $data = json_decode($data);
            if ($data->ACK == 'SUCCESS') {
                $this->submit_success = 2;
            }
        } else {
            //Show error to the user. Probably entered incorrect license key.
            
            //Uncomment the followng line to see the message that returned from the license server
            //echo '<br />The following message was returned from the server: '.$license_data->message;
            
            $this->license_key_error = $resp->message;
        }
    }

    public function admin_page() {
        global $wpdb;
        ?>
        <div class="wrap">
            <h1>BVD Social Feeds</h1>
            <div class="designed-by-wrapper">
                <p>Plugin designed and developed by<br/><a href="https://www.balcom-vetillo.com/" target="_blank">Balcom-Vetillo Design</a>.</p>
                <a href="https://www.balcom-vetillo.com/" target="_blank"><img src="<?php echo plugins_url('images/BVD-Logo-vert.png', __FILE__); ?>" /></a>
            </div>
            <div class="main-content-wrapper">
                        <?php
                if ($this->license_key_error) {
                    ?>
                    <div id="social-feeds-message" class="error">
                        <p><?php echo $this->license_key_error; ?></p>
                    </div>
                    <?php
                }

                if ($this->submit_success == 1) {
                    ?>
                    <div id="social-feeds-message" class="updated">
                        <p>Pro Key successfully submitted.</p>
                    </div>
                    <?php
                } elseif($this->submit_success == 2) {
                    ?>
                    <div id="social-feeds-message" class="updated">
                        <p>Pro Key has been deactivated.</p>
                    </div>
                    <?php
                }
                ?>
            <div class="social-feeds-about-information">
                <p>The Social Feeds Plugin will allow you to display feeds from your Instagram, Facebook, and Twitter feeds. The plugin will display a feed from any account with public settings with minimal setup. Just go to each network page and click the button to authorize and you will be redirected to the login page for that social network if you aren't already logged in. Login here (your password is not shared with the plugin) and accept the requested permissions (the plugin only requests the most basic permissions that only let the plugin read your public data) then you are all set. There are several display options you can set and then view the shortcode tab to see how to add a feed to any page, post or template on your site.</p>
            </div>
            <?php
            if (!$this->check_pro_key()) {
                $hidden_value = 'set-pro-key';
                $pro_key = '';
                $submit_value = 'Submit Key';
            } else {
                $hidden_value = 'deactivate-pro-key';
                $pro_key = $this->get_pro_key();
                $submit_value = 'Deactivate Key';
            }
                ?>
                <div class="pro-key-submit-form-wrapper">
                    <div class="social-feeds-section-title">
                        Pro Version
                    </div>
                    <div class="pro-key-submit-form-info">
                        <?php
                        if (!$this->check_pro_key()) {
                            ?>
                            <p>The BVD Social Feeds plugin has an optional pro version that can be unlocked by purchasing a Pro Key. The pro version will unlock additional plugin settings.</p>
                            <p>If you have a Pro Key, enter it in the form below to activate the pro options in this plugin.</p>
                            <?php
                        } else {
                            ?>
                            <p>Your Pro Key has been activated!</p>
                            <p>You can deactivate your Pro key by submitting the form below.</p>
                            <?php
                        }
                            ?>
                    </div>
                    <form action="" method="post" class="pro-key-submit-form">
                        <input type="hidden" name="bvd-post-action" value="<?php echo $hidden_value; ?>" />
                        <div class="form-section">
                            <div class="form-section-left">
                            <label for="pro-key">Pro Key</label>
                            </div>
                            <div class="form-section-right">
                                <?php
                                if(!empty($pro_key)) {
                                    ?>
                                    <input type="text" name="pro-key" id="pro-key" placeholder="Pro Key" value="<?php echo $pro_key; ?>">
                                    <?php
                                } else {
                                    ?>
                                    <input type="text" name="pro-key" id="pro-key" placeholder="Pro Key">
                                    <?php
                                }
                                ?>
                            </div>
                            <div style="clear:left;"></div>
                        </div>
                        <div class="form-section">
                            <input type="submit" value="<?php echo $submit_value; ?>" />
                        </div>
                    </form>
                </div>
                
            <div class="social-feeds-page-links">
                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-instagram'); ?>">Instagram</a><br/><br/>
                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook'); ?>">Facebook</a><br/><br/>
                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-twitter'); ?>">Twitter</a><br/>
            </div>
            </div>
            
            <?php
            if(!empty($this->bvd_var_dump)) {
                ?>
                <pre><?php print_r($this->bvd_var_dump); ?></pre> 
                <?php
            }
            ?>
        </div>
        <?php
    }

    public function admin_page_instagram() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic-settings';
        include 'admin-page-instagram.php';
    }

    public function admin_page_facebook() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic-settings';
        include 'admin-page-facebook.php';
    }

    public function admin_page_twitter() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic-settings';
        include 'admin-page-twitter.php';
    }

    //Check if there is a pro key
    public function check_pro_key() {
        $request = SFR_URL . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=verify_pro_key";
        $data = file_get_contents($request);
        $data = json_decode($data);

        if ($data->key_status == "SUCCESS") {
            return true;
        } else {
            return false;
        }
    }
    
    public function get_pro_key() {
        $request = SFR_URL . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&action=get_pro_key";
        $data = file_get_contents($request);
        $data = json_decode($data);

        if ($data->key_status == "SUCCESS") {
            return $data->pro_key;
        } else {
            return false;
        }
    }

    public function callback() {
        global $wpdb;
        if (isset($_REQUEST['sfr_callback'])) {
            switch ($_REQUEST['sfr_callback']) {
                case "twitter":
                    //verify uuid here
                    update_option("bvads_twitter_oauth_token", $_REQUEST['oauth_token']);
                    update_option("bvads_twitter_oauth_secret", $_REQUEST['oauth_secret']);
                    update_option("bvads_twitter_screenname", $_REQUEST['oauth_username']);
                    update_option("bvads_twitter_user_id", $_REQUEST['oauth_user_id']);

                    header("Location: " . admin_url() . "admin.php?page=bvd-social-feeds-twitter");
                    die();
                    break;

                case "facebook":
                    //verify uuid here
                    update_option("bvads_facebook_oauth_token", $_REQUEST['oauth_token']);
                    update_option("bvads_facebook_user_id", $_REQUEST['oauth_user_id']);

                    header("Location: " . admin_url() . "admin.php?page=bvd-social-feeds-facebook");
                    die();
                    break;
            }
        }
    }

    //Instagram Feed Display Shortcode
    public function instagram_feed_display($atts) {
        global $wpdb;

        $cache_file = plugin_dir_path(__FILE__) . 'instagram-feed-cache.txt';

        if (get_option("bvads_instagram_settings_change") == 1) {
            $ignore_cache = true;

            update_option("bvads_instagram_settings_change", 0);
        } else {
            $ignore_cache = false;
        }

        if (!$ignore_cache && file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 15 ))) {
            //read from cache
            //less than 15 minutes old
            $output_string = file_get_contents($cache_file);
        } else {
            //cache outdated
            ob_start();

            if ($number_photos = get_option("bvads_social_feed_instagram_number_photos")) {
                if (!empty($number_photos)) {
                    $count_default = $number_photos;
                } else {
                    $count_default = 5;
                }
            } else {
                $count_default = 5;
            }

            if ($number_columns = get_option("bvads_social_feed_instagram_number_columns")) {
                if (!empty($number_columns)) {
                    $cols_default = $number_columns;
                } else {
                    $cols_default = 5;
                }
            } else {
                $cols_default = 5;
            }

            if ($padding_around = get_option("bvads_social_feed_instagram_padding_around")) {
                if (!empty($padding_around)) {
                    $pad_default = $padding_around;
                } else {
                    $pad_default = 5;
                }
            } else {
                $pad_default = 5;
            }

            $user_tag = get_option("bvads_social_feed_instagram_user_tag");
            $show_header = get_option("bvads_social_feed_instagram_show_header");

            $user_id = get_option("bvads_social_feed_instagram_user_id");
            $access_token = get_option("bvads_social_feed_instagram_access_token");

            $atts = shortcode_atts(
                    array(
                'use_tags' => false,
                'tags' => '',
                'count' => $count_default,
                'columns' => $cols_default,
                'padding' => $pad_default
                    ), $atts, 'bvd-instagram-feed');
            
            if(!$this->check_pro_key()) {
                if($atts['count'] > 5) {
                    $atts['count'] = 5;
                } 
            }
            
            if(!$this->check_pro_key()) {
                if($atts['columns'] > 5) {
                    $atts['columns'] = 5;
                } 
            }

            if ($atts['use_tags']) {
                $tags = explode(',', $atts['tags']);
                if(!$this->check_pro_key()) {
                    $tags_use[] = $tags[0];
                } else {
                    $tags_use = $tags;
                }
                
                foreach ($tags_use as $tag) {
                    $url = 'https://api.instagram.com/v1/tags/' . $tag . '/media/recent/?count=' . $atts['count'] . '&access_token=' . $access_token;
                    $resp = json_decode(file_get_contents($url));

                    if ($resp->meta->code === 200) {
                        $width = 100 / $atts['columns'];
                        ?>
                        <div class="instagram-feed-wrapper">
                            <div class="instagram-feed-section-title">
                                Recent From Instagram
                            </div>
                            <?php
                            foreach ($resp->data as $post) {
                                ?>
                                <div class="instagram-feed-item instagram-feed-col-<?php echo $atts['columns']; ?>" style="padding:<?php echo $atts['padding']; ?>px;">
                                    <a href="<?php echo $post->link; ?>"><img src="<?php echo $post->images->standard_resolution->url; ?>" alt="<?php echo $post->caption->text; ?>" /></a>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
            } else {
                if ($user_tag) {
                    $url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?access_token=' . $access_token;
                    $resp = json_decode(file_get_contents($url));

                    if ($resp->meta->code === 200) {
                        $width = 100 / $atts['columns'];
                        ?>
                        <div class="instagram-feed-wrapper">
                            <div class="instagram-feed-section-title">
                                Recent From Instagram
                            </div>
                            <?php
                            if ($show_header) {
                                $url2 = 'https://api.instagram.com/v1/users/' . $user_id . '/?access_token=' . $access_token;
                                $user_resp = json_decode(file_get_contents($url2));
                                if ($user_resp->meta->code === 200) {
                                    ?>
                                    <div class="instagram-feed-header">
                                        <a href="https://instagram.com/<?php echo $user_resp->data->username; ?>/">
                                            <div class="instagram-feed-header-profile-pic">
                                                <img src="<?php echo $user_resp->data->profile_picture; ?>" />
                                            </div>
                                            <div class="instagram-feed-header-profile-username">
                                <?php echo '@' . $user_resp->data->username; ?>
                                            </div>
                                            <div class="instagram-feed-header-profile-bio">
                                <?php echo $user_resp->data->bio; ?>
                                            </div>
                                        </a>
                                        <div style="clear:both;"></div>
                                    </div>
                                    <?php
                                }
                            }

                            $i = 1;
                            $tags = explode(',', $user_tag);
                            if(!$this->check_pro_key()) {
                                $tags_use[] = $tags[0];
                            } else {
                                $tags_use = $tags;
                            }
                            
                            foreach ($resp->data as $post) {
                                $has_tag = false;
                                if ($i <= $atts['count']) {
                                    foreach($tags_use as $tag) {
                                        if (in_array($tag, $post->tags)) {
                                            $has_tag = true;
                                        } else {
                                            if(!$has_tag) {
                                                $has_tag = false;
                                            }
                                        }
                                    }
                                    if ($has_tag) {
                                        $i++;
                                        ?>
                                        <div class="instagram-feed-item instagram-feed-col-<?php echo $atts['columns']; ?>" style="padding:<?php echo $atts['padding']; ?>px;">
                                            <a href="<?php echo $post->link; ?>"><img src="<?php echo $post->images->standard_resolution->url; ?>" alt="<?php echo $post->caption->text; ?>" /></a>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    break;
                                }
                            }
                            ?>
                            <div style="clear:both;"></div>
                        </div>
                        <?php
                    }
                } else {
                    $url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?count=' . $atts['count'] . '&access_token=' . $access_token;
                    $resp = json_decode(file_get_contents($url));

                    if ($resp->meta->code === 200) {
                        $width = 100 / $atts['columns'];
                        ?>
                        <div class="instagram-feed-wrapper">
                            <div class="instagram-feed-section-title">
                                Recent From Instagram
                            </div>
                            <?php
                            if ($show_header) {
                                $url2 = 'https://api.instagram.com/v1/users/' . $user_id . '/?access_token=' . $access_token;
                                $user_resp = json_decode(file_get_contents($url2));
                                if ($user_resp->meta->code === 200) {
                                    ?>
                                    <div class="instagram-feed-header">
                                        <a href="https://instagram.com/<?php echo $user_resp->data->username; ?>/">
                                            <div class="instagram-feed-header-profile-pic">
                                                <img src="<?php echo $user_resp->data->profile_picture; ?>" />
                                            </div>
                                            <div class="instagram-feed-header-profile-username">
                                <?php echo '@' . $user_resp->data->username; ?>
                                            </div>
                                            <div class="instagram-feed-header-profile-bio">
                                <?php echo $user_resp->data->bio; ?>
                                            </div>
                                        </a>
                                        <div style="clear:both;"></div>
                                    </div>
                                    <?php
                                }
                            }
                            foreach ($resp->data as $post) {
                                ?>
                                <div class="instagram-feed-item instagram-feed-col-<?php echo $atts['columns']; ?>" style="padding:<?php echo $atts['padding']; ?>px;">
                                    <a href="<?php echo $post->link; ?>"><img src="<?php echo $post->images->standard_resolution->url; ?>" alt="<?php echo $post->caption->text; ?>" /></a>
                                </div>
                                <?php
                            }
                            ?>
                            <div style="clear:both;"></div>
                        </div>
                        <?php
                    }
                }
            }
            $output_string = ob_get_contents();

            $cache_file_temp = plugin_dir_path(__FILE__) . 'instagram-feed-cache-temp.txt';
            file_put_contents($cache_file_temp, $output_string, LOCK_EX);
            rename($cache_file_temp, $cache_file);

            ob_end_clean();
        }
        return $output_string;
    }

    //Facebook Feed Display Shortcode
    public function facebook_feed_display($atts) {
        global $wpdb;

        $cache_file = plugin_dir_path(__FILE__) . 'facebook-feed-cache.txt';

        if (get_option("bvads_facebook_settings_change") == 1) {
            $ignore_cache = true;

            update_option("bvads_facebook_settings_change", 0);
        } else {
            $ignore_cache = false;
        }

        if (!$ignore_cache && file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 15 ))) {
            //read from cache
            //less than 15 minutes old
            $output_string = file_get_contents($cache_file);
        } else {
            //cache outdated
            ob_start();

            if ($number_items = get_option("bvads_social_feed_facebook_number_items")) {
                if (!empty($number_items)) {
                    $count_default = $number_items;
                } else {
                    $count_default = 2;
                }
            } else {
                $count_default = 2;
            }

            if ($show_header = get_option("bvads_social_feed_facebook_show_header")) {
                $header_default = 1;
            } else {
                $header_default = 0;
            }

            $page_id = get_option("bvads_social_feed_facebook_page_id");

            $atts = shortcode_atts(
                    array(
                'count' => $count_default,
                'header' => $header_default
                    ), $atts, 'bvd-facebook-feed');
            
            if(!$this->check_pro_key()) {
                if($atts['count'] > 2) {
                    $atts['count'] = 2;
                } 
            }

            //Check Facebook creds
            $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=verify";
            $data = file_get_contents($request);
            $data = json_decode($data);
            //print_r($data);
            if ($data->verify->is_valid) { //Facebook creds are valid
                $access_token = get_option("bvads_facebook_oauth_token");
            } else {
                $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=get_app_token";
                $data = file_get_contents($request);
                $data = json_decode($data);

                $access_token = $data->token;
            }
            
            $total_limit = $atts['count'] + 10;
            $ch = curl_init("https://graph.facebook.com/" . $page_id . "/feed?access_token=" . $access_token . "&limit=" .  $total_limit);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($ch);
            curl_close($ch);
            $resp = json_decode($resp, true);
            //echo 'Response:<br/><br/>';
            //print_r($resp);
            ?>
            <div class="sf-facebook-feed-container">
                <?php
                if ($atts['header']) {
                    ?>
                    <div class="sf-facebook-feed-header">
                        Recent From Facebook
                    </div>
                    <?php
                }
                ?>
                <?php
                $i = 1;
                
                foreach ($resp['data'] as $feed) {
                    if($feed['from']['id'] == $page_id) {
                        if ($i <= $atts['count']) {
                            if ($feed['type'] == 'photo') {
                                $photo_id = $feed['object_id'];

                                $ch = curl_init("https://graph.facebook.com/" . $photo_id . "?access_token=" . $access_token);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                $picture = curl_exec($ch);
                                curl_close($ch);
                                $picture = json_decode($picture, true);

                                $image = '';
                                $image_size = 1000000000;
                                foreach ($picture['images'] as $photo) {
                                    if (($photo['width'] < $image_size) && ($photo['width'] >= 400)) {
                                        $image_size = $photo['width'];
                                        $image = $photo['source'];
                                    }
                                }
                                if (empty($image)) {
                                    $image_size = 0;
                                    foreach ($picture['images'] as $photo) {
                                        if ($photo['width'] > $image_size) {
                                            $image_size = $photo['width'];
                                            $image = $photo['source'];
                                        }
                                    }
                                }
                            } else {
                                $image = '';
                            }

                            if (array_key_exists('story', $feed)) {
                                $content = $feed['story'];
                            } else {
                                $content = $feed['message'];
                            }

                            $created_date = date('F j', strtotime($feed['created_time']));
                            $post_link = $feed['link'];
                            ?>
                            <div class="sf-facebook-feed-item">
                                <?php
                                if (!empty($image)) {
                                    ?>
                                    <div class="sf-facebook-feed-item-photo">
                                        <a href="<?php echo $post_link; ?>"><img src="<?php echo $image; ?>" /></a>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div class="sf-facebook-feed-item-text-wrapper">
                                    <div class="sf-facebook-feed-item-content">
                        <?php echo $content; ?>
                                    </div>
                                    <div class="sf-facebook-feed-item-date">
                        <?php echo $created_date; ?>
                                    </div>
                                </div>
                                <div style="clear:both;"></div>
                            </div>
                            <?php
                        }
                        $i++;
                    }
                }
                ?>
            </div>
            <?php
            $output_string = ob_get_contents();

            $cache_file_temp = plugin_dir_path(__FILE__) . 'facebook-feed-cache-temp.txt';
            file_put_contents($cache_file_temp, $output_string, LOCK_EX);
            rename($cache_file_temp, $cache_file);

            ob_end_clean();
        }
        return $output_string;
    }

    //Twitter Feed Display Shortcode
    public function twitter_feed_display($atts) {
        global $wpdb;

        $cache_file = plugin_dir_path(__FILE__) . 'twitter-feed-cache.txt';

        if (get_option("bvads_twitter_settings_change") == 1) {
            $ignore_cache = true;

            update_option("bvads_twitter_settings_change", 0);
        } else {
            $ignore_cache = false;
        }

        if (!$ignore_cache && file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 15 ))) {
            //read from cache
            //less than 15 minutes old
            $output_string = file_get_contents($cache_file);
        } else {
            //cache outdated
            ob_start();

            if ($number_items = get_option("bvads_social_feed_twitter_number_items")) {
                if (!empty($number_items)) {
                    $count_default = $number_items;
                } else {
                    $count_default = 2;
                }
            } else {
                $count_default = 2;
            }

            if ($show_header = get_option("bvads_social_feed_twitter_show_header")) {
                $header_default = 1;
            } else {
                $header_default = 0;
            }

            $user_name = get_option("bvads_twitter_screenname");

            $atts = shortcode_atts(
                    array(
                'count' => $count_default,
                'user_name' => $user_name,
                'header' => $header_default
                    ), $atts, 'bvd-twitter-feed');
            
            if(!$this->check_pro_key()) {
                if($atts['count'] > 2) {
                    $atts['count'] = 2;
                } 
            }

            $request = SFR_URL_TWITTER . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=get_feed&item_count=" . $atts['count'] . "&user_name=" . $atts['user_name'];
            $data = file_get_contents($request);
            $data = json_decode($data, true);
            /* echo 'Response:<br/><br/>';
              echo '<pre>';
              print_r($data);
              echo '</pre>'; */
            ?>
            <div class="sf-twitter-feed-container">
                <?php
                if ($atts['header']) {
                    ?>
                    <div class="sf-twitter-feed-header">
                        Recent From Twitter
                    </div>
                    <?php
                }
                ?>
                <?php
                $i = 1;
                foreach ($data['feed'] as $feed) {
                    if ($i <= $atts['count']) {
                        if (array_key_exists('media', $feed['entities'])) {
                            $image = $feed['entities']['media'][0]['media_url'];
                            $link = $feed['entities']['media'][0]['url'];
                        } else {
                            $image = '';
                            $link = $feed['entities']['urls']['url'];
                        }

                        $created_date = date('F j', strtotime($feed['created_at']));

                        $pos = strpos($feed['text'], 'http://');
                        if ($pos !== false) {
                            $url = substr($feed['text'], $pos);
                            $replace = '<a href="' . $url . '">' . $url . '</a>';
                            $text = str_replace($url, $replace, $feed['text']);
                        }
                        ?>
                        <div class="sf-twitter-feed-item">
                            <?php
                            if (!empty($image)) {
                                ?>
                                <div class="sf-twitter-feed-item-photo">
                                    <a href="<?php echo $link; ?>"><img src="<?php echo $image; ?>" /></a>
                                </div>
                                <?php
                            }
                            ?>
                            <div class="sf-twitter-feed-item-text-wrapper">
                                <div class="sf-twitter-feed-item-content">
                                    <?php echo $text; ?>
                                </div>
                                <div class="sf-twitter-feed-item-date">
                                    <?php echo $created_date; ?>
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                        </div>
                        <?php
                    }
                    $i++;
                }
                ?>
            </div>
            <?php
            $output_string = ob_get_contents();

            $cache_file_temp = plugin_dir_path(__FILE__) . 'twitter-feed-cache-temp.txt';
            file_put_contents($cache_file_temp, $output_string, LOCK_EX);
            rename($cache_file_temp, $cache_file);

            ob_end_clean();
        }
        return $output_string;
    }

    function showAdminMessages() {
        //Check Facebook creds
        $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=verify";
        $data = file_get_contents($request);
        $data = json_decode($data);
        //print_r($data);
        if (!$data->verify->is_valid) {
            echo '<div id="message" class="updated"><p><strong>Social Feeds: Facebook token has expired. Your Facebook feed is still working but you may want to go to the <a href="'.get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook&tab=basic-settings').'">Facebook page</a> to renew your token.</strong></p></div>';
        }
    }

}

$bvdSF = new bvdSocialFeeds();
