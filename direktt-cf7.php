<?php

/**
 * Plugin Name: Direktt Contact Form 7 Integration
 * Description: Direktt Contact Form 7 Integration Addon
 * Version: 1.0.3
 * Author: Direktt
 * Author URI: https://direktt.com/
 * License: GPL2
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$direktt_cf7_plugin_version = "1.0.3";
$direktt_cf7_github_update_cache_allowed = true;

require_once plugin_dir_path( __FILE__ ) . 'direktt-github-updater/class-direktt-github-updater.php';

$direktt_cf7_plugin_github_updater  = new Direktt_Github_Updater( 
    $direktt_cf7_plugin_version, 
    'direktt-cf7/direktt-cf7.php',
    'https://raw.githubusercontent.com/direktt/direktt-cf7/master/info.json',
    'direktt_cf7_github_updater',
    $direktt_cf7_github_update_cache_allowed );

add_filter( 'plugins_api', array( $direktt_cf7_plugin_github_updater, 'github_info' ), 20, 3 );
add_filter( 'site_transient_update_plugins', array( $direktt_cf7_plugin_github_updater, 'github_update' ));
add_filter( 'upgrader_process_complete', array( $direktt_cf7_plugin_github_updater, 'purge'), 10, 2 );

add_action( 'plugins_loaded', 'direktt_cf7_activation_check', -20 );

function direktt_cf7_activation_check() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $required_plugin = 'direktt/direktt.php';
    $is_required_active = is_plugin_active($required_plugin)
        || (is_multisite() && is_plugin_active_for_network($required_plugin));

    if (! $is_required_active) {
        // Deactivate this plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Prevent the “Plugin activated.” notice
        if (isset($_GET['activate'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Justification: not a form processing, just removing a query var.
            unset($_GET['activate']);
        }

        // Show an error notice for this request
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html__('Direktt Contact Form 7 activation failed: The Direktt WordPress Plugin must be active first.', 'direktt-cf7')
                . '</p></div>';
        });

        // Optionally also show the inline row message in the plugins list
        add_action(
            'after_plugin_row_direktt-cf7/direktt-cf7.php',
            function () {
                echo '<tr class="plugin-update-tr"><td colspan="3" style="box-shadow:none;">'
                    . '<div style="color:#b32d2e;font-weight:bold;">'
                    . esc_html__('Direktt Contact Form 7 requires the Direktt WordPress Plugin to be active. Please activate it first.', 'direktt-cf7')
                    . '</div></td></tr>';
            },
            10,
            0
        );
    }
}

add_filter( 'wpcf7_editor_panels', 'direktt_cf7_add_panels' );

function direktt_cf7_add_panels( $panels ) {
    $panels['direktt'] = array(
        'title'    => esc_html__( 'Direktt', 'direktt-cf7' ),
        'callback' => 'direktt_cf7_render_panel',
    );

    return $panels;
}

function direktt_cf7_render_panel( $post ) {
    $send_to_subscriber = get_post_meta( $post->id(), '_direktt_cf7_send_to_subscriber', true );
    $subscriber_message = get_post_meta( $post->id(), '_direktt_cf7_subscriber_message', true );
    $send_to_admin      = get_post_meta( $post->id(), '_direktt_cf7_send_to_admin', true );
    $admin_message      = get_post_meta( $post->id(), '_direktt_cf7_admin_message', true );
    ?>
    <h1><?php echo esc_html__( 'Direktt Settings', 'direktt-cf7' ); ?></h1>
    <p><?php echo esc_html__( 'These settings allow you to send messages to subscribers and admins when the form is submitted.', 'direktt-cf7' ); ?></p>
    <p><?php echo esc_html__( 'You can also customize the messages sent to subscribers and admins.', 'direktt-cf7' ); ?></p>
    <p><?php echo esc_html__( 'Make sure to save your changes after configuring the settings.', 'direktt-cf7' ); ?></p>
    <h2><?php echo esc_html__( 'Send to Subscriber', 'direktt-cf7' ); ?></h3>
    <table class="form-table direktt-cf7-table">
        <tr>
            <th scope="row">
                <?php echo esc_html__( 'Enable', 'direktt-cf7' ); ?>
            </th>
            <td>
                <input type="checkbox" id="send-to-subscriber" name="send-to-subscriber" value="1" <?php checked( $send_to_subscriber, 1 ); ?>>
                <label for="send-to-subscriber"><?php echo esc_html__( 'Send a message to subscriber when form is submitted', 'direktt-cf7' ); ?></label>
            </td>
        </tr>
        <tr id="direktt-cf7-mt-subscriber">
            <th scope="row">
                <label for="subscriber-message"><?php echo esc_html__( 'Subscriber message', 'direktt-cf7' ); ?></label>
            </th>
            <td>
                <textarea id="subscriber-message" name="subscriber-message" cols="100" rows="18" class="large-text code"><?php echo esc_textarea( $subscriber_message ); ?></textarea>
                <p class="description">
                    <?php echo esc_html__( 'Message sent to subscriber when the form is submitted. TODO etc text.', 'direktt-cf7' ); ?>
                </p>
            </td>
        </tr>
    </table>
    <h2><?php echo esc_html__( 'Send to Admin', 'direktt-cf7' ); ?></h3>
    <table class="form-table direktt-cf7-table">
        <tr>
            <th scope="row">
                <?php echo esc_html__( 'Enable', 'direktt-cf7' ); ?>
            </th>
            <td>
                <input type="checkbox" id="send-to-admin" name="send-to-admin" value="1" <?php checked( $send_to_admin, 1 ); ?>>
                <label for="send-to-admin"><?php echo esc_html__( 'Send a message to admin when form is submitted', 'direktt-cf7' ); ?></label>
            </td>
        </tr>
        <tr id="direktt-cf7-mt-admin">
            <th scope="row">
                <label for="admin-message"><?php echo esc_html__( 'Admin message', 'direktt-cf7' ); ?></label>
            </th>
            <td>
                <textarea id="admin-message" name="admin-message" cols="100" rows="18" class="large-text code"><?php echo esc_textarea( $admin_message ); ?></textarea>
                <p class="description">
                    <?php echo esc_html__( 'Message sent to admin when the form is submitted. TODO etc text.', 'direktt-cf7' ); ?>
                </p>
            </td>
        </tr>
        <?php
        wp_nonce_field( 'direktt_cf7_save', 'direktt_cf7_save_nonce' );
        ?>
    </table>
    <?php
}

add_action( 'wpcf7_after_save', 'direktt_cf7_save_settings' );

function direktt_cf7_save_settings( $contact_form ) {
    if ( ! isset( $_POST['direktt_cf7_save_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['direktt_cf7_save_nonce'] ) ), 'direktt_cf7_save' ) ) {
        return;
    }

    $post_id = $contact_form->id();

    $send_to_subscriber = isset( $_POST['send-to-subscriber'] ) ? 1 : 0;
    $subscriber_message = isset( $_POST['subscriber-message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['subscriber-message'] ) ) : '';
    $send_to_admin      = isset( $_POST['send-to-admin'] ) ? 1 : 0;
    $admin_message      = isset( $_POST['admin-message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['admin-message'] ) ) : '';

    update_post_meta( $post_id, '_direktt_cf7_send_to_subscriber', $send_to_subscriber );
    update_post_meta( $post_id, '_direktt_cf7_subscriber_message', $subscriber_message );
    update_post_meta( $post_id, '_direktt_cf7_send_to_admin', $send_to_admin );
    update_post_meta( $post_id, '_direktt_cf7_admin_message', $admin_message );
}

add_action( 'wpcf7_mail_sent', 'direktt_cf7_send_messages' );

function direktt_cf7_send_messages( $contact_form ) {
    $submission = WPCF7_Submission::get_instance();
    if ( ! $submission ) {
        return;
    }

    $posted_data = $submission->get_posted_data();

    $post_id = $contact_form->id();

    // helper: sanitize a single value (recurses for arrays)
    $sanitize_cf7_value = function( $key, $value ) use ( &$sanitize_cf7_value ) {
        if ( is_array( $value ) ) {
            $out = array();
            foreach ( $value as $v ) {
                $out[] = $sanitize_cf7_value( $key, $v );
            }
            return $out;
        }

        $value = (string) $value;
        if ( $value === '' ) {
            return '';
        }

        // email
        if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
            return sanitize_email( $value );
        }

        // url
        if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
            return esc_url_raw( $value );
        }

        // numbers (int/float)
        if ( is_numeric( $value ) ) {
            // keep numeric type but cast to string for message usage
            return ( strpos( $value, '.' ) !== false ) ? (string) floatval( $value ) : (string) intval( $value );
        }

        // telephone-like fields (based on name)
        if ( preg_match( '/phone|tel|mobile|telephone/i', $key ) ) {
            // allow digits, +, spaces, parentheses, dashes
            $value = preg_replace( '/[^\d+\s\-\(\)]/', '', $value );
            return sanitize_text_field( $value );
        }

        // textarea / multiline detection: allow longer input via sanitize_textarea_field
        if ( strpos( $value, "\n" ) !== false || strlen( $value ) > 200 ) {
            return sanitize_textarea_field( $value );
        }

        // fallback: simple text
        return sanitize_text_field( $value );
    };

    // build replacements map
    $placeholders = array();

    $subscription_id = isset( $posted_data['direktt-subscription-id'] ) ? $posted_data['direktt-subscription-id'] : '';

    // sanitize posted fields
    foreach ( $posted_data as $key => $value ) {
        $sanitized = $sanitize_cf7_value( $key, $value );

        // join arrays (checkboxes, multiple selects) into a readable string
        if ( is_array( $sanitized ) ) {
            $sanitized = implode( ', ', $sanitized );
        }

        $placeholders[ '[' . $key . ']' ] = $sanitized;
    }

    $send_to_subscriber = get_post_meta( $post_id, '_direktt_cf7_send_to_subscriber', true );
    $subscriber_message = get_post_meta( $post_id, '_direktt_cf7_subscriber_message', true );
    $send_to_admin      = get_post_meta( $post_id, '_direktt_cf7_send_to_admin', true );
    $admin_message      = get_post_meta( $post_id, '_direktt_cf7_admin_message', true );
    
    if ( $send_to_subscriber && ! empty( $subscriber_message ) && ! empty( $subscription_id ) ) {
        $final_subscriber_message = strtr( $subscriber_message, $placeholders );

        $pushSubscriberMessage = array(
            "type" =>  "text",
            "content" => $final_subscriber_message,
        );

        Direktt_Message::send_message( array( $subscription_id => $pushSubscriberMessage ) );
    }

    if ( $send_to_admin && ! empty( $admin_message ) ) {
        $final_admin_message = strtr( $admin_message, $placeholders );

        $pushAdminMessage = array(
            "type" =>  "text",
            "content" => $final_admin_message,
        );

        Direktt_Message::send_message_to_admin( $pushAdminMessage );

        if ( ! empty( $subscription_id ) ) {
            $user = Direktt_User::get_user_by_subscription_id( $subscription_id );
            $display_name = get_the_title( $user['ID'] );

            Direktt_Message::send_message_to_admin(
                array(
                    "type" => "rich",
                    "content" => json_encode( 
                        array(
                            "subtype" => "buttons",
                            "msgObj" => array(
                                array(
                                    "txt" => esc_html( "$display_name ($subscription_id)" ) . esc_html__( ' submitted a form. Click to chat with them.', 'direktt-cf7' ),
                                    "label" => esc_html__( 'Go to chat', 'direktt-cf7' ),
                                    "action" => array(
                                        "type" => "chat",
                                        "params" => array(
                                            "subscriptionId" => "$subscription_id",
                                        ),
                                        "retVars" => new stdClass()
                                    )
                                )
                            )
                        )
                    )
                )
            );
        }
    }
}

add_filter( 'wpcf7_form_hidden_fields', 'direktt_cf7_add_subscription_id' );

function direktt_cf7_add_subscription_id( $hidden_fields ) {
    global $direktt_user;
    $subscription_id = $direktt_user['direktt_user_id'] ?? '';
    $hidden_fields['direktt-subscription-id'] = $subscription_id;

    return $hidden_fields;
}

add_action( 'admin_enqueue_scripts', 'direktt_cf7_enqueue_scripts' );

function direktt_cf7_enqueue_scripts() {
    global $pagenow;
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }
    $page   = preg_replace( '/.*page_/', '', $screen->id );
    if ( 'admin.php' === $pagenow && ( 'wpcf7-new' === $page || ( 'wpcf7' === $page && isset( $_GET['post'] ) ) ) ) {
        wp_enqueue_style( 'direktt-cf7-css', plugin_dir_url( __FILE__ ) . 'assets/css/direktt-cf7.css', array(), filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/direktt-cf7.css' ) );
    }
}