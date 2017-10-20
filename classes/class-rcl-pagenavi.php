<?php

class Rcl_PageNavi{
    
    public $current_page = 1;//текущая страница
    public $pages_amount;//кол-во страниц
    public $output_number = array(4,4);//диапазон вывода отображаемых страниц
    public $in_page = 30;//кол-во элементов на странице
    public $key = 'rcl-page';//ключ передаваемый номер текущей страницы
    public $data_amount = 0;//общее кол-во элементов
    public $uri = array();//массив параметров из адресной строки
    public $pager_id;//идентификатор навигации
    public $custom = array();//массив параметров
    public $offset;//отступ выборки элементов
    public $ajax = false;//указание использования ajax
    
    function __construct($pager_id,$data_amount,$custom = array()){
        global $rcl_tab;
        
        $this->pager_id = $pager_id;
        
        if(isset($_REQUEST['pager-id'])&&$_REQUEST['pager-id']==$this->pager_id){
            if(isset($_REQUEST[$this->key]))
                $this->current_page = $_REQUEST[$this->key];
        }
        
        if(defined( 'DOING_AJAX' ) && DOING_AJAX && isset($_POST['tab_url'])){
            $post = rcl_decode_post($_POST['post']);
            $this->current_page = $post->page;
        }
        
        if($rcl_tab){
            if(in_array('ajax',$rcl_tab->supports)){
                $this->ajax = true;
            }
        }
        
        $this->data_amount = $data_amount;        
        $this->custom = $custom;
        
        if($this->custom){
            if(isset($this->custom['in_page'])) 
                $this->in_page = $this->custom['in_page'];
            
            if(isset($this->custom['key'])){ 
                $this->key = $this->custom['key'];
                if(isset($_REQUEST[$this->key]))
                    $this->current_page = $_REQUEST[$this->key];
            }
            
            if(isset($this->custom['current_page'])) 
                $this->current_page = $this->custom['current_page'];
            
            if(isset($this->custom['output_number'])) 
                $this->output_number = $this->custom['output_number'];
            
            if(isset($this->custom['ajax'])) 
                $this->ajax = $this->custom['ajax'];
        }
        
        if($this->current_page==0)
            $this->current_page = 1;

        $this->offset = ($this->current_page-1)*$this->in_page;
        $this->pages_amount = ceil($this->data_amount/$this->in_page);
        
        $this->uri_data_init();
    }
    
    function __destruct() {
        remove_all_filters('rcl_page_link_attributes');
    }
    
    function uri_data_init(){
        
        if(defined( 'DOING_AJAX' ) && DOING_AJAX && isset($_POST['tab_url'])){
            $uri = $_POST['tab_url'];
            $uri_string = explode('?',$uri);
            $query_string = $uri_string[1];
            $this->uri['current'] = $uri_string[0];
        }else{
            $query_string = $_SERVER['QUERY_STRING'];
            $this->uri['current'] = get_bloginfo('wpurl').str_replace('?'.$query_string,'',$_SERVER['REQUEST_URI']);
        }

        if($query_string){
            $strings = explode('&',$query_string);
            foreach($strings as $string){
                $query = explode('=',$string);
                $this->uri['args'][$query[0]] = $query[1];
            }
        }
        
        unset($this->uri['args'][$this->key]);
        unset($this->uri['args']['pager-id']);
        
        $str = ($this->pager_id)? array('pager-id='.$this->pager_id): array();
        
        if(isset($this->uri['args']) && $this->uri['args']){
            foreach($this->uri['args'] as $k=>$val){
                $str[] = $k.'='.$val;
            }
        }
        
        $this->uri['string'] = implode('&',$str);
    }
    
    function get_string($params = array()){
        
        if(!$params) return $this->uri['string'];
        
        if(isset($this->uri['args']) && $this->uri['args']){
            foreach($this->uri['args'] as $k=>$val){
                if(!in_array($k, $params)) continue;
                $str[] = $k.'='.$val;
            }
        }
        
        return implode('&',$str);
        
    }
    
    function limit(){
        return $this->offset.','.$this->in_page;
    }
    
    function pager_query(){
        $query = array();
        
        $query['args']['number_left'] = (($this->current_page - $this->output_number[0])<=0)? $this->current_page - 1: $this->output_number[0];
        $query['args']['number_right'] = (($this->current_page + $this->output_number[1])>$this->pages_amount)? $this->pages_amount - $this->current_page: $this->output_number[1];

        if($query['args']['number_left']){
            
            $start = $this->current_page-$query['args']['number_left'];
            
            if($start>1){
                $query['output'][]['page'] = 1;
            }
            
            if($start>2){
                $query['output'][]['separator'] = '...';
            }
                
            
            for($num=$query['args']['number_left'];$num>0;$num--){
                $query['output'][]['page'] = $this->current_page-$num;
            }
        }
        
        $query['output'][]['current'] = $this->current_page;
        
        if($query['args']['number_right']){
            for($num=1;$num<=$query['args']['number_right'];$num++){
                $query['output'][]['page'] = $this->current_page+$num;
            }
        }
        
        $end = $this->pages_amount-($this->current_page+$query['args']['number_right']);
        
        if($end>1)
            $query['output'][]['separator'] = '...';
        
        if($end>0)
            $query['output'][]['page'] = $this->pages_amount;
        
        
        return $query;
    }
    
    function get_url($page_id){ 
        return rcl_format_url($this->uri['current']).$this->uri['string'].'&'.$this->key.'='.$page_id;
    }
    
    function pagenavi($classes = ''){
        global $rcl_tab, $user_LK;
        
        if(!$this->data_amount||$this->pages_amount==1) return false;
        
        $query = $this->pager_query();
        
        $class = 'rcl-pager';
        
        if($classes) $class .= $class.' '.$classes;

        if($this->ajax){
            $class = $class.' rcl-ajax-navi';
        }

        $content = '<div class="'.$class.'">';
        
            $content .= '<div class="rcl-page-navi">';

            foreach($query['output'] as $item){
                foreach($item as $type=>$data){
                    if($type=='page'){
                        
                        $attrs = array(
                            'href'=>$this->get_url($data),
                            'data'=>array(
                                'page'=>$data,
                                'pager-id'=>$this->pager_id
                            )
                        );
                        
                        if($this->ajax && rcl_is_office()){

                            $attrs['data']['post'] = rcl_encode_post(array(
                                'tab_id'=>$rcl_tab->id,
                                'subtab_id'=>$rcl_tab->active_subtab,
                                'master_id'=>$user_LK,
                                'page'=>$attrs['data']['page'],
                            ));

                            $attrs['class'] = 'rcl-ajax';

                        }

                        $attrs = apply_filters('rcl_page_link_attributes',$attrs);
                        
                        $html = '<a '.$this->get_string_attributes($attrs).'>'.$data.'</a>';
                    }else if($type=='current'){
                        $html = '<span data-page="'.$data.'">'.$data.'</span>';
                    }else{
                        $html = '<span>'.$data.'</span>';
                    }
                    $content .= '<span class="pager-item type-'.$type.'">'.$html.'</span>';
                }
            }

            $content .= '</div>';
        
        $content .= '</div>';
        
        return $content;
    }
    
    function get_string_attributes($attrs){
        $str = array();
        foreach($attrs as $name=>$val){
            if(is_array($val)){
               foreach($val as $k=>$v){
                $str[] = $name.'-'.$k.'="'.$v.'"'; 
               }
            }else{
                $str[] = $name.'="'.$val.'"';
            }
        }
        return implode(' ',$str);
    }
}