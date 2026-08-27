<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Ning_Custom_Base extends \Elementor\Widget_Base {

	public function get_categories() {
		return array( 'custom-elements' );
	}

	public function has_widget_inner_wrapper(): bool {
		return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	protected function resolve_real_id( $logical_id ) {
		global $wpdb;
		if ( ! function_exists( 'ning_mcp_pattern_definitions' ) ) {
			return 0;
		}
		$defs = ning_mcp_pattern_definitions();
		if ( ! isset( $defs[ $logical_id ] ) ) {
			return 0;
		}
		$title = 'Pattern: ' . $defs[ $logical_id ]['title'];
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'elementor_library' LIMIT 1", $title ) );
		if ( $id ) {
			return (int) $id;
		}
		$posts = get_posts( array(
			'post_type'      => 'elementor_library',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
		) );
		return ! empty( $posts ) ? (int) $posts[0]->ID : 0;
	}

	protected function render_pattern( $logical_id ) {
		$real_id = $this->resolve_real_id( $logical_id );
		if ( ! $real_id ) {
			echo '<div style="padding:24px;border:1px dashed #E8DDD0;border-radius:12px;color:#8A7A6A;text-align:center;">Pattern not found: ' . esc_html( $logical_id ) . '. Run build-pattern-library.</div>';
			return;
		}
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
			$frontend = \Elementor\Plugin::$instance->frontend;
			if ( method_exists( $frontend, 'get_builder_content_for_display' ) ) {
				echo $frontend->get_builder_content_for_display( $real_id ); // phpcs:ignore
				return;
			}
			if ( method_exists( $frontend, 'get_builder_content' ) ) {
				echo $frontend->get_builder_content( $real_id ); // phpcs:ignore
				return;
			}
		}
		echo '';
	}
}

class Ning_Banner_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-banner'; }
	public function get_title() { return esc_html__( 'Banner', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-banner'; }
	public function get_keywords() { return array( 'banner', 'hero', 'ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Layout', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'layout', array(
			'label'   => esc_html__( 'Style', 'wp-mcp-abilities' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => array(
				'hero-classic' => esc_html__( 'Hero Classic', 'wp-mcp-abilities' ),
				'hero-split'   => esc_html__( 'Hero Split', 'wp-mcp-abilities' ),
				'hero-minimal' => esc_html__( 'Hero Minimal', 'wp-mcp-abilities' ),
			),
			'default' => 'hero-classic',
		) );
		$this->end_controls_section();
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		$logical = isset( $s['layout'] ) ? sanitize_key( $s['layout'] ) : 'hero-classic';
		if ( ! in_array( $logical, array( 'hero-classic','hero-split','hero-minimal' ), true ) ) { $logical = 'hero-classic'; }
		$this->render_pattern( $logical );
	}
}

class Ning_Features_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-features'; }
	public function get_title() { return esc_html__( 'Features', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-apps'; }
	public function get_keywords() { return array( 'features','grid','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview Features Grid.', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'features-grid' ); }
}

class Ning_Cta_Banner_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-cta-banner'; }
	public function get_title() { return esc_html__( 'CTA Banner', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-call-to-action'; }
	public function get_keywords() { return array( 'cta','banner','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview CTA Banner.', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'banner-cta' ); }
}

class Ning_Testimonials_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-testimonials'; }
	public function get_title() { return esc_html__( 'Testimonials', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-testimonial'; }
	public function get_keywords() { return array( 'testimonials','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview Testimonials.', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'testimonials' ); }
}

class Ning_Stats_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-stats'; }
	public function get_title() { return esc_html__( 'Stats', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-counter'; }
	public function get_keywords() { return array( 'stats','counter','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview Stats Band.', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'stats-band' ); }
}

class Ning_Newsletter_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-newsletter'; }
	public function get_title() { return esc_html__( 'Newsletter', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-mail'; }
	public function get_keywords() { return array( 'newsletter','subscribe','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview Newsletter.', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'newsletter' ); }
}

class Ning_Marquee_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-marquee'; }
	public function get_title() { return esc_html__( 'Marquee', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-slider-push'; }
	public function get_keywords() { return array( 'marquee','ticker','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview Marquee.', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'marquee' ); }
}

class Ning_Divider_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-divider'; }
	public function get_title() { return esc_html__( 'Divider', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-divider'; }
	public function get_keywords() { return array( 'divider','separator','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview Filament Divider.', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'filament-divider' ); }
}

class Ning_Gallery_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-gallery'; }
	public function get_title() { return esc_html__( 'Gallery', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_keywords() { return array( 'gallery','images','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview Gallery Strip.', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'gallery-strip' ); }
}

class Ning_Product_Cards_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-product-cards'; }
	public function get_title() { return esc_html__( 'Product Cards', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-products'; }
	public function get_keywords() { return array( 'products','cards','ning' ); }
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Content', 'wp-mcp-abilities' ) ) );
		$this->add_control( 'note', array( 'type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'Drag to preview Product Cards (live Store API).', 'wp-mcp-abilities' ) ) );
		$this->end_controls_section();
	}
	protected function render() { $this->render_pattern( 'product-cards' ); }
}
