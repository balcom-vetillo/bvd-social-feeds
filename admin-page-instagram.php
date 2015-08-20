<div class="wrap">
    <h1>Instagram Settings</h1>
    <div class="designed-by-wrapper">
        <p>Plugin designed and developed by<br/><a href="https://www.balcom-vetillo.com/" target="_blank">Balcom-Vetillo Design</a>.</p>
        <a href="https://www.balcom-vetillo.com/" target="_blank"><img src="<?php echo plugins_url('images/BVD-Logo-vert.png', __FILE__); ?>" /></a>
    </div>
    <div class="main-content-wrapper">
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-instagram&tab=basic-settings'); ?>" class="nav-tab <?php echo $active_tab == 'basic-settings' ? 'nav-tab-active' : ''; ?>">Basic Settings</a>
            <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-instagram&tab=display-options'); ?>" class="nav-tab <?php echo $active_tab == 'display-options' ? 'nav-tab-active' : ''; ?>">Display Options</a>
            <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-instagram&tab=shortcode'); ?>" class="nav-tab <?php echo $active_tab == 'shortcode' ? 'nav-tab-active' : ''; ?>">Shortcode and Shortcode Options</a>
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
                        if (get_option("bvads_social_feed_instagram_access_token")) {
                            ?>
                            <div class="facebook-connection-status-message">Instagram Connection Status: <strong>Connected</strong></div>
                            <div class="social-feeds-disconnect-btn-wrapper">
                                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-instagram&disconnect_account=instagram'); ?>">Disconnect Account</a>
                            </div>
                            <div class="social-feeds-admin-page-section social-feeds-current-user-wrapper">
                                <div class="social-feeds-current-user-item">
                                    <strong>Current User ID:</strong> <?php echo get_option("bvads_social_feed_instagram_user_id"); ?>
                                </div>
                                <div class="social-feeds-current-user-item">
                                    <strong>Current User Name:</strong> <?php echo get_option("bvads_social_feed_instagram_username"); ?>
                                </div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="facebook-connection-status-message">Instagram Connection Status: <strong>Not Connected</strong></div>
                            <a class="button button-primary button-large" href="https://api.instagram.com/oauth/authorize/?client_id=<?php echo $this->instagram_client_id; ?>&redirect_uri=<?php echo urlencode("https://balcom-vetillo.com/social-feeds-redirect/instagram.php/?instagram_auth=auth&bvd_referer=" . $_SERVER['HTTP_HOST']); ?>&response_type=code">
                                Authorize Instagram Account
                            </a>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="social-feeds-admin-page-section social-feeds-extra-settings-wrapper">
                        <div class="social-feeds-section-title">
                            Lookup a Different User ID by Username
                        </div>
                        <form action="" method="post">
                            <input type="hidden" name="bvd-post-action" value="set-instagram-api-settings" />
                            <div class="form-section">
                                <div class="form-section-left"><label for="user-name">Instagram Username</label></div>
                                <div class="form-section-right">
                                    <input type="text" name="user-name" id="user-name" placeholder="Username">
                                    <div class="form-section-right-hint">Only required if you want to display a different user's feed. The feed must be public.</div>
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
                        $number_photos = get_option("bvads_social_feed_instagram_number_photos");
                        $number_columns = get_option("bvads_social_feed_instagram_number_columns");
                        $padding_around = get_option("bvads_social_feed_instagram_padding_around");
                        $user_tag = get_option("bvads_social_feed_instagram_user_tag");
                        $show_header = get_option("bvads_social_feed_instagram_show_header");
                        ?>
                        <div class="social-feeds-section-title">
                            Display Options
                        </div>
                        <form action="" method="post">
                            <input type="hidden" name="bvd-post-action" value="set-instagram-display-options" />
                            <div class="form-section">
                                <div class="form-section-left">
                                    <label for="number-display">Number of Feed Items to Display</label>
                                </div>
                                <div class="form-section-right">
                                    <?php
                                    if (!$this->check_pro_key()) {
                                        if ($number_photos) {
                                            if ($number_photos == 1) {
                                                $option1 = 'selected';
                                            } else {
                                                $option1 = '';
                                            }
                                            if ($number_photos == 2) {
                                                $option2 = 'selected';
                                            } else {
                                                $option2 = '';
                                            }
                                            if ($number_photos == 3) {
                                                $option3 = 'selected';
                                            } else {
                                                $option3 = '';
                                            }
                                            if ($number_photos == 4) {
                                                $option4 = 'selected';
                                            } else {
                                                $option4 = '';
                                            }
                                            if ($number_photos == 5) {
                                                $option5 = 'selected';
                                            } else {
                                                $option5 = '';
                                            }
                                            ?>                                            
                                            <select name="number-display" id="number-display">
                                                <option value="0">Select Number of Feed Items</option>
                                                <option value="1" <?php echo $option1; ?>>1</option>
                                                <option value="2" <?php echo $option2; ?>>2</option>
                                                <option value="3" <?php echo $option3; ?>>3</option>
                                                <option value="4" <?php echo $option4; ?>>4</option>
                                                <option value="5" <?php echo $option5; ?>>5</option>
                                            </select>
                                            <?php
                                        } else {
                                            ?>
                                            <select name="number-display" id="number-display">
                                                <option value="0">Select Number of Feed Items</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                            </select>
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">Unlock the ability to display any number of feed items with a Pro Key.</div>
                                        <?php
                                    } else {
                                        if ($number_photos) {
                                            ?>
                                            <input type="text" name="number-display" id="number-display" placeholder="Default: 5" value="<?php echo $number_photos; ?>" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="number-display" id="number-display" placeholder="Default: 5" />
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div style="clear:left;"></div>
                            </div>
                            <div class="form-section">
                                <div class="form-section-left">
                                <label for="number-columns">Number of Columns</label>
                                </div>
                                <div class="form-section-right">
                                    <?php
                                    if (!$this->check_pro_key()) {
                                        if ($number_columns) {
                                            if ($number_columns == 1) {
                                                $option1 = 'selected';
                                            } else {
                                                $option1 = '';
                                            }
                                            if ($number_columns == 2) {
                                                $option2 = 'selected';
                                            } else {
                                                $option2 = '';
                                            }
                                            if ($number_columns == 3) {
                                                $option3 = 'selected';
                                            } else {
                                                $option3 = '';
                                            }
                                            if ($number_columns == 4) {
                                                $option4 = 'selected';
                                            } else {
                                                $option4 = '';
                                            }
                                            if ($number_columns == 5) {
                                                $option5 = 'selected';
                                            } else {
                                                $option5 = '';
                                            }
                                            ?>                                            
                                            <select name="number-columns" id="number-columns">
                                                <option value="0">Select Number of Columns</option>
                                                <option value="1" <?php echo $option1; ?>>1</option>
                                                <option value="2" <?php echo $option2; ?>>2</option>
                                                <option value="3" <?php echo $option3; ?>>3</option>
                                                <option value="4" <?php echo $option4; ?>>4</option>
                                                <option value="5" <?php echo $option5; ?>>5</option>
                                            </select>
                                            <?php
                                        } else {
                                            ?>
                                            <select name="number-columns" id="number-columns">
                                                <option value="0">Select Number of Columns</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                            </select>
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">Unlock the ability to choose any number of columns with a Pro Key.</div>
                                        <?php
                                    } else {
                                        if ($number_columns) {
                                            ?>
                                            <input type="text" name="number-columns" id="number-columns" placeholder="Default: 5" value="<?php echo $number_columns; ?>" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="number-columns" id="number-columns" placeholder="Default: 5" />
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <div style="clear:left;"></div>
                            </div>
                            <div class="form-section">
                                <div class="form-section-left">
                                <label for="padding-around">Padding Around Images (px)</label>
                                </div>
                                <div class="form-section-right">
                                <?php
                                if ($padding_around) {
                                    ?>
                                    <input type="text" name="padding-around" id="padding-around" placeholder="Default: 5" value="<?php echo $padding_around; ?>" />
                                    <?php
                                } else {
                                    ?>
                                    <input type="text" name="padding-around" id="padding-around" placeholder="Default: 5" />
                                    <?php
                                }
                                ?>
                                </div>
                                <div style="clear:left;"></div>
                            </div>
                            <div class="form-section">
                                <div class="form-section-left">
                                    <?php
                                    if (!$this->check_pro_key()) {
                                        $placeholder = 'User Tag';
                                        $tag_hint = "To only show feed items with a certain tag, enter it below. Leave blank to show any feed item. <strong>You will only be able to pick one tag. To specify more than one tag here, you will need to purchase a Pro Key.</strong><br/>This will only look for this tag in the chosen user's feed.<br/>To find feed items from any public feed by tag, use the shortcode option.";
                                        ?>
                                        <label for="user-tag">Set Tag</label>
                                        <?php
                                    } else {
                                        $placeholder = 'User Tags';
                                        $tag_hint = "To only show feed items with certain tags, enter them below. Leave blank to show any feed item. Separate multiple tags with a comma.<br/>This will only look for these tags in the chosen user's feed.<br/>To find feed items from any public feed by tag, use the shortcode option.";
                                        ?>
                                        <label for="user-tag">Set Tags</label>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="form-section-right">
                                <?php
                                if ($user_tag) {
                                    ?>
                                    <input type="text" name="user-tag" id="user-tag" placeholder="<?php echo $placeholder; ?>" value="<?php echo $user_tag; ?>" />
                                    <?php
                                } else {
                                    ?>
                                    <input type="text" name="user-tag" id="user-tag" placeholder="<?php echo $placeholder; ?>" />
                                    <?php
                                }
                                ?>
                                    <div class="form-section-right-hint"><?php echo $tag_hint; ?></div>
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
                                <div class="form-section-right-hint">The header will display the feed's profile image, username, and description.</div>
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
                                <pre>[bvd-instagram-feed]</pre>
                            </div>
                            <div class="social-feeds-shortcode-options">
                                <div class="social-feeds-section-title">
                                    Shortcode Options
                                </div>
                                <div class="social-feeds-shortcode-message">
                                    <strong>Warning!</strong> Options set in the shortcode will override the options set in the plugin settings.
                                </div>
                                <div class="social-feeds-shortcode-option-item">
                                    <strong>use_tags</strong> -- boolean -- Option to display posts from any public feed. If set to true, this will ignore the selected user and display posts from any public feed based on the tags setting below. -- Default: false 
                                    <div class="shortcode-example">Example: [bvd-instagram-feed use_tags=false]</div>
                                </div>
                                <div class="social-feeds-shortcode-option-item">
                                    <?php
                                    if(!$this->check_pro_key()) {
                                        ?>
                                        <strong>tags</strong> -- string -- The tag to search for. Will only be used if <strong>use_tags</strong> is set to true. -- Default: ''
                                        <div class="shortcode-example">Example: [bvd-instagram-feed use_tags=true tags="nature"]</div>
                                            <div class="shortcode-no-pro-key">You will only be able to choose 1 tag. If more than 1 tag is passed with this option, only the first tag will be used. To unlock the ability to choose any number of tags, you need a pro key.</div>
                                        <?php
                                    } else {
                                        ?>
                                        <strong>tags</strong> -- string -- The tags to search for. Will only be used if <strong>use_tags</strong> is set to true. Separate multiple tags with a comma. -- Default: ''
                                        <div class="shortcode-example">Example: [bvd-instagram-feed use_tags=true tags="nature,landscape"]</div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="social-feeds-shortcode-option-item">
                                    <strong>count</strong> -- integer -- Number of feed items to display. -- Default: 5
                                    <div class="shortcode-example">Example: [bvd-instagram-feed count=5]</div>
                                    <?php
                                    if(!$this->check_pro_key()) {
                                        ?>
                                        <div class="shortcode-no-pro-key">You will only be able to display a max of 5 feed items. If a number higher than 5 is passed with this option, the value will be reset to 5. To unlock the ability to display any number of feed items, you need a pro key.</div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="social-feeds-shortcode-option-item">
                                    <strong>columns</strong> -- integer -- Number of columns. -- Default: 5
                                    <div class="shortcode-example">Example: [bvd-instagram-feed columns=5]</div>
                                    <?php
                                    if(!$this->check_pro_key()) {
                                        ?>
                                        <div class="shortcode-no-pro-key">You will only be able to choose a max of 5 columns. If a number higher than 5 is passed with this option, the value will be reset to 5. To unlock the ability to choose any number of columns, you need a pro key.</div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="social-feeds-shortcode-option-item">
                                    <strong>padding</strong> -- integer -- The padding, in pixels, around each photo.  -- Default: 5
                                    <div class="shortcode-example">Example: [bvd-instagram-feed padding=5]</div>
                                </div>
                            </div>
                            <div class="social-feeds-shortcode-usage-wrapper">
                                <p>You can place the shortcode in the Wordpress visual editor on any page or post (make sure the visual editor tab is selected) just as it appears above.</p>
                                <p>To use the shortcode in a template file, use this PHP code: <code>echo do_shortcode("[bvd-instagram-feed]");</code></p>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>
</div>