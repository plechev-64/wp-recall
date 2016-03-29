<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action('admin_init',array('Rcl_Addons_Manager','update_status_addon'));
add_action('admin_init',array('Rcl_Addons_Manager','update_status_group_addon'));
add_action('admin_init','rcl_init_upload_addon');

class Rcl_Addons_Manager extends WP_List_Table {
	
    var $addon = array();
    var $addons_data = array();    
    var $need_update = array();
    var $column_info = array();
		
    function __construct(){
        global $status, $page, $active_addons;

        parent::__construct( array(
                'singular'  => __( 'add-on', 'wp-recall' ),
                'plural'    => __( 'add-ons', 'wp-recall' ),
                'ajax'      => false
        ) );
        
        $this->need_update = get_option('rcl_addons_need_update');
        $this->column_info = $this->get_column_info();

        add_action( 'admin_head', array( &$this, 'admin_header' ) ); 

    }
    
    function get_addons_data(){
        $paths = array(RCL_PATH.'add-on',RCL_TAKEPATH.'add-on') ;
        
        $add_ons = array();
        foreach($paths as $path){
            if(file_exists($path)){
                $addons = scandir($path,1);

                foreach((array)$addons as $namedir){
                    $addon_dir = $path.'/'.$namedir;
                    $index_src = $addon_dir.'/index.php';
                    if(!is_dir($addon_dir)||!file_exists($index_src)) continue;
                    $info_src = $addon_dir.'/info.txt';
                    if(file_exists($info_src)){
                        $info = file($info_src);
                        $data = rcl_parse_addon_info($info);
                        if(isset($_POST['s'])&&$_POST['s']){                           
                            if (strpos($data['name'], $_POST['s']) !== false) {
                                $this->addons_data[$namedir] = $data;
                            }

                            flush();
                            continue;
                        }
                        $this->addons_data[$namedir] = $data;                        
                        flush();
                    }
                    
                }
            }
        }
    }
    
    function get_addons_content(){
        global $active_addons;

        $add_ons = array();
        foreach($this->addons_data as $namedir=>$data){
            $desc = $this->get_description_column($data);
            $add_ons[$namedir]['ID'] = $namedir;
            $add_ons[$namedir]['addon_name'] = '<strong>'.$data['name'].'</strong>';
            $add_ons[$namedir]['addon_status'] = ($active_addons&&isset($active_addons[$namedir]))? 1: 0;
            $add_ons[$namedir]['addon_description'] = $desc;           
        }
        
        return $add_ons;
    }
	
    function admin_header() {
        
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'manage-addon-recall' != $page ) return;
        
        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-addon_name { width: 25%; }';
        echo '.wp-list-table .column-addon_status { width: 10%; }';
        echo '.wp-list-table .column-addon_description { width: 60%;}';
        echo '</style>';
        
    }

    function no_items() {
        _e( 'No addons found.', 'wp-recall' );
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'addon_name':
                return '<strong>'.$item[ $column_name ].'</strong>';
            case 'addon_status':
                if($item[ $column_name ]){
                    return __( 'Active', 'wp-recall' );
                }else{
                    return __( 'Inactive', 'wp-recall' );
                }
            case 'addon_description':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }

    function get_sortable_columns() {
      $sortable_columns = array(
            'addon_name'  => array('addon_name',false),
            'addon_status' => array('addon_status',false)
      );
      return $sortable_columns;
    }
	
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'addon_name' => __( 'Add-ons', 'wp-recall' ),
            'addon_status'    => __( 'Status', 'wp-recall' ),
            'addon_description'      => __( 'Description', 'wp-recall' )
        );
        return $columns;
    }

    function usort_reorder( $a, $b ) {      
      $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'addon_name';      
      $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';      
      $result = strcmp( $a[$orderby], $b[$orderby] );     
      return ( $order === 'asc' ) ? $result : -$result;
    }

    function column_addon_name($item){

        $actions = array(
            'delete'    => sprintf('<a href="?page=%s&action=%s&addon=%s">'.__( 'Delete', 'wp-recall' ).'</a>',$_REQUEST['page'],'delete',$item['ID'])
        );
        
        if($item['addon_status']==1) $actions['deactivate'] = sprintf('<a href="?page=%s&action=%s&addon=%s">'.__( 'Deactivate', 'wp-recall' ).'</a>',$_REQUEST['page'],'deactivate',$item['ID']);
        else $actions['activate'] = sprintf('<a href="?page=%s&action=%s&addon=%s">'.__( 'Activate', 'wp-recall' ).'</a>',$_REQUEST['page'],'activate',$item['ID']);
        
        return sprintf('%1$s %2$s', $item['addon_name'], $this->row_actions($actions) );
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => __( 'Delete', 'wp-recall' ),
            'activate'    => __( 'Activate', 'wp-recall' ),
            'deactivate'    => __( 'Deactivate', 'wp-recall' ),
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="addons[]" value="%s" />', $item['ID']
        );    
    }
    
    function get_description_column($data){
        $content = '<div class="plugin-description">
                <p>'.$data['description'].'</p>
            </div>
            <div class="active second plugin-version-author-uri">
            '.__('Version','wp-recall').' '.$data['version'];
                    if(isset($data['author-uri'])) $content .= ' | '.__('Author','wp-recall').': <a title="'.__('Visit the page of the author','wp-recall').'" href="'.$data['author-uri'].'" target="_blank">'.$data['author'].'</a>';
                    if(isset($data['add-on-uri'])) $content .= ' | <a title="'.__('Visit the page of the add-on','wp-recall').'" href="'.$data['add-on-uri'].'" target="_blank">'.__('Page Add-on','wp-recall').'</a>';
            $content .= '</div>';
        return $content;
    }
    
    function get_table_classes() {
        return array( 'widefat', 'fixed', 'striped', 'plugins', $this->_args['plural'] );
    }
    
    function single_row( $item ) {
        
        $this->addon = $this->addons_data[$item['ID']];
        $status = ($item['addon_status'])? 'active': 'inactive';        
        $ver = (isset($this->need_update[$item['ID']]))? version_compare($this->need_update[$item['ID']]['new-version'],$this->addon['version']): 0;
        $class = $status;
        $class .= ($ver>0)? ' update': '';

        echo '<tr class="'.$class.'">';
        $this->single_row_columns( $item );
        echo '</tr>';
        
        if($ver>0){
            $colspan = ($hidden = count($this->column_info[1]))? 4-$hidden: 4;
            
            echo '<tr class="plugin-update-tr '.$status.'" id="'.$item['ID'].'-update" data-slug="'.$item['ID'].'">'
                . '<td colspan="'.$colspan.'" class="plugin-update colspanchange">'
                    . '<div class="update-message">'
                        . 'Доступна свежая версия '.$this->addon['name'].' '.$this->need_update[$item['ID']]['new-version'].'. ';
                        if(isset($this->addon['add-on-uri'])) echo 'Можно <a href="'.$this->addon['add-on-uri'].'"  title="'.$this->addon['name'].'">посмотреть информацию о версии '.$xml->version.'</a>';
                    echo 'или <a class="update-add-on" data-addon="'.$item['ID'].'" href="#">Обновить автоматически</a></div>'
                . '</td>'
            . '</tr>';
        }
    }
	
    function prepare_items() {
        
        $addons = $this->get_addons_content();

        $this->_column_headers = $this->get_column_info();
        usort( $addons, array( &$this, 'usort_reorder' ) );

        $per_page = $this->get_items_per_page('addons_per_page', 20);
        $current_page = $this->get_pagenum();
        $total_items = count( $addons );

        $this->set_pagination_args( array(
                'total_items' => $total_items,
                'per_page'    => $per_page
        ) );

        $this->items = array_slice( $addons,( ( $current_page-1 )* $per_page ), $per_page );

    }

    function update_status_addon ( ) {
        //print_r($_GET);
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'manage-addon-recall' != $page ) return;
        
        if ( isset($_GET['addon'])&&isset($_GET['action']) ) {
              //if( !wp_verify_nonce( $_GET['_wpnonce'], 'action_addon' ) ) return false;

              global $wpdb, $user_ID, $active_addons;
              if ( ! current_user_can('activate_plugins') ) wp_die(__('You cant control polucheniya plugins on this site.','wp-recall'));

              $addon = $_GET['addon'];
              $action = parent::current_action();

              if($action=='activate'){
                  rcl_activate_addon($addon);
                  wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=activate') );exit;
              }
              if($action=='deactivate'){
                  rcl_deactivate_addon($addon);
                  wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=deactivate') );exit;
              }
              if($action=='delete'){
                 rcl_delete_addon($addon);
                 wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=delete') );exit;
              }
        }
    }
    
    function update_status_group_addon ( ) {
        
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'manage-addon-recall' != $page ) return;

	  if ( parent::current_action() && isset( $_POST['addons'] )&& is_array($_POST['addons']) ) {
              global $wpdb,$user_ID,$active_addons;

                $action = parent::current_action();

                $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

		if($action=='activate'){
                    foreach($_POST['addons'] as $addon){
                        rcl_activate_addon($addon);
                    }			
                    wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=activate') );exit;
		}
                
		if($action=='deactivate'){
                    foreach($_POST['addons'] as $addon){
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
                
                if($action=='delete'){
                    foreach($_POST['addons'] as $addon){
                            foreach((array)$active_addons as $name=>$data){
                                if($name!=$addon){
                                    $new_active_list[$name] = $data;
                                }else{
                                    rcl_delete_addon($addon);                                   
                                }
                            }

                            $active_addons = '';
                            $active_addons = $new_active_list;
                            $new_active_list = '';
                    }
                    //update_site_option('rcl_active_addons',$active_addons);
                    wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=deactivate') );exit;
		}
	  }
	}

} //class

function rcl_init_upload_addon ( ) {
    if ( isset( $_POST['install-addon-submit'] ) ) {
          if( !wp_verify_nonce( $_POST['_wpnonce'], 'install-addons-rcl' ) ) return false;
          rcl_upload_addon();
    }
}

function rcl_upload_addon(){

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

add_filter('set-screen-option', 'rcl_manager_set_option', 10, 3);
function rcl_manager_set_option($status, $option, $value) {
  return $value;
}

function rcl_add_options_addons_manager() {
    global $Rcl_Addons_Manager;
    
    $option = 'per_page';
    $args = array(
        'label' => __( 'Add-ons', 'wp-recall' ),
        'default' => 20,
        'option' => 'addons_per_page'
    );
    add_screen_option( $option, $args );
    $Rcl_Addons_Manager = new Rcl_Addons_Manager();
}

function rcl_render_addons_manager(){
    global $active_addons,$Rcl_Addons_Manager;
        
    $Rcl_Addons_Manager->get_addons_data();
    
    $cnt_all = count($Rcl_Addons_Manager->addons_data);
    $cnt_act = count($active_addons);
    $cnt_inact = $cnt_all - $cnt_act;

    echo '</pre><div class="wrap">'; 
    
    echo '<div id="icon-plugins" class="icon32"><br></div>
        <h2>'.__('Add-ons Wp-Recall','wp-recall').'</h2>';

        if(isset($_GET['update-addon'])){
                switch($_GET['update-addon']){
                    case 'activate': $text_notice = __('Addition <strong>activated</strong>. It is possible that on the settings page of Wp-Recall new settings','wp-recall'); $type='updated'; break;
                    case 'deactivate': $text_notice = __('Addition <strong>deactivated</strong>.','wp-recall'); $type='updated'; break;
                    case 'delete': $text_notice = __('Files and data additions have been <strong>removed</strong>.','wp-recall'); $type='updated'; break;
                    case 'error-info': $text_notice = __('The Supplement has not been loaded. Add missing the correct header.','wp-recall'); $type='error'; break;
                    case 'error-activate': $text_notice = $_GET['error-text']; $type='error'; break;
                }
                
                rcl_update_scripts();
                rcl_minify_style();
                
                echo '<div id="message" class="'.$type.'"><p>'.$text_notice.'</p></div>';
        }

        if(isset($_POST['save-rcl-key'])){
            if( wp_verify_nonce( $_POST['_wpnonce'], 'add-rcl-key' ) ){
                update_option('rcl-key',$_POST['rcl-key']);
                echo '<div id="message" class="'.$type.'"><p>'.__('Key is stored','wp-recall').'!</p></div>';
            }
        }

        echo '<h4>'.__('RCLKEY','wp-recall').'</h4>
        <form action="" method="post">
                '.__('Enter RCLKEY','wp-recall').' <input type="text" name="rcl-key" value="'.get_option('rcl-key').'">
                <input class="button" type="submit" value="'.__('Save','wp-recall').'" name="save-rcl-key">
                '.wp_nonce_field('add-rcl-key','_wpnonce',true,false).'
        </form>
        <p class="install-help">'.__('He will need to update the add-ons here. Get it , you can profile your account online <a href="http://codeseller.ru/" target="_blank">http://codeseller.ru</a>','wp-recall').'</p>';
            
        
    echo '
        <h4>'.__('To install the add-on to Wp-Recall format .zip','wp-recall').'</h4>
        <p class="install-help">'.__('If you have the archive add-on for wp-recall format .zip, here you can download and install it.','wp-recall').'</p>
        <form class="wp-upload-form" action="" enctype="multipart/form-data" method="post">
                <label class="screen-reader-text" for="addonzip">'.__('Plugin archive','wp-recall').'</label>
                <input id="addonzip" type="file" name="addonzip">
                <input id="install-plugin-submit" class="button" type="submit" value="'.__('To install','wp-recall').'" name="install-addon-submit">
                '.wp_nonce_field('install-addons-rcl','_wpnonce',true,false).'
        </form>

        <ul class="subsubsub">
                <li class="all"><b>'.__('All','wp-recall').'<span class="count">('.$cnt_all.')</span></b>|</li>
                <li class="active"><b>'.__('Active','wp-recall').'<span class="count">('.$cnt_act.')</span></b>|</li>
                <li class="inactive"><b>'.__('Inactive','wp-recall').'<span class="count">('.$cnt_inact.')</span></b></li>
        </ul>';
    
    $Rcl_Addons_Manager->prepare_items(); ?>

    <form method="post">
    <input type="hidden" name="page" value="manage-addon-recall">
    <?php
    $Rcl_Addons_Manager->search_box( 'Search by name', 'search_id' );
    $Rcl_Addons_Manager->display(); 
    echo '</form></div>'; 
}

