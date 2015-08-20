<div class="wrap">
    <h1>Twitter Settings</h1>
    <div class="designed-by-wrapper">
        <p>Plugin designed and developed by<br/><a href="https://www.balcom-vetillo.com/" target="_blank">Balcom-Vetillo Design</a>.</p>
        <a href="https://www.balcom-vetillo.com/" target="_blank"><img src="<?php echo plugins_url('images/BVD-Logo-vert.png', __FILE__); ?>" /></a>
    </div>
    <div class="main-content-wrapper">
        <?php
        //Check if there is a Pro Key in the DB
        //if ($this->check_pro_key()) {
        ?>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-twitter&tab=basic-settings'); ?>" class="nav-tab <?php echo $active_tab == 'basic-settings' ? 'nav-tab-active' : ''; ?>">Basic Settings</a>
            <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-twitter&tab=display-options'); ?>" class="nav-tab <?php echo $active_tab == 'display-options' ? 'nav-tab-active' : ''; ?>">Display Options</a>
            <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-twitter&tab=shortcode'); ?>" class="nav-tab <?php echo $active_tab == 'shortcode' ? 'nav-tab-active' : ''; ?>">Shortcode and Shortcode Options</a>
        </h2>

        <?php
        if ($this->submit_success) {
            ?>
            <div id="social-feeds-message" class="updated">
                <p>Social Feeds Plugin Settings Updated.</p>
            </div>
            <?php
        }
        ?>

        <div class="social-feeds-settings-container">
            <?php
            switch ($active_tab) {
                case 'basic-settings' :
                    ?>
                    <div class="social-feeds-login-btn-wrapper">
                        <?php
                        //Check Twitter creds
                        $request = SFR_URL_TWITTER . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=verify";
                        $data = file_get_contents($request);
                        $data = json_decode($data);
                        //print_r($data);
                        if ($data->verify) { //Twitter creds are valid
                            ?>
                            <div class="facebook-connection-status-message">Twitter Connection Status: <strong>Connected</strong></div>
                            <div class="social-feeds-disconnect-btn-wrapper">
                                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-twitter&disconnect_account=twitter'); ?>">Disconnect Account</a>
                            </div>
                            <div class="social-feeds-admin-page-section social-feeds-current-user-wrapper">
                                <div class="social-feeds-current-user-item">
                                    <strong>Current User Name:</strong> <?php echo get_option("bvads_twitter_screenname"); ?>
                                </div>
                            </div>
                            <?php
                        } else { //Twitter creds are not valid, so login
                            ?>
                            <div class="facebook-connection-status-message">Twitter Connection Status: <strong>Not Connected</strong></div>
                            <a class="button button-primary button-large" href="<?php echo SFR_URL; ?>?sfr_uuid=<?php echo $this->uuid; ?>&callback=<?php echo $this->callback_url(); ?>&sfr_twitter=twitter_login">Authorize Twitter Account</a>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="social-feeds-admin-page-section social-feeds-extra-settings-wrapper">
                        <div class="social-feeds-section-title">
                            Twitter Feed Information
                        </div>
                        <form action="" method="post">
                            <input type="hidden" name="bvd-post-action" value="set-twitter-api-settings" />
                            <div class="form-section">
                                <div class="form-section-left">
                                    <label for="page-id">Twitter User Name</label>
                                </div>
                                <div class="form-section-right">
                                    <input type="text" name="page-id" id="page-id" placeholder="User Name">
                                    <div class="form-section-right-hint">Enter a Twitter username to display that user's feed. User's feed must be public.</div>
                                </div>
                                <div style="clear:left;"></div>
                            </div>
                            <div class="form-section">
                                <input type="submit" value="Submit" />
                            </div>
                        </form>
                    </div>
                    <?php
                    break;

                case 'display-options' :
                    ?>
                    <div class="social-feeds-admin-page-section social-feeds-display-options-wrapper">
                        <?php
                        $number_items = get_option("bvads_social_feed_twitter_number_items");
                        $show_header = get_option("bvads_social_feed_twitter_show_header");
                        ?>
                        <div class="social-feeds-section-title">
                            Display Options
                        </div>
                        <form action="" method="post">
                            <input type="hidden" name="bvd-post-action" value="set-twitter-display-options" />
                            <div class="form-section">
                                <div class="form-section-left">
                                    <label for="number-display">Number of Feed Items to Display</label>
                                </div>
                                <div class="form-section-right">
                                    <?php
                                    if (!$this->check_pro_key()) {
                                        if ($number_items) {
                                            if ($number_items == 1) {
                                                $option1 = 'selected';
                                            } else {
                                                $option1 = '';
                                            }
                                            if ($number_items == 2) {
                                                $option2 = 'selected';
                                            } else {
                                                $option2 = '';
                                            }
                                            ?>                                            
                                            <select name="number-display" id="number-display">
                                                <option value="0">Select Number of Feed Items</option>
                                                <option value="1" <?php echo $option1; ?>>1</option>
                                                <option value="2" <?php echo $option2; ?>>2</option>
                                            </select>
                                            <?php
                                        } else {
                                            ?>
                                            <select name="number-display" id="number-display">
                                                <option value="0">Select Number of Feed Items</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                            </select>
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">Unlock the ability to display any number of feed items with a Pro Key.</div>
                                        <?php
                                    } else {
                                        if ($number_items) {
                                            ?>
                                            <input type="text" name="number-display" id="number-display" placeholder="Default: 2" value="<?php echo $number_items; ?>" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="number-display" id="number-display" placeholder="Default: 2" />
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div style="clear:left;"></div>
                            </div>
                            <div class="form-section">
                                <?php
                                if ($show_header) {
                                    ?>
                                    <input type="checkbox" name="show-header" id="show-header" value="1" checked />
                                    <?php
                                } else {
                                    ?>
                                    <input type="checkbox" name="show-header" id="show-header" value="1" />
                                    <?php
                                }
                                ?>
                                <label for="show-header">Show Header</label>
                                <div class="form-section-right-hint">The header will say "Recent From Twitter"</div>
                            </div>
                            <div class="form-section">
                                <input type="submit" value="Submit" />
                            </div>
                        </form>
                    </div>
                    <?php
                    break;

                case 'shortcode' :
                    ?>
                    <div class="social-feeds-admin-page-section social-feeds-shortcode-doc-wrapper">
                        <div class="social-feeds-section-title">
                            Shortcode
                        </div>
                        <div class="social-feeds-shortcode-doc">
                            <div class="social-feeds-shortcode">
                                <p>Base shortcode to display feed with all preset options</p>
                                <pre>[bvd-twitter-feed]</pre>
                            </div>
                            <div class="social-feeds-shortcode-options">
                                <div class="social-feeds-section-title">
                                    Shortcode Options
                                </div>
                                <div class="social-feeds-shortcode-message">
                                    <strong>Warning!</strong> Options set in the shortcode will override the options set in the plugin settings.
                                </div>
                                <div class="social-feeds-shortcode-option-item">
                                    <strong>user_name</strong> -- string -- User name of feed to display. -- Default: {authenticated user}
                                    <div class="shortcode-example">Example: [bvd-twitter-feed user_name='twitterapi']</div>
                                </div>
                                <div class="social-feeds-shortcode-option-item">
                                    <strong>count</strong> -- integer -- Number of feed items to display. -- Default: 2
                                    <div class="shortcode-example">Example: [bvd-twitter-feed count=2]</div>
                                    <?php
                                    if(!$this->check_pro_key()) {
                                        ?>
                                        <div class="shortcode-no-pro-key">You will only be able to display a max of 2 feed items. If a number higher than 2 is passed with this option, the value will be reset to 2. To unlock the ability to display any number of feed items, you need a pro key.</div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="social-feeds-shortcode-option-item">
                                    <strong>header</strong> -- integer -- Choose to show or hide the feed header. Set to 0 to hide, 1 to show. -- Default: 0
                                    <div class="shortcode-example">Example: [bvd-twitter-feed header=0]</div>
                                </div>
                            </div>
                            <div class="social-feeds-shortcode-usage-wrapper">
                                <p>You can place the shortcode in the Wordpress visual editor on any page or post (make sure the visual editor tab is selected) just as it appears above.</p>
                                <p>To use the shortcode in a template file, use this PHP code: <code>echo do_shortcode("[bvd-twitter-feed]");</code></p>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
        </div>
        <?php
        /* } else { //There isn't a Pro Key
          echo '<strong>Twitter is a Pro Feature.</strong>';
          } */
        ?>
    </div>
</div>