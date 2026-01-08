<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Activator {

    public static function activate() {
        self::create_upload_directory();
        self::set_default_options();
        self::set_activation_flag();
        self::schedule_initial_generation();
    }

    private static function create_upload_directory() {
        $upload_dir = QRCraft::get_upload_dir();

        if ( ! file_exists( $upload_dir ) ) {
            wp_mkdir_p( $upload_dir );
        }

        $htaccess_file = $upload_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $htaccess_content = "Options -Indexes\n";
            file_put_contents( $htaccess_file, $htaccess_content );
        }

        $index_file = $upload_dir . '/index.php';
        if ( ! file_exists( $index_file ) ) {
            file_put_contents( $index_file, '<?php // Silence is golden.' );
        }
    }

    private static function set_default_options() {
        $default_settings = array(
            'qr_color'         => '#000000',
            'bg_color'         => '#ffffff',
            'size'             => 150,
            'error_correction' => 'M',
            'batch_size'       => 10,
        );

        if ( ! get_option( 'qrcraft_settings' ) ) {
            update_option( 'qrcraft_settings', $default_settings );
        }
    }

    private static function set_activation_flag() {
        update_option( 'qrcraft_activated', 'yes' );
    }

    private static function schedule_initial_generation() {
        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            return;
        }

        as_schedule_single_action(
            time() + 10,
            'qrcraft_bulk_generate_start',
            array(),
            'qrcraft'
        );
    }
}
