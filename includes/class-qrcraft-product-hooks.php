<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Product_Hooks {

    private $generator;

    public function __construct() {
        add_action( 'save_post_product', array( $this, 'on_product_save' ), 20, 3 );
        add_action( 'woocommerce_update_product', array( $this, 'on_product_update' ), 20, 1 );
        add_action( 'before_delete_post', array( $this, 'on_product_delete' ), 10, 1 );
    }

    public function on_product_save( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( $post->post_status !== 'publish' ) {
            return;
        }

        $this->generate_qr( $post_id );
    }

    public function on_product_update( $product_id ) {
        $product = wc_get_product( $product_id );

        if ( ! $product || $product->get_status() !== 'publish' ) {
            return;
        }

        $this->generate_qr( $product_id );
    }

    public function on_product_delete( $post_id ) {
        if ( get_post_type( $post_id ) !== 'product' ) {
            return;
        }

        $generator = new QRCraft_Generator();
        $generator->delete_qr( $post_id );
    }

    private function generate_qr( $product_id ) {
        $generator = new QRCraft_Generator();
        $generator->generate_for_product( $product_id );
    }
}
