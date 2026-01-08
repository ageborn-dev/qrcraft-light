<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Admin {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( QRCRAFT_PLUGIN_FILE ), array( $this, 'add_action_links' ) );
        add_action( 'wp_ajax_qrcraft_regenerate_all', array( $this, 'ajax_regenerate_all' ) );
        add_action( 'wp_ajax_qrcraft_get_progress', array( $this, 'ajax_get_progress' ) );
        add_action( 'wp_ajax_qrcraft_regenerate_single', array( $this, 'ajax_regenerate_single' ) );
    }

    public function enqueue_scripts( $hook ) {
        $allowed_hooks = array(
            'woocommerce_page_qrcraft-settings',
            'edit.php',
        );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for post type
        $is_product_page = ( $hook === 'edit.php' && isset( $_GET['post_type'] ) && sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) === 'product' );

        if ( ! in_array( $hook, $allowed_hooks, true ) && ! $is_product_page ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style(
            'qrcraft-admin',
            QRCRAFT_PLUGIN_URL . 'admin/css/qrcraft-admin.css',
            array(),
            QRCRAFT_VERSION
        );

        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script(
            'qrcraft-admin',
            QRCRAFT_PLUGIN_URL . 'admin/js/qrcraft-admin.js',
            array( 'jquery', 'wp-color-picker' ),
            QRCRAFT_VERSION,
            true
        );

        wp_localize_script( 'qrcraft-admin', 'qrcraft', array(
            'ajax_url'         => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'qrcraft_nonce' ),
            'regenerating'     => __( 'Regenerating...', 'qrcraft' ),
            'complete'         => __( 'Complete!', 'qrcraft' ),
            'error'            => __( 'An error occurred.', 'qrcraft' ),
            'confirm_regen'    => __( 'This will regenerate all QR codes. Continue?', 'qrcraft' ),
        ) );
    }

    public function handle_activation_redirect() {
        if ( get_option( 'qrcraft_activated' ) === 'yes' ) {
            delete_option( 'qrcraft_activated' );

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for multi activation
            if ( ! isset( $_GET['activate-multi'] ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=qrcraft-settings' ) );
                exit;
            }
        }
    }

    public function add_action_links( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=qrcraft-settings' ),
            __( 'Settings', 'qrcraft' )
        );

        array_unshift( $links, $settings_link );
        return $links;
    }

    public function ajax_regenerate_all() {
        check_ajax_referer( 'qrcraft_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'qrcraft' ) ) );
        }

        $scheduler = new QRCraft_Scheduler();
        $scheduler->schedule_bulk_generation();

        wp_send_json_success( array( 'message' => __( 'Bulk generation started.', 'qrcraft' ) ) );
    }

    public function ajax_get_progress() {
        check_ajax_referer( 'qrcraft_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'qrcraft' ) ) );
        }

        $scheduler = new QRCraft_Scheduler();
        $progress = $scheduler->get_progress();

        wp_send_json_success( $progress );
    }

    public function ajax_regenerate_single() {
        check_ajax_referer( 'qrcraft_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'qrcraft' ) ) );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'qrcraft' ) ) );
        }

        $generator = new QRCraft_Generator();
        $result = $generator->generate_for_product( $product_id );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'QR code regenerated.', 'qrcraft' ),
                'url'     => $result,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to generate QR code.', 'qrcraft' ) ) );
        }
    }
}
