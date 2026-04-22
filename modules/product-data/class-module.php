<?php
/**
 * Product Data module: adds a metabox to the WooCommerce product edit screen
 * showing all post meta, product attributes, and raw product data as JSON.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSD_Product_Data_Module implements SSD_Module_Interface {

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the metabox on the product edit screen.
	 */
	public function add_metabox(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_meta_box(
			'ssd-product-data',
			'Simple Site Data: Product Debug',
			array( $this, 'render_metabox' ),
			$this->get_product_screen(),
			'normal',
			'low'
		);
	}

	/**
	 * Enqueue CSS and JS on the product edit screen only.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ! $this->is_product_screen( $screen ) ) {
			return;
		}

		wp_enqueue_style(
			'ssd-product-data',
			SSD_PLUGIN_URL . 'assets/css/product-data.css',
			array(),
			SSD_VERSION
		);

		wp_enqueue_script(
			'ssd-product-data',
			SSD_PLUGIN_URL . 'assets/js/product-data.js',
			array(),
			SSD_VERSION,
			true
		);
	}

	/**
	 * Render the metabox content.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render_metabox( $post ): void {
		$data = $this->collect_product_data( $post );
		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $json ) {
			echo '<p>Unable to encode product data as JSON.</p>';
			return;
		}
		?>
		<div class="ssd-product-data-wrap">
			<div class="ssd-toolbar">
				<input
					type="text"
					id="ssd-search"
					class="ssd-search"
					placeholder="Search keys or values..."
				/>
				<button type="button" class="button ssd-copy-btn">
					Copy JSON
				</button>
				<button type="button" class="button ssd-toggle-btn" data-expanded="true">
					Collapse All
				</button>
			</div>
			<pre id="ssd-json-output" class="ssd-json-output"><?php echo esc_html( $json ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Collect all useful product data into a single array.
	 *
	 * @param WP_Post $post The current post object.
	 * @return array<string, mixed>
	 */
	private function collect_product_data( $post ): array {
		return array(
			'post'       => $this->get_post_fields( $post ),
			'meta'       => $this->get_all_meta( $post->ID ),
			'attributes' => $this->get_product_attributes( $post->ID ),
			'taxonomies' => $this->get_taxonomy_terms( $post->ID ),
		);
	}

	/**
	 * Return the core WP_Post fields as an array.
	 *
	 * @param WP_Post $post The post object.
	 * @return array<string, mixed>
	 */
	private function get_post_fields( $post ): array {
		return array(
			'ID'            => $post->ID,
			'post_title'    => $post->post_title,
			'post_name'     => $post->post_name,
			'post_status'   => $post->post_status,
			'post_type'     => $post->post_type,
			'post_date'     => $post->post_date,
			'post_modified' => $post->post_modified,
			'post_parent'   => $post->post_parent,
			'menu_order'    => $post->menu_order,
			'guid'          => $post->guid,
		);
	}

	/**
	 * Get every meta row for the post, including hidden underscore-prefixed keys.
	 *
	 * @param int $post_id The post ID.
	 * @return array<string, mixed>
	 */
	private function get_all_meta( int $post_id ): array {
		$raw_meta = get_post_meta( $post_id );

		if ( ! is_array( $raw_meta ) ) {
			return array();
		}

		$meta = array();

		foreach ( $raw_meta as $key => $values ) {
			// Single-value keys are unwrapped for readability.
			$meta[ $key ] = ( count( $values ) === 1 )
				? $this->safe_unserialize( $values[0] )
				: array_map( array( $this, 'safe_unserialize' ), $values );
		}

		ksort( $meta );

		return $meta;
	}

	/**
	 * Safely unserialize a stored meta value without instantiating arbitrary
	 * classes. Prevents PHP object-injection / gadget-chain attacks when
	 * surfacing untrusted stored data in the debug metabox.
	 *
	 * @param mixed $value The raw meta value.
	 * @return mixed Unserialized value with only scalars/arrays, or the original value.
	 */
	private function safe_unserialize( $value ) {
		if ( ! is_string( $value ) || ! is_serialized( $value ) ) {
			return $value;
		}

		$result = @unserialize( $value, array( 'allowed_classes' => false ) );

		return false === $result && 'b:0;' !== $value ? $value : $result;
	}

	/**
	 * Get product attributes if WooCommerce is active.
	 *
	 * @param int $post_id The post ID.
	 * @return array<string, mixed>
	 */
	private function get_product_attributes( int $post_id ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array( '_notice' => 'WooCommerce not active' );
		}

		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return array();
		}

		$attributes = array();

		foreach ( $product->get_attributes() as $key => $attribute ) {
			if ( is_a( $attribute, 'WC_Product_Attribute' ) ) {
				$attributes[ $key ] = array(
					'name'      => $attribute->get_name(),
					'options'   => $attribute->get_options(),
					'position'  => $attribute->get_position(),
					'visible'   => $attribute->get_visible(),
					'variation' => $attribute->get_variation(),
					'taxonomy'  => $attribute->is_taxonomy(),
				);
			} else {
				$attributes[ $key ] = $attribute;
			}
		}

		return $attributes;
	}

	/**
	 * Get all taxonomy terms assigned to this product.
	 *
	 * @param int $post_id The post ID.
	 * @return array<string, mixed>
	 */
	private function get_taxonomy_terms( int $post_id ): array {
		$taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
		$result     = array();

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );

			if ( is_array( $terms ) ) {
				$result[ $taxonomy ] = wp_list_pluck( $terms, 'name' );
			}
		}

		return $result;
	}

	/**
	 * Return the screen ID for the product edit page.
	 *
	 * @return string The product screen ID.
	 */
	private function get_product_screen(): string {
		return 'product';
	}

	/**
	 * Check whether a screen object is the product edit screen.
	 *
	 * @param WP_Screen $screen The screen object.
	 * @return bool
	 */
	private function is_product_screen( $screen ): bool {
		return 'product' === $screen->id || 'product' === $screen->post_type;
	}
}

return new SSD_Product_Data_Module();
