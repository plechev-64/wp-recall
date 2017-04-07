<?php

/**
 * Description of Rcl_Rating_Box
 *
 * @author Андрей
 */

class Rcl_Rating_Box {
    
    public $object_id;
    public $object_author;
    public $rating_type;
    public $output_type;
    public $user_id;
    public $total_rating;
    public $user_vote;
    public $rating_none = false;
    public $user_can = array(
        'view_history' => false,
        'vote' => false
    );
    public $buttons = array(
        'plus' => array(
            'type' => 'plus',
            'class' => 'vote-plus',
            'icon' => 'fa-thumbs-up'
        ),
        'minus' => array(
            'type' => 'minus',
            'class' => 'vote-minus',
            'icon' => 'fa-thumbs-down'
        ),
        'like' => array(
            'type' => 'plus',
            'class' => 'vote-heart',
            'icon' => 'fa-heart'
        )
    );

    function __construct($args) {
        global $rcl_options;
        
        $args = apply_filters('rcl_rating_box_args',$args);
        
        $this->init_properties($args);
        
        $this->setup_rating_allowed();
        
        $this->setup_user_can();
        
        $this->output_type = (isset($rcl_options['rating_type_'.$this->rating_type]))? $rcl_options['rating_type_'.$this->rating_type]: 0;
        
        $data = array(
            'object_id' => $this->object_id,
            'object_author' => $this->object_author,
            'rating_type' => $this->rating_type,
        );
        
        $this->user_can = apply_filters('rcl_rating_user_can', $this->user_can, $data);
        
        $this->buttons = apply_filters('rcl_rating_buttons', $this->buttons, $data);

    }
    
    function init_properties($args){
        global $post, $comment, $user_ID;
        
        if(!isset($args['user_id']))
            $args['user_id'] = $user_ID;
            
        
        if(!isset($args['object_author']) || !$args['object_author']){
            
            if($comment && is_object($comment)){
            
                $object = ($comment)? $comment: get_comment($this->object_id);
                $args['object_author'] = $object->user_id;

            }else if($post && is_object($post)){
                
                $object = ($post)? $post: get_post($this->object_id);
                $args['object_author'] = $object->post_author;

            }

        }
        
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }

    }
    
    function setup_user_can(){
        global $rcl_options;
        
        $access = (isset($rcl_options['rating_results_can']))? $rcl_options['rating_results_can']: false;

        $can = true;

        if($access){
            
            $user_info = get_userdata($this->user_id);
            
            if ( $user_info->user_level < $access ) 
                $can = false;
            
        }
        
        $this->user_can['view_history'] = $can;
        
        if(doing_filter('the_excerpt') || is_front_page()) return;
        
        if(!$this->user_id || $this->user_id == $this->object_author) return;
        
        $this->user_vote = rcl_get_vote_value($this->user_id,$this->object_id,$this->rating_type);
        
        if($this->user_vote && !$rcl_options['rating_delete_voice']) return;
        
        $this->user_can['vote'] = true;
        
    }
    
    function rating_type_exist($type){
        global $rcl_options;
        
        if(!isset($rcl_options['rating_'.$type]))
            return false;
        
        if(!$rcl_options['rating_'.$type])
            return false;
        
        return true;
        
    }
    
    function setup_rating_allowed(){
        global $post,$comment;
        
        if($post && !$comment){
            $this->rayting_none = (isset($post->rating_none))? $post->rating_none: get_post_meta($post->ID, 'rayting-none', 1);
        }
        
    }
    
    function get_box(){
        
        if(!$this->rating_type_exist($this->rating_type)) return false;
        
        if($this->rating_none) return false;
        
        $this->total_rating = $this->get_total();

        $content = '<div class="rcl-rating-box">';
        
            $content .= '<div class="rating-wrapper">';

                $content .= $this->box_content();

            $content .= '</div>';
        
        $content .= '</div>';
        
        return $content;
        
    }
    
    function box_content(){
        
        $content = '';
        
        if(!$this->user_can['vote'])
            $content .= '<span class="vote-heart"><i class="fa fa-heartbeat" aria-hidden="true"></i></span>';
        
        if($this->output_type == 1){
            
            $content .= $this->get_box_like();
            
        }else{

            $content .= $this->get_box_default();

        }

        return $content;

    }
    
    function get_box_like(){
        
        $content = $this->get_html_button($this->buttons['like']);

        $content .= $this->get_html_total_rating();
        
        return $content;
    }
    
    function get_box_default(){
        
        $content = $this->get_html_button($this->buttons['minus']);

        $content .= $this->get_html_total_rating();

        $content .= $this->get_html_button($this->buttons['plus']);
        
        return $content;
        
    }
    
    function get_class_vote_button($type){
        
        $classes = array('rating-vote');
        
        if($this->user_vote){
            
            if($this->user_vote > 0 && $type == 'plus' || $this->user_vote < 0 && $type == 'minus')
                $classes[] = 'user-vote';

        }
        
        return implode(' ',$classes);
        
    }
    
    function get_total(){
        
        if($this->is_comment()){
            
            $total = $this->get_comment_total();
            
        }else if($this->is_post()){
            
            $total = $this->get_post_total();
            
        }else{
            
            $total = rcl_get_total_rating($this->object_id,$this->rating_type);
            
        }
        
        return $total;
    }
    
    function is_comment(){
        global $comment;
        
        if($this->rating_type != 'comment') 
            return false;
        
        if(!$comment || !is_object($comment)) 
            return false;
        
        if($this->object_id != $comment->comment_ID)
            return false;
        
        return true;
    }
    
    function is_post(){
        global $post;
        
        if(!$post || !is_object($post)) 
            return false;
        
        if(!isset($post->rating_total))
            return false;
        
        if($this->object_id != $post->ID)
            return false;
        
        return true;
    }
    
    function get_comment_total(){
        global $rcl_options,$comment;  
        return ($rcl_options['rating_overall_comment']==1)? $comment->rating_votes: $comment->rating_total;
    }
    
    function get_post_total(){
        global $post;
        return $post->rating_total;
    }
    
    function get_encode_string($type){
        
        $args = array(
            'object_id' => $this->object_id,
            'object_author' => $this->object_author,
            'rating_type' => $this->rating_type
        );
        
        if($type != 'view'){
            $args['user_id'] = $this->user_id;
        }
        
        return rcl_encode_data_rating($type,$args);
        
    }
    
    function get_html_total_rating(){
        
        if(!$this->total_rating || !$this->user_can['view_history']){
            return '<span class="rating-value">'.rcl_format_rating($this->total_rating).'</span>';
        }
        
        return '<span class="rating-value rating-value-view" data-rating="'.$this->get_encode_string('view').'" onclick="rcl_view_list_votes(this);">'.rcl_format_rating($this->total_rating).'</span>';
    }
    
    function get_html_button($args){
        
        if(!$this->user_can['vote']) return false;
        
        return '<span class="'.$this->get_class_vote_button($args['type']).' '.$args['class'].'" data-rating="'.$this->get_encode_string($args['type']).'" onclick="rcl_edit_rating(this);">'
                    . '<i class="fa '.$args['icon'].'" aria-hidden="true"></i>'
                . '</span>';
        
    }
    
}
