<?php
/**
 * The children shortcode.
 *
 * @package hestia
 */

namespace SSNepenthe\Hestia\Shortcode;

use WP_Query;
use SSNepenthe\Hestia\Cache\Repository;
use SSNepenthe\Hestia\View\Plates_Manager;
use function SSNepenthe\Hestia\parse_atts;
use function SSNepenthe\Hestia\generate_cache_key;
use function SSNepenthe\Hestia\get_cache_lifetime;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * The class defines the children shortcode.
 */
class Children {
	/**
	 * Cache repository.
	 *
	 * @var Repository
	 */
	protected $repository;

	/**
	 * Template instance.
	 *
	 * @var Plates_Manager
	 */
	protected $template;

	/**
	 * Class constructor.
	 *
	 * @param Repository     $repository Cache repository.
	 * @param Plates_Manager $template   Template instance.
	 */
	public function __construct( Repository $repository, Plates_Manager $template ) {
		$this->repository = $repository;
		$this->template = $template;
	}

	/**
	 * Delegates to the template instance to render the shortcode output.
	 *
	 * @param  mixed  $atts Shortcode attributes.
	 * @param  mixed  $_    The shortcode content.
	 * @param  string $tag  The shortcode tag.
	 *
	 * @return string
	 */
	public function shortcode_handler(
		$atts,
		$_ = null,
		string $tag = ''
	) : string {
		if ( ! is_post_type_hierarchical( get_post_type() ) ) {
			return '';
		}

		$atts = parse_atts( $atts, $tag );
		$key = generate_cache_key( $atts, $tag );
		$lifetime = get_cache_lifetime( $tag );

		return $this->repository->remember(
			$key,
			$lifetime,
			function() use ( $atts ) {
				return $this->template->render(
					'hestia-children',
					$this->generate_data_array( $atts )
				);
			}
		);
	}

	/**
	 * Generates the data array for the template.
	 *
	 * @param  array $atts Shortcode attributes.
	 *
	 * @return array
	 */
	protected function generate_data_array( array $atts ) : array {
		// Atts assumed to have already been validated.
		$args = [
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'order'                  => $atts['order'],
			'post_parent'            => get_the_ID(),
			'post_type'              => get_post_type(),
			'posts_per_page'         => $atts['max'],
			'update_post_term_cache' => false,
		];

		if ( ! $atts['thumbnails'] ) {
			$args['update_post_meta_cache'] = false;
		}

		$query = new WP_Query( $args );
		$children = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$id = get_the_ID();
				$permalink = get_permalink();
				$thumbnail = $atts['thumbnails'] ? get_the_post_thumbnail() : '';
				$title = get_the_title();

				$children[] = compact( 'id', 'permalink', 'thumbnail', 'title' );
			}

			wp_reset_postdata();
		}

		return compact( 'children' );
	}
}
