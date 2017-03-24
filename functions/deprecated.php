<?php

add_filter('rcl_public_form','rcl_edit_old_filter_public_form',10,2);
function rcl_edit_old_filter_public_form($content,$formdata){
    return apply_filters('public_form_rcl',$content,$formdata);
}

//выправляем ситуацию со старым хранением 
//данных в опциях произвольных полей
function rcl_edit_old_option_fields($option, $type = false){
    
    $types = array(
        'select',
        'multiselect',
        'checkbox',
        'radio',
    );
    
    if($type && !in_array($type,$types)) return $option;
    
    if($option && !is_array($option)){
            
        $array = explode('#',$option);

        if($array && is_array($array)){
            $option = $array;
        }

    }
    
    return $option;
}

/*15.0.0*/
class RCL_navi{

    public $inpage;
    public $navi;
    public $cnt_data;
    public $num_page;
    public $get;
    public $page;
    public $offset;
    public $g_name;

    function __construct($inpage,$cnt_data,$get=false,$page=false,$getname='navi'){
        $this->navi=1;
        $this->g_name=$getname;
        if(isset($_GET[$this->g_name])) $this->navi = $_GET[$this->g_name];
        if($page) $this->navi = $page;
        $this->inpage = $inpage;
        $this->cnt_data = $cnt_data;
        $this->get = $get;
        $this->offset = ($this->navi-1)*$this->inpage;
        $this->limit();
    }

    function limit(){
        $limit_us = $this->offset.','.$this->inpage;
        if($this->inpage) $this->num_page = ceil($this->cnt_data/$this->inpage);
        else $this->num_page = 1;
        return $limit_us;
    }

    function navi(){
        global $post,$group_id,$user_LK;
        $class = 'rcl-navi';
        $page_navi = '';

        if($group_id){
                $prm = get_term_link((int)$group_id,'groups' );
                if($_GET['group-page']) $prm = rcl_format_url($prm).'group-page='.$_GET['group-page'];
        }else if($user_LK){
            $prm = get_author_posts_url($user_LK);
        }else{
            if(isset($post))$prm = get_permalink($post->ID);
        }

        if($this->inpage&&$this->cnt_data>$this->inpage){

            if(isset($prm))$redirect_url = rcl_format_url($prm);
            else $redirect_url = '#';

            if($redirect_url=='#'||$group_id) $class .= ' ajax-navi';

            $page_navi = '<div class="'.$class.'">';
            $next = $this->navi + 3;
            $prev = $this->navi - 4;
            if($prev==1) $page_navi .= '<a href="'.$redirect_url.$this->g_name.'=1'.$this->get.'">1</a>';
            for($a=1;$a<=$this->num_page;$a++){
                if($a==1&&$a<=$prev&&$prev!=1) $page_navi .= '<a href="'.$redirect_url.$this->g_name.'=1'.$this->get.'">1</a> ... ';
                if($prev<$a&&$a<=$next){
                    if($this->navi==$a) $page_navi .= '<span>'.$a.'</span>';
                    else $page_navi .= '<a href="'.$redirect_url.$this->g_name.'='.$a.''.$this->get.'">'.$a.'</a>';
                }
            }
            if($next<$this->num_page&&$this->num_page!=$next+1) $page_navi .= ' ... <a href="'.$redirect_url.'navi='.$this->num_page.''.$this->get.'">'.$this->num_page.'</a>';
            if($this->num_page==$next+1) $page_navi .= '<a href="'.$redirect_url.$this->g_name.'='.$this->num_page.''.$this->get.'">'.$this->num_page.'</a>';
            $page_navi .= '</div>';
        }

        return $page_navi;
    }
}

function rcl_navi_admin($inpage,$cnt_data,$page,$page_id,$get_data){

    $page = (isset($_GET['paged']))? $_GET['paged']: 1;

    $num_page = ceil($cnt_data/$inpage);

    $prev = $page-1;
    $next = $page+1;
    
    $pagination = '<div class="tablenav">
        <div class="tablenav-pages">
            <span class="pagination-links">';

            if($page!=1)$pagination .= '<a class="first-page" href="'.admin_url('admin.php?page='.$page_id.''.$get_data).'" title="'.__('Go to the first page','wp-recall').'">«</a>
            <a class="prev-page" href="'.admin_url('admin.php?page='.$page_id.''.$get_data.'&paged='.$prev).'" title="'.__('Go to the previous page','wp-recall').'">‹</a>';
            $pagination .= '<span class="paging-input">
                    '.$page.' '.__('of','wp-recall').' <span class="total-pages">'.$num_page.'</span>
            </span>';
            if($page!=$num_page)$pagination .= '<a class="next-page" href="'.admin_url('admin.php?page='.$page_id.''.$get_data.'&paged='.$next).'" title="'.__('Go to the next page','wp-recall').'">›</a>
            <a class="last-page" href="'.admin_url('admin.php?page='.$page_id.''.$get_data.'&paged='.$num_page).'" title="'.__('Go to the last page','wp-recall').'">»</a>

            </span>
        </div>
        <input type="button" value="'.__('Go back','wp-recall').'" onClick="history.back()">
    </div>';

    return $pagination;
}