<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Deactivator {

    public static function deactivate() {
        self::clear_scheduled_actions();
        self::clear_transients();
    }

    private static function clear_scheduled_actions() {
        if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
            return;
        }

        as_unschedule_all_actions( 'qrcraft_bulk_generate_start', array(), 'qrcraft' );
        as_unschedule_all_actions( 'qrcraft_bulk_generate_batch', array(), 'qrcraft' );
    }

    private static function clear_transients() {
        delete_transient( 'qrcraft_generating' );
    }
}
