<?php
/**
 * Plugin Name:       QRCraft
 * Description:       The simplest way to add QR codes to your WooCommerce store. Lightweight, automatic, and hassle-free.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Ageborn Dev
 * Author URI:        https://github.com/ageborn-dev
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       qrcraft
 * WC requires at least: 8.0
 * WC tested up to:   10.5
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'QRCRAFT_VERSION', '1.0.0' );
define( 'QRCRAFT_PLUGIN_FILE', __FILE__ );
define( 'QRCRAFT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QRCRAFT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QRCRAFT_UPLOAD_DIR', 'qrcraft' );

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

final class QRCraft {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->check_requirements();
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function check_requirements() {
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            return;
        }

        add_action( 'admin_init', array( $this, 'check_woocommerce' ) );
    }

    public function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
            deactivate_plugins( plugin_basename( QRCRAFT_PLUGIN_FILE ) );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Standard WP pattern for activation
            if ( isset( $_GET['activate'] ) && current_user_can( 'activate_plugins' ) ) {
                unset( $_GET['activate'] );
            }
        }
    }

    public function php_version_notice() {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            sprintf(
                /* translators: %s: PHP version number */
                esc_html__( 'QRCraft requires PHP version 8.0 or higher. You are running PHP %s.', 'qrcraft' ),
                esc_html( PHP_VERSION )
            )
        );
    }

    public function woocommerce_notice() {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'QRCraft requires WooCommerce to be installed and activated.', 'qrcraft' )
        );
    }

    private function load_dependencies() {
        require_once QRCRAFT_PLUGIN_DIR . 'includes/class-qrcraft-qrcode.php';
        require_once QRCRAFT_PLUGIN_DIR . 'includes/class-qrcraft-activator.php';
        require_once QRCRAFT_PLUGIN_DIR . 'includes/class-qrcraft-deactivator.php';
        require_once QRCRAFT_PLUGIN_DIR . 'includes/class-qrcraft-generator.php';
        require_once QRCRAFT_PLUGIN_DIR . 'includes/class-qrcraft-scheduler.php';
        require_once QRCRAFT_PLUGIN_DIR . 'includes/class-qrcraft-product-hooks.php';
        require_once QRCRAFT_PLUGIN_DIR . 'includes/class-qrcraft-frontend.php';

        if ( is_admin() ) {
            require_once QRCRAFT_PLUGIN_DIR . 'admin/class-qrcraft-admin.php';
            require_once QRCRAFT_PLUGIN_DIR . 'admin/class-qrcraft-settings.php';
            require_once QRCRAFT_PLUGIN_DIR . 'admin/class-qrcraft-product-column.php';
        }
    }

    private function init_hooks() {
        register_activation_hook( QRCRAFT_PLUGIN_FILE, array( 'QRCraft_Activator', 'activate' ) );
        register_deactivation_hook( QRCRAFT_PLUGIN_FILE, array( 'QRCraft_Deactivator', 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
    }

    public function init_plugin() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        new QRCraft_Generator();
        new QRCraft_Scheduler();
        new QRCraft_Product_Hooks();
        new QRCraft_Frontend();

        if ( is_admin() ) {
            new QRCraft_Admin();
            new QRCraft_Settings();
            new QRCraft_Product_Column();
        }
    }

    public static function get_upload_dir() {
        $upload_dir = wp_upload_dir();
        return trailingslashit( $upload_dir['basedir'] ) . QRCRAFT_UPLOAD_DIR;
    }

    public static function get_upload_url() {
        $upload_dir = wp_upload_dir();
        return trailingslashit( $upload_dir['baseurl'] ) . QRCRAFT_UPLOAD_DIR;
    }
}

function qrcraft() {
    return QRCraft::instance();
}

add_action( 'plugins_loaded', 'qrcraft', 0 );
