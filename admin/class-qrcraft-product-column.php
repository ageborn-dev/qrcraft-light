<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Product_Column {

    public function __construct() {
        add_filter( 'manage_edit-product_columns', array( $this, 'add_qr_column' ) );
        add_action( 'manage_product_posts_custom_column', array( $this, 'render_qr_column' ), 10, 2 );
        add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_sortable' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
    }

    public function add_qr_column( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( $key === 'name' ) {
                $new_columns['qrcraft'] = __( 'QR Code', 'qrcraft' );
            }
        }

        return $new_columns;
    }

    public function render_qr_column( $column, $post_id ) {
        if ( $column !== 'qrcraft' ) {
            return;
        }

        $qr_url = get_post_meta( $post_id, '_qrcraft_code_url', true );

        if ( $qr_url ) {
            ?>
            <div class="qrcraft-column-preview">
                <img src="<?php echo esc_url( $qr_url ); ?>" alt="<?php esc_attr_e( 'QR Code', 'qrcraft' ); ?>" class="qrcraft-thumb">
                <div class="qrcraft-column-actions">
                    <a href="<?php echo esc_url( $qr_url ); ?>" class="button button-small" download="qr-<?php echo esc_attr( $post_id ); ?>.svg"><?php esc_html_e( 'Download', 'qrcraft' ); ?></a>
                    <button type="button" class="button button-small qrcraft-regenerate" data-product-id="<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Regenerate', 'qrcraft' ); ?></button>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="qrcraft-column-empty">
                <span class="qrcraft-no-qr"><?php esc_html_e( 'Not generated', 'qrcraft' ); ?></span>
                <button type="button" class="button button-small qrcraft-regenerate" data-product-id="<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Generate', 'qrcraft' ); ?></button>
            </div>
            <?php
        }
    }

    public function make_sortable( $columns ) {
        return $columns;
    }

    public function add_meta_box() {
        add_meta_box(
            'qrcraft_product_qr',
            __( 'QRCraft - Product QR Code', 'qrcraft' ),
            array( $this, 'render_meta_box' ),
            'product',
            'side',
            'default'
        );
    }

    public function render_meta_box( $post ) {
        $qr_url = get_post_meta( $post->ID, '_qrcraft_code_url', true );
        ?>
        <div class="qrcraft-metabox">
            <?php if ( $qr_url ) : ?>
                <div class="qrcraft-metabox-preview">
                    <img src="<?php echo esc_url( $qr_url ); ?>" alt="<?php esc_attr_e( 'QR Code', 'qrcraft' ); ?>">
                </div>
                <div class="qrcraft-metabox-actions">
                    <a href="<?php echo esc_url( $qr_url ); ?>" class="button" download="qr-<?php echo esc_attr( $post->ID ); ?>.svg"><?php esc_html_e( 'Download QR Code', 'qrcraft' ); ?></a>
                    <button type="button" class="button qrcraft-regenerate" data-product-id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Regenerate', 'qrcraft' ); ?></button>
                </div>
            <?php else : ?>
                <p><?php esc_html_e( 'QR code will be generated when the product is published.', 'qrcraft' ); ?></p>
                <?php if ( $post->post_status === 'publish' ) : ?>
                    <button type="button" class="button button-primary qrcraft-regenerate" data-product-id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Generate Now', 'qrcraft' ); ?></button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
