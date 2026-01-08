<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Scheduler {

    private $generator;
    private $batch_size = 50;

    public function __construct() {
        $settings = get_option( 'qrcraft_settings', array() );
        $this->batch_size = isset( $settings['batch_size'] ) ? (int) $settings['batch_size'] : 50;

        add_action( 'qrcraft_bulk_generate_start', array( $this, 'start_bulk_generation' ) );
        add_action( 'qrcraft_bulk_generate_batch', array( $this, 'process_batch' ), 10, 1 );
    }

    public function start_bulk_generation() {
        $generator = new QRCraft_Generator();
        $remaining = $generator->count_products_without_qr();

        if ( $remaining === 0 ) {
            $this->complete_generation();
            return;
        }

        update_option( 'qrcraft_bulk_progress', array(
            'total'     => $remaining,
            'processed' => 0,
            'status'    => 'running',
            'started'   => time(),
        ) );

        $this->schedule_next_batch( 0 );
    }

    public function process_batch( $offset ) {
        $generator = new QRCraft_Generator();
        $products = $generator->get_products_without_qr( $this->batch_size );

        if ( empty( $products ) ) {
            $this->complete_generation();
            return;
        }

        $processed = 0;
        foreach ( $products as $product_id ) {
            $result = $generator->generate_for_product( $product_id );
            if ( $result ) {
                $processed++;
            }
        }

        $progress = get_option( 'qrcraft_bulk_progress', array() );
        $progress['processed'] = isset( $progress['processed'] ) ? $progress['processed'] + $processed : $processed;
        update_option( 'qrcraft_bulk_progress', $progress );

        $remaining = $generator->count_products_without_qr();

        if ( $remaining > 0 ) {
            $this->schedule_next_batch( $offset + $this->batch_size );
        } else {
            $this->complete_generation();
        }
    }

    private function schedule_next_batch( $offset ) {
        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            return;
        }

        as_schedule_single_action(
            time() + 1,
            'qrcraft_bulk_generate_batch',
            array( 'offset' => $offset ),
            'qrcraft'
        );
    }

    private function complete_generation() {
        $progress = get_option( 'qrcraft_bulk_progress', array() );
        $progress['status'] = 'complete';
        $progress['completed'] = time();
        update_option( 'qrcraft_bulk_progress', $progress );
    }

    public function schedule_bulk_generation() {
        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            $generator = new QRCraft_Generator();
            $generator->regenerate_all();
            return;
        }

        as_unschedule_all_actions( 'qrcraft_bulk_generate_start', array(), 'qrcraft' );
        as_unschedule_all_actions( 'qrcraft_bulk_generate_batch', array(), 'qrcraft' );

        as_schedule_single_action(
            time() + 2,
            'qrcraft_bulk_generate_start',
            array(),
            'qrcraft'
        );
    }

    public function get_progress() {
        return get_option( 'qrcraft_bulk_progress', array(
            'total'     => 0,
            'processed' => 0,
            'status'    => 'idle',
        ) );
    }

    public function is_running() {
        $progress = $this->get_progress();
        return isset( $progress['status'] ) && $progress['status'] === 'running';
    }
}
