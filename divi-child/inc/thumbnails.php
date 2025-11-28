<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * BuildX ADU catalogue – thumbnail helpers (plan + isometric).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Given an image URL (floor plan), derive the isometric URL
 * by inserting "-Iso" before the extension.
 *
 * Example:
 *   /X-1-Floor-Plan.png → /X-1-Floor-Plan-Iso.png
 */
function buildx_adu_iso_image_url( $image_url ) {
	if ( empty( $image_url ) ) {
		return '';
	}

	return preg_replace(
		'/(\.[a-zA-Z0-9]+)(\?.*)?$/',
		'-Iso$1$2',
		$image_url
	);
}

/**
 * Render the thumbnail strip (plan image + isometric image).
 *
 * @param array $plan First plan from the catalogue data.
 */
function buildx_render_adu_thumbnails( $plan ) {
	if ( empty( $plan['image'] ) ) {
		return;
	}

	$plan_image = $plan['image'];
	$iso_image  = ! empty( $plan['iso_image'] )
		? $plan['iso_image']
		: buildx_adu_iso_image_url( $plan_image );
	?>
	<div class="bx-adu-catalog-thumbs" aria-label="Alternate views" role="group">
		<button type="button" class="bx-adu-thumb is-active" data-variant="plan">
			<img src="<?php echo esc_url( $plan_image ); ?>"
				 alt="<?php echo esc_attr( $plan['title'] ); ?> floor plan thumbnail">
		</button>

		<button type="button" class="bx-adu-thumb" data-variant="iso">
			<img src="<?php echo esc_url( $iso_image ); ?>"
				 alt="<?php echo esc_attr( $plan['title'] ); ?> isometric thumbnail">
		</button>
	</div>
	<?php
}
