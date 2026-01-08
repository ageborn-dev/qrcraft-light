<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Frontend {

    public function __construct() {
        add_action( 'woocommerce_single_product_summary', array( $this, 'display_qr_code' ), 3 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    public function enqueue_styles() {
        if ( is_product() ) {
            wp_enqueue_style(
                'qrcraft-frontend',
                QRCRAFT_PLUGIN_URL . 'assets/css/qrcraft-frontend.css',
                array(),
                QRCRAFT_VERSION
            );
        }
    }

    public function display_qr_code() {
        global $product;

        if ( ! $product ) {
            return;
        }

        $qr_url = get_post_meta( $product->get_id(), '_qrcraft_code_url', true );

        if ( ! $qr_url ) {
            return;
        }

        $settings = get_option( 'qrcraft_settings', array() );
        $size = isset( $settings['size'] ) ? (int) $settings['size'] : 150;
        ?>
        <div class="qrcraft-product-qr">
            <img src="<?php echo esc_url( $qr_url ); ?>" 
                 alt="<?php
                 /* translators: %s: product name */
                 echo esc_attr( sprintf( __( 'QR Code for %s', 'qrcraft' ), $product->get_name() ) );
                 ?>"
                 width="<?php echo esc_attr( $size ); ?>"
                 height="<?php echo esc_attr( $size ); ?>">
        </div>
        <?php
    }
}
