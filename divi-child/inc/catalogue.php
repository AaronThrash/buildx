<?php
/**
 * BuildX ADU Catalogue shortcode
 * Slug: [buildx_adu_catalogue]
 */
require_once get_stylesheet_directory() . '/inc/thumbnails.php';

if ( ! function_exists( 'buildx_adu_catalogue_shortcode' ) ) {

	function buildx_adu_catalogue_shortcode( $atts ) {
		// TODO: adjust image URLs, plan URLs and base prices for staging.
		$plans = [
			[
				'id'         => 'x1',
				'title'      => 'X-1 • The Cottage',
				'image'      => '/wp-content/uploads/2025/11/X-1-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-1/',
				'bedrooms'   => 2,
				'bathrooms'  => 1.5,
				'stories'    => 1,
				'porch'      => 'farmers',
				'garage'     => false,
				'base_price' => 265000,
			],
			[
				'id'         => 'x2',
				'title'      => 'X-2 • The Hideaway',
				'image'      => '/wp-content/uploads/2025/11/X-2-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-2/',
				'bedrooms'   => 1,
				'bathrooms'  => 1,
				'stories'    => 1,
				'porch'      => 'front',
				'garage'     => false,
				'base_price' => 225000,
			],
			[
				'id'         => 'x3',
				'title'      => 'X-3 • The Haven',
				'image'      => '/wp-content/uploads/2025/11/X-3-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-3/',
				'bedrooms'   => 2,
				'bathrooms'  => 1,
				'stories'    => 1,
				'porch'      => 'front',
				'garage'     => false,
				'base_price' => 245000,
			],
			[
				'id'         => 'x4',
				'title'      => 'X-4 • The Retreat',
				'image'      => '/wp-content/uploads/2025/11/X-4-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-4/',
				'bedrooms'   => 1,
				'bathrooms'  => 1,
				'stories'    => 1,
				'porch'      => 'farmers',
				'garage'     => false,
				'base_price' => 215000,
			],
			[
				'id'         => 'x5',
				'title'      => 'XX-5 • The Glen',
				'image'      => '/wp-content/uploads/2025/11/X-5-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-5/',
				'bedrooms'   => 2,
				'bathrooms'  => 1,
				'stories'    => 1,
				'porch'      => 'front',
				'garage'     => false,
				'base_price' => 230000,
			],
			[
				'id'         => 'x6',
				'title'      => 'X-6 • The Nest',
				'image'      => '/wp-content/uploads/2025/11/X-6-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-6/',
				'bedrooms'   => 1,
				'bathrooms'  => 1,
				'stories'    => 1,
				'porch'      => 'front',
				'garage'     => false,
				'base_price' => 255000,
			],
			[
				'id'         => 'x7',
				'title'      => 'X-7 • The Gables',
				'image'      => '/wp-content/uploads/2025/11/X-7-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-7/',
				'bedrooms'   => 2,
				'bathrooms'  => 1,
				'stories'    => 2,
				'porch'      => 'deck',
				'garage'     => true,
				'base_price' => 315000,
			],
			[
				'id'         => 'x8',
				'title'      => 'X-8 • The Garden House',
				'image'      => '/wp-content/uploads/2025/11/X-8-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-8/',
				'bedrooms'   => 2,
				'bathrooms'  => 1.5,
				'stories'    => 2,
				'porch'      => 'farmers',
				'garage'     => false,
				'base_price' => 335000,
			],
			[
				'id'         => 'x9',
				'title'      => 'X-9 • The Breeze',
				'image'      => '/wp-content/uploads/2025/11/X-9-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-9/',
				'bedrooms'   => 2,
				'bathrooms'  => 2,
				'stories'    => 1,
				'porch'      => 'farmers',
				'garage'     => false,
				'base_price' => 295000,
			],
			[
				'id'         => 'x10',
				'title'      => 'X-10 • The Nook',
				'image'      => '/wp-content/uploads/2025/11/X-10-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-10/',
				'bedrooms'   => 1,
				'bathrooms'  => 1,
				'stories'    => 1,
				'porch'      => 'farmers',
				'garage'     => false,
				'base_price' => 210000,
			],
			[
				'id'         => 'x11',
				'title'      => 'X-11 • The Meadow',
				'image'      => '/wp-content/uploads/2025/11/X-11-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-11/',
				'bedrooms'   => 1,      // set 1 or 2
				'bathrooms'  => 1,    // 1, 1.5, or 2
				'stories'    => 1,      // 1 or 2
				'porch'      => 'farmers', // 'none', 'front', 'farmers', or 'deck'
				'garage'     => false,  // true if it has a garage
				'base_price' => 0,      // update with real price
			],
			[
				'id'         => 'x12',
				'title'      => 'X-12 • The Homestead',
				'image'      => '/wp-content/uploads/2025/11/X-12-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-12/',
				'bedrooms'   => 2,      // set 1 or 2
				'bathrooms'  => 2,    // 1, 1.5, or 2
				'stories'    => 1,      // 1 or 2
				'porch'      => 'farmers', // 'none', 'front', 'farmers', or 'deck'
				'garage'     => true,  // true if it has a garage
				'base_price' => 0,      // update with real price
			],
			[
				'id'         => 'x14',
				'title'      => 'X-14 • The Woodland',
				'image'      => '/wp-content/uploads/2025/11/X-14-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-14/',
				'bedrooms'   => 2,      // set 1 or 2
				'bathrooms'  => 1.5,    // 1, 1.5, or 2
				'stories'    => 1,      // 1 or 2
				'porch'      => 'farmers', // 'none', 'front', 'farmers', or 'deck'
				'garage'     => false,  // true if it has a garage
				'base_price' => 0,      // update with real price
			],
						[
				'id'         => 'x15',
				'title'      => 'X-15 • The Cove',
				'image'      => '/wp-content/uploads/2025/11/X-15-Floor-Plan.png',
				'url'        => '/adu-floor-plans/x-15/',
				'bedrooms'   => 1,      // set 1 or 2
				'bathrooms'  => 1,    // 1, 1.5, or 2
				'stories'    => 1,      // 1 or 2
				'porch'      => 'covered', // 'none', 'front', 'farmers', or 'deck'
				'garage'     => true,  // true if it has a garage
				'base_price' => 0,      // update with real price
			],




		];

		$plan_data = [
			'plans' => array_map(
				static function ( $p ) {
					return [
						'id'         => $p['id'],
						'title'      => $p['title'],
						'image'      => esc_url( $p['image'] ),
						'url'        => esc_url( $p['url'] ),
						'iso_image'  => esc_url( buildx_adu_iso_image_url( $p['image'] ) ),
						'bedrooms'   => (float) $p['bedrooms'],
						'bathrooms'  => (float) $p['bathrooms'],
						'stories'    => (float) $p['stories'],
						'porch'      => $p['porch'],
						'garage'     => (bool) $p['garage'],
						'base_price' => (int) $p['base_price'],
					];
				},
				$plans
			),
		];

		ob_start();
		?>
		<div id="bx-adu-catalogue" class="bx-adu-catalogue">
			<div class="bx-adu-visual">
				<div class="bx-adu-image-wrap">
					<img id="bx-plan-image" src="" alt="ADU floor plan">
				</div>

                <?php if ( ! empty( $plans[0] ) ) : ?>
					<?php buildx_render_adu_thumbnails( $plans[0] ); ?>
				<?php endif; ?>
                <div class="bx-adu-meta">
					<h2 id="bx-plan-title" class="bx-adu-title"></h2>
					<p id="bx-plan-specs" class="bx-adu-specs"></p>
					<p class="bx-adu-price">
						Starting at <strong id="bx-price"></strong>
					</p>
					<a id="bx-plan-link" href="#" class="bx-adu-cta">View this floor plan</a>
				</div>
			</div>

			<div class="bx-adu-controls-row">
				<div class="bx-adu-control">
					<label for="bx-beds">Bedrooms</label>
					<select id="bx-beds">
						<option value="1">1</option>
						<option value="2" selected>2</option>
					</select>
				</div>

				<div class="bx-adu-control">
					<label for="bx-baths">Bathrooms</label>
					<select id="bx-baths">
						<option value="1">1</option>
						<option value="1.5">1.5</option>
						<option value="2">2</option>
					</select>
				</div>

				<div class="bx-adu-control">
					<label for="bx-stories">Stories</label>
					<select id="bx-stories">
						<option value="1" selected>1</option>
						<option value="2">2</option>
					</select>
				</div>

				<div class="bx-adu-control">
					<label for="bx-garage">Garage</label>
					<select id="bx-garage">
						<option value="0" selected>No</option>
						<option value="1">Yes</option>
					</select>
				</div>

				<div class="bx-adu-control">
					<label for="bx-porch">Porch / Deck</label>
					<select id="bx-porch">
						<option value="covered" selected>Covered Entryway</option>
						<option value="farmers">Farmer's porch</option>
						<option value="deck">Deck</option>
					</select>
				</div>
			</div>

			<script type="application/json" id="bx-adu-data">
				<?php echo wp_json_encode( $plan_data ); ?>
			</script>
		</div>

		<style>
		#bx-adu-catalogue.bx-adu-catalogue {
			max-width: 1100px;
			margin: 0 auto;
		}
		.bx-adu-visual {
			text-align: center;
			margin-bottom: 1.5rem;
		}

		.bx-adu-image-wrap {
			position: relative;
			padding-top: 60%;
			border-radius: 12px;
			border: 1px solid #e5e7eb;
			overflow: hidden;
			background: #f9fafb;
		}
		#bx-plan-image {
			position: absolute;
			inset: 0;
			width: 100%;
			height: 100%;
			object-fit: contain;
			opacity: 0;
			transform: scale(1.02);
			transition: opacity 0.6s ease-in-out, transform 0.6s ease-in-out;
			cursor: zoom-in;
		}
        /* Thumbnail Styles */
		.bx-adu-catalog-thumbs {
			display: flex;
			justify-content: center;
			gap: 0.75rem;
			margin: 1rem 0 0.5rem; /* Spacing between main image and title */
		}
		.bx-adu-thumb {
			width: 80px;
			height: 60px;
			padding: 0;
			border: 2px solid transparent;
			border-radius: 6px;
			overflow: hidden;
			cursor: pointer;
			background: #f3f4f6;
			transition: all 0.2s ease;
		}
		.bx-adu-thumb:hover {
			border-color: #fbbf24; /* Yellow highlight on hover */
		}
		.bx-adu-thumb.is-active {
			border-color: #fbbf24; /* Yellow highlight when active */
			box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.3);
		}
		.bx-adu-thumb img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
		}
		#bx-plan-image.is-visible {
			opacity: 1;
			transform: scale(1);
		}
		.bx-adu-title {

			margin-top: 0.75rem;
			margin-bottom: 0.25rem;
			font-size: 1.25rem;
			font-weight: 600;
		}
		.bx-adu-specs {
			margin: 0;
			font-size: 0.95rem;
			color: #4b5563;
		}
		.bx-adu-price {
			margin: 0.5rem 0;
			font-size: 1rem;
			font-weight: 500;
		}
		.bx-adu-cta {
			display: inline-block;
			margin-top: 0.25rem;
			padding: 0.55rem 1.2rem;
			border-radius: 999px;
			text-decoration: none;
			background: #fbbf24;
			color: #111827;
			font-weight: 600;
			font-size: 0.95rem;
		}
		.bx-adu-controls-row {
			display: flex;
			flex-wrap: wrap;
			gap: 1rem;
			justify-content: center;
			padding: 1rem;
			border-radius: 12px;
			background: #f3f4f6;
		}
		.bx-adu-control {
			min-width: 150px;
			display: flex;
			flex-direction: column;
			font-size: 0.9rem;
		}
		.bx-adu-control label {
			margin-bottom: 0.3rem;
			font-weight: 500;
		}
		.bx-adu-control select {
			border-radius: 999px;
			padding: 0.3rem 0.75rem;
			border: 1px solid #d1d5db;
			background: white;
			font-size: 0.9rem;
		}
		.bx-adu-control select option.bx-disabled {
			color: #9ca3af;
		}
				@media (max-width: 767px) {
			.bx-adu-controls-row {
				align-items: stretch;
			}
			.bx-adu-control {
				flex: 1 1 45%;
			}
		}

		/* Lightbox overlay */
				.bx-adu-lightbox {
			position: fixed;
			inset: 0;
			background: rgba(0, 0, 0, 0.8);
			display: none;
			align-items: center;
			justify-content: center;
			z-index: 9999;
			padding: 24px;
			box-sizing: border-box;
			overflow: auto;
		}
		.bx-adu-lightbox.is-open {
			display: flex;
		}
		.bx-adu-lightbox-inner {
			position: relative;
			margin: 0 auto;
			max-width: min(96vw, 1600px);
			max-height: calc(100vh - 80px);
		}
		.bx-adu-lightbox-img {
			display: block;
			max-width: 100%;
			max-height: 100%;
			width: auto;
			height: auto;
			margin: 0 auto;
			border-radius: 12px;
			background: #fff;
		}

		.bx-adu-lightbox-close {
			position: absolute;
			top: -12px;
			right: -12px;
			width: 32px;
			height: 32px;
			border-radius: 999px;
			border: none;
			cursor: pointer;
			font-size: 20px;
			line-height: 1;
			background: #fbbf24;
			color: #111827;
		}
		</style>


		<script>
		(function() {
			function parseData() {
				var raw = document.getElementById('bx-adu-data');
				if (!raw) return { plans: [] };
				try {
					return JSON.parse(raw.textContent);
				} catch (e) {
					console.error('Bad ADU JSON', e);
					return { plans: [] };
				}
			}

			function scorePlan(plan, sel) {
				var penalty = 0;
				penalty += Math.abs(plan.bedrooms  - sel.bedrooms)  * 3;
				penalty += Math.abs(plan.bathrooms - sel.bathrooms) * 2;
				penalty += Math.abs(plan.stories   - sel.stories)   * 2;

				if (sel.garage && !plan.garage)   penalty += 4;
				if (!sel.garage && plan.garage)   penalty += 1;

				if (sel.porch !== plan.porch)     penalty += 1;

				return penalty;
			}

			
			function availableSet(plans, key) {
				var set = new Set();
				plans.forEach(function(plan) {
					set.add(plan[key]);
				});
				return set;
			}

			function updateSelectOptions(selectEl, allowedSet, parser) {
				var options = Array.prototype.slice.call(selectEl.options);
				var hasCurrent = false;
				options.forEach(function(opt) {
					var val = parser(opt.value);
					var allowed = allowedSet.has(val);
					opt.disabled = !allowed;
					opt.classList.toggle('bx-disabled', !allowed);
					if (allowed && opt.value === selectEl.value) {
						hasCurrent = true;
					}
				});
				if (!hasCurrent) {
					var firstAllowed = options.find(function(opt) { return !opt.disabled; });
					if (firstAllowed) {
						selectEl.value = firstAllowed.value;
					}
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				var data = parseData();
				if (!data.plans.length) return;

				var img   = document.getElementById('bx-plan-image');
				var title = document.getElementById('bx-plan-title');
				var specs = document.getElementById('bx-plan-specs');
				var link  = document.getElementById('bx-plan-link');
				var price = document.getElementById('bx-price');

				var beds    = document.getElementById('bx-beds');
				var baths   = document.getElementById('bx-baths');
				var stories = document.getElementById('bx-stories');
				var garage  = document.getElementById('bx-garage');
				var porch   = document.getElementById('bx-porch');
				// 1. Select thumbnail elements and track current view state
				var thumbBtns = document.querySelectorAll('.bx-adu-thumb');
				var thumbPlanImg = document.querySelector('.bx-adu-thumb[data-variant="plan"] img');
				var thumbIsoImg = document.querySelector('.bx-adu-thumb[data-variant="iso"] img');
				// Default view state:
				var currentViewType = 'plan'; // 'plan' or 'iso'

				// --- Simple lightbox overlay for enlarged floor plan ---
				var lightbox = document.createElement('div');
				lightbox.className = 'bx-adu-lightbox';
				lightbox.innerHTML =
					'<div class="bx-adu-lightbox-inner">' +
						'<img class="bx-adu-lightbox-img" src="" alt="ADU floor plan enlarged">' +
						'<button type="button" class="bx-adu-lightbox-close" aria-label="Close">&times;</button>' +
					'</div>';
				document.body.appendChild(lightbox);

				var lightboxImg   = lightbox.querySelector('.bx-adu-lightbox-img');
				var lightboxClose = lightbox.querySelector('.bx-adu-lightbox-close');

				function openLightbox(src, alt) {
					lightboxImg.src = src;
					lightboxImg.alt = alt || '';
					lightbox.classList.add('is-open');
				}

				function closeLightbox() {
					lightbox.classList.remove('is-open');
				}

				lightbox.addEventListener('click', function(e) {
					if (e.target === lightbox || e.target === lightboxClose) {
						closeLightbox();
					}
				});

				document.addEventListener('keyup', function(e) {
					if (e.key === 'Escape') {
						closeLightbox();
					}
				});


				function getSelection() {
					return {
						bedrooms: parseFloat(beds.value),
						bathrooms: parseFloat(baths.value),
						stories: parseFloat(stories.value),
						garage: garage.value === '1',
						porch: porch.value,
					};
				}

				function findBestPlan(plans, sel) {
    // 1. Try to find an exact match first
    var exactMatches = plans.filter(function(plan) {
        return plan.bedrooms  === sel.bedrooms &&
               plan.bathrooms === sel.bathrooms &&
               plan.stories   === sel.stories &&
               plan.garage    === sel.garage &&
               plan.porch     === sel.porch;
    });

    // 2. If exact matches exist, score them. If not, score against all plans.
    var pool = exactMatches.length ? exactMatches : plans;
    var best = null;
    var bestScore = Infinity;

    pool.forEach(function(plan) {
        var s = scorePlan(plan, sel);
        if (s < bestScore) {
            bestScore = s;
            best = plan;
        }
    });

    return best;
}

				function update() {
					if (!data || !data.plans || !data.plans.length) {
						return;
					}

					var sel;
					var pool;
					var set;

					// 1) BEDROOMS — allowed across all plans
					pool = data.plans.slice();
					set  = availableSet(pool, 'bedrooms');
					updateSelectOptions(
						beds,
						set,
						function (value) {
							return parseFloat(value);
						}
					);
					sel = getSelection();

					// 2) BATHROOMS — constrained by selected bedrooms
					pool = data.plans.filter(function (plan) {
						return plan.bedrooms === sel.bedrooms;
					});
					set = availableSet(pool, 'bathrooms');
					updateSelectOptions(
						baths,
						set,
						function (value) {
							return parseFloat(value);
						}
					);
					sel = getSelection();

					// 3) STORIES — constrained by bedrooms + bathrooms
					pool = data.plans.filter(function (plan) {
						return (
							plan.bedrooms === sel.bedrooms &&
							plan.bathrooms === sel.bathrooms
						);
					});
					set = availableSet(pool, 'stories');
					updateSelectOptions(
						stories,
						set,
						function (value) {
							return parseFloat(value);
						}
					);
					sel = getSelection();

					// 4) GARAGE — constrained by bedrooms + bathrooms + stories
					pool = data.plans.filter(function (plan) {
						return (
							plan.bedrooms === sel.bedrooms &&
							plan.bathrooms === sel.bathrooms &&
							plan.stories === sel.stories
						);
					});
					set = availableSet(pool, 'garage');
					updateSelectOptions(
						garage,
						set,
						function (value) {
							return value === '1';
						}
					);
					sel = getSelection();

					// 5) PORCH / DECK — constrained by all prior selections
					pool = data.plans.filter(function (plan) {
						return (
							plan.bedrooms === sel.bedrooms &&
							plan.bathrooms === sel.bathrooms &&
							plan.stories === sel.stories &&
							plan.garage === sel.garage
						);
					});
					set = availableSet(pool, 'porch');
					updateSelectOptions(
						porch,
						set,
						function (value) {
							return value;
						}
					);

					// Final selection after any snapping
					sel = getSelection();

					var plan = findBestPlan(data.plans, sel);
					if (!plan) {
						return;
					}

					// Smooth cross-fade of the main image + details
					img.classList.remove('is-visible');

					window.setTimeout(function () {
						// Determine which main image to show based on current view state
						var mainImageSrc = (currentViewType === 'iso' && plan.iso_image)
							? plan.iso_image
							: plan.image;

						img.src = mainImageSrc;
						img.alt = plan.title + ' floor plan';

						title.textContent = plan.title;
						specs.textContent =
							plan.bedrooms + ' bed · ' +
							plan.bathrooms + ' bath · ' +
							(plan.stories === 1 ? '1 story' : plan.stories + ' stories') +
							(plan.garage ? ' · with garage' : '');

						link.href = plan.url;
						price.textContent = '$' + (plan.base_price || 0).toLocaleString();

						img.classList.add('is-visible');
					}, 300);
					// Update thumbnail images so they match the currently selected plan
					if (thumbPlanImg) thumbPlanImg.src = plan.image;
					if (thumbIsoImg && plan.iso_image) thumbIsoImg.src = plan.iso_image;
                    
				}



					[beds, baths, stories, garage, porch].forEach(function(el) {
					el.addEventListener('change', update);
				});
					// Add click handlers to thumbnails to toggle view state
				thumbBtns.forEach(function(btn) {
					btn.addEventListener('click', function() {
						var variant = btn.getAttribute('data-variant');
						// If clicking the already active one, do nothing
						if (variant === currentViewType) return;

						// Update state
						currentViewType = variant;

						// Update active CSS classes on thumbnails
						thumbBtns.forEach(function(t) { t.classList.remove('is-active'); });
						btn.classList.add('is-active');

						// Trigger update to change main image
						update();
					});
				});

				// Open lightbox when clicking on the plan image
				img.addEventListener('click', function() {
					if (!img.src) return;
					openLightbox(img.src, img.alt);
				});

				update();
			});

		})();
		</script>
		<?php

		return ob_get_clean();
	}

	add_shortcode( 'buildx_adu_catalogue', 'buildx_adu_catalogue_shortcode' );
}
