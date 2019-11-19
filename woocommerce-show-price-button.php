<?php
/**
 * Plugin Name: Show price button for Woocommerce
 * Description: Simple plugin to track your users habbits in checking price
 * Author: Piotr Josko
 * Author URI:  http://wordpressdesign.pl/
 * Version: 1.0
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package woocommerce-show-price-button
 */

require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

	if ( ! class_exists( 'Show_Price' ) ) {
		/**
		 * Class initiates the plugin.
		 */
		class Show_Price {

			/**
			 * Construcor.
			 */
			public function __construct() {
				add_action( 'admin_menu', array( $this, 'show_price_options_page' ) );
				add_action( 'wp_enqueue_scripts', array( __CLASS__, 'script' ) );
				add_filter( 'woocommerce_get_sections_products', array( $this, 'show_price_add_section' ) );
				add_filter( 'woocommerce_get_settings_products', array( __CLASS__, 'show_price_all_settings' ), 10, 2 );
				add_filter( 'woocommerce_get_price_html', array( $this, 'remove_price_shop' ) );
				add_filter( 'woocommerce_get_price_html', array( $this, 'remove_price_single_product' ) );
				add_action( 'wp_ajax_show_price', array( $this, 'show_price' ) );
				add_action( 'wp_ajax_nopriv_show_price', array( $this, 'show_price' ) );
			}

			/**
			 * Create Woocommerce Show price admin page
			 */
			public function show_price_options_page() {
				add_submenu_page(
					'woocommerce',
					__( 'Show Price Data', 'woocommerce-show-price-button' ),
					__( 'Show Price Data', 'woocommerce-show-price-button' ),
					'edit_posts',
					'show-price-options',
					array( $this, 'show_price_options_page_html' )
				);
			}
			/**
			 * Hide price on product page
			 */
			public static function script() {
				if ( is_product() && 'yes' === get_option( 'show-price-product-page' ) ) {
					wp_enqueue_script( 'hide_price', plugins_url( 'assets/js/main.js', __FILE__ ), '', '1.0', true );
					wp_localize_script( 'show_price', 'admin_url', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
				} elseif ( is_shop() && 'yes' === get_option( 'show-price-shop-page' ) ) {
					wp_enqueue_script( 'hide_price', plugins_url( 'assets/js/main.js', __FILE__ ), '', '1.0', true );
				}
			}

			/**
			 * Hide price on product page
			 *
			 * @param int $price Price.
			 */
			public function remove_price_single_product( $price ) {
				if ( is_product() && 'yes' === get_option( 'show-price-product-page' ) ) {
					$this->remove_add_to_cart_button();

					$current_user = wp_get_current_user();
					global $wp;
					if ( ! is_user_logged_in() ) {
						$current_user->user_email = $this->get_user_ip();
					}
					$current_user->user_email;
					$dzisiaj     = date( 'Y-m-d  h:i' );
					$current_url = home_url( $wp->request );

					?>
						<form id="form" method="post" action="" name="form">
							<input type="hidden" name="current_user" id="current_user" value="<?php echo esc_attr( $current_user->user_email ); ?>">
							<input type="hidden" name="dzisiaj" id="dzisiaj" value="<?php echo esc_attr( $dzisiaj ); ?>">
							<input type="hidden" name="current_url" id="current_url" value="<?php echo esc_attr( $current_url ); ?>">
							<input type="hidden" name="action" value="show_price"/>
							<button class="cena" id="submit-price" type="button" NAME="submit"><?php esc_html_e( 'Pokaż cenę', 'show price' ); ?></button>
						</form>
					<?php
					return false;
				} else {

					return $price;
				}
			}

			/**
			 * Retrieve IP address of user.
			 */
			public function show_price() {

				if ( ! empty( $_POST ) ) {

						print_r( $_POST );
						die();
						return false;
				}
				return false;
			}

			/**
			 * Retrieve IP address of user.
			 */
			public function get_user_ip() {
				if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
					$ip = filter_var( wp_unslash( $_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP ) );
				} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
					$ip = filter_var( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) );
				} else {
					$ip = ( isset( $_SERVER['REMOTE_ADDR'] ) ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : '0.0.0.0';
				}
				$ip = filter_var( $ip, FILTER_VALIDATE_IP );
				$ip = ( false === $ip ) ? '0.0.0.0' : $ip;
				return $ip;
			}


			/**
			 * Hide price on shop page
			 *
			 * @param int $price Price.
			 */
			public function remove_price_shop( $price ) {
				if ( is_shop() && 'yes' === get_option( 'show-price-shop-page' ) ) {
					$this->remove_add_to_cart_button();
					return false;
				} else {
					return $price;
				}
			}

			/**
			 * Remove Add to cart button
			 */
			private function remove_add_to_cart_button() {
				remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			}

			/**
			 * Create Woocommerce Show price admin page html
			 */
			public function show_price_options_page_html() {
				echo 'coś';
			}

			/**
			 * Create the section beneath the products tab
			 *
			 * @param array $sections Array of settings sections.
			 */
			public function show_price_add_section( $sections ) {
				$sections['show_price'] = __( 'Show Price', 'woocommerce-show-price-button' );
				return $sections;
			}

			/**
			 * Create Woocommerce Show price settings
			 *
			 * @param string $settings Coś.
			 * @param string $current_section Coś.
			 */
			public static function show_price_all_settings( $settings, $current_section ) {
				/**
				 * Check the current section is what we want
				 */
				if ( 'show_price' === $current_section ) {
					$show_price_settings = array();
					// Add Title to the Settings.
					$show_price_settings[] = array(
						'name' => __( 'Show price button ', 'woocommerce-show-price-button' ),
						'type' => 'title',
						'desc' => __( 'The following options are used to configure Show price button for Woocommerce', 'woocommerce-show-price-button' ),
						'id'   => 'show_price',
					);

					$show_price_settings[] = array(
						'title'    => __( 'Hide price on shop page', 'woocommerce-show-price-button' ),
						'id'       => 'show-price-shop-page',
						'default'  => 'yes',
						'type'     => 'checkbox',
						'autoload' => false,
						'class'    => 'manage_stock_field',
					);

					$show_price_settings[] = array(
						'title'    => __( 'Hide price on product page', 'woocommerce-show-price-button' ),
						'id'       => 'show-price-product-page',
						'default'  => 'yes',
						'type'     => 'checkbox',
						'autoload' => false,
						'class'    => 'manage_stock_field',
					);

					$show_price_settings[] = array(
						'type' => 'sectionend',
						'id'   => 'show_price',
					);

					return $show_price_settings;

					/**
					 * If not, return the standard settings
					*/
				} else {
					return $settings;
				}
			}
		}
		new Show_Price();
	}
}
