<?php

class Rcl_Userlist{

	public $exclude;
	public $orderby;
	public $order;
	public $limit;
        public $group;
        public $where;
        public $inpage;
        public $group_admin;
        public $userlist;

	function __construct(){

	}

	function get_args(){
		if(isset($_GET['name-user'])) $username = sanitize_user($_GET['name-user']);
		if($_GET['orderuser']==1){ //по имени
                    $args = array(
                        'meta_query'   => array(
                            'relation' => 'OR',
                            array(
                                    'key' => 'first_name',
                                    'value' => $username,
                                    'compare' => 'LIKE',
                            ),
                            array(
                                    'key' => 'last_name',
                                    'value' => $username,
                                    'compare' => 'LIKE',
                            )
                        )
                    );
		}else{ //по логину
			$args = array( 'search'=>'*'.$username.'*');
		}
		return $args;
	}

	function get_users_lst($us_data,$key=false){
		$a = 0;
                $us_lst = '';
		if(is_array($us_data)){
			foreach((array)$us_data as $id=>$data){
				if(++$a>1)$us_lst .= ',';
                                if($key=='data') $us_lst .= $data;
				else $us_lst .= $id;
			}
		}
		if(is_object($us_data)){
			foreach($us_data as $user){
				if(++$a>1)$us_lst .= ',';
				$us_lst .= $user->$key;
			}
		}
		return $us_lst;
	}

	function get_actions($us_lst=false){
		global $wpdb;
		$orderby = '';
                $limit = '';
                $in = '';
                $exclude = '';
		if($us_lst) $in = "user IN ($us_lst)";
		if(isset($this->exclude)){
			if($in) $exclude = "AND ";
			$exclude .= "user NOT IN ($this->exclude)";
		}
		if($exclude||$in) $where = "WHERE $in $exclude";
		if($this->orderby) $orderby = "ORDER BY $this->orderby $this->order";
		if($this->limit) $limit = "LIMIT $this->limit";
		$rcl_action_users = $wpdb->get_results("SELECT user,time_action FROM ".RCL_PREF."user_action $where $orderby $limit");
		if(!$rcl_action_users){
			$exclude = '';
			if($us_lst) $in = "ID IN ($us_lst)";
			if(isset($this->exclude)){
				if($in) $exclude = " AND ";
				$exclude .= "ID NOT IN ($this->exclude)";
			}
			if($exclude||$in) $where = "WHERE $in $exclude";

			$rcl_action_users = $wpdb->get_results("
			SELECT
				us.ID AS user,us.user_registered AS time_action
			FROM
				".$wpdb->prefix."users AS us
			WHERE
				us.ID NOT IN (SELECT ua.user FROM ".RCL_PREF."user_action AS ua)");
			//print_r($rcl_action_users); exit;
		}
		return $rcl_action_users;
	}

	function get_rayts($us_lst=false){
		global $wpdb;
		$exclude = '';
                $orderby = '';
                $limit = '';
                $in = null;
		if($us_lst) $in = "user_id IN ($us_lst)";
		if(isset($this->exclude)){
			if($in) $exclude = "AND ";
			$exclude .= "user_id NOT IN ($this->exclude)";
		}
		if($exclude||$in) $where = "WHERE $in $exclude";
		if($this->orderby) $orderby = "ORDER BY CAST($this->orderby AS DECIMAL) $this->order";
		if($this->limit) $limit = "LIMIT $this->limit";

		$rayt_users = $wpdb->get_results("SELECT user_id,rating_total FROM ".RCL_PREF."rating_users $where $orderby $limit");

		return $rayt_users;
	}

	function get_usersdata($us_data,$object,$id,$name,$key){
		foreach($object as $us){
                    $us_data[$us->$id][$name] = $us->$key;
		}
		return $us_data;
	}

	function get_usdata_actions($us_data,$us_lst=false){
		$rcl_action_users = $this->get_actions($us_lst);
		$us_data = $this->get_usersdata($us_data,$rcl_action_users,'user','user_action','time_action');
		return $us_data;
	}

	function get_usdata_rayts($us_data,$us_lst=false){
		$rayt_users = $this->get_rayts($us_lst);
                //print_r($rayt_users);
		$us_data = $this->get_usersdata($us_data,$rayt_users,'user_id','user_rayting','rating_total');
		return $us_data;
	}

        function search_request(){
            if(isset($_GET['search-user'])){
                $rqst = array();
                foreach($_GET as $k=>$v){
                    if($k=='navi'||$k=='filter') continue;
                    $rqst[] = $k.'='.$v;
                }
                return $rqst;
            }
            return false;
        }

        function get_usdata($orderby,$us_data='',$us_lst=false){
            $func = 'get_'.$orderby.'_data';
            return $this->$func($us_data,$us_lst);
        }

        function get_user_registered_data(){
            global $wpdb;
            $users = $wpdb->get_results("SELECT ID,display_name,user_registered FROM $wpdb->users WHERE ID NOT IN ($this->exclude) $this->where ORDER BY $this->orderby $this->order LIMIT $this->limit");
            return $this->get_usersdata(false,$users,'ID','user_register','user_registered');
        }

        function get_rating_total_data($us_data,$us_lst){
            $us_data = $this->get_usdata_rayts($us_data,$us_lst);
            $us_lst = $this->get_users_lst($us_data);
            $this->orderby = false;
            $this->limit = false;
            return $this->get_usdata_actions($us_data,$us_lst);
        }

        function get_time_action_data($us_data,$us_lst){
            $us_data = $this->get_usdata_actions($us_data,$us_lst);
            //print_r($us_data);exit;
            $this->orderby = false;
            return $this->get_usdata_rayts($us_data,$us_lst);
        }

        function get_comments_count_data($us_data,$us_lst){
            global $wpdb;

            $users = $wpdb->get_results("
                SELECT COUNT(user_id) AS comments_count, user_id, comment_author
                FROM ".$wpdb->prefix."comments
                WHERE comment_approved = 1 $us_lst GROUP BY user_id ORDER BY $this->orderby $this->order LIMIT $this->limit"
            );

            return $this->get_usersdata($us_data,$users,'user_id','user_comments','comments_count');
        }

        function get_post_count_data($us_data,$us_lst){
            global $wpdb;

			$query = "SELECT COUNT(post_author) AS post_count, post_author
                FROM (select * from $wpdb->posts order by ID desc) as pc
                WHERE post_status = 'publish' $us_lst GROUP BY post_author ORDER BY $this->orderby $this->order LIMIT $this->limit";

            $users = $wpdb->get_results($query);

            return $this->get_usersdata($us_data,$users,'post_author','user_posts','post_count');
        }

        function get_feeds_data(){
            global $wpdb,$user_ID;
            $sql = $wpdb->prepare("SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = 'rcl_feed' AND user_id = '%d'",$user_ID);
            $users = $wpdb->get_results($sql." ORDER BY umeta_id $this->order LIMIT $this->limit");

            if(!$limit){
				$rclnavi->cnt_data = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'rcl_feed' AND user_id = '%d'",$user_ID));
				$rclnavi->num_page = ceil($rclnavi->cnt_data/$this->inpage);
            }

            if($users) return $this->get_usersdata($us_data,$users,'meta_value','feed','meta_value');
            return false;
        }

	function add_post_count_data($us_data,$us_lst){
			global $wpdb,$user_ID;
			$query = "SELECT COUNT(post_author) AS post_count, post_author
                FROM $wpdb->posts
                WHERE post_status = 'publish' AND post_author IN ($us_lst) AND post_type NOT IN ('attachment') GROUP BY post_author";
			$postdata = $wpdb->get_results($query);
			return $this->get_usersdata($us_data,$postdata,'post_author','user_posts','post_count');
	}

	function add_comments_count_data($us_data,$us_lst){
            global $wpdb;

            $users = $wpdb->get_results("
                SELECT COUNT(user_id) AS comments_count, user_id, comment_author
                FROM ".$wpdb->prefix."comments
                WHERE comment_approved = 1 AND user_id IN ($us_lst) GROUP BY user_id"
            );

            return $this->get_usersdata($us_data,$users,'user_id','user_comments','comments_count');
        }

	function add_user_registered_data($us_data,$us_lst){
            global $wpdb;
            $users = $wpdb->get_results("SELECT ID,display_name,user_registered FROM $wpdb->users WHERE ID IN ($us_lst)");
            return $this->get_usersdata($us_data,$users,'ID','user_register','user_registered');
        }
}

function rcl_setup_datauser($userdata){
    global $user;
    $user = (object)$userdata;
    return $user;
}

function rcl_user_name(){
    global $user;
    echo $user->display_name;
}

function rcl_user_url(){
    global $user;
    echo get_author_posts_url($user->user_id);
}

function rcl_user_avatar($size=50){
    global $user;
    echo get_avatar($user->user_id,$size);
}

function rcl_user_rayting(){
    global $user;
    if(!function_exists('rcl_get_rating_block')) return false;
    $rtng = (isset($user->user_rayting)&&$user->user_rayting)? $user->user_rayting: 0;
    echo rcl_rating_block(array('value'=>$rtng));
}

function rcl_user_action($type=1){
    global $user;
    switch($type){
        case 1: $last_action = rcl_get_useraction($user->user_action);
                if(!$last_action) echo '<span class="status_user online"><i class="fa fa-circle"></i></span>';
                else echo '<span class="status_user offline" title="'.__('not online','rcl').' '.$last_action.'"><i class="fa fa-circle"></i></span>';
        break;
        case 2: echo rcl_get_miniaction($user->user_action); break;
    }
}

function rcl_user_description(){
    global $user;
    if(!$user->description) return false;
    echo '<div class="ballun-status">
        <span class="ballun"></span>
        <p class="status-user-rcl">'.nl2br(esc_textarea($user->description)).'</p>
    </div>';
}

add_action('user_description','rcl_user_comments');
function rcl_user_comments(){
    global $user;
    if(!isset($user->user_comments)||!$user->user_comments) $user->user_comments = 0;
    echo '<span class="filter-data"><i class="fa fa-comment"></i>'.__('Comments','rcl').': '.$user->user_comments.'</span>';
}
add_action('user_description','rcl_user_posts');
function rcl_user_posts(){
    global $user;
    if(!isset($user->user_posts)||!$user->user_posts) $user->user_posts = 0;
    echo '<span class="filter-data"><i class="fa fa-file-text-o"></i>'.__('Publics','rcl').': '.$user->user_posts.'</span>';
}

add_action('user_description','rcl_user_register');
function rcl_user_register(){
    global $user;
    if(!isset($user->user_register)||!$user->user_register) return false;
    echo '<span class="filter-data"><i class="fa fa-calendar-check-o"></i>'.__('Registration','rcl').': '.mysql2date('d-m-Y', $user->user_register).'</span>';
}

add_action('user_description','rcl_filter_user_description');
function rcl_filter_user_description(){
    global $user;
    $cont = '';
    echo $cont = apply_filters('rcl_description_user',$cont,$user->user_id);
}
