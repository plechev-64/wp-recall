<?php

rcl_include_addons();

function rcl_include_addons(){
    global $active_addons;

    $active_addons = get_site_option('active_addons_recall');
    $path_addon_rcl = RCL_PATH.'add-on/';
    $path_addon_theme = RCL_TAKEPATH.'add-on/';
    foreach((array)$active_addons as $key=>$addon){
        if(!$addon) continue;
        if(file_exists($path_addon_theme.$key.'/index.php')){
            include_once($path_addon_theme.$key.'/index.php');
        }else if(file_exists($path_addon_rcl.$key.'/index.php')){
            include_once($path_addon_rcl.$key.'/index.php');
        }else{
            unset($active_addons[$key]);
        }
    }

    $rcl_addons = new Rcl_Addons();

}

function rcl_activate_addon($addon){
    $active_addons = get_site_option('active_addons_recall');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $path){
        if ( false !== strpos($path, '\\') ) $path = str_replace('\\','/',$path);
        $index_src = $path.'/'.$addon.'/index.php';
        if(!file_exists($index_src)) continue;

        $active_addons[$addon]['src'] = $path.'/'.$addon;
        $install_src = $path.'/'.$addon.'/activate.php';

        if(file_exists($install_src)){
            include($install_src);
            break;
        }
    }

    update_site_option('active_addons_recall',$active_addons);

    do_action('rcl_activate_'.$addon,$active_addons[$addon]);
}

function rcl_deactivate_addon($addon){
    $active_addons = get_site_option('active_addons_recall');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $path){
        if(file_exists($path.'/'.$addon.'/deactivate.php')){
            include($path.'/'.$addon.'/deactivate.php');
            break;
        }
    }

    unset($active_addons[$addon]);

    update_site_option('active_addons_recall',$active_addons);

    do_action('rcl_deactivate_'.$addon);
}

function rcl_delete_addon($addon){
    $active_addons = get_site_option('active_addons_recall');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $path){
        if(file_exists($path.'/'.$addon.'/delete.php')) include($path.'/'.$addon.'/deactivate.php');
        rcl_remove_dir($path.'/'.$addon);
    }

    unset($active_addons[$addon]);

    update_site_option('active_addons_recall',$active_addons);

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
		add_submenu_page( 'manage-wprecall', __('Add-on manager','rcl'), __('Add-on manager','rcl'), 'manage_options', 'manage-addon-recall', array( $this, 'recall_addon_manage'));
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
                                if(!file_exists($index_src)) continue;
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
			<h2>'.__('Add-ons Wp-Recall','rcl').'</h2>';

			if(isset($_GET['update-addon'])){
				switch($_GET['update-addon']){
					case 'activate': $text_notice = __('Addition <strong>activated</strong>. It is possible that on the settings page of Wp-Recall new settings','rcl'); $type='updated'; break;
					case 'deactivate': $text_notice = __('Addition <strong>deactivated</strong>.','rcl'); $type='updated'; break;
					case 'delete': $text_notice = __('Files and data additions have been <strong>removed</strong>.','rcl'); $type='updated'; break;
					case 'error-info': $text_notice = __('The Supplement has not been loaded. Add missing the correct header.','rcl'); $type='error'; break;
				}
				$this->get_update_scripts_file_rcl();
				$this->get_update_scripts_footer_rcl();
				rcl_minify_style();
				$table .='<div id="message" class="'.$type.'"><p>'.$text_notice.'</p></div>';
			}

                        if(isset($_POST['save-rcl-key'])){
                            if( wp_verify_nonce( $_POST['_wpnonce'], 'add-rcl-key' ) ){
                                update_option('rcl-key',$_POST['rcl-key']);
                                $table .='<div id="message" class="'.$type.'"><p>'.__('Key is stored','rcl').'!</p></div>';
                            }
			}

                        $table .= '<h4>'.__('RCLKEY','rcl').'</h4>
			<form action="" method="post">
				'.__('Enter RCLKEY','rcl').' <input type="text" name="rcl-key" value="'.get_option('rcl-key').'">
				<input class="button" type="submit" value="'.__('Save','rcl').'" name="save-rcl-key">
				'.wp_nonce_field('add-rcl-key','_wpnonce',true,false).'
			</form>
                        <p class="install-help">'.__('He will need to update the add-ons here. Get it , you can profile your account online <a href="http://wppost.ru/" target="_blank">http://wppost.ru</a>','rcl').'</p>';

			$table .= '
			<h4>'.__('To install the add-on to Wp-Recall format .zip','rcl').'</h4>
			<p class="install-help">'.__('If you have the archive add-on for wp-recall format .zip, here you can download and install it.','rcl').'</p>
			<form class="wp-upload-form" action="/" enctype="multipart/form-data" method="post">
				<label class="screen-reader-text" for="addonzip">'.__('Plugin archive','rcl').'</label>
				<input id="addonzip" type="file" name="addonzip">
				<input id="install-plugin-submit" class="button" type="submit" value="'.__('To install','rcl').'" name="install-addon-submit">
				'.wp_nonce_field('install-addons-rcl','_wpnonce',true,false).'
			</form>

			<ul class="subsubsub">
				<li class="all"><b>'.__('All','rcl').'<span class="count">('.$cnt_all.')</span></b>|</li>
				<li class="active"><b>'.__('Active','rcl').'<span class="count">('.$cnt_act.')</span></b>|</li>
				<li class="inactive"><b>'.__('Inactive','rcl').'<span class="count">('.$cnt_inact.')</span></b></li>
			</ul>
			<form action="" method="post">
				'.wp_nonce_field('action-addons','_wpnonce',true,false).'
				<input type="hidden" value="active" name="plugin_status">
				<input type="hidden" value="1" name="paged">
				<div class="tablenav top">';
					$table .= '<div class="alignleft actions bulkactions">
						<select name="group-addon-action">
							<option selected="selected" value="">'.__('Action','rcl').'</option>
							<option value="deactivate">'.__('Deactivate','rcl').'</option>
							<option value="activate">'.__('To activate','rcl').'</option>
						</select>
						<input id="doaction" class="button action" type="submit" value="'.__('Apply','rcl').'" name="">
					</div>';
				$table .= '</div>
				<table class="wp-list-table widefat plugins" cellspacing="0">
				<thead>
					<tr>
						<th id="cb" class="manage-column column-cb check-column" style="" scope="col">';
						$table .= '<label class="screen-reader-text" for="cb-select-all-1">'.__('Select all','rcl').'</label>
						<input id="cb-select-all-1" type="checkbox">';
						$table .= '</th>
						<th id="name" class="manage-column column-name" style="" scope="col">'.__('Additions','rcl').'</th>
						<th id="description" class="manage-column column-description" style="" scope="col">'.__('Description','rcl').'</th>
					</tr>
				</thead>';

				foreach((array)$addons_data as $key=>$addon){
					if($active_addons&&isset($active_addons[$key])) $status = 1;
					else $status = 0;

                                        $ver = (isset($need_update[$key]))? version_compare($need_update[$key]['new-version'],$addon['version']): 0;

                                        $update = ($ver>0)? 'update': '';

					$table .= '<tr id="better-wp-security" class="'.($status ? "active" : "inactive" ).' '.$update.'">
						<th class="check-column" scope="row">';
							$table .= '<label class="screen-reader-text" for="checkbox_'.$key.'">'.__('Choose','rcl').' '.$addon['name'].'</label>
							<input id="checkbox_'.$key.'" type="checkbox" value="'.$key.'" name="checked[]">';
						$table .= '</th>
						<td class="plugin-title">
							<strong>'.$addon['name'].'</strong>
							<div class="row-actions visible">';
							if($active_addons&&isset($active_addons[$key])){
								$table .= '<span class="inactivate">
								<a title="'.__('Deactivate','rcl').'" href="'.wp_nonce_url( get_bloginfo('wpurl').'/?action-addon=update&status=deactivate&addon='.$key, 'action_addon' ).'">'.__('Deactivate','rcl').'</a>
								</span>';

								if($key=='magazin'){
									$table .= '|<span class="options">
									<a title="'.__('Settings','rcl').'" href="'.admin_url('admin.php?page=manage-wpm-options&options='.$key).'">'.__('Settings','rcl').'</a>
									</span>';
								}else{
									$table .= '|<span class="options">
									<a title="'.__('Settings','rcl').'" href="'.admin_url('admin.php?page=manage-wprecall&options='.$key).'">'.__('Settings','rcl').'</a>
									</span>';
								}
							}else{
								$table .= '<span class="inactivate">
								<a title="'.__('Activate','rcl').'" href="'.wp_nonce_url( admin_url('admin.php?action-addon=update&status=activate&addon='.$key), 'action_addon' ).'">'.__('Activate','rcl').'</a>
								</span>|
								<span class="inactivate">
								<a title="'.__('Delete','rcl').'" href="'.wp_nonce_url( admin_url('admin.php?action-addon=update&status=delete&addon='.$key), 'action_addon' ).'">'.__('Delete','rcl').'</a>
								</span>';
							}
							$table .= '</div>
						</td>
						<td class="column-description desc">
							<div class="plugin-description">
							<p>'.$addon['description'].'</p>
							</div>
							<div class="active second plugin-version-author-uri">
							'.__('Version','rcl').' '.$addon['version'];
                                                                if(isset($addon['author-uri'])) $table .= ' | '.__('Author','rcl').': <a title="'.__('Visit the page of the author','rcl').'" href="'.$addon['author-uri'].'" target="_blank">'.$addon['author'].'</a>';
                                                                if(isset($addon['add-on-uri'])) $table .= ' | <a title="'.__('Visit the page of the add-on','rcl').'" href="'.$addon['add-on-uri'].'" target="_blank">'.__('Page Add-on','rcl').'</a>';
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

        function get_actual_version($key,$version){
            global $active_addons;

            $status = (isset($active_addons[$key]))?1:0;

            $url = "http://wppost.ru/products-files/api/update.php"
                    . "?rcl-addon-action=version-check"
                    . "&host=".$_SERVER['SERVER_NAME']
                    . "&addon=".$key
                    . "&version=".$version
                    . "&status=".$status;

            $ver = 0;

            $xml = @simplexml_load_file($url);

            if(!$xml) return false;

            $act_version = (string)$xml->version;
            $ver = (version_compare($act_version,$version));
            if($ver>0) return $act_version;

            return $ver;
        }

	function rcl_removeDir( $path ){
            if ( $content_del_cat = glob( $path.'/*') ){
                foreach ( $content_del_cat as $object ){
                    if ( is_dir( $object ) ){
                        $this->rcl_removeDir( $object );
                        }else {
                            @chmod( $object, 0777 );
                            unlink( $object );
                        }
                    }
                }
            @chmod( $object, 0777 );
            rmdir( $path );
	}

	function update_status_addon_recall_activate ( ) {
	  if ( isset( $_GET['action-addon'] ) ) {
		if( !wp_verify_nonce( $_GET['_wpnonce'], 'action_addon' ) ) return false;

                global $wpdb, $user_ID, $active_addons;
		if ( ! current_user_can('activate_plugins') ) wp_die(__('You cant control polucheniya plugins on this site.','rcl'));

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

		$path = RCL_UPLOAD_PATH.'scripts/';
		if(!is_dir($path)){
			mkdir($path);
			chmod($path, 0755);
		}
		$filename = 'header-scripts.js';
		$file_src = $path.$filename;
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
		$path = RCL_UPLOAD_PATH.'scripts/';
		if(!is_dir($path)){
			mkdir($path);
			chmod($path, 0755);
		}
		$filename = 'footer-scripts.js';
		$file_src = $path.$filename;
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
			foreach((array)$_POST['checked'] as $key){
                            foreach($paths as $path){
                                if(file_exists($path.'/'.$key.'/index.php')){
                                    $active_addons[$key]['src'] = $path.'/'.$key.'/';
                                    if(file_exists($path.'/'.$key.'/activate.php')) include($path.'/'.$key.'/activate.php');
                                    break;
                                }
                            }
			}
			update_site_option('active_addons_recall',$active_addons);
			wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=activate') );exit;
		}
		if($_POST['group-addon-action']=='deactivate'){
			foreach((array)$_POST['checked'] as $key){
				foreach((array)$active_addons as $name=>$src){
                                    if($name!=$key){
                                        $new_active_list[$name] = $src;
                                    }else{
                                        foreach($paths as $path){
                                            if(file_exists($path.'/'.$key.'/deactivate.php')){
                                                include($path.'/'.$key.'/deactivate.php');
                                                break;
                                            }
                                        }
                                    }
				}

				$active_addons = '';
				$active_addons = $new_active_list;
				$new_active_list = '';
			}
			update_site_option('active_addons_recall',$active_addons);
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
                      wp_die(__('Unpacking of archive failed.','rcl'));
                }
            } else {
                    wp_die(__('ZIP archive not found.','rcl'));
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
	  if ( isset( $_POST['primary-rcl-options'] ) ) {
		if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rcl' ) ) return false;

		$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

		if($_POST['login_form_recall']==1&&!isset($_POST['page_login_form_recall'])){
			$_POST['page_login_form_recall'] = wp_insert_post(array('post_title'=>__('Login and register','rcl'),'post_content'=>'[loginform]','post_status'=>'publish','post_author'=>1,'post_type'=>'page','post_name'=>'login-form'));
		}

		foreach((array)$_POST as $key => $value){
			if($key=='primary-rcl-options') continue;
			$options[$key] = $value;
		}

		update_option('primary-rcl-options',$options);

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