<?php
/**
 * ChattyPage_Elementor_Widget — places a ChattyPage section inside any Elementor layout.
 * Deliberately a thin WRAPPER over the shared renderer (the section stays self-contained
 * ChattyPage HTML): we never translate designs into native Elementor widgets, so the design
 * survives 1:1 and Elementor upgrades can't break it. Loaded only when Elementor is active
 * (see the elementor/widgets/register hook in chattypage.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ChattyPage_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'chattypage_section';
	}

	public function get_title() {
		return __( 'ChattyPage Section', 'chattypage' );
	}

	public function get_icon() {
		return 'eicon-section';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'chattypage', 'section', 'ai', 'design' );
	}

	protected function register_controls() {
		$options  = array( '' => __( 'Choose a section', 'chattypage' ) );
		$sections = ChattyPage_Api_Client::sections();
		if ( ! is_wp_error( $sections ) ) {
			foreach ( $sections as $section ) {
				$options[ $section['id'] ] = $section['name'];
			}
		}

		$this->start_controls_section( 'chattypage_content', array(
			'label' => __( 'ChattyPage', 'chattypage' ),
		) );

		$this->add_control( 'section_id', array(
			'label'   => __( 'Section', 'chattypage' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => $options,
			'default' => '',
		) );

		$this->add_control( 'edit_hint', array(
			'type' => \Elementor\Controls_Manager::RAW_HTML,
			'raw'  => '<a href="' . esc_url( CHATTYPAGE_APP_BASE ) . '" target="_blank" rel="noopener">'
				. __( 'Design and edit sections in ChattyPage', 'chattypage' ) . '</a>',
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		// Renderer output is the site owner's own ChattyPage design (same trust model as a
		// theme file) — printed as-is on purpose; see ChattyPage_Renderer.
		echo ChattyPage_Renderer::render( isset( $settings['section_id'] ) ? $settings['section_id'] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
