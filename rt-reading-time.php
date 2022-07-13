<?php

/**
 * The plugin creation file.
 *
 *
 * Plugin Name: زمان مطالعه تیماکلارو
 * Plugin URI: https://clarotm.ir
 * Description: Add an estimated reading time to your posts.
 * Version: 2.0.10
 * Author: تیماکلارو
 * Author URI: https://t-ma.ir
 * License: GPL2
 * Text Domain: reading-time-wp
 * Domain Path: /languages
 *
 * Copyright 2019  Amirreza Heydari  (email : info@clarotm.ir)
 */
if(!defined('WPINC')){die;}
class Reading_Time_WP{public $reading_time;public $rtwp_kses=array('br'=>array(),'em'=>array(),'b'=>array(),'strong'=>array(),);public function __construct(){load_plugin_textdomain('reading-time-wp',false,basename(dirname(__FILE__)).'/languages/');$default_settings=array('label'=>__('Reading Time: ','reading-time-wp'),'postfix'=>__('minutes','reading-time-wp'),'postfix_singular'=>__('minute','reading-time-wp'),'wpm'=>300,'before_content'=>true,'before_excerpt'=>true,'exclude_images'=>false,'include_shortcodes'=>false,);$rtwp_post_type_args=array('public'=>true,);$rtwp_post_type_args=apply_filters('rtwp_post_type_args',$rtwp_post_type_args);$rtwp_post_types=get_post_types($rtwp_post_type_args);foreach($rtwp_post_types as $rtwp_post_type){if('attachment'===$rtwp_post_type){continue;}
$default_settings['post_types'][$rtwp_post_type]=true;}
$rt_reading_time_options=get_option('rt_reading_time_options');add_shortcode('rt_reading_time',array($this,'rt_reading_time'));add_option('rt_reading_time_options',$default_settings);add_action('admin_menu',array($this,'rt_reading_time_admin_actions'));$rt_before_content=$this->rt_convert_boolean($rt_reading_time_options['before_content']);if(isset($rt_before_content)&&true===$rt_before_content){add_filter('the_content',array($this,'rt_add_reading_time_before_content'));}
$rt_after_content=$this->rt_convert_boolean($rt_reading_time_options['before_excerpt']);if(isset($rt_after_content)&&true===$rt_after_content){add_filter('get_the_excerpt',array($this,'rt_add_reading_time_before_excerpt'),1000);}}
public function rt_calculate_reading_time($rt_post_id,$rt_options){$rt_content=get_post_field('post_content',$rt_post_id);$number_of_images=substr_count(strtolower($rt_content),'<img ');if(!isset($rt_options['include_shortcodes'])){$rt_content=strip_shortcodes($rt_content);}
$rt_content=wp_strip_all_tags($rt_content);$word_count=count(preg_split('/\s+/',$rt_content));if(isset($rt_options['exclude_images'])&&!$rt_options['exclude_images']){$additional_words_for_images=$this->rt_calculate_images($number_of_images,$rt_options['wpm']);$word_count+=$additional_words_for_images;}
$word_count=apply_filters('rtwp_filter_wordcount',$word_count);$this->reading_time=$word_count / $rt_options['wpm'];if(1>$this->reading_time){$this->reading_time=__('< 1','reading-time-wp');}else{$this->reading_time=ceil($this->reading_time);}
return $this->reading_time;}
public function rt_calculate_images($total_images,$wpm){$additional_time=0;for($i=1;$i<=$total_images;$i++){if($i>=10){$additional_time+=3*(int)$wpm / 60;}else{$additional_time+=(12-($i-1))*(int)$wpm / 60;}}
return $additional_time;}
public function rt_add_postfix($time,$singular,$multiple){if($time>1){$postfix=$multiple;}else{$postfix=$singular;}
$postfix=apply_filters('rt_edit_postfix',$postfix,$time,$singular,$multiple);return $postfix;}
public function rt_reading_time($atts,$content=null){$atts=shortcode_atts(array('label'=>'','postfix'=>'','postfix_singular'=>'','post_id'=>'',),$atts,'rt_reading_time');$rt_reading_time_options=get_option('rt_reading_time_options');$rt_post=$atts['post_id']&&(get_post_status($atts['post_id']))?$atts['post_id']:get_the_ID();$this->rt_calculate_reading_time($rt_post,$rt_reading_time_options);$calculated_postfix=$this->rt_add_postfix($this->reading_time,$atts['postfix_singular'],$atts['postfix']);return'<span class="span-reading-time rt-reading-time"><span class="rt-label rt-prefix">'.wp_kses($atts['label'],$this->rtwp_kses).'</span> <span class="rt-time"> '.esc_html($this->reading_time).'</span> <span class="rt-label rt-postfix">'.wp_kses($calculated_postfix,$this->rtwp_kses).'</span></span>';}
public function rt_reading_time_admin(){include'rt-reading-time-admin.php';}
public function rt_reading_time_admin_actions(){add_options_page(__('Reading Time WP Settings','reading-time-wp'),__('Reading Time WP','reading-time-wp'),'manage_options','rt-reading-time-settings',array($this,'rt_reading_time_admin'));}
public function rt_add_reading_time_before_content($content){$rt_reading_time_options=get_option('rt_reading_time_options');$rtwp_current_post_type=get_post_type();if(!isset($rt_reading_time_options['post_types'])){$rt_reading_time_options['post_types']=array();}
if(!isset($rt_reading_time_options['post_types'][$rtwp_current_post_type])||!$rt_reading_time_options['post_types'][$rtwp_current_post_type]){return $content;}
$original_content=$content;$rt_post=get_the_ID();$this->rt_calculate_reading_time($rt_post,$rt_reading_time_options);$label=$rt_reading_time_options['label'];$postfix=$rt_reading_time_options['postfix'];$postfix_singular=$rt_reading_time_options['postfix_singular'];if(in_array('get_the_excerpt',$GLOBALS['wp_current_filter'],true)){return $content;}
$calculated_postfix=$this->rt_add_postfix($this->reading_time,$postfix_singular,$postfix);$content='<span class="rt-reading-time" style="display: block;"><span class="rt-label rt-prefix">'.wp_kses($label,$this->rtwp_kses).'</span> <span class="rt-time">'.esc_html($this->reading_time).'</span> <span class="rt-label rt-postfix">'.wp_kses($calculated_postfix,$this->rtwp_kses).'</span></span>';$content.=$original_content;return $content;}
public function rt_add_reading_time_before_excerpt($content){$rt_reading_time_options=get_option('rt_reading_time_options');$rtwp_current_post_type=get_post_type();if(!isset($rt_reading_time_options['post_types'])){$rt_reading_time_options['post_types']=array();}
if(!isset($rt_reading_time_options['post_types'][$rtwp_current_post_type])||!$rt_reading_time_options['post_types'][$rtwp_current_post_type]){return $content;}
$original_content=$content;$rt_post=get_the_ID();$this->rt_calculate_reading_time($rt_post,$rt_reading_time_options);$label=$rt_reading_time_options['label'];$postfix=$rt_reading_time_options['postfix'];$postfix_singular=$rt_reading_time_options['postfix_singular'];$calculated_postfix=$this->rt_add_postfix($this->reading_time,$postfix_singular,$postfix);$content='<span class="rt-reading-time" style="display: block;"><span class="rt-label rt-prefix">'.wp_kses($label,$this->rtwp_kses).'</span> <span class="rt-time">'.esc_html($this->reading_time).'</span> <span class="rt-label rt-postfix">'.wp_kses($calculated_postfix,$this->rtwp_kses).'</span></span> ';$content.=$original_content;return $content;}
public function rt_convert_boolean($value){if('true'===$value||true===$value){return true;}else{return false;}}}
$reading_time_wp=new Reading_Time_WP();
