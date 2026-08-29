<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Ning_Custom_Base extends \Elementor\Widget_Base {
	public function get_categories() { return array( 'custom-elements' ); }
	public function has_widget_inner_wrapper(): bool {
		return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}
	protected function tokens() {
		return function_exists('ning_mcp_design_tokens') ? ning_mcp_design_tokens() : array(
			'palette'=>array('primary'=>'#A67C52','accent'=>'#D9A679','bg'=>'#FDF6EE','text'=>'#3B3B3B','muted'=>'#8A7A6A','border'=>'#E8DDD0'),
			'typography'=>array('heading_font'=>'Playfair Display, serif','body_font'=>'Inter, sans-serif'),
			'spacing'=>array('section'=>'72','gap'=>'24'),
			'radius'=>array('card'=>'18','button'=>'999'),
			'shadow'=>array('card'=>'0 4px 20px rgba(166,124,82,.12)'),
		);
	}
}

class Ning_Banner_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-banner'; }
	public function get_title() { return esc_html__( 'Banner', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-banner'; }
	public function get_keywords() { return array( 'banner','hero','ning' ); }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('layout', array(
			'label'=>esc_html__('Style','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::SELECT,
			'options'=>array('hero-classic'=>esc_html__('Hero Classic','wp-mcp-abilities'),'hero-split'=>esc_html__('Hero Split','wp-mcp-abilities'),'hero-minimal'=>esc_html__('Hero Minimal','wp-mcp-abilities')),
			'default'=>'hero-classic',
		));
		$this->add_control('title', array('label'=>esc_html__('Title','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Handmade with Love','wp-mcp-abilities'),'label_block'=>true,'dynamic'=>array('active'=>true)));
		$this->add_control('subtitle', array('label'=>esc_html__('Subtitle','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXTAREA,'rows'=>2,'default'=>esc_html__('Thoughtfully crafted pieces for a warm home.','wp-mcp-abilities'),'dynamic'=>array('active'=>true)));
		$this->add_control('cta_text', array('label'=>esc_html__('Button Text','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Shop New In','wp-mcp-abilities')));
		$this->add_control('cta_url', array('label'=>esc_html__('Button Link','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::URL,'default'=>array('url'=>'#'),'placeholder'=>'https://','dynamic'=>array('active'=>true)));
		$this->add_control('image', array(
			'label'=>esc_html__('Image','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::MEDIA,
			'default'=>array('url'=>\Elementor\Utils::get_placeholder_image_src()),
			'media_types'=>array('image'),'dynamic'=>array('active'=>true),
			'condition'=>array('layout'=>array('hero-classic','hero-split')),
		));
		$this->end_controls_section();
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		$t = $this->tokens();
		$layout = $s['layout'] ?? 'hero-classic';
		$title = $s['title'] ?? 'Handmade with Love';
		$subtitle = $s['subtitle'] ?? '';
		$cta_text = $s['cta_text'] ?? 'Shop New In';
		$cta_url = isset($s['cta_url']['url']) ? $s['cta_url']['url'] : '#';
		$img = isset($s['image']['url']) && $s['image']['url'] ? $s['image']['url'] : ( function_exists( 'ning_mcp_pexels_fallback_url' ) ? ning_mcp_pexels_fallback_url( $title ?: 'handmade crochet', 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image' ) : 'https://placehold.co/600x720/FDF6EE/A67C52?text=Hero+Image' );
		$pad = $t['spacing']['section'] . 'px';
		$gap = $t['spacing']['gap'] . 'px';
		if ('hero-minimal' === $layout) {
			echo '<div style="padding:96px 24px;text-align:center;max-width:900px;margin:0 auto;">';
			echo '<h1 style="font-family:'.esc_attr($t['typography']['heading_font']).';font-size:46px;line-height:1.15;color:'.esc_attr($t['palette']['text']).';margin:0 0 16px;">'.esc_html($title).'</h1>';
			echo '<p style="font-family:'.esc_attr($t['typography']['body_font']).';color:'.esc_attr($t['palette']['muted']).';font-size:17px;line-height:1.6;margin:0 0 24px;">'.esc_html($subtitle).'</p>';
			echo '<a href="'.esc_url($cta_url).'" style="display:inline-block;padding:14px 28px;background:'.esc_attr($t['palette']['primary']).';color:#fff;border-radius:'.esc_attr($t['radius']['button']).'px;text-decoration:none;font-weight:600;">'.esc_html($cta_text).'</a>';
			echo '</div>';
			return;
		}
		$is_split = 'hero-split' === $layout;
		echo '<div style="display:flex;flex-wrap:wrap;gap:'.$gap.';align-items:center;max-width:1200px;margin:0 auto;padding:'.$pad.' 24px;">';
		$left = '<div style="flex:1;min-width:280px;">'
			.'<h1 style="font-family:'.esc_attr($t['typography']['heading_font']).';font-size:46px;line-height:1.15;color:'.esc_attr($t['palette']['text']).';margin:0 0 16px;">'.esc_html($title).'</h1>'
			.'<p style="font-family:'.esc_attr($t['typography']['body_font']).';color:'.esc_attr($t['palette']['muted']).';font-size:17px;line-height:1.6;margin:0 0 20px;">'.esc_html($subtitle).'</p>'
			.'<a href="'.esc_url($cta_url).'" style="display:inline-block;padding:14px 28px;background:'.esc_attr($t['palette']['primary']).';color:#fff;border-radius:'.esc_attr($t['radius']['button']).'px;text-decoration:none;font-weight:600;">'.esc_html($cta_text).'</a></div>';
		$right = '<div style="flex:1;min-width:280px;"><img src="'.esc_url($img).'" alt="" style="width:100%;height:auto;display:block;border-radius:12px;"></div>';
		if ($is_split) { echo $right . $left; } else { echo $left . $right; }
		echo '</div>';
	}
}

class Ning_Features_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-features'; }
	public function get_title() { return esc_html__( 'Features', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-apps'; }
	public function get_keywords() { return array( 'features','grid','ning' ); }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('title', array('label'=>esc_html__('Section Title','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Why Choose Us','wp-mcp-abilities'),'label_block'=>true,'dynamic'=>array('active'=>true)));
		$repeater = new \Elementor\Repeater();
		$repeater->add_control('title_text', array('label'=>esc_html__('Title','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Feature','wp-mcp-abilities'),'label_block'=>true));
		$repeater->add_control('description_text', array('label'=>esc_html__('Description','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXTAREA,'default'=>esc_html__('Thoughtfully designed with care and intention.','wp-mcp-abilities'),'rows'=>2));
		$repeater->add_control('selected_icon', array('label'=>esc_html__('Icon','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::ICONS,'default'=>array('value'=>'fas fa-heart','library'=>'fa-solid')));
		$this->add_control('features', array(
			'label'=>esc_html__('Features','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::REPEATER,
			'fields'=>$repeater->get_controls(),'default'=>array(
				array('title_text'=>esc_html__('Quality Craft','wp-mcp-abilities'),'description_text'=>esc_html__('Thoughtfully designed with care and intention.','wp-mcp-abilities'),'selected_icon'=>array('value'=>'fas fa-heart','library'=>'fa-solid')),
				array('title_text'=>esc_html__('Natural Materials','wp-mcp-abilities'),'description_text'=>esc_html__('Thoughtfully designed with care and intention.','wp-mcp-abilities'),'selected_icon'=>array('value'=>'fas fa-leaf','library'=>'fa-solid')),
				array('title_text'=>esc_html__('Fair Trade','wp-mcp-abilities'),'description_text'=>esc_html__('Thoughtfully designed with care and intention.','wp-mcp-abilities'),'selected_icon'=>array('value'=>'fas fa-handshake','library'=>'fa-solid')),
				array('title_text'=>esc_html__('Made to Last','wp-mcp-abilities'),'description_text'=>esc_html__('Thoughtfully designed with care and intention.','wp-mcp-abilities'),'selected_icon'=>array('value'=>'fas fa-certificate','library'=>'fa-solid')),
			),
			'title_field'=>'{{{ title_text }}}',
		));
		$this->end_controls_section();
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		$t = $this->tokens();
		$title = $s['title'] ?? 'Why Choose Us';
		$items = $s['features'] ?? array();
		echo '<div style="max-width:1200px;margin:0 auto;padding:'.esc_attr($t['spacing']['section']).'px 24px;text-align:center;">';
		echo '<h2 style="font-family:'.esc_attr($t['typography']['heading_font']).';font-size:32px;color:'.esc_attr($t['palette']['text']).';margin:0 0 32px;">'.esc_html($title).'</h2>';
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;">';
		foreach ($items as $it) {
			$tt = $it['title_text'] ?? '';
			$dd = $it['description_text'] ?? '';
			$icon = '';
			if (!empty($it['selected_icon']['value'])) {
				ob_start(); \Elementor\Icons_Manager::render_icon($it['selected_icon'], array('aria-hidden'=>'true')); $icon = ob_get_clean();
			}
			echo '<div style="background:#fff;border:1px solid '.esc_attr($t['palette']['border']).';border-radius:'.esc_attr($t['radius']['card']).'px;padding:24px;box-shadow:'.esc_attr($t['shadow']['card']).';">';
			if ($icon) echo '<div style="width:56px;height:56px;border-radius:999px;background:'.esc_attr($t['palette']['primary']).';color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:22px;">'.$icon.'</div>';
			echo '<div style="font-family:'.esc_attr($t['typography']['heading_font']).';font-weight:600;color:'.esc_attr($t['palette']['text']).';margin:0 0 8px;">'.esc_html($tt).'</div>';
			echo '<div style="font-family:'.esc_attr($t['typography']['body_font']).';color:'.esc_attr($t['palette']['muted']).';font-size:14px;line-height:1.5;">'.esc_html($dd).'</div>';
			echo '</div>';
		}
		echo '</div></div>';
	}
}

class Ning_Cta_Banner_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-cta-banner'; }
	public function get_title() { return esc_html__( 'CTA Banner', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-call-to-action'; }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('title', array('label'=>esc_html__('Title','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Join Our Community','wp-mcp-abilities'),'label_block'=>true,'dynamic'=>array('active'=>true)));
		$this->add_control('subtitle', array('label'=>esc_html__('Subtitle','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXTAREA,'rows'=>2,'default'=>esc_html__('Be the first to know about new arrivals.','wp-mcp-abilities')));
		$this->add_control('cta_text', array('label'=>esc_html__('Button Text','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Subscribe','wp-mcp-abilities')));
		$this->add_control('cta_url', array('label'=>esc_html__('Button Link','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::URL,'default'=>array('url'=>'#')));
		$this->add_control('background_image', array('label'=>esc_html__('Background Image','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::MEDIA,'default'=>array('url'=>''),'media_types'=>array('image'),'dynamic'=>array('active'=>true)));
		$this->end_controls_section();
	}
	protected function render() {
		$s=$this->get_settings_for_display(); $t=$this->tokens();
		$title=$s['title']??'Join Our Community'; $subtitle=$s['subtitle']??''; $cta_text=$s['cta_text']??'Subscribe'; $cta_url=$s['cta_url']['url']??'#';
		$bg_url = isset($s['background_image']['url']) ? $s['background_image']['url'] : '';
		$bg_style = $bg_url ? 'background:url('.esc_url($bg_url).') center/cover;' : 'background:'.esc_attr($t['palette']['primary']).';';
		echo '<div style="max-width:1200px;margin:0 auto;padding:64px 24px;'.$bg_style.'border-radius:24px;text-align:center;color:#fff;position:relative;overflow:hidden;">';
		if ($bg_url) echo '<div style="position:absolute;inset:0;background:rgba(0,0,0,.35);"></div><div style="position:relative;">';
		echo '<h2 style="font-family:'.esc_attr($t['typography']['heading_font']).';font-size:32px;margin:0 0 12px;color:#fff;">'.esc_html($title).'</h2>';
		echo '<p style="font-family:'.esc_attr($t['typography']['body_font']).';color:rgba(255,255,255,.92);margin:0 0 20px;">'.esc_html($subtitle).'</p>';
		echo '<a href="'.esc_url($cta_url).'" style="display:inline-block;padding:14px 28px;background:#fff;color:'.esc_attr($t['palette']['primary']).';border-radius:999px;text-decoration:none;font-weight:600;">'.esc_html($cta_text).'</a>';
		if ($bg_url) echo '</div>';
		echo '</div>';
	}
}

class Ning_Testimonials_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-testimonials'; }
	public function get_title() { return esc_html__( 'Testimonials', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-testimonial'; }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('title', array('label'=>esc_html__('Section Title','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Kind Words','wp-mcp-abilities'),'label_block'=>true));
		$repeater = new \Elementor\Repeater();
		$repeater->add_control('testimonial_name', array('label'=>esc_html__('Name','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Anna K.','wp-mcp-abilities')));
		$repeater->add_control('testimonial_job', array('label'=>esc_html__('Job','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Happy Customer','wp-mcp-abilities')));
		$repeater->add_control('testimonial_content', array('label'=>esc_html__('Content','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXTAREA,'default'=>esc_html__('Absolutely love my handmade piece — you can feel the care in every stitch.','wp-mcp-abilities'),'rows'=>3));
		$repeater->add_control('testimonial_image', array('label'=>esc_html__('Avatar','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::MEDIA,'default'=>array('url'=>\Elementor\Utils::get_placeholder_image_src())));
		$repeater->add_control('rating', array('label'=>esc_html__('Rating','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::NUMBER,'min'=>1,'max'=>5,'step'=>1,'default'=>5));
		$this->add_control('testimonials', array('label'=>esc_html__('Testimonials','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::REPEATER,'fields'=>$repeater->get_controls(),'default'=>array(
			array('testimonial_name'=>'Anna K.','testimonial_job'=>'Happy Customer','testimonial_content'=>'Absolutely love my handmade piece — you can feel the care in every stitch.','rating'=>5),
			array('testimonial_name'=>'Tom B.','testimonial_job'=>'Collector','testimonial_content'=>'Beautiful quality and quick delivery. Will definitely order again.','rating'=>5),
			array('testimonial_name'=>'Mia L.','testimonial_job'=>'Gift Buyer','testimonial_content'=>'The perfect gift. So unique and well made.','rating'=>5),
		),'title_field'=>'{{{ testimonial_name }}}'));
		$this->end_controls_section();
	}
	protected function render() {
		$s=$this->get_settings_for_display(); $t=$this->tokens();
		$title=$s['title']??'Kind Words'; $items=$s['testimonials']??array();
		echo '<div style="max-width:1200px;margin:0 auto;padding:'.esc_attr($t['spacing']['section']).'px 24px;text-align:center;">';
		echo '<h2 style="font-family:'.esc_attr($t['typography']['heading_font']).';font-size:32px;color:'.esc_attr($t['palette']['text']).';margin:0 0 32px;">'.esc_html($title).'</h2>';
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;">';
		foreach ($items as $it) {
			$name=$it['testimonial_name']??''; $job=$it['testimonial_job']??''; $content=$it['testimonial_content']??''; $rating=intval($it['rating']??5);
			$img = $it['testimonial_image']['url'] ?? '';
			echo '<div style="background:#fff;border:1px solid '.esc_attr($t['palette']['border']).';border-radius:'.esc_attr($t['radius']['card']).'px;padding:24px;text-align:left;">';
			echo '<div style="color:'.esc_attr($t['palette']['accent']).';margin:0 0 12px;">'.str_repeat('★', max(1,min(5,$rating))).str_repeat('☆', 5-max(1,min(5,$rating))).'</div>';
			echo '<p style="font-family:'.esc_attr($t['typography']['body_font']).';color:'.esc_attr($t['palette']['text']).';margin:0 0 12px;line-height:1.5;">'.esc_html($content).'</p>';
			echo '<div style="display:flex;gap:12px;align-items:center;">';
			if ($img) echo '<img src="'.esc_url($img).'" alt="" style="width:40px;height:40px;border-radius:999px;object-fit:cover;">';
			echo '<div><div style="font-weight:600;color:'.esc_attr($t['palette']['text']).';">'.esc_html($name).'</div><div style="font-size:13px;color:'.esc_attr($t['palette']['muted']).';">'.esc_html($job).'</div></div></div>';
			echo '</div>';
		}
		echo '</div></div>';
	}
}

class Ning_Stats_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-stats'; }
	public function get_title() { return esc_html__( 'Stats', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-counter'; }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$repeater = new \Elementor\Repeater();
		$repeater->add_control('number', array('label'=>esc_html__('Number','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::NUMBER,'default'=>100));
		$repeater->add_control('suffix', array('label'=>esc_html__('Suffix','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>'+'));
		$repeater->add_control('label', array('label'=>esc_html__('Label','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Happy Customers','wp-mcp-abilities'),'label_block'=>true));
		$this->add_control('stats', array('label'=>esc_html__('Stats','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::REPEATER,'fields'=>$repeater->get_controls(),'default'=>array(
			array('number'=>1200,'suffix'=>'+','label'=>'Happy Customers'),
			array('number'=>50,'suffix'=>'+','label'=>'Artisans'),
			array('number'=>30,'suffix'=>'+','label'=>'Countries'),
			array('number'=>15,'suffix'=>'+','label'=>'Years'),
		),'title_field'=>'{{{ label }}}: {{{ number }}}{{{ suffix }}}'));
		$this->end_controls_section();
	}
	protected function render() {
		$s=$this->get_settings_for_display(); $t=$this->tokens();
		$items=$s['stats']??array();
		echo '<div style="max-width:1200px;margin:0 auto;padding:56px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;text-align:center;">';
		foreach ($items as $it) {
			$num = $it['number'] ?? 0; $suffix=$it['suffix']??'+'; $label=$it['label']??'';
			echo '<div><div style="font-family:'.esc_attr($t['typography']['heading_font']).';font-size:36px;font-weight:700;color:'.esc_attr($t['palette']['primary']).';">'.esc_html($num).esc_html($suffix).'</div><div style="font-family:'.esc_attr($t['typography']['body_font']).';color:'.esc_attr($t['palette']['muted']).';">'.esc_html($label).'</div></div>';
		}
		echo '</div>';
	}
}

class Ning_Newsletter_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-newsletter'; }
	public function get_title() { return esc_html__( 'Newsletter', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-mail'; }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('title', array('label'=>esc_html__('Title','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Stay in the Loop','wp-mcp-abilities'),'label_block'=>true));
		$this->add_control('subtitle', array('label'=>esc_html__('Subtitle','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXTAREA,'rows'=>2,'default'=>esc_html__('Sign up for our newsletter.','wp-mcp-abilities')));
		$this->end_controls_section();
	}
	protected function render() {
		$s=$this->get_settings_for_display(); $t=$this->tokens();
		$title=$s['title']??'Stay in the Loop'; $subtitle=$s['subtitle']??'Sign up for our newsletter.';
		echo '<div style="max-width:900px;margin:0 auto;padding:'.esc_attr($t['spacing']['section']).'px 24px;text-align:center;">';
		echo '<h2 style="font-family:'.esc_attr($t['typography']['heading_font']).';font-size:32px;color:'.esc_attr($t['palette']['text']).';margin:0 0 12px;">'.esc_html($title).'</h2>';
		echo '<p style="font-family:'.esc_attr($t['typography']['body_font']).';color:'.esc_attr($t['palette']['muted']).';margin:0 0 20px;">'.esc_html($subtitle).'</p>';
		echo '<form action="#" method="post" style="display:flex;gap:10px;max-width:460px;margin:0 auto;flex-wrap:wrap;justify-content:center;">'
			.'<input type="email" required placeholder="Your email" style="flex:1;min-width:220px;padding:14px 18px;border:1px solid '.esc_attr($t['palette']['border']).';border-radius:'.esc_attr($t['radius']['card']).'px;font-family:'.esc_attr($t['typography']['body_font']).';font-size:15px;">'
			.'<button type="submit" style="padding:14px 26px;border:0;border-radius:'.esc_attr($t['radius']['button']).'px;background:'.esc_attr($t['palette']['primary']).';color:#fff;font-family:'.esc_attr($t['typography']['body_font']).';font-size:15px;font-weight:600;cursor:pointer">Subscribe</button></form>';
		echo '</div>';
	}
}

class Ning_Marquee_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-marquee'; }
	public function get_title() { return esc_html__( 'Marquee', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-slider-push'; }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('text', array('label'=>esc_html__('Text','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::TEXT,'default'=>esc_html__('Handmade with Love • Natural Materials • Fair Trade','wp-mcp-abilities'),'label_block'=>true));
		$this->end_controls_section();
	}
	protected function render() {
		$s=$this->get_settings_for_display(); $t=$this->tokens();
		$text=$s['text']??'Handmade with Love • Natural Materials • Fair Trade';
		$line = str_repeat(esc_html($text).' &nbsp;•&nbsp; ',3);
		echo '<div style="overflow:hidden;white-space:nowrap;background:'.esc_attr($t['palette']['primary']).';color:#fff;padding:14px 0;"><div style="display:inline-block;font-family:'.esc_attr($t['typography']['heading_font']).';font-size:18px;letter-spacing:.05em;animation:wpmp-marquee 18s linear infinite">'.$line.'</div></div><style>@keyframes wpmp-marquee{from{transform:translateX(0)}to{transform:translateX(-33.333%)}}</style>';
	}
}

class Ning_Divider_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-divider'; }
	public function get_title() { return esc_html__( 'Divider', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-divider'; }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('line_color', array('label'=>esc_html__('Line Color','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::COLOR,'default'=>'#E8DDD0'));
		$this->add_control('icon_color', array('label'=>esc_html__('Icon Color','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::COLOR,'default'=>'#D9A679'));
		$this->add_control('gap', array('label'=>esc_html__('Gap','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>array('px'),'range'=>array('px'=>array('min'=>0,'max'=>60)),'default'=>array('size'=>14,'unit'=>'px')));
		$this->add_control('max_width', array('label'=>esc_html__('Max Width','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>array('px','%'),'range'=>array('px'=>array('min'=>100,'max'=>800),'%'=>array('min'=>10,'max'=>100)),'default'=>array('size'=>420,'unit'=>'px')));
		$this->end_controls_section();
	}
	protected function render() {
		$s=$this->get_settings_for_display(); $t=$this->tokens();
		$line_color = $s['line_color'] ?? $t['palette']['border'];
		$icon_color = $s['icon_color'] ?? $t['palette']['accent'];
		$gap = isset($s['gap']['size']) ? $s['gap']['size'] . ($s['gap']['unit'] ?? 'px') : '14px';
		$max_width = isset($s['max_width']['size']) ? $s['max_width']['size'] . ($s['max_width']['unit'] ?? 'px') : '420px';
		echo '<div style="display:flex;align-items:center;gap:'.esc_attr($gap).';max-width:'.esc_attr($max_width).';margin:24px auto;"><span style="flex:1;height:1px;background:linear-gradient(90deg,transparent,'.esc_attr($line_color).')"></span><svg width="16" height="16" viewBox="0 0 24 24" fill="'.esc_attr($icon_color).'"><path d="M12 0l2.6 9.4L24 12l-9.4 2.6L12 24l-2.6-9.4L0 12l9.4-2.6z"/></svg><span style="flex:1;height:1px;background:linear-gradient(270deg,transparent,'.esc_attr($line_color).')"></span></div>';
	}
}

class Ning_Gallery_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-gallery'; }
	public function get_title() { return esc_html__( 'Gallery', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('images', array('label'=>esc_html__('Images','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::GALLERY,'default'=>array()));
		$this->end_controls_section();
	}
	protected function render() {
		$s=$this->get_settings_for_display(); $t=$this->tokens();
		$gallery = $s['images'] ?? array();
		if (empty($gallery)) {
			if (function_exists('ning_mcp_pexels_fallback_ids')) {
				$urls = ning_mcp_pexels_fallback_ids('handmade gallery', 4, 'https://placehold.co/400x400/FDF6EE/A67C52?text=Gallery');
				$gallery = array_map(function($u){ return array('url'=>$u); }, $urls);
			} else {
				$gallery = array(array('url'=>'https://placehold.co/400x400/FDF6EE/A67C52?text=1'),array('url'=>'https://placehold.co/400x400/FDF6EE/A67C52?text=2'),array('url'=>'https://placehold.co/400x400/FDF6EE/A67C52?text=3'),array('url'=>'https://placehold.co/400x400/FDF6EE/A67C52?text=4'));
			}
		}
		echo '<div style="max-width:1200px;margin:0 auto;padding:'.esc_attr($t['spacing']['section']).'px 24px;"><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:'.esc_attr($t['spacing']['gap']).'px;">';
		foreach ($gallery as $img) {
			$url = $img['url'] ?? '';
			if (!$url) continue;
			echo '<img src="'.esc_url($url).'" alt="" style="width:100%;aspect-ratio:1;object-fit:cover;display:block;border-radius:'.esc_attr($t['radius']['card']).'px;">';
		}
		echo '</div></div>';
	}
}

class Ning_Product_Cards_Widget extends Ning_Custom_Base {
	public function get_name() { return 'ning-product-cards'; }
	public function get_title() { return esc_html__( 'Product Cards', 'wp-mcp-abilities' ); }
	public function get_icon() { return 'eicon-products'; }
	protected function register_controls() {
		$this->start_controls_section('section_content', array('label'=>esc_html__('Content','wp-mcp-abilities')));
		$this->add_control('count', array('label'=>esc_html__('Products to show','wp-mcp-abilities'),'type'=>\Elementor\Controls_Manager::NUMBER,'min'=>1,'max'=>8,'step'=>1,'default'=>4));
		$this->end_controls_section();
	}
	protected function render() {
		$s=$this->get_settings_for_display(); $t=$this->tokens();
		$count = max(1,min(8,intval($s['count'] ?? 4)));
		$ph_url = function_exists('ning_mcp_pexels_fallback_url') ? ning_mcp_pexels_fallback_url('handmade product', 'https://placehold.co/600x600/FDF6EE/A67C52?text=Product') : 'https://placehold.co/600x600/FDF6EE/A67C52?text=Product';
		echo '<div class="wpmp-products" data-count="'.esc_attr($count).'" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;max-width:1200px;margin:0 auto;padding:'.esc_attr($t['spacing']['section']).'px 24px;font-family:'.esc_attr($t['typography']['body_font']).';"><div style="grid-column:1/-1;text-align:center;color:'.esc_attr($t['palette']['muted']).';padding:40px 0;">Loading products…</div></div>';
		echo '<style>.wpmp-products .wpmp-card{background:#fff;border:1px solid '.esc_attr($t['palette']['border']).';border-radius:'.esc_attr($t['radius']['card']).'px;overflow:hidden;display:flex;flex-direction:column;text-decoration:none;color:inherit;}.wpmp-products img{width:100%;aspect-ratio:1;object-fit:cover;display:block;}.wpmp-body{padding:14px;} .wpmp-price{color:'.esc_attr($t['palette']['primary']).';font-weight:600;}</style>';
		echo '<script>(function(){var root=document.currentScript.previousElementSibling.previousElementSibling; if(!root) root=document.querySelector(".wpmp-products"); if(!root) return; var n=parseInt(root.getAttribute("data-count")||"4",10); var url="'.esc_url(home_url()).'/wp-json/wc/store/v1/products?per_page="+n; fetch(url).then(function(r){return r.ok?r.json():[]}).then(function(items){if(!items||!items.length){root.innerHTML=\'<div style="grid-column:1/-1;text-align:center;color:#8A7A6A;padding:30px 0;">No products yet.</div>\';return;} root.innerHTML=""; items.forEach(function(p){var img=p.images&&p.images[0]?p.images[0].src:""; var price=p.prices&&p.prices.price?(parseInt(p.prices.price,10)/Math.pow(10,p.prices.currency_decimals||2)).toFixed(2):null; var cur=p.prices&&p.prices.currency_symbol?p.prices.currency_symbol:"$"; var card=document.createElement("a");card.className="wpmp-card";card.href=p.permalink||"#"; var im=document.createElement("img");im.loading="lazy";im.alt=p.name||"";im.src=img||"'.esc_js($ph_url).'"; card.appendChild(im); var body=document.createElement("div");body.className="wpmp-body"; var h=document.createElement("h3");h.textContent=p.name||""; var pr=document.createElement("span");pr.className="wpmp-price";pr.textContent=null===price?"":cur+price; body.appendChild(h);body.appendChild(pr);card.appendChild(body); root.appendChild(card);});}).catch(function(){root.innerHTML=\'<div style="grid-column:1/-1;text-align:center;color:#8A7A6A;padding:30px 0;">Could not load products.</div>\';});})();</script>';
	}
}
