<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QRCraft_Settings {

    private $option_name = 'qrcraft_settings';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            __( 'QRCraft Settings', 'qrcraft' ),
            __( 'QRCraft', 'qrcraft' ),
            'manage_woocommerce',
            'qrcraft-settings',
            array( $this, 'render_settings_page' ),
            null
        );
    }

    public function register_settings() {
        register_setting(
            'qrcraft_settings_group',
            $this->option_name,
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            'qrcraft_main_section',
            __( 'QR Code Settings', 'qrcraft' ),
            array( $this, 'render_section_description' ),
            'qrcraft-settings'
        );

        add_settings_field(
            'qr_color',
            __( 'QR Code Color', 'qrcraft' ),
            array( $this, 'render_color_field' ),
            'qrcraft-settings',
            'qrcraft_main_section',
            array( 'field' => 'qr_color', 'default' => '#000000' )
        );

        add_settings_field(
            'bg_color',
            __( 'Background Color', 'qrcraft' ),
            array( $this, 'render_color_field' ),
            'qrcraft-settings',
            'qrcraft_main_section',
            array( 'field' => 'bg_color', 'default' => '#ffffff' )
        );

        add_settings_field(
            'size',
            __( 'QR Code Size', 'qrcraft' ),
            array( $this, 'render_size_field' ),
            'qrcraft-settings',
            'qrcraft_main_section'
        );

        add_settings_field(
            'error_correction',
            __( 'Error Correction Level', 'qrcraft' ),
            array( $this, 'render_error_correction_field' ),
            'qrcraft-settings',
            'qrcraft_main_section'
        );

        add_settings_field(
            'delete_on_uninstall',
            __( 'On Uninstall', 'qrcraft' ),
            array( $this, 'render_uninstall_field' ),
            'qrcraft-settings',
            'qrcraft_main_section'
        );
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();

        $sanitized['qr_color'] = isset( $input['qr_color'] ) ? sanitize_hex_color( $input['qr_color'] ) : '#000000';
        $sanitized['bg_color'] = isset( $input['bg_color'] ) ? sanitize_hex_color( $input['bg_color'] ) : '#ffffff';
        $sanitized['size'] = isset( $input['size'] ) ? absint( $input['size'] ) : 150;
        $sanitized['error_correction'] = isset( $input['error_correction'] ) ? sanitize_text_field( $input['error_correction'] ) : 'M';
        $sanitized['batch_size'] = 50;

        $valid_sizes = array( 100, 150, 200, 300 );
        if ( ! in_array( $sanitized['size'], $valid_sizes, true ) ) {
            $sanitized['size'] = 150;
        }

        $valid_ec = array( 'L', 'M', 'Q', 'H' );
        if ( ! in_array( $sanitized['error_correction'], $valid_ec, true ) ) {
            $sanitized['error_correction'] = 'M';
        }

        if ( isset( $input['delete_on_uninstall'] ) ) {
            update_option( 'qrcraft_delete_files_on_uninstall', $input['delete_on_uninstall'] === 'yes' ? 'yes' : 'no' );
        }

        return $sanitized;
    }

    public function render_section_description() {
        echo '<p>' . esc_html__( 'Configure how your product QR codes look and behave.', 'qrcraft' ) . '</p>';
    }

    public function render_color_field( $args ) {
        $options = get_option( $this->option_name );
        $field = $args['field'];
        $default = $args['default'];
        $value = isset( $options[ $field ] ) ? $options[ $field ] : $default;
        ?>
        <input type="text"
               name="<?php echo esc_attr( $this->option_name . '[' . $field . ']' ); ?>"
               value="<?php echo esc_attr( $value ); ?>"
               class="qrcraft-color-picker"
               data-default-color="<?php echo esc_attr( $default ); ?>">
        <?php
    }

    public function render_size_field() {
        $options = get_option( $this->option_name );
        $value = isset( $options['size'] ) ? $options['size'] : 150;
        $sizes = array(
            100 => '100 x 100 px',
            150 => '150 x 150 px',
            200 => '200 x 200 px',
            300 => '300 x 300 px',
        );
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[size]">
            <?php foreach ( $sizes as $size => $label ) : ?>
                <option value="<?php echo esc_attr( $size ); ?>" <?php selected( $value, $size ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function render_error_correction_field() {
        $options = get_option( $this->option_name );
        $value = isset( $options['error_correction'] ) ? $options['error_correction'] : 'M';
        $levels = array(
            'L' => __( 'Low (7% recovery)', 'qrcraft' ),
            'M' => __( 'Medium (15% recovery)', 'qrcraft' ),
            'Q' => __( 'Quartile (25% recovery)', 'qrcraft' ),
            'H' => __( 'High (30% recovery)', 'qrcraft' ),
        );
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[error_correction]">
            <?php foreach ( $levels as $level => $label ) : ?>
                <option value="<?php echo esc_attr( $level ); ?>" <?php selected( $value, $level ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Higher levels allow the QR code to remain readable even if partially damaged.', 'qrcraft' ); ?></p>
        <?php
    }

    public function render_uninstall_field() {
        $value = get_option( 'qrcraft_delete_files_on_uninstall', 'no' );
        ?>
        <label>
            <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[delete_on_uninstall]" value="no" <?php checked( $value, 'no' ); ?>>
            <?php esc_html_e( 'Keep QR code files', 'qrcraft' ); ?>
        </label>
        <br>
        <label>
            <input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[delete_on_uninstall]" value="yes" <?php checked( $value, 'yes' ); ?>>
            <?php esc_html_e( 'Delete all QR code files', 'qrcraft' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Choose what happens to generated QR code files when the plugin is uninstalled.', 'qrcraft' ); ?></p>
        <?php
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $scheduler = new QRCraft_Scheduler();
        $progress = $scheduler->get_progress();
        $generator = new QRCraft_Generator();
        $pending = $generator->count_products_without_qr();
        $total_products = $this->count_total_products();
        $products_with_qr = $total_products - $pending;
        ?>
        <div class="wrap qrcraft-settings-wrap">
            <h1><?php esc_html_e( 'QRCraft Settings', 'qrcraft' ); ?></h1>

            <div class="qrcraft-settings-container">
                <div class="qrcraft-settings-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'qrcraft_settings_group' );
                        do_settings_sections( 'qrcraft-settings' );
                        submit_button();
                        ?>
                    </form>
                </div>

                <div class="qrcraft-settings-sidebar">
                    <div class="qrcraft-card">
                        <h2><?php esc_html_e( 'Bulk Generation', 'qrcraft' ); ?></h2>
                        <p>
                        <?php
                        /* translators: %d: number of total products */
                        printf( esc_html__( 'Total products: %d', 'qrcraft' ), esc_html( $total_products ) );
                        ?>
                        </p>
                        <p>
                        <?php
                        /* translators: %d: number of products with QR codes */
                        printf( esc_html__( 'With QR codes: %d', 'qrcraft' ), esc_html( $products_with_qr ) );
                        ?>
                        </p>
                        <p>
                        <?php
                        /* translators: %d: number of pending products */
                        printf( esc_html__( 'Pending: %d', 'qrcraft' ), esc_html( $pending ) );
                        ?>
                        </p>

                        <div class="qrcraft-progress-wrap" style="<?php echo esc_attr( $progress['status'] === 'running' ? '' : 'display:none;' ); ?>">
                            <div class="qrcraft-progress-bar">
                                <div class="qrcraft-progress-fill" style="width: <?php echo esc_attr( $progress['total'] > 0 ? ( ( $progress['processed'] / $progress['total'] ) * 100 ) : 0 ); ?>%;"></div>
                            </div>
                            <span class="qrcraft-progress-text">
                                <?php
                                /* translators: %1$d: processed count, %2$d: total count */
                                printf( esc_html__( '%1$d / %2$d processed', 'qrcraft' ), esc_html( $progress['processed'] ), esc_html( $progress['total'] ) );
                                ?>
                            </span>
                        </div>

                        <button type="button" id="qrcraft-regenerate-all" class="button button-primary" <?php echo esc_attr( $progress['status'] === 'running' ? 'disabled' : '' ); ?>>
                            <?php esc_html_e( 'Regenerate All QR Codes', 'qrcraft' ); ?>
                        </button>
                    </div>

                    <div class="qrcraft-card">
                        <h2><?php esc_html_e( 'About QRCraft', 'qrcraft' ); ?></h2>
                        <p><?php esc_html_e( 'QRCraft automatically generates QR codes for your WooCommerce products. Each QR code links directly to the product page.', 'qrcraft' ); ?></p>
                        <p><strong><?php esc_html_e( 'Version:', 'qrcraft' ); ?></strong> <?php echo esc_html( QRCRAFT_VERSION ); ?></p>
                        <p><a href="https://github.com/ageborn-dev" target="_blank" rel="noopener"><?php esc_html_e( 'Visit Developer', 'qrcraft' ); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function count_total_products() {
        $count = wp_count_posts( 'product' );
        return isset( $count->publish ) ? (int) $count->publish : 0;
    }
}
