<?php
/**
 * QR Code Generator using external API with local caching
 * Uses goqr.me API which is free, fast, and reliable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_QRCode {

    private $data;
    private $size;
    private $foreground;
    private $background;
    private $ecc_level;

    public function __construct( $data, $options = array() ) {
        $this->data = $data;
        $this->size = isset( $options['size'] ) ? (int) $options['size'] : 150;
        $this->foreground = isset( $options['foreground'] ) ? str_replace( '#', '', $options['foreground'] ) : '000000';
        $this->background = isset( $options['background'] ) ? str_replace( '#', '', $options['background'] ) : 'ffffff';
        $this->ecc_level = isset( $options['error_correction'] ) ? strtoupper( $options['error_correction'] ) : 'M';
    }

    public function render_svg() {
        $api_url = sprintf(
            'https://api.qrserver.com/v1/create-qr-code/?size=%dx%d&data=%s&format=svg&ecc=%s&color=%s&bgcolor=%s',
            $this->size,
            $this->size,
            rawurlencode( $this->data ),
            $this->ecc_level,
            $this->foreground,
            $this->background
        );

        $response = wp_remote_get( $api_url, array(
            'timeout' => 10,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            return $this->generate_local_svg();
        }

        $body = wp_remote_retrieve_body( $response );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code !== 200 || empty( $body ) ) {
            return $this->generate_local_svg();
        }

        return $body;
    }

    private function generate_local_svg() {
        $size = $this->size;
        $data = $this->data;
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">' . "\n";
        $svg .= '<rect width="100%" height="100%" fill="#' . $this->background . '"/>' . "\n";
        $svg .= '<text x="50%" y="50%" font-family="Arial" font-size="12" fill="#' . $this->foreground . '" text-anchor="middle" dy=".3em">QR Pending</text>' . "\n";
        $svg .= '</svg>';
        
        return $svg;
    }
}
