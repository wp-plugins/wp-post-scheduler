<?php
/*
Plugin Name: WP Post Scheduler
Plugin URI: http://www.wamtengineers.com
Description: This plugin is being used as a bridge between WP Post Scheduler and Wordpress.
Version: 1.0
Author: wamtengineers
Author URI: http://www.wamtengineers.com
*/

class wp_post_scheduler {
	
	private $plugin_links;
	public function __construct(){
		add_action('admin_menu', array($this, 'wp_post_scheduler_admin_add_page'));
		add_action('admin_init', array($this, 'wp_post_scheduler_admin_init'));
		add_action('init', array($this, 'wp_post_scheduler_init'));
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this, 'wp_post_scheduler_action_link'));
		add_filter( 'plugin_row_meta', array($this, 'wp_post_scheduler_meta_link'), 10, 2);
	}
	
	function wp_post_scheduler_action_link( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="options-general.php?page=wp-post-scheduler">Settings</a>'
			),
			$links
		);
	}
	
	function wp_post_scheduler_meta_link( $links, $file ) {
		$plugin = plugin_basename(__FILE__);
		if ( $file == $plugin ) {
			return array_merge(
				$links,
				array(
					'settings' => '<a href="options-general.php?page=wp-post-scheduler">Settings</a>'
				)
			);
		}
		return $links;
	}
	
	function wp_post_scheduler_admin_add_page() {
		add_options_page('WP Post Scheduler', 'WP Post Scheduler', 'manage_options', 'wp-post-scheduler', array($this, 'wp_post_scheduler_options_page'));
	}
	
	function wp_post_scheduler_options_page(){
		?>
		<div>
			<h2>WP Post Scheduler</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields('wp_post_scheduler_options');
				do_settings_sections('wp_post_scheduler_settings');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
	
	function wp_post_scheduler_admin_init(){
		register_setting( 'wp_post_scheduler_options', 'wp_post_scheduler_key', array($this, 'wp_post_scheduler_key_validate'));
		add_settings_section('wp_post_scheduler_main', 'WP Post Scheduler Settings', array($this, 'wp_post_scheduler_section_text'), 'wp_post_scheduler_settings');
		add_settings_field('wp_post_scheduler_key', 'API Key', array($this, 'wp_post_scheduler_string'), 'wp_post_scheduler_settings', 'wp_post_scheduler_main');
	}
	
	function wp_post_scheduler_section_text(){
		echo '<p>You will need to create an API key for this blog. After saving the API key you can add it to your WP Post Schedulat Blog settings. Then script can post the articles with authentication process.</p>
		<p>You willl nedd to add URL of the blog. <strong>'.get_bloginfo('url').'/</strong></p>';
	}
		
	function wp_post_scheduler_string(){
		$wp_post_scheduler_key = get_option('wp_post_scheduler_key');
		echo "<input id='wp_post_scheduler_key' name='wp_post_scheduler_key' size='40' type='text' value='{$wp_post_scheduler_key}' />";
	}
	
	function wp_post_scheduler_key_validate($input) {
		return $input;
	}
	
	function wp_post_scheduler_init(){
		if(isset($_POST['wp_post_scheduler_api_key'])){
			$wp_post_scheduler_key = get_option('wp_post_scheduler_key');
			if($wp_post_scheduler_key==$_POST['wp_post_scheduler_api_key']){
				if(isset($_POST['post_title']) && isset($_POST['post_content'])){
					$post_title=$_POST["post_title"];
					$post_content=$_POST["post_content"];
					if(isset($_POST["post_images_count"])){
						$post_images_count=$_POST["post_images_count"];
						$post_images_count=rand(0, $post_images_count);
						if($post_images_count>0){
							$args=array(
								'posts_per_page'=>-1,
								'orderby'=>'rand',
								'post_type' => 'attachment',
        						'post_mime_type' => 'image',
								's'=>'impressum'
							);
							$exclude_images=get_posts($args);
							$exclude_images_array=array();
							foreach($exclude_images as $exclude_image)
								$exclude_images_array[]=$exclude_image->ID;
							$exclude_images=implode(",", $exclude_images_array);
							$args=array(
								'posts_per_page'=>$post_images_count,
								'orderby'=>'rand',
								'post_type' => 'attachment',
        						'post_mime_type' => 'image',
								'exclude'=>$exclude_images
							);
							$images=get_posts($args);
							foreach($images as $image){
								$post_content=$this->put_random_images($post_content, wp_get_attachment_image($image->ID, 'large', 0, array('class'=>'alignleft')));
							}
							$post_content=$post_content;
						}
					}
					$post=array(
						'post_content'=>$post_content,
						'post_title'=>$post_title,
						'post_status'=>'publish',
						'post_author'   => 1
					);
					$post=wp_insert_post($post, true);
					if(is_wp_error($post)){
						$rtn=array(
							'status'=>'0',
							'message'=>$post->get_error_message()
						);
					}
					else{
						$rtn=array(
							'status'=>'1',
							'post_url'=>get_permalink($post)
						);
					}
				}
				else{
					$rtn=array(
						'status'=>'0',
						'message'=>'Please provide Post Title, Post Content.'
					);
				}
			}
			else{
				$rtn=array(
					'status'=>'0',
					'message'=>'Error in Authentication. '.$wp_post_scheduler_key.'=='.$_POST['wp_post_scheduler_api_key']
				);
			}
			echo json_encode($rtn);
			die;
		}
	}
	
	function put_random_images($content, $image_url){
		$paragraphs=explode("\n", $content);
		$paragraph=rand(1, count($paragraphs));
		$paragraph=$paragraphs[$paragraph-1];
		return preg_replace("/".$paragraph."/", $paragraph.$image_url, $content, 1);
	}
}
$wp_post_scheduler=new wp_post_scheduler();