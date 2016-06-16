<?php
/*14.0.0*/
add_action('wp_head','rcl_head_js_data',1);
function rcl_head_js_data(){
    global $user_ID;
    $data = "<script>
	var user_ID = $user_ID;
	var wpurl = '".preg_quote(trailingslashit(get_bloginfo('wpurl')),'/:')."';
	var rcl_url = '".preg_quote(RCL_URL,'/:')."';
	</script>\n";
    echo $data;
}

/*14.0.0*/
function rcl_get_user_money($user_id=false){
    global $wpdb,$user_ID;
    _deprecated_function( 'rcl_get_user_money', '14.0.0', 'rcl_get_user_balance' );
    if(!$user_id) $user_id = $user_ID;
    return $wpdb->get_var($wpdb->prepare("SELECT user_balance FROM ".RMAG_PREF."users_balance WHERE user_id='%d'",$user_id));
}

/*14.0.0*/
function rcl_update_user_money($newmoney,$user_id=false){
    global $user_ID,$wpdb;
    _deprecated_function( 'rcl_update_user_money', '14.0.0', 'rcl_update_user_balance' );
    if(!$user_id) $user_id = $user_ID;

    $money = rcl_get_user_money($user_id);

    if(isset($money)) return $wpdb->update(RMAG_PREF .'users_balance',
            array( 'user_balance' => $newmoney ),
            array( 'user_id' => $user_id )
        );

    return rcl_add_user_money($newmoney,$user_id);
}

/*14.0.0*/
function rcl_add_user_money($money,$user_id=false){
    global $wpdb,$user_ID;
    _deprecated_function( 'rcl_add_user_money', '14.0.0', 'rcl_add_user_balance' );
    if(!$user_id) $user_id = $user_ID;
    return $wpdb->insert( RMAG_PREF .'users_balance',
	array( 'user_id' => $user_id, 'user_balance' => $money ));
}

function get_key_addon_rcl($path_parts){
    _deprecated_function( 'get_key_addon_rcl', '14.0.0', 'rcl_key_addon' );
    return rcl_key_addon($path_parts);
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