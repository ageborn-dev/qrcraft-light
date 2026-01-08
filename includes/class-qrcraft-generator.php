<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Generator {

    private $settings;

    public function __construct() {
        $this->settings = get_option( 'qrcraft_settings', array() );
    }

    public function generate_for_product( $product_id ) {
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return false;
        }

        $product_url = get_permalink( $product_id );

        if ( ! $product_url ) {
            return false;
        }

        $this->settings = get_option( 'qrcraft_settings', array() );

        $options = array(
            'size'             => isset( $this->settings['size'] ) ? (int) $this->settings['size'] : 150,
            'error_correction' => isset( $this->settings['error_correction'] ) ? $this->settings['error_correction'] : 'M',
            'foreground'       => isset( $this->settings['qr_color'] ) ? $this->settings['qr_color'] : '#000000',
            'background'       => isset( $this->settings['bg_color'] ) ? $this->settings['bg_color'] : '#ffffff',
            'margin'           => 4,
        );

        try {
            $qr = new QRCraft_QRCode( $product_url, $options );
            $svg_content = $qr->render_svg();
        } catch ( Exception $e ) {
            return false;
        }

        if ( empty( $svg_content ) ) {
            return false;
        }

        $upload_dir = QRCraft::get_upload_dir();
        $this->ensure_upload_directory( $upload_dir );

        $filename = 'qr-' . $product_id . '-' . substr( md5( $product_url ), 0, 8 ) . '.svg';
        $filepath = trailingslashit( $upload_dir ) . $filename;

        $old_file = get_post_meta( $product_id, '_qrcraft_code_file', true );
        if ( $old_file && file_exists( $old_file ) ) {
            wp_delete_file( $old_file );
        }

        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $result = $wp_filesystem->put_contents( $filepath, $svg_content, FS_CHMOD_FILE );

        if ( ! $result ) {
            return false;
        }

        $file_url = trailingslashit( QRCraft::get_upload_url() ) . $filename;

        update_post_meta( $product_id, '_qrcraft_code_url', $file_url );
        update_post_meta( $product_id, '_qrcraft_code_file', $filepath );

        return $file_url;
    }

    public function get_qr_url( $product_id ) {
        return get_post_meta( $product_id, '_qrcraft_code_url', true );
    }

    public function delete_qr( $product_id ) {
        $filepath = get_post_meta( $product_id, '_qrcraft_code_file', true );

        if ( $filepath && file_exists( $filepath ) ) {
            wp_delete_file( $filepath );
        }

        delete_post_meta( $product_id, '_qrcraft_code_url' );
        delete_post_meta( $product_id, '_qrcraft_code_file' );
    }

    public function regenerate_all() {
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );

        $product_ids = get_posts( $args );
        $generated = 0;

        foreach ( $product_ids as $product_id ) {
            $result = $this->generate_for_product( $product_id );
            if ( $result ) {
                $generated++;
            }
        }

        return $generated;
    }

    public function get_products_without_qr( $limit = 50 ) {
        global $wpdb;

        $cache_key = 'qrcraft_products_no_qr_' . $limit;
        $results = wp_cache_get( $cache_key, 'qrcraft' );

        if ( false === $results ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required for bulk operations with LEFT JOIN
            $results = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT p.ID FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                    WHERE p.post_type = %s
                    AND p.post_status = %s
                    AND pm.meta_value IS NULL
                    LIMIT %d",
                    '_qrcraft_code_url',
                    'product',
                    'publish',
                    $limit
                )
            );
            wp_cache_set( $cache_key, $results, 'qrcraft', 60 );
        }

        return $results;
    }

    public function count_products_without_qr() {
        global $wpdb;

        $cache_key = 'qrcraft_count_no_qr';
        $count = wp_cache_get( $cache_key, 'qrcraft' );

        if ( false === $count ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required for counting with LEFT JOIN
            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(p.ID) FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                    WHERE p.post_type = %s
                    AND p.post_status = %s
                    AND pm.meta_value IS NULL",
                    '_qrcraft_code_url',
                    'product',
                    'publish'
                )
            );
            wp_cache_set( $cache_key, $count, 'qrcraft', 60 );
        }

        return $count;
    }

    private function ensure_upload_directory( $dir ) {
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );

            global $wp_filesystem;
            if ( empty( $wp_filesystem ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }

            $htaccess = trailingslashit( $dir ) . '.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                $wp_filesystem->put_contents( $htaccess, "Options -Indexes\n", FS_CHMOD_FILE );
            }

            $index = trailingslashit( $dir ) . 'index.php';
            if ( ! file_exists( $index ) ) {
                $wp_filesystem->put_contents( $index, '<?php // Silence is golden.', FS_CHMOD_FILE );
            }
        }
    }
}
