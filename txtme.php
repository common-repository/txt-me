<?php
/*
Plugin Name: txt.me
Plugin URI: https://txt.me/
Description: Add txt.me Live Chat Widget to your site
Version: 1.0.5
Author: txt-me
Author URI: https://profiles.wordpress.org/txtme/
*/

/*
     Copyright 2021  txt.me  (email : support {at} txt.me)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class txtme {
    public function __construct()
    {
        add_option('txtme_login');
        add_option('txtme_password');
        add_option('txtme_is_authenticated', 'false');
        add_option('txtme_apiKey');
        add_option('txtme_websiteSlug', 'websiteSlug');
        add_option('txtme_nonceAction');
        add_option('txtme_nonceValue');

        add_action('admin_menu', array($this, 'admin'));
        add_action('wp_enqueue_scripts', array($this, 'my_scripts_method'), 99, 1);
        add_filter('script_loader_tag', array($this, 'namespace_async_scripts'), 10, 2);
    }

    public function namespace_async_scripts($tag, $handle)
    {
        // Just return the tag normally if this isn't one we want to async
        if ('txt-me' !== $handle) {
            return $tag;
        }

        return str_replace(' src', ' async src', $tag);
    }

    public function my_scripts_method()
    {
        $meta = get_option('txtme_websiteSlug');
        if (empty($meta)) {
            return;
        }
        if ($meta === 'websiteSlug' || trim($meta) === '') {
            return;
        }
        wp_footer();
        wp_register_script('txt-me', 'https://v3.txt.me/livechat/js/wrapper/' . $meta, array(), false, true);
        wp_enqueue_script('txt-me');
    }


    public function admin()
    {
        if (function_exists('add_menu_page')) {
            add_menu_page(
                'txt.me Options',
                'txt.me',
                'administrator',
                basename(__FILE__),
                array(&$this, 'admin_form'),
                plugins_url('img/logo_small.png?1.0.1', __FILE__)
            );
        }
    }

    public function admin_form() {
        $txtme_websiteSlug = get_option( 'txtme_websiteSlug' );
        $nonceAction       = get_option( 'txtme_nonceAction' );
        $nonceValue        = get_option( 'txtme_nonceValue' );

        if (function_exists('wp_create_nonce')) {
            if (isset($_GET['_wpnonce'])) {
                if (wp_verify_nonce($_GET['_wpnonce'], $nonceAction)) {
                    if (function_exists('current_user_can') && !current_user_can('manage_options')) {
                        die ('Hacker?');
                    }
                    $txtme_apiKey = sanitize_text_field($_GET['token']);
                    update_option('txtme_apiKey', $txtme_apiKey);

                    update_option('txtme_is_authenticated', 'true');
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo esc_html__('Successfully logged in!', 'txt-me'); ?></p>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo esc_html__('Wrong nonce. Possible request was forged', 'txt-me'); ?></p>
                    </div>
                    <?php
                }
            } else {
                $nonceAction = wp_generate_password('21', false);
                update_option('txtme_nonceAction', $nonceAction);
                $nonceValue = wp_create_nonce($nonceAction);
                update_option('txtme_nonceValue', $nonceValue);
            }
        } else {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html__('Your WP is too old please upgrade it.', 'txt-me'); ?></p>
            </div>
            <?php
        }

        if (isset($_POST['logout'])) {
            if (function_exists('current_user_can') && !current_user_can('manage_options')) {
                die ('Hacker?');
            }

            if (function_exists('check_admin_referer')) {
                check_admin_referer('txtme_logout_form');
            }

            update_option('txtme_is_authenticated', 'false');
            update_option('txtme_apiKey', '');
            update_option('txtme_websiteSlug', '');
//			update_option( 'txtme_nonceValue', '' );
//			update_option( 'txtme_nonceAction', '' );
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__('Successfully log out from txt.me', 'txt-me'); ?></p>
            </div>
            <?php
            update_option('txtme_login', '');
            update_option('txtme_password', '');
        }

        if (isset($_POST['select'])) {
            if (function_exists('current_user_can') && !current_user_can('manage_options')) {
                die ('Hacker?');
            }

            if (function_exists('check_admin_referer')) {
                check_admin_referer('txtme_select_form');
            }

            if (isset($_POST['websiteSlug'])) {
                if ($txtme_websiteSlug === 'websiteSlug') {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo esc_html__('Widget successfully selected for txt.me', 'txt-me'); ?></p>
                    </div>
                    <?php
                    update_option('txtme_websiteSlug', sanitize_text_field($_POST['websiteSlug']));
                } elseif ($txtme_websiteSlug !== $_POST['websiteSlug']) {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo esc_html__('Widget successfully updated for txt.me', 'txt-me'); ?></p>
                    </div>
                    <?php
                    update_option('txtme_websiteSlug', sanitize_text_field($_POST['websiteSlug']));
                }
            }
        }

        $txtme_is_authenticated = get_option('txtme_is_authenticated');
        $txtme_apiKey = get_option('txtme_apiKey');
        $txtme_websiteSlug = get_option('txtme_websiteSlug');

        $login_url = ('https://settings.txt.me/login?callbackUrl=' . urlencode(network_admin_url('admin.php', __FILE__) . '?page=txtme.php&_wpnonce=' . $nonceValue));
        ?>

        <?php if ($txtme_is_authenticated === 'true') { ?>
            <div class="wrap">
                <h2><img src="<?php echo plugins_url('img/logo.png?1.0.1', __FILE__); ?>" alt=""/></h2>
                <h3><?php echo esc_html__("You logged in ", "txt-me"); ?></h3>
                <form name="logout" method="post" action="<?php echo esc_url(network_admin_url('admin.php', __FILE__)) . '?page=txtme.php'; ?>">
                    <?php
                    if (function_exists('wp_nonce_field')) {
                        wp_nonce_field('txtme_logout_form');
                    }
                    ?>
                    <input type="hidden" name="action" value="update"/>
                    <input type="hidden" name="page_options" value="txtme_login,txtme_password,txtme_is_authenticated,txtme_apiKey"/>
                    <p class="submit">
                        <input type="submit" name="logout" value="<?php echo esc_html__('Logout', 'txt-me'); ?>"/>
                    </p>
                </form>
            </div>

            <?php
            $response = wp_remote_get('https://v3.txt.me/livechat/company/widgets/list', array(
                'timeout' => 5,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                        "Content-Type" => "application/json",
                        "Authorization" => "Bearer " . $txtme_apiKey
                ),
                'body' => array(),
                'cookies' => array()
            ));

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo esc_html('Something goes wrong: ' . $error_message);
            } elseif (isset($response['body'])) {
                $bodyObject = json_decode($response['body'], true);
                if (count($bodyObject) >= 1) {
                    ?>
                    <div class="wrap">
                        <h2><?php echo esc_html__('txt.me List of Widgets', 'txt-me'); ?></h2>
                        <form name="select" method="post" action="<?php echo esc_url(network_admin_url( 'admin.php', __FILE__ )) . '?page=txtme.php'; ?>">
                            <?php
                            if (function_exists('wp_nonce_field')) {
                                wp_nonce_field('txtme_select_form');
                            }
                            ?>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">Not selected</th>
                                    <td>
                                        <input type="radio" id="contactChoice0" name="websiteSlug" value=""<?php echo (empty($txtme_websiteSlug) ? ' checked' : ''); ?>/>
                                    </td>
                                </tr>
                                <?php
                                foreach ($bodyObject as $item) {
                                    echo '    <tr>';
                                    echo '        <th scope="row">' . esc_html($item["name"]) . '</th>';
                                    echo '        <td>';
                                    echo '            <input type="radio" id="contactChoice' . esc_attr($item["id"]) . '" name="websiteSlug" value="' . esc_attr($item["id"]) . '"' . ($txtme_websiteSlug === $item["id"] ? ' checked' : '') . '/>';
                                    echo '        </td>';
                                    echo '    </tr>';
                                }
                                ?>
                            </table>
                            <input type="hidden" name="action" value="update"/>
                            <input type="hidden" name="page_options" value="txtme_websiteSlug"/>
                            <p class="submit">
                                <input type="submit" name="select" value="<?php echo esc_html__('Save selection', 'txt-me'); ?>"/>
                            </p>
                        </form>
                    </div>
                    <?php
                }
            } else {
                echo "Something goes wrong: Response not have body part";
            }
        } else {
            ?>
            <div class="wrap">
                <img src="<?php echo plugins_url('img/logo.png?1.0.1', __FILE__); ?>" alt=""/>
                <h2><?php echo esc_html__('txt.me Login Link', 'txt-me'); ?></h2>
                <a href="<?php echo esc_url($login_url); ?>" target="_blank"><?php echo esc_html__('Click to Login/Sign Up', 'txt-me'); ?></a>
            </div>
            <?php
        }
    }
}
$txtme = new txtme();