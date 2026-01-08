<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$qrcraft_delete_files = get_option( 'qrcraft_delete_files_on_uninstall', 'no' );
delete_option( 'qrcraft_settings' );
delete_option( 'qrcraft_delete_files_on_uninstall' );
delete_option( 'qrcraft_activated' );
delete_option( 'qrcraft_bulk_progress' );

if ( $qrcraft_delete_files === 'yes' ) {
    $qrcraft_upload_dir = wp_upload_dir();
    $qrcraft_qr_dir = trailingslashit( $qrcraft_upload_dir['basedir'] ) . 'qrcraft';

    if ( is_dir( $qrcraft_qr_dir ) ) {
        $qrcraft_files = glob( $qrcraft_qr_dir . '/*' );
        if ( is_array( $qrcraft_files ) ) {
            foreach ( $qrcraft_files as $qrcraft_file ) {
                if ( is_file( $qrcraft_file ) ) {
                    wp_delete_file( $qrcraft_file );
                }
            }
        }

        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $wp_filesystem->rmdir( $qrcraft_qr_dir );
    }
}

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup, runs once
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
        '_qrcraft_code_url'
    )
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup, runs once
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
        '_qrcraft_code_file'
    )
);

if ( class_exists( 'ActionScheduler_DBStore' ) ) {
    ActionScheduler_DBStore::instance()->cancel_actions_by_group( 'qrcraft' );
}
