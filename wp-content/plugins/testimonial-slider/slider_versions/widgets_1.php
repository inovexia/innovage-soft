<?php

if (!defined('ABSPATH')) die('No direct access.');

if(!class_exists('Testimonial_Slider_Simple_Widget')){
	class Testimonial_Slider_Simple_Widget extends WP_Widget {
		public function __construct() {
			$widget_options = array('classname' => 'testimonial_slider_wclass', 'description' => 'Insert Testimonial Slider' );
			parent::__construct('testimonial_sslider_wid', 'Testimonial Slider - Simple', $widget_options);
		}

		public function widget($args, $instance) {
			extract($args, EXTR_SKIP);
		    global $testimonial_slider;
	
			echo $before_widget; //phpcs:ignore WordPress.Security - passed from WP core
			if($testimonial_slider['multiple_sliders'] == '1') {
			$slider_id = empty($instance['slider_id']) ? '1' : apply_filters('widget_slider_id', $instance['slider_id']);
			}
			else{
			 $slider_id = '1';
			}
	
			$set = empty($instance['set']) ? '' : apply_filters('widget_set', $instance['set']);

			echo $before_title . $after_title; //phpcs:ignore WordPress.Security - passed from WP core
			 get_testimonial_slider($slider_id,$set);
			echo $after_widget; //phpcs:ignore WordPress.Security - passed from WP core
		}

		public function update($new_instance, $old_instance) {
		    global $testimonial_slider;
			$instance = $old_instance;
			if($testimonial_slider['multiple_sliders'] == '1') {
			   $instance['slider_id'] = strip_tags($new_instance['slider_id']);
			}
			$instance['set'] = strip_tags($new_instance['set']);

			return $instance;
		}

		public function form($instance) {
		    global $testimonial_slider;
	
			$instance = wp_parse_args( (array) $instance, array( 'slider_id' => '','set' => '' ) );
			$set = strip_tags($instance['set']);
			$scounter=get_option('testimonial_slider_scounter');		
			if($testimonial_slider['multiple_sliders'] == '1') {	
				$slider_id = strip_tags($instance['slider_id']);		
				$sliders = testimonial_ss_get_sliders();
				$sname_html='<option value="0" selected >Select the Slider</option>';
		 
			  foreach ($sliders as $slider) { 
				 if($slider['slider_id']==$slider_id){$selected = 'selected';} else{$selected='';}
				 $sname_html =$sname_html.'<option value="'.$slider['slider_id'].'" '.$selected.'>'.$slider['slider_name'].'</option>';
			  } 
		?>
					<p><label for="<?php echo esc_attr($this->get_field_id('slider_id')); ?>">Select Slider Name: <select class="widefat" id="<?php echo esc_attr($this->get_field_id('slider_id')); ?>" name="<?php echo esc_attr($this->get_field_name('slider_id')); ?>"><?php echo wp_kses_post($sname_html);?></select></label></p>
	<?php  }
	   
		 $sset_html='<option value="0" selected >Select the Settings</option>';
			  for($i=1;$i<=$scounter;$i++) { 
				 if($i==$set){$selected = 'selected';} else{$selected='';}
				   if($i==1){
				     $settings='Default Settings';
					 $sset_html =$sset_html.'<option value="'.$i.'" '.$selected.'>'.$settings.'</option>';
				   }
				   else{
					  if($settings_set=get_option('testimonial_slider_options'.$i))
						$sset_html =$sset_html.'<option value="'.$i.'" '.$selected.'>'.$settings_set['setname'].' (ID '.$i.')</option>';
				   }
			  } 
	   ?>             
		 <p><label for="<?php echo esc_attr($this->get_field_id('set')); ?>">Select Settings to Apply: <select class="widefat" id="<?php echo esc_attr($this->get_field_id('set')); ?>" name="<?php echo esc_attr($this->get_field_name('set')); ?>"><?php echo wp_kses_post($sset_html);?></select></label></p> 
		 
	<?php }
	}
	function testimonial_slider_simple_widget_register() {
		return register_widget("Testimonial_Slider_Simple_Widget");
	}
	add_action('widgets_init', 'testimonial_slider_simple_widget_register');
}
if(!class_exists('Testimonial_Slider_Category_Widget')){
	//Category Widget
	class Testimonial_Slider_Category_Widget extends WP_Widget {
		public function __construct() {
			$widget_options = array('classname' => 'testimonial_sliderc_wclass', 'description' => 'Testimonial Category Slider' );
			parent::__construct('testimonial_ssliderc_wid', 'Testimonial Slider - Category', $widget_options);
		}

		public function widget($args, $instance) {
			extract($args, EXTR_SKIP);
		    global $testimonial_slider;
		
			echo $before_widget; //phpcs:ignore WordPress.Security - passed from WP core
		
			$cat = empty($instance['cat']) ? '' : apply_filters('widget_cat', $instance['cat']);
			$set = empty($instance['set']) ? '' : apply_filters('widget_set', $instance['set']);

			echo $before_title . $after_title; //phpcs:ignore WordPress.Security - passed from WP core
			 get_testimonial_slider_category($cat,$set);
			echo $after_widget; //phpcs:ignore WordPress.Security - passed from WP core
		}

		public function update($new_instance, $old_instance) {
		    global $testimonial_slider;
			$instance = $old_instance;
		
			$instance['cat'] = strip_tags($new_instance['cat']);
			$instance['set'] = strip_tags($new_instance['set']);

			return $instance;
		}

		public function form($instance) {
		    global $testimonial_slider;
		
			$scounter=get_option('testimonial_slider_scounter');

			$instance = wp_parse_args( (array) $instance, array( 'cat' => '','set' => '' ) );
			$cat = strip_tags($instance['cat']);
			$set = strip_tags($instance['set']);
		
				$args=array(
						'taxonomy'=> 'testimonial_category'
					);
				$categories = get_categories($args);
				$scat_html='<option value="" selected >Select the Category</option>';
		 
			  foreach ($categories as $category) { 
				 if($category->slug==$cat){$selected = 'selected';} else{$selected='';}
				 $scat_html =$scat_html.'<option value="'.esc_attr($category->slug).'" '.$selected.'>'.htmlspecialchars($category->name).'</option>';
			  } 
		?>
			  <p><label for="<?php echo esc_attr($this->get_field_id('cat')); ?>">Select Category for Slider: <select class="widefat" id="<?php echo esc_attr($this->get_field_id('cat')); ?>" name="<?php echo esc_attr($this->get_field_name('cat')); ?>"><?php echo wp_kses_post($scat_html);?></select></label></p>
	  
	 <?php  
		  $sset_html='<option value="0" selected >Select the Settings</option>';
			  for($i=1;$i<=$scounter;$i++) { 
				 if($i==$set){$selected = 'selected';} else{$selected='';}
				   if($i==1){
					 $sset_html =$sset_html.'<option value="'.esc_attr($i).'" '.$selected.'>Default Settings</option>';
				   }
				   else{
				       if($settings_set=get_option('testimonial_slider_options'.$i))
						$sset_html =$sset_html.'<option value="'.esc_attr($i).'" '.$selected.'>'.htmlspecialchars($settings_set['setname']).' (ID '.esc_attr($i).')</option>';
				   }
			  } 
	   ?>             
		 <p><label for="<?php echo esc_attr($this->get_field_id('set')); ?>">Select Settings to Apply: <select class="widefat" id="<?php echo esc_attr($this->get_field_id('set')); ?>" name="<?php echo esc_attr($this->get_field_name('set')); ?>"><?php echo wp_kses_post($sset_html);?></select></label></p> 
		 
	<?php }
	}
	add_action( 'widgets_init', 'testimonial_slider_category_widget_register');
	function testimonial_slider_category_widget_register() {
		return register_widget("Testimonial_Slider_Category_Widget");
	}
}
if(!class_exists('Testimonial_Slider_Recent_Widget')){
	//Recent Posts Widget
	class Testimonial_Slider_Recent_Widget extends WP_Widget {
		public function __construct() {
			$widget_options = array('classname' => 'testimonial_sliderr_wclass', 'description' => 'Testimonial Recent Posts Slider' );
			parent::__construct('testimonial_ssliderr_wid', 'Testimonial Slider - Recent Posts', $widget_options);
		}
	
		public function widget($args, $instance) {
			extract($args, EXTR_SKIP);
		    global $testimonial_slider;
		
			echo $before_widget; //phpcs:ignore WordPress.Security - passed from WP core
		
			$set = empty($instance['set']) ? '' : apply_filters('widget_set', $instance['set']);

			echo $before_title . $after_title; //phpcs:ignore WordPress.Security - passed from WP core
			 get_testimonial_slider_recent($set);
			echo $after_widget; //phpcs:ignore WordPress.Security - passed from WP core
		}

		public function update($new_instance, $old_instance) {
		    global $testimonial_slider;
			$instance = $old_instance;
		
			$instance['set'] = strip_tags($new_instance['set']);

			return $instance;
		}

		public function form($instance) {
		    global $testimonial_slider;
		
			$scounter=get_option('testimonial_slider_scounter');

			$instance = wp_parse_args( (array) $instance, array( 'set' => '' ) );
			$set = strip_tags($instance['set']); ?>
		 
	  <?php  
		  $sset_html='<option value="0" selected >Select the Settings</option>';
			  for($i=1;$i<=$scounter;$i++) { 
				 if($i==$set){$selected = 'selected';} else{$selected='';}
				   if($i==1){
					 $sset_html =$sset_html.'<option value="'.esc_attr($i).'" '.$selected.'>Default Settings</option>';
				   }
				   else{
				       if($settings_set=get_option('testimonial_slider_options'.$i))
						$sset_html =$sset_html.'<option value="'.esc_attr($i).'" '.$selected.'>'.htmlspecialchars($settings_set['setname']).' (ID '.esc_attr($i).')</option>';
				   }
			  } 
	   ?>             
		 <p><label for="<?php echo esc_attr($this->get_field_id('set')); ?>">Select Settings to Apply: <select class="widefat" id="<?php echo esc_attr($this->get_field_id('set')); ?>" name="<?php echo esc_attr($this->get_field_name('set')); ?>"><?php echo wp_kses_post($sset_html);?></select></label></p> 
		 
	<?php }
	}
	add_action( 'widgets_init', 'testimonial_slider_recent_widget_register');
	function testimonial_slider_recent_widget_register() {
		return register_widget("Testimonial_Slider_Recent_Widget");
	}
}
