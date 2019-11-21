<?php
/**
 * Plugin Name:  Show price button for Woocommerce
 * Description:  Simple plugin to track your users habbits in checking price.
 * Author:       Piotr Josko
 * Author URI:   http://wordpressdesign.pl/
 * Version:      1.0
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package woocommerce-show-price-button
 */

require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	/**
	 * Create date base
	 */
	function spfw_careate_database_structure() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'spfw_data';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			ID mediumint(9) NOT NULL AUTO_INCREMENT,
			time DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
			user_id INT NOT NULL,
			product_id INT NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
	register_activation_hook( __FILE__, 'spfw_careate_database_structure' );

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
				add_action( 'wp_enqueue_scripts', array( $this, 'script' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'script' ) );
				add_filter( 'woocommerce_get_sections_products', array( $this, 'show_price_add_section' ) );
				add_filter( 'woocommerce_get_settings_products', array( __CLASS__, 'show_price_all_settings' ), 10, 2 );
				add_filter( 'woocommerce_get_price_html', array( $this, 'remove_price_shop' ) );
				add_filter( 'woocommerce_before_add_to_cart_form', array( $this, 'remove_price_single_product' ) );
				add_action( 'wp_ajax_show_price', array( $this, 'show_price' ) );
				add_action( 'wp_ajax_nopriv_show_price', array( $this, 'show_price' ) );
				add_action( 'wp_ajax_show_price', array( $this, 'get_user_data' ) );
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
			public function script() {
				if ( is_product() && 'yes' === get_option( 'show-price-product-page' ) ) {
					wp_enqueue_script( 'hide_price', plugins_url( 'assets/js/main.js', __FILE__ ), array( 'jquery' ), '1.0', true );
					wp_localize_script(
						'show_price',
						'admin_url',
						array(
							'url' => admin_url( 'admin-ajax.php' ),
						)
					);
				} elseif ( is_shop() && 'yes' === get_option( 'show-price-shop-page' ) ) {
					wp_enqueue_script( 'hide_price', plugins_url( 'assets/js/main.js', __FILE__ ), array( 'jquery' ), '1.0', true );
				}
				wp_enqueue_script( 'datatables_js', plugins_url( 'assets/js/datatables.min.js', __FILE__ ), array( 'jquery' ), '1.0', true );
				wp_enqueue_style( 'datatables_css', plugins_url( 'assets/css/datatables.min.css', __FILE__ ), '', '1.0' );
			}

			/**
			 * Hide price on product page
			 *
			 * @param int $price Price.
			 */
			public function remove_price_single_product( $price ) {
				if ( is_product() && 'yes' === get_option( 'show-price-product-page' ) ) {
					if ( ! is_user_logged_in() ) {
						$user_id = 0;
					} else {
						$user_id = get_current_user_id();
					}

					?>
						<form id="spfw_form" method="post" action="" name="spfw_form">
							<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( $user_id ); ?>">
							<input type="hidden" name="product_id" id="product_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
							<input type="hidden" name="action" value="show_price"/>
							<button class="cena" id="submit-price" type="button" name="submit"><?php esc_html_e( 'Pokaż cenę', 'show price' ); ?></button>
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

				if ( ! empty( $_POST ) && 'show_price' === $_POST['action'] ) {
					$user_id    = ( filter_var( wp_unslash( $_POST['user_id'] ), FILTER_VALIDATE_INT ) ) ? filter_var( wp_unslash( $_POST['user_id'] ), FILTER_VALIDATE_INT ) : 0;
					$product_id = ( filter_var( wp_unslash( $_POST['product_id'] ), FILTER_VALIDATE_INT ) ) ? filter_var( wp_unslash( $_POST['product_id'] ), FILTER_VALIDATE_INT ) : false;

					if ( 0 <= $user_id && $product_id ) {
						$passed_data = $this->set_user_data( $user_id, $product_id );
						if ( $passed_data ) {
							wp_send_json_success();
						}
					}
				}
				wp_send_json_error();
			}

			/**
			 * Function is save user data in datebase.
			 *
			 * @param int $user_id    User id.
			 * @param int $product_id Product id.
			 */
			private function set_user_data( $user_id, $product_id ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'spfw_data';
				$saved = $wpdb->insert( // phpcs:ignore
					$table_name,
					array(
						'time'       => current_time( 'mysql' ),
						'user_id'    => $user_id,
						'product_id' => $product_id,
					)
				);

				if ( $saved ) {
					return true;
				}
			}

			/**
			 * Function is save user data in datebase.
			 */
			public function get_user_data() {
				global $wpdb;
				$table_name = $wpdb->prefix . 'spfw_data';
				$rows       = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
				return $rows;
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
				$users_data = $this->get_user_data();
				?>

				<div class="wrap">
				<!-- Table -->
				<table id="user_data" class="display" style="width:100%">
					<h1 class="wp-heading-inline">Show Price data</h1>
					<thead>
						<tr>
							<th>ID</th>
							<th>User email</th>
							<th>Product</th>
							<th>Time</th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $users_data as $user ) {
						$user_email = ( 0 === (int) $user['user_id'] ) ? 'Guest' : get_user_by( 'ID', $user['user_id'] )->user_email;
						?>
						<tr>
							<td><?php echo esc_html( $user['ID'] ); ?></td>
							<td><?php echo esc_html( $user_email ); ?></td>
							<td><a href="<?php echo esc_url( get_page_link( $user['product_id'] ) ); ?>"><?php echo esc_html( get_the_title( $user['product_id'] ) ); ?></a></td>
							<td><?php echo esc_html( $user['time'] ); ?></td>
						</tr>
					<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<th>ID</th>
							<th>User email</th>
							<th>Product</th>
							<th>Time</th>
						</tr>
					</tfoot>
				</table>
				</div>

				<script>
				jQuery(document).ready(function() {
					jQuery('#user_data').DataTable( {
					} );
				} );
				</script>

				<?php
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
