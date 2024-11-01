<?php 
/*
Plugin Name: Wp-Scribd-List
Description: Provide a list of documents for a Scribd's user. Usage: before go in admin options and add the Api Key, than write in a post or a page [scribd-list].
Version: 1.2
Author: Capa
*/

/*  Copyright 2009  Giovanni Colucci  (email : giovanni.colucci@maxmap.it)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


require_once 'scribd.php';


function wp_scribd_list($text){
	//Set the Api key and secret from the DB
	if(function_exists("get_option")){
		$scribd_api_key = get_option("wp-scribd-list-key");
		$scribd_secret = get_option("wp-scribd-list-secret");
		$description_flag = get_option("wp-scribd-list-option");
	}else{
		echo "I can't find the \"get_option\" function.";
		return $text;
	}
	
	if((empty($scribd_api_key))||(empty($scribd_secret))){
		echo "The API Key or the Secret Key are not valid! Check in the administration pannel.";
		return $text;
	}
	
	$scribd = new Scribd($scribd_api_key, $scribd_secret); //create the scribd object
	
	$index = strpos($text, "[scribd-list]");
	if($index !== false){ //whitout this, the plugin retrieve the information from scribd everytime
		$list = $scribd->getList();
		if(is_string($list)){
			$f_list = "There are not download available for now!";
		}else{
			$f_list = "<ul id='wp-scribd-list'>";
			$list_len = count($list);
			for($count=0; $count<$list_len; $count++){ //now i extract the results one at the time
				if($count == 0){ //the first time, the index of the array is whitout number
					$title = $list['result']['title'];
					$link = '<a href="http://www.scribd.com/doc/'.$list['result']["doc_id"].'" >';
					$description = $list['result']['description'];
					$f_list .= "<li>".$link.$title."</a></li>";
					if(($description)&&($description_flag)){
						$f_list .= "<p style='margin-left: 10px;'><strong>Description</strong>:".$description."</p>";
					}
				}else{
					$title = $list['result '.(string)($count+1)]['title'];
					$link = '<a href="http://www.scribd.com/doc/'.$list["result ".(string)($count+1)]["doc_id"].'" >';
					$description = $list['result '.(string)($count+1)]['description'];
					$f_list .= "<li id='wp-scribd-element'>".$link.$title."</a></li>";
					if(($description)&&($description_flag)){
						$f_list .= "<p style='margin-left: 10px;'><strong>Descriptions</strong>:".$description."</p>";
					}
				}
			}
			$f_list .= "</ul>";
			
		}
		$text = str_replace("[scribd-list]", $f_list, $text); //now i'm replacing the plugin code whit the list
	} 
	return $text;
}


/***********Add the Style CSS ************/

function wp_scribd_list_style(){
	echo '<link rel="stylesheet" href="'.get_bloginfo("wpurl").'/wp-content/plugins/wp-scribd-list/style.css" type="text/css" />
	';
}


/********************* Add the admin menu, in the back end *******************/
function wp_scribd_menu(){
	add_options_page('WP Scribd List Options', 'Wp-Scribd-List', 9, 'wp-scribd-list', 'wp_scribd_list_menu');
}

function wp_scribd_list_menu(){

	echo '<h2>Wp Scribd List Menu</h2>';
	echo '<p>In this page you can modify or add your api key and secret key of your account on Scribd. For more informations about the Scribd API, visit <a href="http://www.scribd.com/developers" target="_blank">Developers/API</a> on Scribd</p>';

	
	//Update the values in the DataBase
	if(isset($_POST['wp-scribd-hidden'])){
		if($_POST['wp-scribd-hidden']=='Y'){
			$updated_api_key = $_POST['key'];
			$updated_secret = $_POST['secret'];
			$update_description_flag = $_POST['description_flag'];
		
			update_option('wp-scribd-list-key', $updated_api_key);
			update_option('wp-scribd-list-secret', $updated_secret);
			update_option('wp-scribd-list-option', $update_description_flag);
			$OK = 1;
		}
	}
	
	
	//Check if there's already a key in the DB
	$key = get_option("wp-scribd-list-key");
	$secret = get_option("wp-scribd-list-secret");
	$value_set = 0;
	if(($key)&&($secret)){
		echo '<strong style="color: green;">Your API and Secret Keys are already set!</strong>';
		$scribd_api_key = get_option("wp-scribd-list-key");
		$scribd_secret = get_option("wp-scribd-list-secret");
		$description_flag = get_option("wp-scribd-list-option");
		$value_set = 1;
	}else{
		echo '<strong style="color: red;">Your API and Secret Keys are not set!</strong>';
	}
	
	//Create the option's from.
	echo '<form method="post" action="">';
	echo '<input type="hidden" name="wp-scribd-hidden" value="Y">';
	if($value_set){
		echo '<p>API Key </p><p><input type="text" value="'.$scribd_api_key.'" name="key" style="font-style: italic;"/></p>';
		echo '<p>Secret Key</p><p><input type="text" value="'.$scribd_secret.'" name="secret" style="font-style: italic;"/></p>';
	}else{
		echo '<p>API Key</p><p><input type="text" value="Here your API Key" name="key" style="font-style: italic;"/></p>';
		echo '<p>Secret Key</p><p><input type="text" value="Here your Secret Key" name="secret" style="font-style: italic;"/></p>';
	}
	
	if($description_flag){ 
		echo '<p>Display descriptions <input type="checkbox" name="description_flag" checked="checked" /></p>';
	}else{
		echo '<p>Display descriptions <input type="checkbox" name="description_flag" /></p>';
	}
	
	echo '<p><input type="submit" name="Submit" value="Submit" /></p>';
	echo '</form>';
	
	if($OK){
		echo '<p><strong style="color: green">Configuration updated!</strong></p>';
	}
	
	echo '<p><strong>Usage</strong></p>';
	echo '<p>Add to your post or page the tag [scribd-list], the plugin will do the rest! :)</p>';
	echo '<p><strong>CSS Style</strong></p>';
	echo '<p>You can customize the css style, simple by editing the file style.css in ../wp-content/plugins/wp-scribd-list/style.css</p>';
	

}


//Add the actions and the filter

add_action('admin_menu', 'wp_scribd_menu');
add_action('wp_head', 'wp_scribd_list_style');
add_filter("the_content", "wp_scribd_list");

?>