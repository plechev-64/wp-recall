<?php

class Rcl_Groups_List extends Rcl_Groups_Query{

    public $template = 'list';
    public $filters = 0;
    public $search_form = 1;
    public $user_id;
    public $admin_id;
    public $orderby = 'name';
    public $search_name = false;
    public $add_uri;

    function __construct($args){
        
        parent::__construct();

        $this->init_properties($args);
        
        $this->set_query($args);
        
        $this->setup_termdata();

        if(isset($_GET['groups-filter'])&&$this->filters) 
            $this->orderby = $_GET['groups-filter'];
        
        if(isset($_GET['group-name'])) 
            $this->search_name = $_GET['group-name'];

        $this->add_uri['groups-filter'] = $this->orderby;

        if($this->search_name)
            add_filter('rcl_groups_query',array($this,'add_query_search_name'));

        if($this->user_id)
            add_filter('rcl_groups_query',array($this,'add_query_user_id'));
        
        if($this->admin_id)
            add_filter('rcl_groups_query',array($this,'add_query_admin_id'));

        if($this->orderby=='posts')
            add_filter('rcl_groups_query',array($this,'add_query_orderby_posts'));
        
        if($this->orderby=='date')
            add_filter('rcl_groups_query',array($this,'add_query_orderby_date'));
        
        if($this->orderby=='name')
            add_filter('rcl_groups_query',array($this,'add_query_orderby_name'));

        if($this->orderby=='users')
            add_filter('rcl_groups_query',array($this,'add_query_orderby_users'));
        
        $this->query = apply_filters('rcl_groups_query',$this->query);
        
    }

    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }

    function remove_data(){
        remove_all_filters('rcl_groups_query');
    }

    function setup_groupdata($data){
        global $rcl_group;
        $rcl_group = (object)$data;
        return $rcl_group;
    }

    function add_query_search_name($query){
        $query['where'][] = "wp_terms.name LIKE '%$this->search_name%'";
        return $query;
    }

    function add_query_user_id($query){
        
        $where = "rcl_groups.admin_id='$this->user_id'";
        
        $users = new Rcl_Groups_Users_Query();
        
        $groups_ids = $users->get_col(array(
            'user_id' => $this->user_id,
            'fields' => array('group_id')
        ));

        if($groups_ids)
            $where = "($where OR rcl_groups.ID IN (".implode(',',$groups_ids)."))";
        
        $query['where'][] = $where;  

        return $query;
    }
    
    function add_query_admin_id($query){

        $query['where'][] = "rcl_groups.admin_id='$this->admin_id'";

        return $query;
    }

    //добавляем выборку данных постов в основной запрос
    function add_query_orderby_posts($query){
           
        $query['orderby'] = "wp_term_taxonomy.count";

        return $query;
    }
    
    function add_query_orderby_date($query){
           
        $query['orderby'] = "wp_terms.term_id";

        return $query;
    }
    
    function add_query_orderby_name($query){
           
        $query['orderby'] = "wp_terms.name";

        return $query;
    }

    function add_query_orderby_users($query){

        $query['orderby'] = "rcl_groups.group_users";

        return $query;
    }

    function search_request(){
        global $user_LK;

        $rqst = '';

        if(isset($_GET['group-name'])||$user_LK){
            $rqst = array();
            foreach($_GET as $k=>$v){
                if($k=='rcl-page'||$k=='groups-filter') continue;
                $rqst[$k] = $k.'='.$v;
            }

        }

        if($this->add_uri){
            foreach($this->add_uri as $k=>$v){
                $rqst[$k] = $k.'='.$v;
            }
        }

        $rqst = apply_filters('rcl_groups_uri',$rqst);

        return $rqst;
    }

    function get_filters($count_groups = false){
        global $post,$active_addons,$user_LK;

        if(!$this->filters) return false;
        
        $content = '';
        
        if($this->search_form){

            $search_text = ((isset($_GET['group-name'])))? $_GET['group-name']: '';

            $content ='<div class="rcl-search-form">
                    <form method="get" action="">
                        <p>'.__('Search groups','wp-recall').'</p>
                        <input type="text" name="group-name" value="'.$search_text.'">
                        <input type="submit" class="recall-button" value="'.__('Search','wp-recall').'">
                    </form>
                </div>';

            $content = apply_filters('rcl_groups_search_form',$content);
         
        }

        $count_groups = (false!==$count_groups)? $count_groups: $this->count_groups();

        $content .='<h3>'.__('Total number of groups','wp-recall').': '.$count_groups.'</h3>';

        if(isset($this->add_uri['groups-filter'])) unset($this->add_uri['groups-filter']);

        $s_array = $this->search_request();

        $rqst = ($s_array)? implode('&',$s_array).'&' :'';

        if($user_LK){
            $url = (isset($_POST['tab_url']))? $_POST['tab_url']: get_author_posts_url($user_LK);
        }else{
            $url = get_permalink($post->ID);
        }

        $perm = rcl_format_url($url).$rqst;

        $filters = array(
            'name'       => __('Name','wp-recall'),
            'date'    => __('Date','wp-recall'),
            'posts'      => __('Publications','wp-recall'),
            'users'      => __('Users','wp-recall'),
        );

        $filters = apply_filters('rcl_groups_filter',$filters);

        $content .= '<div class="rcl-data-filters">'.__('Filter by','wp-recall').': ';
        
        foreach($filters as $key=>$name){
            $content .= '<a class="data-filter recall-button '.rcl_a_active($this->orderby,$key).'" href="'.$perm.'groups-filter='.$key.'">'.$name.'</a> ';
        }

        $content .= '</div>';

        return $content;

    }

}

