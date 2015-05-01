<?php
/**
 * Plugin Name: markdownR
 * Plugin URI:  http://wordpress.org/plugins/markdownR/
 * Description: A powerful plugin to write blog in rmd format.
 * Author: Jianhong Ou
 * Author URI: http://qiuworld.com
 * Tags: R, knitr, post
 * Version: 1.0
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author     Jianhong Ou
 * @version    1.0
 * @package    markdownR
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
 
/****  Registers the activation hook. It will be used when the plugin activates. ****/
register_activation_hook( __FILE__, 'ou_markdownR_activate' );

/**** Registers the deactivation hook. It will be used when the plugin deactivates. ****/
register_deactivation_hook( __FILE__, 'ou_markdownR_deactivate' );
 
/**** Function called when the plugin activates. ****/ 
function ou_markdownR_activate() {
  // Checks for the options of markdownR. If found do nothing else create one.\
	add_option( 'ou_markdownR_options_R_path', "R", "", "yes" );
	add_option( 'ou_markdownR_options_rstudio_pandoc_path', "pandoc", "", "yes");
}

/**** Function called when the plugin deactivates. ****/
function ou_markdownR_deactivate() {
  // Deletes the values saved in the database. \
    delete_option( 'ou_markdownR_options_R_path' );
    delete_option( 'ou_markdownR_options_rstudio_pandoc_path');
}


/**** This action is hooked to admin_menu. ****/
if( is_admin() ){
	add_action( 'admin_menu', 'ou_markdownR_options_page' );
	
	function ou_markdownR_options_page() {
		add_options_page("markdown R", "markdown R", "administrator", 
						"markdownR", "markdown_html_page");
	}
}

function markdown_html_page(){
?>
<div>
<h2>markdownR Options</h2>

<form method="post" action="options.php">
<?php wp_nonce_field('ou_markdownR_update-options'); ?>

<table width="510">
<tr valign="top">
<th width="92" scope="row">Enter R path</th>
<td width="406">
<input name="ou_markdownR_options_R_path" type="text" id="ou_markdownR_options_R_path"
value="<?php echo get_option('ou_markdownR_options_R_path'); ?>" />
(ex. /usr/bin/R)</td>
</tr>
<tr valign="top">
<th width="92" scope="row">Enter pandoc path</th>
<td width="406">
<input name="ou_markdownR_options_rstudio_pandoc_path" type="text" id="ou_markdownR_options_rstudio_pandoc_path"
value="<?php echo get_option('ou_markdownR_options_rstudio_pandoc_path'); ?>" />
(ex. /usr/local/bin/pandoc)</td>
</tr>
</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="ou_markdownR_options_R_path,ou_markdownR_options_rstudio_pandoc_path" />

<p>
<input type="submit" value="<?php _e('Save Changes') ?>" />
</p>

</form>
</div>
<?php
}

/**** This filter is hooked to the_content. ****/
add_filter( 'content_save_pre' , 'ou_markdownR_content' , 10, 1);
function ou_markdownR_overwrite_cust_filename($dir, $name, $ext){
	$postid = get_the_ID();
	return("rmd_".$postid.$name.$ext);
}
function ou_markdownR_content( $content ) {
	if(preg_match("/^==Rmd==/", $content)){
		$Rpath = get_option("ou_markdownR_options_R_path", "R");
		$rstudio_pandoc_path = get_option("ou_markdownR_options_rstudio_pandoc_path", "pandoc");
		$path = plugin_dir_path( __FILE__ );
		$code = substr($content, 7);
		$content = "<div class='rmdSourceCode' style='display: none;'>" . $code . "=====EndrmdSourceCode=====</div>";
		## run R in back ground
		### write tmp file
		$temp = tempnam(sys_get_temp_dir(), 'OUmdR');
		$code = mb_convert_encoding($code, 'UTF-8', mb_internal_encoding());
		$fp = fopen($temp, 'w');
		fwrite($fp, $code);
		fclose($fp);
		$output = tempnam(sys_get_temp_dir(), 'OUTmdR');
		$cmd = "$Rpath CMD BATCH --no-save --no-restore '--args tmp=\"$temp\" out=\"$output\" tmpdir=\"".sys_get_temp_dir()."\" rstudio_pandoc_path=\"".$rstudio_pandoc_path."\"' ". $path . "/run.R ". $path ."/run.log";
		system($cmd);
		$html = file_get_contents($output);
		if (preg_match('/(?:<body[^>]*>)(.*)<\/body>/isU', $html, $matches)) {
			$body =$matches[1];
			##move images
			if ( ! function_exists( 'wp_handle_upload' ) ) 
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			if(preg_match_all("/<img src=\"(figure\/.*?)\" alt=.*?\/>/", $body, $matches)){
				foreach($matches[1] as $f){
					$path_parts = pathinfo($f);
					$uploadedfile = array(
						'name' => $path_parts['basename'],
						'type' => 'image/'.$path_parts['extension'],
						'tmp_name' => ABSPATH . "wp-admin/$f",
						'error' => 0,
						'size' => filesize( ABSPATH . "wp-admin/$f" ),
					);
					$upload_overrides = array( 
						'test_form' => false,
						'test_size' => true,
						'test_upload' => true,
						'unique_filename_callback' => 'ou_markdownR_overwrite_cust_filename',
					);
					$movefile = wp_handle_sideload( $uploadedfile, $upload_overrides );
					if ( ! empty($movefile['error']) ) {
						// handle error?
					}else{
						$body = str_replace("$f", $movefile['url'], $body);
					}
				}
			}
			$content .= $body;
		}
		//unlink($temp);
		//unlink($output);
	}
	return $content;
} 

/**** add meta boxes to the administrative interface ****/
add_action( 'add_meta_boxes', 'ou_markdownR_add_meta_box' );
function ou_markdownR_add_meta_box() {

	$screens = array( 'post', 'page' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'markdownR_sectionid',
			'R log file',
			'ou_markdownR_meta_box_callback',
			$screen,
			'advanced',
			'high'
		);
	}
}

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function ou_markdownR_meta_box_callback( $post ) {
	echo("<pre>".file_get_contents(plugin_dir_path( __FILE__ )."/run.log")."</pre>");
}
/**** delete the content of log.txt before a new post ****/
add_filter( 'default_content', 'ou_markdownR_default_content', 10, 2);
function ou_markdownR_default_content( $content ){
	ou_markdownR_clean_log_txt();
	$content = "==Rmd==\n$content";
	return ($content);
}
add_action('new_to_publish', 'ou_markdownR_clean_log_txt');
function ou_markdownR_clean_log_txt(){
	file_put_contents(plugin_dir_path( __FILE__ )."/run.log", "Nothing to show");
}

/**** loading a post for editing ***/
add_filter( 'content_edit_pre', 'ou_markdownR_edit', 10, 2 );
function ou_markdownR_edit( $content, $post_id ) {
  // Process content here
  if(preg_match("/<div class='rmdSourceCode' style='display: none;'>(.*?)=====EndrmdSourceCode=====<\/div>/isU", $content, $matches)){
  	$content = "==Rmd==". $matches[1];
  }else{
  	#clean log file
  	ou_markdownR_clean_log_txt;
  }
  return $content;
}

/***** css and javascript ****/
// Register style sheet.
add_action( 'wp_enqueue_scripts', 'ou_markdownR_register_plugin_styles' );

/**
 * Register style sheet.
 */
function ou_markdownR_register_plugin_styles() {
	wp_register_style( 'markdownRcss', plugins_url( 'style.css', __FILE__ ) );
	wp_enqueue_style( 'markdownRcss' );
}
// Register script.
add_action( 'wp_enqueue_scripts', 'ou_markdownR_enqueue_and_register_js' );

function ou_markdownR_enqueue_and_register_js(){
    wp_register_script( 'markdownRjs', plugins_url( 'script.js', __FILE__ ));
    wp_enqueue_script( 'markdownRjs');
}
?>