<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action('admin_init','rcl_update_status_addon');
add_action('admin_init','rcl_update_status_group_addon');
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
            
            $path = wp_normalize_path($path);
            
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
                        if(isset($data['template'])) continue;
                        if(isset($data['custom-manager'])) continue;
                        if(isset($_POST['s'])&&$_POST['s']){
                            if (strpos(strtolower(trim($data['name'])), strtolower(trim($_POST['s']))) !== false) {
                                $this->addons_data[$namedir] = $data;
                                $this->addons_data[$namedir]['path'] = $addon_dir;
                            }
                            continue;
                        }
                        $this->addons_data[$namedir] = $data;
                        $this->addons_data[$namedir]['path'] = $addon_dir;
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
            if(isset($data['template'])) $add_ons[$namedir]['template'] = $data['template'];
            $add_ons[$namedir]['addon_name'] = $data['name'];
            $add_ons[$namedir]['addon_path'] = $data['path'];
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
        echo '.wp-list-table .column-addon_icon { width: 35px; }';
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
            case 'addon_icon':
                if(file_exists($item['addon_path'].'/icon.jpg')){
                   return '<img src="'.rcl_path_to_url($item['addon_path'].'/icon.jpg').'">';
                }
                break;
            case 'addon_name':
                $name = (isset($item['template']))? $item[ 'addon_name' ].' ('.__('Template','wp-recall').')': $item[ 'addon_name' ];
                return '<strong>'.$name.'</strong>';
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
            'addon_icon' => '',
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
        $name = (isset($item['template']))? $item[ 'addon_name' ].' ('.__('Template','wp-recall').')': $item[ 'addon_name' ];
        return sprintf('%1$s %2$s', '<strong>'.$name.'</strong>', $this->row_actions($actions) );
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
                    if(isset($data['author-uri'])) $content .= ' | '.__('Author','wp-recall').': <a title="'.__('Visit the author’s page','wp-recall').'" href="'.$data['author-uri'].'" target="_blank">'.$data['author'].'</a>';
                    if(isset($data['add-on-uri'])) $content .= ' | <a title="'.__('Visit the add-on page','wp-recall').'" href="'.$data['add-on-uri'].'" target="_blank">'.__('Add-on page','wp-recall').'</a>';
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

        echo '<tr id="addon-'.$item['ID'].'" class="'.$class.'">';
        $this->single_row_columns( $item );
        echo '</tr>';
        
        if($ver>0){
            $colspan = ($hidden = count($this->column_info[1]))? 5-$hidden: 5;
            
            echo '<tr class="addon-box plugin-update-tr '.$status.'" id="'.$item['ID'].'-update" data-slug="'.$item['ID'].'">'
                . '<td colspan="'.$colspan.'" class="plugin-update colspanchange">'
                    . '<div class="update-message notice inline notice-warning notice-alt">'
                    . '<p>'
                        . __('New version available','wp-recall').' '.$this->addon['name'].' '.$this->need_update[$item['ID']]['new-version'].'. ';
                        if(isset($this->addon['add-on-uri'])) echo ' <a href="#"  onclick=\'rcl_get_details_addon('.json_encode(array('slug'=>$item['ID'])).',this);return false;\' title="'.$this->addon['name'].'">'.__('view information about the version','wp-recall').'</a> или';
                        echo ' <a class="update-add-on" data-addon="'.$item['ID'].'" href="#">'.__('update automatically','wp-recall').'</a></div>'
                    . '</p>'
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

} //class

function rcl_init_upload_addon ( ) {
    if ( isset( $_POST['install-addon-submit'] ) ) {
          if( !wp_verify_nonce( $_POST['_wpnonce'], 'install-addons-rcl' ) ) return false;
          rcl_upload_addon();
    }
}

function rcl_update_status_addon ( ) {
	
    $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
    if( 'manage-addon-recall' != $page ) return;

    if ( isset($_GET['addon'])&&isset($_GET['action']) ) {

        global $wpdb, $user_ID, $active_addons;
        if ( ! current_user_can('activate_plugins') ) wp_die(__('Insufficient rights to install plugins on this site.','wp-recall'));

        $addon = $_GET['addon'];
        $action = rcl_wp_list_current_action();

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
    
 function rcl_update_status_group_addon ( ) {
	
    $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
    if( 'manage-addon-recall' != $page ) return;

    if ( rcl_wp_list_current_action() && isset( $_POST['addons'] )&& is_array($_POST['addons']) ) {

        global $wpdb,$user_ID,$active_addons;

        $action = rcl_wp_list_current_action();

        $paths = rcl_get_addon_paths();

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

            wp_redirect( admin_url('admin.php?page=manage-addon-recall&update-addon=deactivate') );exit;
        }
    }
}

function rcl_upload_addon(){

    $paths = rcl_get_addon_paths();

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
        'default' => 100,
        'option' => 'addons_per_page'
    );
    
    add_screen_option( $option, $args );
    $Rcl_Addons_Manager = new Rcl_Addons_Manager();
}

