<?php
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

function rcl_navi($inpage,$cnt_data,$num_page,$page=false,$get=false){

		global $post,$group_id;
		if(isset($_GET['navi'])) $navi = $_GET['navi'];
                else $navi=1;
		if($page) $navi = $page;

		if($group_id){
			$prm = get_term_link((int)$group_id,'groups' );
			if($_GET['group-page']) $prm = rcl_format_url($prm).'group-page='.$_GET['group-page'];
		}else{ $prm = get_permalink($post->ID);}

		if($inpage&&$cnt_data>$inpage){

			$redirect_url = rcl_format_url($prm);

            $page_navi = '<div class="rcl-navi">';
            $next = $navi + 3;
            $prev = $navi - 4;
            if($prev==1) $page_navi .= '<a href="'.$redirect_url.'navi=1'.$get.'">1</a>';
            for($a=1;$a<=$num_page;$a++){
                if($a==1&&$a<=$prev&&$prev!=1) $page_navi .= '<a href="'.$redirect_url.'navi=1'.$get.'">1</a> ... ';
                if($prev<$a&&$a<=$next){
                    if($navi==$a) $page_navi .= '<span>'.$a.'</span>';
                    else $page_navi .= '<a href="'.$redirect_url.'navi='.$a.''.$get.'">'.$a.'</a>';
                }
            }
            if($next<$num_page&&$num_page!=$next+1) $page_navi .= ' ... <a href="'.$redirect_url.'navi='.$num_page.''.$get.'">'.$num_page.'</a>';
            if($num_page==$next+1) $page_navi .= '<a href="'.$redirect_url.'navi='.$num_page.''.$get.'">'.$num_page.'</a>';
            $page_navi .= '</div>';
        }

        return $page_navi;
}

function rcl_get_ajax_pagenavi($userid,$post_type){
	global $wpdb;

	$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->base_prefix."posts WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft')",$userid,$post_type));
	if(is_multisite()){
		$blog_list = get_blog_list( 0, 'all' );

		foreach ($blog_list as $blog) {
			$pref = $wpdb->base_prefix.$blog['blog_id'].'_posts';
			$count += $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$pref." WHERE post_author='%d' AND post_type='%s' AND post_status NOT IN ('draft','auto-draft')",$userid,$post_type));
		}

	}
        if(!$count) return false;
	$in_page = 20;
        $page = 0;
	$pages = ceil($count/$in_page);

	$navi = '<ul id="'.$post_type.'-pagenavi" class="pagenavi-rcl">';
	for($a=0;$a<$pages;$a++){
		$navi .= '<li><a type="'.$post_type.'" data="'.$a*$in_page.'" class="sec_block_button';
		if($a==0)$navi .= ' active';
		$navi .= '" href="#">'.++$page.'</a></li>';
	}
	$slider = str_replace('-','',$post_type);
	$navi .= '</ul>
	<script>
	jQuery(function($){
		$("#'.$post_type.'-pagenavi").bxSlider({
            pager:false,
            minSlides: 1,
            maxSlides: 20,
            slideWidth: 25,
            infiniteLoop:false,
            slideMargin: 0,
            moveSlides:10
		});
	});
	</script>';
	return $navi;
}

function rcl_navi_admin($inpage,$cnt_data,$page,$page_id,$get_data){

	if($_GET['paged']) $page = $_GET['paged'];
	else $page=1;

	$num_page = ceil($cnt_data/$inpage);

	$prev = $page-1;
	$next = $page+1;
	$pagination .= '<div class="tablenav">
		<div class="tablenav-pages">
			<span class="pagination-links">';

			if($page!=1)$pagination .= '<a class="first-page" href="'.admin_url('admin.php?page='.$page_id.''.$get_data).'" title="Перейти на первую страницу">«</a>
			<a class="prev-page" href="'.admin_url('admin.php?page='.$page_id.''.$get_data.'&paged='.$prev).'" title="Перейти на предыдущую страницу">‹</a>';
			$pagination .= '<span class="paging-input">
				'.$page.' из <span class="total-pages">'.$num_page.'</span>
			</span>';
			if($page!=$num_page)$pagination .= '<a class="next-page" href="'.admin_url('admin.php?page='.$page_id.''.$get_data.'&paged='.$next).'" title="Перейти на следующую страницу">›</a>
			<a class="last-page" href="'.admin_url('admin.php?page='.$page_id.''.$get_data.'&paged='.$num_page).'" title="Перейти на последнюю страницу">»</a>

			</span>
		</div>
	</div>
	<input type="button" value="Назад" onClick="history.back()">';

	return $pagination;
}