<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types(  [ 'public' => true ] );
		$class_name = $attributes['className'];
		ob_start();
		?>
        <div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php _e( "Post Counts", "site-counts" ); ?></h2>
			<ul>
				<?php
				foreach ( $post_types as $post_type_slug ) :
					$post_type_object = get_post_type_object( $post_type_slug  );
					$post_count = wp_count_posts( $post_type_slug );
					if ( $post_count->publish !== 0 ) :
						?>
						<li>
							<?php
							/* translators: 1: text, 2: post count, 3: post type name */
							printf( '%1$s %2$s %3$s',
								__( 'There are', 'site-counts' ),
								$post_count->publish,
								esc_html( $post_type_object->labels->name )
							);
							?>
						</li>
						<?php
					endif;
				endforeach;
				?>
			</ul>

			<p>
				<?php 
				$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : get_the_ID();
				$post_id = esc_html( $post_id );
				/* translators: 1: text, 2: post id */
				printf( '%1$s %2$s',
					__( 'The current post ID is', 'site-counts' ),
					$post_id
				);
				?>
			</p>

			<?php $this->get_foo_baz_posts( $post_id ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Gets posts with Foo and Baz.
	 * get all posts with tag: foo and category: baz, then display the title.
	 *
	 * @param int $post_id - The post ID.
	 * @return void
	 */
	private function get_foo_baz_posts( $post_id ) {
		$query = new WP_Query(
			array(
				'post_type'					=> [ 'post', 'page' ],
				'post_status'				=> 'any',
				'date_query' => array(
					array(
						'hour'      => 9,
						'compare'   => '>=',
					),
					array(
						'hour'		=> 17,
						'compare'	=> '<=',
					),
				),
				'tag'						=> 'foo',
				'category_name'				=> 'baz',
				'no_found_rows'          	=> true,
				'update_post_meta_cache'	=> false,
			)
		);

		if ( $query->have_posts() ) :
			$post_count = 0;
			foreach ( $query->posts as $post ) {
				if ( $post->ID != $post_id ) {
					$post_count++;
				}
			}
			?>
			<h2>
				<?php
				/* translators: 1: post count, 2: text */
				printf( '%d %s',
					$post_count,
					__( 'posts with the tag of foo and the category of baz', 'site-counts' ),
				);
				?>
			</h2>

			<ul>
				<?php
				$count = 0;
				while ( $query->have_posts() ) :
					$query->the_post();
					$count++;
					if ( get_the_ID() == $post_id ) continue;
					?>
					<li><?php the_title(); ?></li>
					<?php
					if ( $count > 5 ) break;
				endwhile;
				?>
			</ul>
			<?php
			wp_reset_postdata();
		endif;
	}
}
