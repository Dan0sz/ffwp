<?php
/**
 * @package   Dan0sz/custom-checkout-fields
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined( 'ABSPATH' ) || exit;

class FFWP_CustomCheckoutFields_Add {
	const FFWP_OPTION_NAME_LAST_SCHEDULED_DATE       = 'ffwp_last_scheduled_date';
	const FFWP_CHECKOUT_FIELD_NAME_SCHEDULED_DATE    = 'ffwp_edd_scheduled_date';
	const FFWP_CHECKOUT_FIELD_NAME_URL               = 'ffwp_edd_url';
	const FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO   = 'ffwp_edd_additional_info';
	const FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_MESSAGE = 'Please provide a valid URL as project information.';
	const FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_ID      = 'no_url_provided';
	
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'ffwp';
	
	/** @var array $item_ids */
	private $item_ids = [ '4473', '4483', '4487', '4713', '4714', '4715' ];
	
	/** @var string $speed_optimization */
	private $speed_optimization = '4473';
	
	/** @var bool $do_not_write */
	private $do_not_write = false;
	
	/**
	 * FFWP_CustomCheckoutFields_Add constructor.
	 */
	public function __construct() {
		add_action( 'edd_checkout_form_top', [ $this, 'maybe_add_section' ], 30 );
		add_filter( 'edd_purchase_form_required_fields', [ $this, 'add_required_field' ] );
		add_action( 'edd_checkout_error_checks', [ $this, 'validate_required_field' ], 10, 2 );
		add_filter( 'edd_payment_meta', [ $this, 'save_fields' ] );
		add_action( 'edd_view_order_details_billing_after', [ $this, 'add_payment_project_information' ], 10, 1 );
		add_filter( 'edd_purchase_receipt_after_files', [ $this, 'add_receipt_project_information' ], 10, 4 );
		add_action( 'edd_complete_purchase', [ $this, 'set_last_scheduled_date' ], 10, 2 );
	}
	
	/**
	 * @return bool
	 */
	private function item_in_cart() {
		foreach ( $this->item_ids as $item_id ) {
			$item_in_cart = edd_item_in_cart( $item_id );
			
			if ( $item_in_cart ) {
				break;
			}
		}
		
		return $item_in_cart;
	}
	
	/**
	 * @throws Exception
	 */
	public function maybe_add_section() {
		if ( $this->item_in_cart() ) {
			$this->add_section();
		}
	}
	
	/**
	 * @throws Exception
	 */
	public function add_section() {
		?>
        <fieldset id="edd-ffwp-project-info">
            <legend><?= __( 'Project Information', $this->plugin_text_domain ); ?></legend>
			<?php if ( edd_item_in_cart( $this->speed_optimization ) ): ?>
                <p id="ffwp-edd-date-scheduled-wrap">
					<?php printf( __( 'After completing your purchase, your WordPress Speed Optimization is scheduled to start in <strong>week %d</strong> (approx. %s.) The average completion time is 2 - 4 weeks and depends on the complexity of the project.', $this->plugin_text_domain ), $this->get_next_scheduled_date(), $this->get_next_scheduled_date( get_option( 'date_format' ) ) ); ?>
                    <input type="hidden" value="<?= $this->get_next_scheduled_date( 'Y-m-d' ); ?>"
                           name="<?= self::FFWP_CHECKOUT_FIELD_NAME_SCHEDULED_DATE; ?>"/>
                </p>
			<?php endif; ?>
            <p id="ffwp-edd-url-wrap">
                <label class="edd-label" for="ffwp-edd-url">
					<?php _e( 'URL', $this->plugin_text_domain ); ?> <span class="edd-required-indicator">*</span>
                </label>
                <span class="edd-description">
                    <?php _e( 'The URL of the website you want me to work on.', $this->plugin_text_domain ); ?>
                </span>
                <input class="edd-input required" type="url" name="<?= self::FFWP_CHECKOUT_FIELD_NAME_URL; ?>"
                       id="ffwp-edd-url"
                       placeholder="<?php _e( 'E.g. https://yourdomain.com', $this->plugin_text_domain ); ?>"/>
            </p>
            <p id="ffwp-edd-additional-info-wrap">
                <label class="edd-label" for="ffwp-edd-additional-info">
					<?= __( 'Additional Information', $this->plugin_text_domain ); ?>
                </label>
                <span class="edd-description">
                    <?= __( 'Provide any additional information about the project you think is relevant.', $this->plugin_text_domain ); ?>
                </span>
                <textarea class="edd-input" name="<?= self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO; ?>"
                          id="ffwp-edd-additional-info" rows="5"></textarea>
            </p>
        </fieldset>
		<?php
	}
	
	/**
	 * @param string $format
	 *
	 * @return string
	 * @throws Exception
	 */
	private function get_next_scheduled_date( $format = 'W' ) {
		$last_scheduled_date = new DateTime( get_option( self::FFWP_OPTION_NAME_LAST_SCHEDULED_DATE ) );
		$current_date        = new DateTime();
		
		if ( $last_scheduled_date <= $current_date ) {
			$current_date->modify( 'next monday' );
			$next_scheduled_date = $current_date;
		} else {
			$last_scheduled_date->modify( '+ 14 days' );
			$next_scheduled_date = $last_scheduled_date;
		}
		
		return $next_scheduled_date->format( $format );
	}
	
	/**
	 * @param        $date
	 * @param string $format
	 *
	 * @return string
	 * @throws Exception
	 */
	private function get_scheduled_date( $date, $format = 'W' ) {
		$scheduled_date = new DateTime( $date );
		
		return $scheduled_date->format( $format );
	}
	
	/**
	 * @param $required_fields
	 *
	 * @return array
	 */
	public function add_required_field( $required_fields ) {
		if ( $this->item_in_cart() ) {
			$required_fields[ self::FFWP_CHECKOUT_FIELD_NAME_URL ] = [
				'error_id'      => self::FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_ID,
				'error_message' => __( self::FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_MESSAGE, $this->plugin_text_domain )
			];
		}
		
		return $required_fields;
	}
	
	/**
	 * @param $valid_data
	 * @param $data
	 */
	public function validate_required_field( $valid_data, $data ) {
		if ( ! $this->item_in_cart() ) {
			return;
		}
		
		if ( empty( $data[ self::FFWP_CHECKOUT_FIELD_NAME_URL ] ) ) {
			edd_set_error( self::FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_ID, __( self::FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_MESSAGE, $this->plugin_text_domain ) );
		}
	}
	
	/**
	 * @param $payment_meta
	 *
	 * @return mixed
	 */
	public function save_fields( $payment_meta ) {
		if ( isset( $_POST[ self::FFWP_CHECKOUT_FIELD_NAME_URL ] ) ) {
			$payment_meta[ self::FFWP_CHECKOUT_FIELD_NAME_URL ] = esc_url_raw( $_POST[ self::FFWP_CHECKOUT_FIELD_NAME_URL ] );
		}
		
		if ( isset( $_POST[ self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO ] ) ) {
			$payment_meta[ self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO ] = sanitize_textarea_field( $_POST[ self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO ] );
		}
		
		if ( isset( $_POST[ self::FFWP_CHECKOUT_FIELD_NAME_SCHEDULED_DATE ] ) ) {
			$payment_meta[ self::FFWP_CHECKOUT_FIELD_NAME_SCHEDULED_DATE ] = sanitize_text_field( $_POST[ self::FFWP_CHECKOUT_FIELD_NAME_SCHEDULED_DATE ] );
		}
		
		return $payment_meta;
	}
	
	/**
	 * @param $payment_id
	 *
	 * @throws Exception
	 */
	public function add_payment_project_information( $payment_id ) {
		$payment         = new EDD_Payment( $payment_id );
		$url             = $payment->get_meta()[ self::FFWP_CHECKOUT_FIELD_NAME_URL ] ?? '';
		$additional_info = $payment->get_meta()[ self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO ] ?? '';
		$scheduled       = $payment->get_meta()[ self::FFWP_CHECKOUT_FIELD_NAME_SCHEDULED_DATE ] ?? '';
		
		if ( $url ): ?>
            <div id="ffwp-project-information" class="postbox">
                <h3 class="hndle"><span><?= __( 'Project Information', $this->plugin_text_domain ); ?></span></h3>
                <div class="inside edd-clearfix">
                    <p>
                        <a target="_blank" href="<?= $url; ?>"><?= $url; ?></a>
						<?php if ( $scheduled ): ?>
                            | <strong><?= __( "Scheduled for week", $this->plugin_text_domain ); ?> <?= $this->get_scheduled_date( $scheduled ); ?></strong>
						<?php endif; ?>
                    </p>
                    <p><?= $additional_info; ?></p>
                </div>
            </div>
		<?php endif;
	}
	
	/**
	 * @param $item_id
	 * @param $payment_id
	 * @param $meta
	 * @param $price_id
	 *
	 * @throws Exception
	 */
	public function add_receipt_project_information( $item_id, $payment_id, $meta, $price_id ) {
		if ( ! in_array( $item_id, $this->item_ids ) || $this->do_not_write ) {
			return;
		}
		
		$purchase = edd_get_purchase_session();
		
		if ( empty( $purchase ) ) {
			return;
		}
		
		$url = $purchase['post_data'][ self::FFWP_CHECKOUT_FIELD_NAME_URL ] ?? '';
		
		if ( ! $url ) {
			return;
		}
		
		$this->do_not_write = true;
		$additional_info    = $purchase['post_data'][ self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO ] ?? '';
		$scheduled          = $purchase['post_data'][ self::FFWP_CHECKOUT_FIELD_NAME_SCHEDULED_DATE ] ?? '';
		?>
        <ul class="ffwp-project-information">
            <li><?= __( 'URL', $this->plugin_text_domain ); ?>: <a target='_blank' href='<?= $url; ?>'><?= $url; ?></a>
            </li>
			<?php if ( $scheduled ): ?>
                <li><?= __( 'Scheduled in week', $this->plugin_text_domain ); ?> <?= $this->get_scheduled_date( $scheduled ); ?>.</li>
			<?php endif; ?>
            <li><em><?= $additional_info; ?></em></li>
        </ul>
		<?php
	}
	
	/**
	 * @param $payment_id
	 * @param $payment
	 */
	public function set_last_scheduled_date( $payment_id, $payment ) {
		$scheduled_date = $payment->payment_meta[ self::FFWP_CHECKOUT_FIELD_NAME_SCHEDULED_DATE ] ?? '';
		
		if ( ! $scheduled_date ) {
			return;
		}
		
		update_option( self::FFWP_OPTION_NAME_LAST_SCHEDULED_DATE, $scheduled_date );
	}
}
