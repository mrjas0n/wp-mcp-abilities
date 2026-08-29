<?php
if (!defined('ABSPATH')) exit;
	// ─────────────────────────────────────────────────────────────
	// v1.7.0 — Design tokens + pattern library (Elementor component trees)
	// ─────────────────────────────────────────────────────────────

	function ning_mcp_design_tokens() {
		$defaults = array(
			'palette'    => array(
				'primary' => '#A67C52',
				'accent'  => '#D9A679',
				'bg'      => '#FDF6EE',
				'text'    => '#3B3B3B',
				'muted'   => '#8A7A6A',
				'border'  => '#E8DDD0',
			),
			'typography' => array(
				'heading_font' => 'Playfair Display, serif',
				'body_font'    => 'Inter, sans-serif',
			),
			'spacing'    => array(
				'section' => '72',
				'gap'     => '24',
			),
			'radius'     => array(
				'card'   => '18',
				'button' => '999',
			),
			'shadow'     => array(
				'card' => '0 4px 20px rgba(166,124,82,.12)',
			),
		);
		$saved = get_option( 'wp_mcp_design_tokens', array() );
		if ( is_array( $saved ) ) {
			foreach ( $saved as $group => $values ) {
				if ( isset( $defaults[ $group ] ) && is_array( $values ) ) {
					$defaults[ $group ] = array_merge( $defaults[ $group ], $values );
				}
			}
		}
		return $defaults;
	}

	function ning_mcp_widget_catalog() {
		return array(
			'heading'       => array( 'title', 'header_size', 'align', 'link', 'title_color', 'title_typography_*' ),
			'text-editor'   => array( 'editor', 'text_color', 'typography_*' ),
			'button'        => array( 'text', 'link', 'size', 'align', 'button_text_color', 'background_color', 'border_radius', 'button_hover_color', 'typography_*' ),
			'image'         => array( 'image{url,id,size}', 'size', 'opacity' ),
			'icon-box'      => array( 'selected_icon', 'view', 'shape', 'title_text', 'description_text', 'primary_color', 'icon_size' ),
			'divider'       => array( 'look', 'text', 'html_tag', 'color', 'gap' ),
			'counter'       => array( 'starting_number', 'ending_number', 'prefix', 'suffix', 'duration', 'number_color', 'title', 'title_color' ),
			'testimonial'   => array( 'testimonial_content', 'testimonial_name', 'testimonial_job', 'content_content_color' ),
			'star-rating'   => array( 'rating_scale', 'rating', 'stars_color' ),
			'html'          => array( 'html' ),
			'shortcode'     => array( 'shortcode' ),
			'container'     => array( 'content_width', 'boxed_width', 'flex_direction', 'justify_content', 'align_items', 'width(+_tablet/_mobile)', 'padding', 'gap', 'background_background', 'background_color', 'border_radius' ),
		);
	}

	function ning_mcp_tree_uid_next() {
		static $counter = 0;
		$counter++;
		return substr( 'w' . base_convert( (string) ( 268435456 + $counter * 7919 ), 10, 36 ), 0, 8 );
	}

	function ning_mcp_tree_to_elementor_data( $tree ) {
		$walk = function ( $node ) use ( &$walk ) {
			if ( 'widget' === $node['type'] ) {
				return array(
					'id'         => ning_mcp_tree_uid_next(),
					'elType'     => 'widget',
					'widgetType' => $node['widget'],
					'settings'   => isset( $node['settings'] ) ? $node['settings'] : array(),
					'elements'   => array(),
					'isInner'    => false,
					'isLocked'   => false,
				);
			}
			$out = array(
				'id'       => ning_mcp_tree_uid_next(),
				'elType'   => 'container',
				'settings' => isset( $node['settings'] ) ? $node['settings'] : array(),
				'elements' => array(),
				'isInner'  => false,
				'isLocked' => false,
			);
			foreach ( $node['children'] as $child ) {
				$out['elements'][] = $walk( $child );
			}
			return $out;
		};
		return array_map( $walk, $tree );
	}

	function ning_mcp_px( $size ) {
		return array( 'unit' => 'px', 'size' => (int) $size, 'sizes' => array() );
	}

	function ning_mcp_box( $top, $right, $bottom, $left ) {
		return array( 'unit' => 'px', 'top' => (int) $top, 'right' => (int) $right, 'bottom' => (int) $bottom, 'left' => (int) $left, 'isLinked' => false );
	}

	function ning_mcp_t_heading( $text, $tag, $t, $extra = array() ) {
		$base = array(
			'title'                       => $text,
			'header_size'                 => $tag,
			'title_color'                 => $t['palette']['text'],
			'typography_typography'       => 'custom',
			'typography_font_family'      => $t['typography']['heading_font'],
			'typography_font_size'        => ning_mcp_px( 'h1' === $tag ? 46 : 32 ),
			'typography_line_height'      => array( 'unit' => 'em', 'size' => 1.15, 'sizes' => array() ),
		);
		return array_merge( $base, $extra );
	}

	function ning_mcp_t_text( $html, $t, $extra = array() ) {
		$base = array(
			'editor'                 => $html,
			'text_color'             => $t['palette']['muted'],
			'typography_typography'  => 'custom',
			'typography_font_family' => $t['typography']['body_font'],
			'typography_font_size'   => ning_mcp_px( 17 ),
			'typography_line_height' => array( 'unit' => 'em', 'size' => 1.6, 'sizes' => array() ),
		);
		return array_merge( $base, $extra );
	}

	function ning_mcp_t_button( $text, $url, $t, $extra = array() ) {
		$base = array(
			'text'                   => $text,
			'link'                   => array( 'url' => $url, 'is_external' => '', 'nofollow' => '', 'custom_attributes' => '' ),
			'size'                   => 'lg',
			'button_text_color'      => '#FFFFFF',
			'background_color'       => $t['palette']['primary'],
			'border_radius'          => ning_mcp_box( $t['radius']['button'], $t['radius']['button'], $t['radius']['button'], $t['radius']['button'] ),
			'typography_typography'  => 'custom',
			'typography_font_family' => $t['typography']['body_font'],
			'typography_font_size'   => ning_mcp_px( 16 ),
			'button_hover_color'     => $t['palette']['accent'],
		);
		return array_merge( $base, $extra );
	}

	function ning_mcp_t_image( $url ) {
		return array(
			'image' => array( 'url' => $url, 'id' => '', 'size' => 'full' ),
			'size'  => 'full',
		);
	}

	function ning_mcp_t_container( $settings, $children ) {
		return array( 'type' => 'container', 'settings' => $settings, 'children' => $children );
	}

	function ning_mcp_t_widget( $widget, $settings ) {
		return array( 'type' => 'widget', 'widget' => $widget, 'settings' => $settings );
	}

	function ning_mcp_section_base( $t, $pad = null, $extra = array() ) {
		$s = array_merge(
			array(
				'content_width' => 'boxed',
				'flex_direction' => 'column',
				'padding'       => ning_mcp_box( $pad ? $pad : $t['spacing']['section'], 24, $pad ? $pad : $t['spacing']['section'], 24 ),
				'gap'           => ning_mcp_px( $t['spacing']['gap'] ),
			),
			$extra
		);
		return $s;
	}

	function ning_mcp_row( $children ) {
		return ning_mcp_t_container(
			array( 'content_width' => 'full', 'flex_direction' => 'row', 'gap' => ning_mcp_px( 24 ) ),
			$children
		);
	}

	function ning_mcp_col( $width_pct, $children ) {
		return ning_mcp_t_container(
			array(
				'content_width'   => 'full',
				'flex_direction'  => 'column',
				'justify_content' => 'center',
				'width'           => array( 'unit' => '%', 'size' => (int) $width_pct, 'sizes' => array() ),
				'width_tablet'    => array( 'unit' => '%', 'size' => 100, 'sizes' => array() ),
			),
			$children
		);
	}

	function ning_mcp_pattern_definitions() {
		$defs = array();

		$defs['hero-classic'] = array(
			'title'       => 'Hero Classic',
			'description' => 'Two-column hero: headline + text + CTA left, image right.',
			'params'      => array(
				'title'     => array( 'type' => 'string', 'default' => 'Handmade with Love' ),
				'subtitle'  => array( 'type' => 'string', 'default' => 'Thoughtfully crafted pieces for a warm home.' ),
				'cta_text'  => array( 'type' => 'string', 'default' => 'Shop New In' ),
				'cta_url'   => array( 'type' => 'string', 'default' => '#' ),
				'image_url' => array( 'type' => 'string', 'default' => '' ),
			),
			'build'       => function ( $p, $t ) {
				$img = $p['image_url'] ? $p['image_url'] : ( function_exists( 'ning_mcp_pexels_fallback_url' ) ? ning_mcp_pexels_fallback_url( $p['title'] ?: 'handmade crochet', 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image' ) : 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image' );
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'flex_direction' => 'row', 'align_items' => 'center' ) ),
						array(
							ning_mcp_col( 50, array(
								ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h1', $t ) ),
								ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p>' . esc_html( $p['subtitle'] ) . '</p>', $t ) ),
								ning_mcp_t_widget( 'button', ning_mcp_t_button( $p['cta_text'], $p['cta_url'], $t ) ),
							) ),
							ning_mcp_col( 50, array(
								ning_mcp_t_widget( 'image', ning_mcp_t_image( $img ) ),
							) ),
						)
					),
				);
			},
		);

		$defs['hero-split'] = array(
			'title'       => 'Hero Split',
			'description' => 'Two-column hero: image left, copy right.',
			'params'      => array(
				'title'     => array( 'type' => 'string', 'default' => 'Crafted Stories' ),
				'subtitle'  => array( 'type' => 'string', 'default' => 'Every piece begins with intention and ends with a story.' ),
				'cta_text'  => array( 'type' => 'string', 'default' => 'Explore' ),
				'cta_url'   => array( 'type' => 'string', 'default' => '#' ),
				'image_url' => array( 'type' => 'string', 'default' => '' ),
			),
			'build'       => function ( $p, $t ) {
				$img = $p['image_url'] ? $p['image_url'] : ( function_exists( 'ning_mcp_pexels_fallback_url' ) ? ning_mcp_pexels_fallback_url( $p['title'] ?: 'handmade crochet', 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image' ) : 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image' );
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'flex_direction' => 'row', 'align_items' => 'center' ) ),
						array(
							ning_mcp_col( 50, array(
								ning_mcp_t_widget( 'image', ning_mcp_t_image( $img ) ),
							) ),
							ning_mcp_col( 50, array(
								ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h1', $t ) ),
								ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p>' . esc_html( $p['subtitle'] ) . '</p>', $t ) ),
								ning_mcp_t_widget( 'button', ning_mcp_t_button( $p['cta_text'], $p['cta_url'], $t ) ),
							) ),
						)
					),
				);
			},
		);

		$defs['hero-minimal'] = array(
			'title'       => 'Hero Minimal',
			'description' => 'Centered headline + short text + one CTA.',
			'params'      => array(
				'title'    => array( 'type' => 'string', 'default' => 'Simple & Warm' ),
				'subtitle' => array( 'type' => 'string', 'default' => 'A quiet statement for every space.' ),
				'cta_text' => array( 'type' => 'string', 'default' => 'Discover' ),
				'cta_url'  => array( 'type' => 'string', 'default' => '#' ),
			),
			'build'       => function ( $p, $t ) {
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, 96, array( 'align_items' => 'center' ) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h1', $t, array( 'align' => 'center' ) ) ),
							ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p style="text-align:center">' . esc_html( $p['subtitle'] ) . '</p>', $t ) ),
							ning_mcp_t_widget( 'button', ning_mcp_t_button( $p['cta_text'], $p['cta_url'], $t, array( 'align' => 'center' ) ) ),
						)
					),
				);
			},
		);

		$defs['features-grid'] = array(
			'title'       => 'Features Grid',
			'description' => 'Section heading + 4-column icon-box features.',
			'params'      => array(
				'title'    => array( 'type' => 'string', 'default' => 'Why Choose Us' ),
				'feature1' => array( 'type' => 'string', 'default' => 'Quality Craft' ),
				'feature2' => array( 'type' => 'string', 'default' => 'Natural Materials' ),
				'feature3' => array( 'type' => 'string', 'default' => 'Fair Trade' ),
				'feature4' => array( 'type' => 'string', 'default' => 'Made to Last' ),
			),
			'build'       => function ( $p, $t ) {
				$cells = array();
				foreach ( array( $p['feature1'], $p['feature2'], $p['feature3'], $p['feature4'] ) as $f ) {
					$cells[] = ning_mcp_col( 25, array(
						ning_mcp_t_widget( 'icon-box', array(
							'selected_icon'    => array( 'value' => 'fas fa-heart', 'library' => 'fa-solid' ),
							'view'             => 'stacked',
							'shape'            => 'circle',
							'title_text'       => $f,
							'description_text' => 'Thoughtfully designed with care and intention.',
							'primary_color'    => $t['palette']['primary'],
							'icon_size'        => ning_mcp_px( 26 ),
							'title_typography_typography'  => 'custom',
							'title_typography_font_family' => $t['typography']['heading_font'],
							'content_typography_typography'=> 'custom',
							'content_typography_font_family'=> $t['typography']['body_font'],
						) ),
					) );
				}
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'align_items' => 'center' ) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h2', $t, array( 'align' => 'center' ) ) ),
							ning_mcp_row( $cells ),
						)
					),
				);
			},
		);

		$defs['banner-cta'] = array(
			'title'       => 'Banner CTA',
			'description' => 'Full-width CTA band on the primary color.',
			'params'      => array(
				'title'    => array( 'type' => 'string', 'default' => 'Join Our Community' ),
				'subtitle' => array( 'type' => 'string', 'default' => 'Be the first to know about new arrivals.' ),
				'cta_text' => array( 'type' => 'string', 'default' => 'Subscribe' ),
				'cta_url'  => array( 'type' => 'string', 'default' => '#' ),
			),
			'build'       => function ( $p, $t ) {
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, 64, array(
							'background_background' => 'classic',
							'background_color'      => $t['palette']['primary'],
							'border_radius'         => ning_mcp_box( 24, 24, 24, 24 ),
							'align_items'           => 'center',
						) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h2', $t, array( 'align' => 'center', 'title_color' => '#FFFFFF' ) ) ),
							ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p style="text-align:center">' . esc_html( $p['subtitle'] ) . '</p>', $t, array( 'text_color' => 'rgba(255,255,255,.92)' ) ) ),
							ning_mcp_t_widget( 'button', ning_mcp_t_button( $p['cta_text'], $p['cta_url'], $t, array( 'align' => 'center', 'background_color' => '#FFFFFF', 'button_text_color' => $t['palette']['primary'] ) ) ),
						)
					),
				);
			},
		);

		$defs['testimonials'] = array(
			'title'       => 'Testimonials',
			'description' => 'Section heading + 3 testimonial cards with star ratings.',
			'params'      => array(
				'title' => array( 'type' => 'string', 'default' => 'Kind Words' ),
			),
			'build'       => function ( $p, $t ) {
				$quotes = array(
					array( 'Anna K.', 'Happy Customer', 'Absolutely love my handmade piece — you can feel the care in every stitch.' ),
					array( 'Tom B.', 'Collector', 'Beautiful quality and quick delivery. Will definitely order again.' ),
					array( 'Mia L.', 'Gift Buyer', 'The perfect gift. So unique and well made.' ),
				);
				$cells = array();
				foreach ( $quotes as $q ) {
					$cells[] = ning_mcp_col( 33, array(
						ning_mcp_t_widget( 'star-rating', array(
							'rating_scale' => 5,
							'rating'       => 5,
							'stars_color'  => $t['palette']['accent'],
						) ),
						ning_mcp_t_widget( 'testimonial', array(
							'testimonial_content'           => $q[2],
							'testimonial_name'              => $q[0],
							'testimonial_job'               => $q[1],
							'content_content_color'         => $t['palette']['text'],
							'content_typography_typography' => 'custom',
							'content_typography_font_family'=> $t['typography']['body_font'],
						) ),
					) );
				}
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'align_items' => 'center' ) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h2', $t, array( 'align' => 'center' ) ) ),
							ning_mcp_row( $cells ),
						)
					),
				);
			},
		);

		$defs['stats-band'] = array(
			'title'       => 'Stats Band',
			'description' => 'Four animated counters.',
			'params'      => array(
				'stat1' => array( 'type' => 'integer', 'default' => 1200 ),
				'stat2' => array( 'type' => 'integer', 'default' => 50 ),
				'stat3' => array( 'type' => 'integer', 'default' => 30 ),
				'stat4' => array( 'type' => 'integer', 'default' => 15 ),
			),
			'build'       => function ( $p, $t ) {
				$stats = array(
					array( $p['stat1'], '+', 'Happy Customers' ),
					array( $p['stat2'], '+', 'Artisans' ),
					array( $p['stat3'], '+', 'Countries' ),
					array( $p['stat4'], '+', 'Years' ),
				);
				$cells = array();
				foreach ( $stats as $s ) {
					$cells[] = ning_mcp_col( 25, array(
						ning_mcp_t_widget( 'counter', array(
							'starting_number' => 0,
							'ending_number'   => (int) $s[0],
							'suffix'          => $s[1],
							'duration'        => 2000,
							'number_color'    => $t['palette']['primary'],
							'title'           => $s[2],
							'title_color'     => $t['palette']['muted'],
						) ),
					) );
				}
				return array(
					ning_mcp_t_container( ning_mcp_section_base( $t, 56 ), array( ning_mcp_row( $cells ) ) ),
				);
			},
		);

		$defs['newsletter'] = array(
			'title'       => 'Newsletter',
			'description' => 'Centered signup band with inline email input + button.',
			'params'      => array(
				'title'    => array( 'type' => 'string', 'default' => 'Stay in the Loop' ),
				'subtitle' => array( 'type' => 'string', 'default' => 'Sign up for our newsletter.' ),
			),
			'build'       => function ( $p, $t ) {
				$form = '<form class="wpmp-newsletter" action="#" method="post" style="display:flex;gap:10px;max-width:460px;margin:20px auto 0;flex-wrap:wrap;justify-content:center">'
					. '<input type="email" required placeholder="Your email" style="flex:1;min-width:220px;padding:14px 18px;border:1px solid ' . esc_attr( $t['palette']['border'] ) . ';border-radius:' . esc_attr( $t['radius']['card'] ) . 'px;font-family:' . esc_attr( $t['typography']['body_font'] ) . ';font-size:15px">'
					. '<button type="submit" style="padding:14px 26px;border:0;border-radius:' . esc_attr( $t['radius']['button'] ) . 'px;background:' . esc_attr( $t['palette']['primary'] ) . ';color:#fff;font-family:' . esc_attr( $t['typography']['body_font'] ) . ';font-size:15px;font-weight:600;cursor:pointer">Subscribe</button>'
					. '</form>';
				return array(
					ning_mcp_t_container(
						ning_mcp_section_base( $t, null, array( 'align_items' => 'center' ) ),
						array(
							ning_mcp_t_widget( 'heading', ning_mcp_t_heading( $p['title'], 'h2', $t, array( 'align' => 'center' ) ) ),
							ning_mcp_t_widget( 'text-editor', ning_mcp_t_text( '<p style="text-align:center">' . esc_html( $p['subtitle'] ) . '</p>', $t ) ),
							ning_mcp_t_widget( 'html', array( 'html' => $form ) ),
						)
					),
				);
			},
		);

		$defs['marquee'] = array(
			'title'       => 'Marquee',
			'description' => 'Scrolling text strip (pure CSS).',
			'params'      => array(
				'text' => array( 'type' => 'string', 'default' => 'Handmade with Love • Natural Materials • Fair Trade' ),
			),
			'build'       => function ( $p, $t ) {
				$line = str_repeat( esc_html( $p['text'] ) . ' &nbsp;•&nbsp; ', 3 );
				$html = '<div class="wpmp-marquee" style="overflow:hidden;white-space:nowrap;background:' . esc_attr( $t['palette']['primary'] ) . ';color:#fff;padding:14px 0">'
					. '<div style="display:inline-block;font-family:' . esc_attr( $t['typography']['heading_font'] ) . ';font-size:18px;letter-spacing:.05em;animation:wpmp-marquee 18s linear infinite">' . $line . '</div></div>'
					. '<style>@keyframes wpmp-marquee{from{transform:translateX(0)}to{transform:translateX(-33.333%)}}@media(prefers-reduced-motion:reduce){.wpmp-marquee div{animation:none!important}}</style>';
				return array(
					ning_mcp_t_container( array( 'content_width' => 'full' ), array(
						ning_mcp_t_widget( 'html', array( 'html' => $html ) ),
					) ),
				);
			},
		);

		$defs['filament-divider'] = array(
			'title'       => 'Filament Divider',
			'description' => 'Ornamental divider with star SVG center.',
			'params'      => array(),
			'build'       => function ( $p, $t ) {
				$html = '<div style="display:flex;align-items:center;gap:14px;max-width:420px;margin:0 auto">'
					. '<span style="flex:1;height:1px;background:linear-gradient(90deg,transparent,' . esc_attr( $t['palette']['border'] ) . ')"></span>'
					. '<svg width="16" height="16" viewBox="0 0 24 24" fill="' . esc_attr( $t['palette']['accent'] ) . '"><path d="M12 0l2.6 9.4L24 12l-9.4 2.6L12 24l-2.6-9.4L0 12l9.4-2.6z"/></svg>'
					. '<span style="flex:1;height:1px;background:linear-gradient(270deg,transparent,' . esc_attr( $t['palette']['border'] ) . ')"></span>'
					. '</div>';
				return array(
					ning_mcp_t_container( array( 'content_width' => 'full', 'padding' => ning_mcp_box( 24, 0, 24, 0 ) ), array(
						ning_mcp_t_widget( 'html', array( 'html' => $html ) ),
					) ),
				);
			},
		);

		$defs['gallery-strip'] = array(
			'title'       => 'Gallery Strip',
			'description' => 'Responsive image grid (works with external URLs, token-styled hover).',
			'params'      => array(
				'images' => array( 'type' => 'string', 'default' => 'https://placehold.co/400x400/FDF6EE/A67C52?text=1|https://placehold.co/400x400/FDF6EE/A67C52?text=2|https://placehold.co/400x400/FDF6EE/A67C52?text=3|https://placehold.co/400x400/FDF6EE/A67C52?text=4' ),
			),
			'build'       => function ( $p, $t ) {
				$urls = array_values( array_filter( array_map( 'trim', explode( '|', $p['images'] ) ) ) );
				$is_default_placehold = false;
				if ( ! empty( $urls ) ) {
					$is_default_placehold = true;
					foreach ( $urls as $u ) { if ( strpos( $u, 'placehold.co' ) === false ) { $is_default_placehold = false; break; } }
				}
				if ( empty( $urls ) || $is_default_placehold ) {
					if ( function_exists( 'ning_mcp_pexels_fallback_ids' ) ) {
						$urls = ning_mcp_pexels_fallback_ids( 'handmade gallery', 4, 'https://placehold.co/400x400/FDF6EE/A67C52?text=Gallery' );
					} else {
						$urls = array( 'https://placehold.co/400x400/FDF6EE/A67C52?text=Gallery' );
					}
				}
				$imgs = '';
				foreach ( $urls as $u ) {
					$imgs .= '<img loading="lazy" src="' . esc_url( $u ) . '" alt="">';
				}
				$html = '<div class="wpmp-gallery" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:' . esc_attr( $t['spacing']['gap'] ) . 'px">' . $imgs . '</div>'
					. '<style>.wpmp-gallery img{width:100%;aspect-ratio:1;object-fit:cover;display:block;border-radius:' . esc_attr( $t['radius']['card'] ) . 'px;transition:transform .3s ease}.wpmp-gallery img:hover{transform:scale(1.02)}</style>';
				return array(
					ning_mcp_t_container( ning_mcp_section_base( $t ), array(
						ning_mcp_t_widget( 'html', array( 'html' => $html ) ),
					) ),
				);
			},
		);

		$defs['product-cards'] = array(
			'title'       => 'Product Cards',
			'description' => 'Dynamic product grid — JS fetches WooCommerce Store API at page load (realtime data).',
			'params'      => array(
				'count' => array( 'type' => 'integer', 'default' => 4 ),
			),
			'build'       => function ( $p, $t ) {
				$count = max( 1, min( 8, (int) $p['count'] ) );
				$tpl   = <<<'WPMPTPL'
<div class="wpmp-products" data-count="{COUNT}" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;font-family:{BODYFONT}">
<div style="grid-column:1/-1;text-align:center;color:{MUTED};padding:40px 0">Loading products…</div>
</div>
<style>
.wpmp-products .wpmp-card{background:#fff;border:1px solid {BORDER};border-radius:{RADIUS}px;overflow:hidden;display:flex;flex-direction:column;text-decoration:none;color:inherit;transition:transform .3s ease,box-shadow .3s ease}
.wpmp-products .wpmp-card:hover{transform:translateY(-4px);box-shadow:{SHADOW}}
.wpmp-products .wpmp-card img{width:100%;aspect-ratio:1;object-fit:cover;display:block}
.wpmp-products .wpmp-body{padding:14px}
.wpmp-products h3{margin:0 0 6px;font-family:{HEADFONT};font-size:17px;color:{TEXT}}
.wpmp-price{color:{PRIMARY};font-weight:600}
.wpmp-placeholder{background:{BG};display:flex;align-items:center;justify-content:center;aspect-ratio:1;color:{MUTED};font-size:13px}
.wpmp-empty{grid-column:1/-1;text-align:center;color:{MUTED};padding:30px 0}
</style>
<script>
(function(){
var root=document.querySelector('.wpmp-products');if(!root)return;
var n=parseInt(root.getAttribute('data-count')||'4',10);
var url='{HOME}/wp-json/wc/store/v1/products?per_page='+n;
fetch(url).then(function(r){return r.ok?r.json():[]}).then(function(items){
if(!items||!items.length){root.innerHTML='<div class="wpmp-empty">No products yet.</div>';return}
root.innerHTML='';
items.forEach(function(p){
var img=p.images&&p.images[0]?p.images[0].src:'';
var price=p.prices&&p.prices.price?(parseInt(p.prices.price,10)/Math.pow(10,p.prices.currency_decimals||2)).toFixed(2):null;
var cur=p.prices&&p.prices.currency_symbol?p.prices.currency_symbol:'$';
var card=document.createElement('a');card.className='wpmp-card';card.href=p.permalink||'#';
var im=document.createElement('img');im.loading='lazy';im.alt=p.name||'';im.src=img||'{PH}';
card.appendChild(im);
var body=document.createElement('div');body.className='wpmp-body';
var h=document.createElement('h3');h.textContent=p.name||'';
var pr=document.createElement('span');pr.className='wpmp-price';pr.textContent=null===price?'':cur+price;
body.appendChild(h);body.appendChild(pr);card.appendChild(body);
root.appendChild(card);
});
}).catch(function(){root.innerHTML='<div class="wpmp-empty">Could not load products.</div>'});
})();
</script>
WPMPTPL;
				$ph_url = function_exists( 'ning_mcp_pexels_fallback_url' ) ? ning_mcp_pexels_fallback_url( 'handmade product', 'https://placehold.co/600x600/FDF6EE/A67C52?text=Product' ) : 'https://placehold.co/600x600/FDF6EE/A67C52?text=Product';
				$html = strtr( $tpl, array(
					'{COUNT}'    => (string) $count,
					'{HOME}'     => esc_url( untrailingslashit( home_url() ) ),
					'{PH}'       => $ph_url,
					'{PRIMARY}'  => esc_attr( $t['palette']['primary'] ),
					'{ACCENT}'   => esc_attr( $t['palette']['accent'] ),
					'{BG}'       => esc_attr( $t['palette']['bg'] ),
					'{TEXT}'     => esc_attr( $t['palette']['text'] ),
					'{MUTED}'    => esc_attr( $t['palette']['muted'] ),
					'{BORDER}'   => esc_attr( $t['palette']['border'] ),
					'{RADIUS}'   => esc_attr( $t['radius']['card'] ),
					'{SHADOW}'   => esc_attr( $t['shadow']['card'] ),
					'{BODYFONT}' => esc_attr( $t['typography']['body_font'] ),
					'{HEADFONT}' => esc_attr( $t['typography']['heading_font'] ),
				) );
				return array(
					ning_mcp_t_container( ning_mcp_section_base( $t ), array(
						ning_mcp_t_widget( 'html', array( 'html' => $html ) ),
					) ),
				);
			},
		);

		return $defs;
	}

	function ning_mcp_build_patterns( $patterns, $palette_overrides, $param_overrides ) {
		$defs   = ning_mcp_pattern_definitions();
		$tokens = ning_mcp_design_tokens();
		if ( is_array( $palette_overrides ) ) {
			foreach ( $palette_overrides as $k => $v ) {
				if ( array_key_exists( $k, $tokens['palette'] ) ) {
					$tokens['palette'][ $k ] = sanitize_hex_color( $v );
				}
			}
		}
		if ( 'all' === $patterns ) {
			$patterns = array_keys( $defs );
		}
		$created = array();
		foreach ( (array) $patterns as $id ) {
			if ( ! isset( $defs[ $id ] ) ) {
				$created[] = array( 'id' => $id, 'error' => 'Unknown pattern id.' );
				continue;
			}
			$def    = $defs[ $id ];
			$params = array();
			foreach ( $def['params'] as $pk => $pv ) {
				$params[ $pk ] = isset( $pv['default'] ) ? $pv['default'] : '';
			}
			if ( isset( $param_overrides[ $id ] ) && is_array( $param_overrides[ $id ] ) ) {
				$params = array_merge( $params, $param_overrides[ $id ] );
			}
			$data = ning_mcp_tree_to_elementor_data( $def['build']( $params, $tokens ) );

			$existing = get_posts( array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'any',
				'title'          => 'Pattern: ' . $def['title'],
				'posts_per_page' => 1,
			) );
			if ( ! empty( $existing ) ) {
				$post_id = (int) $existing[0]->ID;
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
			} else {
				$post_id = (int) wp_insert_post( array(
					'post_type'   => 'elementor_library',
					'post_status' => 'publish',
					'post_title'  => 'Pattern: ' . $def['title'],
				) );
			}
			if ( ! $post_id ) {
				$created[] = array( 'id' => $id, 'error' => 'Could not create template post.' );
				continue;
			}
			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
			update_post_meta( $post_id, '_elementor_template_type', 'section' );
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
			$created[] = array(
				'id'          => $id,
				'title'       => $def['title'],
				'template_id' => $post_id,
				'shortcode'   => '[elementor-template id="' . $post_id . '"]',
				'edit_url'    => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
				'preview_url' => home_url( '/?elementor_library=pattern-' . sanitize_title( $def['title'] ) . '&p=' . $post_id . '&preview=true' ),
			);
		}
		return $created;
	}
