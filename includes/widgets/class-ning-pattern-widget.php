<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ning_Pattern_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ning-pattern';
	}

	public function get_title() {
		return esc_html__( 'Pattern', 'wp-mcp-abilities' );
	}

	public function get_icon() {
		return 'eicon-library-open';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'pattern', 'library', 'ning' );
	}

	public function has_widget_inner_wrapper(): bool {
		return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	public function show_in_panel() {
		return false;
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => esc_html__( 'Pattern', 'wp-mcp-abilities' ) )
		);

		$options = array();
		if ( function_exists( 'ning_mcp_pattern_definitions' ) ) {
			foreach ( ning_mcp_pattern_definitions() as $pid => $def ) {
				$options[ $pid ] = $def['title'] . ' — ' . $def['description'];
			}
		}
		if ( empty( $options ) ) {
			$options = array( 'hero-classic' => 'Hero Classic' );
		}

		$this->add_control(
			'pattern_id',
			array(
				'label'   => esc_html__( 'Pattern', 'wp-mcp-abilities' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
				'default' => 'hero-classic',
			)
		);

		$this->end_controls_section();
	}

	private function resolve_real_id( $logical_id ) {
		if ( ! function_exists( 'ning_mcp_pattern_definitions' ) ) {
			return 0;
		}
		$defs = ning_mcp_pattern_definitions();
		if ( ! isset( $defs[ $logical_id ] ) ) {
			return 0;
		}
		$title = 'Pattern: ' . $defs[ $logical_id ]['title'];
		$posts = get_posts( array(
			'post_type'      => 'elementor_library',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
		) );
		return ! empty( $posts ) ? (int) $posts[0]->ID : 0;
	}

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$logical_id = isset( $settings['pattern_id'] ) ? sanitize_key( $settings['pattern_id'] ) : '';
		$real_id    = $this->resolve_real_id( $logical_id );
		if ( ! $real_id ) {
			echo '<div class="ning-pattern-empty" style="padding:24px;border:1px dashed #E8DDD0;border-radius:12px;color:#8A7A6A;text-align:center;">Pattern not found. Run build-pattern-library.</div>';
			return;
		}
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
			$frontend = \Elementor\Plugin::$instance->frontend;
			if ( method_exists( $frontend, 'get_builder_content_for_display' ) ) {
				echo $frontend->get_builder_content_for_display( $real_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}
			if ( method_exists( $frontend, 'get_builder_content' ) ) {
				echo $frontend->get_builder_content( $real_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				return;
			}
		}
		echo '';
	}

	protected function content_template() {}
}
