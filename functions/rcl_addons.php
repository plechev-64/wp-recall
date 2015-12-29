<?php

function rcl_activate_addon($addon){
    $active_addons = get_site_option('rcl_active_addons');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $k=>$path){
        if ( false !== strpos($path, '\\') ) $path = str_replace('\\','/',$path);
        $index_src = $path.'/'.$addon.'/index.php';
        if(!is_readable($index_src)) continue;

        $active_addons[$addon]['path'] = $path.'/'.$addon;
        $active_addons[$addon]['priority'] = (!$k)? 1: 0;
        $install_src = $path.'/'.$addon.'/activate.php';

        if(file_exists($install_src)){
            include($install_src);
            break;
        }
    }

    update_site_option('rcl_active_addons',$active_addons);

    do_action('rcl_activate_'.$addon,$active_addons[$addon]);
}

function rcl_deactivate_addon($addon){
    $active_addons = get_site_option('rcl_active_addons');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $path){
        if(is_readable($path.'/'.$addon.'/deactivate.php')){
            include($path.'/'.$addon.'/deactivate.php');
            break;
        }
    }

    unset($active_addons[$addon]);

    update_site_option('rcl_active_addons',$active_addons);

    do_action('rcl_deactivate_'.$addon);
}

function rcl_delete_addon($addon){
    $active_addons = get_site_option('rcl_active_addons');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $path){
        if(is_readable($path.'/'.$addon.'/delete.php')) include($path.'/'.$addon.'/deactivate.php');
        rcl_remove_dir($path.'/'.$addon);
    }

    unset($active_addons[$addon]);

    update_site_option('rcl_active_addons',$active_addons);

    do_action('rcl_delete_'.$addon);
}

class Rcl_Addons{

    public function __construct() {
        add_action('init', array(&$this, 'update_status_addon_recall_activate'));
        add_action('init', array(&$this, 'update_options_rcl_activate'));
        add_action('init', array(&$this, 'upload_addon_recall_activate'));
        add_action('admin_init', array(&$this, 'update_group_addon_recall_activate'));
        add_action('admin_menu', array(&$this, 'wp_recall_addons_panel'),20);
        //add_action('init', array(&$this, 'get_include_activate_addons_recall'),1);
    }

	function wp_recall_addons_panel(){
		add_submenu_page( 'manage-wprecall', __('Add-on manager','wp-recall'), __('Add-on manager','wp-recall'), 'manage_options', 'manage-addon-recall', array( $this, 'recall_addon_manage'));
	}

	function actual_url_rcl(){
		$wpurl = get_bloginfo('wpurl');
		$wpurl = explode('/',$wpurl);
		$cnd = count($wpurl);
		if($wpurl[$cnd]){
			$dom = '/'.$wpurl[$cnd];
		}else{
			$s = count(explode('.',$wpurl[$cnd-1]));
			if($s==1) $dom = '/'.$wpurl[$cnd-1];
		}
		$dir = $_SERVER['DOCUMENT_ROOT'].$dom;
		return $dir;
	}

	function recall_addon_manage(){
		global $active_addons;
		//$dir   = RCL_PATH.'add-on';

                $need_update = get_option('rcl_addons_need_update');

                $paths = array(RCL_PATH.'add-on',RCL_TAKEPATH.'add-on') ;

                foreach($paths as $path){
                    if(file_exists($path)){
                        $addons = scandir($path,1);
                        $a=0;
                        foreach((array)$addons as $namedir){
                                $addon_dir = $path.'/'.$namedir;
                                $index_src = $addon_dir.'/index.php';
                                if(!is_dir($addon_dir)||!file_exists($index_src)) continue;
                                $info_src = $addon_dir.'/info.txt';
                                if(file_exists($info_src)){
                                        $info = file($info_src);
                                        $addons_data[$namedir] = $this->get_parse_addon_info($info);
                                        $addons_data[$namedir]['src'] = $index_src;
                                        $a++;
                                        flush();
                                }
                        }
                    }
                }

		$cnt_all = count($addons_data);

		$cnt_act = count($active_addons);
		$cnt_inact = $cnt_all - $cnt_act;

		$table = '<div class="wrap">
			<div id="icon-plugins" class="icon32"><br></div>
			<h2>'.__('Add-ons Wp-Recall','wp-recall').'</h2>';

			if(isset($_GET['update-addon'])){
				switch($_GET['update-addon']){
					case 'activate': $text_notice = __('Addition <strong>activated</strong>. It is possible that on the settings page of Wp-Recall new settings','wp-recall'); $type='updated'; break;
					case 'deactivate': $text_notice = __('Addition <strong>deactivated</strong>.','wp-recall'); $type='updated'; break;
					case 'delete': $text_notice = __('Files and data additions have been <strong>removed</strong>.','wp-recall'); $type='updated'; break;
					case 'error-info': $text_notice = __('The Supplement has not been loaded. Add missing the correct header.','wp-recall'); $type='error'; break;
				}
				$this->get_update_scripts_file_rcl();
				$this->get_update_scripts_footer_rcl();
				rcl_minify_style();
				$table .='<div id="message" class="'.$type.'"><p>'.$text_notice.'</p></div>';
			}

                        if(isset($_POST['save-rcl-key'])){
                            if( wp_verify_nonce( $_POST['_wpnonce'], 'add-rcl-key' ) ){
                                update_option('rcl-key',$_POST['rcl-key']);
                                $table .='<div id="message" class="'.$type.'"><p>'.__('Key is stored','wp-recall').'!</p></div>';
                            }
			}

                        $table .= '<h4>'.__('RCLKEY','wp-recall').'</h4>
			<form action="" method="post">
				'.__('Enter RCLKEY','wp-recall').' <input type="text" name="rcl-key" value="'.get_option('rcl-key').'">
				<input class="button" type="submit" value="'.__('Save','wp-recall').'" name="save-rcl-key">
				'.wp_nonce_field('add-rcl-key','_wpnonce',true,false).'
			</form>
                        <p class="install-help">'.__('He will need to update the add-ons here. Get it , you can profile your account online <a href="http://wppost.ru/" target="_blank">http://wppost.ru</a>','wp-recall').'</p>';

			$table .= '
			<h4>'.__('To install the add-on to Wp-Recall format .zip','wp-recall').'</h4>
			<p class="install-help">'.__('If you have the archive add-on for wp-recall format .zip, here you can download and install it.','wp-recall').'</p>
			<form class="wp-upload-form" action="/" enctype="multipart/form-data" method="post">
				<label class="screen-reader-text" for="addonzip">'.__('Plugin archive','wp-recall').'</label>
				<input id="addonzip" type="file" name="addonzip">
				<input id="install-plugin-submit" class="button" type="submit" value="'.__('To install','wp-recall').'" name="install-addon-submit">
				'.wp_nonce_field('install-addons-rcl','_wpnonce',true,false).'
			</form>

			<ul class="subsubsub">
				<li class="all"><b>'.__('All','wp-recall').'<span class="count">('.$cnt_all.')</span></b>|</li>
				<li class="active"><b>'.__('Active','wp-recall').'<span class="count">('.$cnt_act.')</span></b>|</li>
				<li class="inactive"><b>'.__('Inactive','wp-recall').'<span class="count">('.$cnt_inact.')</span></b></li>
			</ul>
			<form action="" method="post">
				'.wp_nonce_field('action-addons','_wpnonce',true,false).'
				<input type="hidden" value="active" name="plugin_status">
				<input type="hidden" value="1" name="paged">
				<div class="tablenav top">';
					$table .= '<div class="alignleft actions bulkactions">
						<select name="group-addon-action">
							<option selected="selected" value="">'.__('Action','wp-recall').'</option>
							<option value="deactivate">'.__('Deactivate','wp-recall').'</option>
							<option value="activate">'.__('To activate','wp-recall').'</option>
						</select>
						<input id="doaction" class="button action" type="submit" value="'.__('Apply','wp-recall').'" name="">
					</div>';
				$table .= '</div>
				<table class="wp-list-table widefat plugins" cellspacing="0">
				<thead>
					<tr>
						<th id="cb" class="manage-column column-cb check-column" style="" scope="col">';
						$table .= '<label class="screen-reader-text" for="cb-select-all-1">'.__('Select all','wp-recall').'</label>
						<input id="cb-select-all-1" type="checkbox">';
						$table .= '</th>
						<th id="name" class="manage-column column-name" style="" scope="col">'.__('Additions','wp-recall').'</th>
						<th id="description" class="manage-column column-description" style="" scope="col">'.__('Description','wp-recall').'</th>
					</tr>
				</thead>';

				foreach((array)$addons_data as $key=>$addon){
					if($active_addons&&isset($active_addons[$key])) $status = 1;
					else $status = 0;

                                        $ver = (isset($need_update[$key]))? version_compare($need_update[$key]['new-version'],$addon['version']): 0;

                                        $update = ($ver>0)? 'update': '';

					$table .= '<tr id="better-wp-security" class="'.($status ? "active" : "inactive" ).' '.$update.'">
						<th class="check-column" scope="row">';
							$table .= '<label class="screen-reader-text" for="checkbox_'.$key.'">'.__('Choose','wp-recall').' '.$addon['name'].'</label>
							<input id="checkbox_'.$key.'" type="checkbox" value="'.$key.'" name="checked[]">';
						$table .= '</th>
						<td class="plugin-title">
							<strong>'.$addon['name'].'</strong>
							<div class="row-actions visible">';
							if($active_addons&&isset($active_addons[$key])){
								$table .= '<span class="inactivate">
								<a title="'.__('Deactivate','wp-recall').'" href="'.wp_nonce_url( get_bloginfo('wpurl').'/?action-addon=update&status=deactivate&addon='.$key, 'action_addon' ).'">'.__('Deactivate','wp-recall').'</a>
								</span>';

								if($key=='magazin'){
									$table .= '|<span class="options">
									<a title="'.__('Settings','wp-recall').'" href="'.admin_url('admin.php?page=manage-wpm-options&options='.$key).'">'.__('Settings','wp-recall').'</a>
									</span>';
								}else{
									$table .= '|<span class="options">
									<a title="'.__('Settings','wp-recall').'" href="'.admin_url('admin.php?page=manage-wprecall&options='.$key).'">'.__('Settings','wp-recall').'</a>
									</span>';
								}
							}else{
								$table .= '<span class="inactivate">
								<a title="'.__('Activate','wp-recall').'" href="'.wp_nonce_url( admin_url('admin.php?action-addon=update&status=activate&addon='.$key), 'action_addon' ).'">'.__('Activate','wp-recall').'</a>
								</span>|
								<span class="inactivate">
								<a title="'.__('Delete','wp-recall').'" href="'.wp_nonce_url( admin_url('admin.php?action-addon=update&status=delete&addon='.$key), 'action_addon' ).'">'.__('Delete','wp-recall').'</a>
								</span>';
							}
							$table .= '</div>
						</td>
						<td class="column-description desc">
							<div class="plugin-description">
							<p>'.$addon['description'].'</p>
							</div>
							<div class="active second plugin-version-author-uri">
							'.__('Version','wp-recall').' '.$addon['version'];
                                                                if(isset($addon['author-uri'])) $table .= ' | '.__('Author','wp-recall').': <a title="'.__('Visit the page of the author','wp-recall').'" href="'.$addon['author-uri'].'" target="_blank">'.$addon['author'].'</a>';
                                                                if(isset($addon['add-on-uri'])) $table .= ' | <a title="'.__('Visit the page of the add-on','wp-recall').'" href="'.$addon['add-on-uri'].'" target="_blank">'.__('Page Add-on','wp-recall').'</a>';
							$table .= '</div>
						</td>
					</tr>';

                                        if($ver>0){
                                            $table .= '<tr class="plugin-update-tr active" id="'.$key.'-update" data-slug="'.$key.'">'
                                                        . '<td colspan="3" class="plugin-update colspanchange">'
                                                            . '<div class="update-message">'
                                                                . 'Доступна свежая версия '.$addon['name'].' '.$need_update[$key]['new-version'].'. ';
                                                                if(isset($addon['add-on-uri'])) $table .= 'Можно <a href="'.$addon['add-on-uri'].'"  title="'.$addon['name'].'">посмотреть информацию о версии '.$xml->version.'</a>';
                                                            $table .= 'или <a class="update-add-on" data-addon="'.$key.'" href="#">Обновить автоматически</a></div>'
                                                        . '</td>'
                                                    . '</tr>';
                                        }
				}
				$table .= '</table>
			</form>
		</div>';

		echo $table;
	}

	function update_status_addon_recall_activate ( ) {
	  if ( isset( $_GET['action-addon'] ) ) {
		if( !wp_verify_nonce( $_GET['_wpnonce'], 'action_addon' ) ) return false;

                global $wpdb, $user_ID, $active_addons;
		if ( ! current_user_can('activate_plugins') ) wp_die(__('You cant control polucheniya plugins on this site.','wp-recall'));

                $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

                $addon = $_GET['addon'];

		if($_GET['status']=='activate'){
                    rcl_activate_addon($addon);
                    wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=activate') );exit;
		}
		if($_GET['status']=='deactivate'){
                    rcl_deactivate_addon($addon);
                    wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=deactivate') );exit;
		}
		if($_GET['status']=='delete'){
                   rcl_delete_addon($addon);
                   wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=delete') );exit;
		}
	  }
	}

	function get_update_scripts_file_rcl(){
		//$upload_dir = wp_upload_dir();
                global $rcl_options;
                $opt_slider = "''";
                if(isset($rcl_options['slide-pause'])&&$rcl_options['slide-pause']){
                    $pause = $rcl_options['slide-pause']*1000;
                    $opt_slider = "{auto:true,pause:$pause}";
                }

		$path = RCL_UPLOAD_PATH.'scripts';

                wp_mkdir_p($path);

		$filename = 'header-scripts.js';
		$file_src = $path.'/'.$filename;
		$f = fopen($file_src, 'w');

		$scripts = "var SliderOptions = ".$opt_slider.";"
                        . "jQuery(function(){";
		$scripts = apply_filters('file_scripts_rcl',$scripts);
		$scripts .= "});";
		$scripts = apply_filters('rcl_functions_js',$scripts);
		$scripts = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $scripts);
		$scripts =  preg_replace('/ {2,}/',' ',$scripts);
		fwrite($f, $scripts);
		fclose($f);
	}

	function get_update_scripts_footer_rcl(){
		//$upload_dir = wp_upload_dir();
		$path = RCL_UPLOAD_PATH.'scripts';

                wp_mkdir_p($path);

		$filename = 'footer-scripts.js';
		$file_src = $path.'/'.$filename;
		$f = fopen($file_src, 'w');

		$scripts = '';
		$scripts = apply_filters('file_footer_scripts_rcl',$scripts);
                if(!isset($scripts)) return false;
		if($scripts) $scripts = "jQuery(function($){".$scripts."});";
		$scripts = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $scripts);
		$scripts =  preg_replace('/ {2,}/',' ',$scripts);
		fwrite($f, $scripts);
		fclose($f);
	}

	function update_group_addon_recall_activate ( ) {

	  if ( isset( $_POST['group-addon-action'] ) ) {
              global $wpdb,$user_ID,$active_addons;

		if(!$_POST['checked']|| !wp_verify_nonce($_POST['_wpnonce'],'action-addons') ){
			wp_redirect( admin_url('admin.php?page=manage-addon-recall') );exit;
		}

                $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

		if($_POST['group-addon-action']=='activate'){
			foreach((array)$_POST['checked'] as $addon){
                            foreach($paths as $k=>$path){
                                if(file_exists($path.'/'.$addon.'/index.php')){
                                    $active_addons[$addon]['path'] = $path.'/'.$addon;
                                    $active_addons[$addon]['priority'] = $k;
                                    if(file_exists($path.'/'.$addon.'/activate.php')) include($path.'/'.$addon.'/activate.php');
                                    do_action('rcl_activate_'.$addon,$active_addons[$addon]);
                                    break;
                                }
                            }
			}
			update_site_option('rcl_active_addons',$active_addons);
			wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=activate') );exit;
		}
		if($_POST['group-addon-action']=='deactivate'){
			foreach((array)$_POST['checked'] as $addon){
				foreach((array)$active_addons as $name=>$data){
                                    if($name!=$addon){
                                        $new_active_list[$name] = $data;
                                    }else{
                                        foreach($paths as $path){
                                            if(file_exists($path.'/'.$addon.'/deactivate.php')){
                                                include($path.'/'.$addon.'/deactivate.php');
                                                break;
                                            }
                                        }
                                        do_action('rcl_deactivate_'.$addon,$active_addons[$addon]);
                                    }
				}

				$active_addons = '';
				$active_addons = $new_active_list;
				$new_active_list = '';
			}
			update_site_option('rcl_active_addons',$active_addons);
			wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=deactivate') );exit;
		}
	  }
	}

	function upload_addon_recall(){

            $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

            $filename = $_FILES['addonzip']['tmp_name'];
            $arch = current(wp_upload_dir()) . "/" . basename($filename);
            copy($filename,$arch);

            $zip = new ZipArchive;

            $res = $zip->open($arch);

            if($res === TRUE){

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    //echo $zip->getNameIndex($i).'<br>';
                    if($i==0) $dirzip = $zip->getNameIndex($i);

                    if($zip->getNameIndex($i)==$dirzip.'info.txt'){
                            $info = true;
                    }
                }

                if(!$info){
                      $zip->close();
                      wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=error-info') );exit;
                }

                foreach($paths as $path){
                      if(file_exists($path.'/')){
                          $rs = $zip->extractTo($path.'/');
                          break;
                      }
                }

                $zip->close();
                unlink($arch);
                if($rs){
                      wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=upload') );exit;
                }else{
                      wp_die(__('Unpacking of archive failed.','wp-recall'));
                }
            } else {
                    wp_die(__('ZIP archive not found.','wp-recall'));
            }

	}

	function upload_addon_recall_activate ( ) {
	  if ( isset( $_POST['install-addon-submit'] ) ) {
		if( !wp_verify_nonce( $_POST['_wpnonce'], 'install-addons-rcl' ) ) return false;
		add_action( 'wp', array(&$this, 'upload_addon_recall') );
	  }
	}

	function get_parse_addon_info($info){
		$addon_data = array();
		$cnt = count($info);

		if($cnt==1) $info = explode(';',$info[0]);

		foreach((array)$info as $string){

			if($cnt>1) $string = str_replace(';','',$string);

			if ( false !== strpos($string, 'Name:') ){
				preg_match_all('/(?<=Name\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s\(\)]*/iu', $string, $string_value);
				$addon_data['name'] = trim($string_value[0][0]);
				continue;
			}
			if ( false !== strpos($string, 'Version:') ){
				preg_match_all('/(?<=Version\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s]*/iu', $string, $version_value);
				$addon_data['version'] = trim($version_value[0][0]);
				continue;
			}
                        if ( false !== strpos($string, 'Support Core:') ){
				preg_match_all('/(?<=Support Core\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s]*/iu', $string, $version_value);
				$addon_data['support-core'] = trim($version_value[0][0]);
				continue;
			}
			if ( false !== strpos($string, 'Description:') ){
				preg_match_all('/(?<=Description\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s\(\)]*/iu', $string, $desc_value);
				$addon_data['description'] = trim($desc_value[0][0]);
				continue;
			}
			if ( false !== strpos($string, 'Author:') ){
				preg_match_all('/(?<=Author\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s]*/iu', $string, $author_value);
				$addon_data['author'] = trim($author_value[0][0]);
				continue;
			}
			if ( false !== strpos($string, 'Url:') ){
				preg_match_all('/(?<=Url\:)[A-zА-я0-9\-\_\:\/\.\?\=\&\@\s]*/iu', $string, $url_value);
				$addon_data['url'] = trim($url_value[0][0]);
				continue;
			}
                        if ( false !== strpos($string, 'Add-on URI:') ){
				preg_match_all('/(?<=Add-on URI\:)[A-zА-я0-9\-\_\:\/\.\?\=\&\@\s]*/iu', $string, $url_value);
				$addon_data['add-on-uri'] = trim($url_value[0][0]);
				continue;
			}
                        if ( false !== strpos($string, 'Author URI:') ){
				preg_match_all('/(?<=Author URI\:)[A-zА-я0-9\-\_\:\/\.\?\=\&\@\s]*/iu', $string, $url_value);
				$addon_data['author-uri'] = trim($url_value[0][0]);
				continue;
			}
		}

		return $addon_data;
	}

	function update_options_rcl_activate ( ) {
            global $rcl_options;
	  if ( isset( $_POST['rcl_global_options'] ) ) {
		if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rcl' ) ) return false;

		$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
                
		if($_POST['global']['login_form_recall']==1&&!isset($_POST['global']['page_login_form_recall'])){
			$_POST['global']['page_login_form_recall'] = wp_insert_post(array('post_title'=>__('Login and register','wp-recall'),'post_content'=>'[loginform]','post_status'=>'publish','post_author'=>1,'post_type'=>'page','post_name'=>'login-form'));
		}

		foreach((array)$_POST['global'] as $key => $value){			
			$options[$key] = $value;
		}

		update_option('rcl_global_options',$options);
                
                if(isset($_POST['local'])){
                    foreach((array)$_POST['local'] as $key => $value){
                        update_option($key,$value);
                    }
                }

		$rcl_options = $options;

		if( current_user_can('edit_plugins') ){
                    $this->get_update_scripts_file_rcl();
                    $this->get_update_scripts_footer_rcl();
                    rcl_minify_style();
		}

		wp_redirect(admin_url('admin.php?page=manage-wprecall'));
		exit;
	  }
	}
}

require_once("rcl_update.php");