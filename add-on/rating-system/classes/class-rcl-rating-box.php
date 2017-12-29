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
    public $output_type = null;
    public $user_id;
    public $total_rating;
    public $average_rating = 0;
    public $user_vote;
    public $vote_max = 1;
    public $vote_count = 0;
    public $item_count = 0;
    public $item_value;
    public $rating_none = false;
    public $view_total_rating = true;
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
    
    function __construct($args){
        
        $args = apply_filters('rcl_rating_args',$args);
        
        $this->init_properties($args);
        
    }

    function setup_box() {
        global $post, $comment, $user_ID;
        
        if(!$this->user_id)
            $this->user_id = $user_ID;

        if(!$this->object_author){

            if($this->rating_type == 'comment'){
            
                $object = ($comment && is_object($comment))? $comment: get_comment($this->object_id);
                $this->object_author = $object->user_id;

            }else{

                if(in_array($this->rating_type, get_post_types())){
                    $object = ($post && is_object($post) && $post->ID == $this->object_id)? $post: get_post($this->object_id);
                    $this->object_author = $object->post_author;
                }

            }

        }
        
        if(!isset($this->output_type))
            $this->output_type = rcl_get_option('rating_type_'.$this->rating_type,0);
        
        $this->setup_rating_allowed($object);
        
        if($this->rating_none) return false;
        
        $this->setup_user_can();

        $data = array(
            'object_id' => $this->object_id,
            'object_author' => $this->object_author,
            'rating_type' => $this->rating_type,
        );
        
        if($this->output_type == 2){ //звезды
            
            if(!$this->vote_count) $this->vote_count = rcl_count_rating_values($data);
            
            if(!$this->item_count) $this->item_count = rcl_get_option('rating_item_amount_'.$this->rating_type, 1);
            
        }

        $this->vote_max = rcl_get_option('rating_point_'.$this->rating_type, 1);
        
        $this->user_can = apply_filters('rcl_rating_user_can', $this->user_can, $data);
        
        $this->buttons = apply_filters('rcl_rating_buttons', $this->buttons, $data);
        
        $this->total_rating = $this->get_total();

    }
    
    function init_properties($args){
      
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }

    }
    
    function setup_user_can(){
        
        $access = rcl_get_option('rating_results_can');

        $can = true;

        if($access){
            
            $user_info = get_userdata($this->user_id);
            
            if ( $user_info->user_level < $access ) 
                $can = false;
            
        }
        
        $this->user_can['view_history'] = $can;
        
        if(doing_filter('the_excerpt') || is_front_page()) return;
        
        if(!$this->user_id || $this->user_id == $this->object_author) return;
        
        $user_vote = apply_filters('rcl_rating_user_vote', false, $this);
        
        $this->user_vote = $user_vote !== false? $user_vote: rcl_get_vote_value($this->user_id,$this->object_id,$this->rating_type);
        
        if($this->user_vote && (!rcl_get_option('rating_delete_voice') || $this->output_type == 2)) return;
        
        $this->user_can['vote'] = true;
        
    }
    
    function rating_type_exist($type){
        
        if(!rcl_get_option('rating_'.$type))
            return false;
        
        return true;
        
    }
    
    function setup_rating_allowed($object){
        global $post;
        
        if(!isset($object->post_type)) return false;
        
        $this->rating_none = ($object->ID == $post->ID && isset($post->rating_none))? $post->rating_none: get_post_meta($object->ID, 'rayting-none', 1);
        
    }
    
    function get_box(){
        
        $this->setup_box();
        
        if(!$this->rating_type_exist($this->rating_type)) return false;
        
        if($this->rating_none) return false;

        return $this->box_content();
        
    }
    
    function box_content(){
        
        if($this->output_type == 2){
            $this->item_value = round( $this->vote_max / $this->item_count, 1 );
        }
        
        $this->average_rating = $this->vote_count? round( $this->total_rating / $this->vote_count, 1 ): 0;
        
        $class = 'box-default';
        
        if($this->output_type)
            $class = $this->output_type == 1? 'box-like': 'box-stars';

        $content = '<div class="rcl-rating-box rating-type-'.$this->rating_type.' '.$class.'">';
        
            $content .= '<div class="rating-wrapper">';

            if($this->output_type == 1){

                $content .= $this->get_box_like();

            }else if($this->output_type == 2){

                if($this->object_id && is_single($this->object_id) && rcl_get_option('rating_shema_'.$this->rating_type)){
                    $content .= $this->get_stars_shema();
                }

                $content .= $this->get_box_star();

            }else{

                $content .= $this->get_box_default();

            }
        
            $content .= '</div>';
        
        $content .= '</div>';

        return $content;

    }
    
    function get_box_star(){

        $args = array(
            'average_rating' => $this->average_rating,
            'item_value' => $this->item_value,
            'rating_value' => round( $this->average_rating / $this->item_value, 1 )
        );
        
        $content = $this->get_html_stars($args);
        
        if($this->view_total_rating){
            
            //$content .= '<span class="vote-heart"><i class="fa fa-heartbeat" aria-hidden="true"></i></span>';

            $content .= $this->get_html_total_stars($args);
            
        }

        return $content;
    }
    
    function get_box_like(){
        
        $content = '';
        
        if($this->view_total_rating && !$this->user_can['vote'])
            $content .= '<span class="vote-heart"><i class="fa fa-heartbeat" aria-hidden="true"></i></span>';
        
        $content .= $this->get_html_button($this->buttons['like']);

        if($this->view_total_rating)
            $content .= $this->get_html_total_rating();
        
        return $content;
    }
    
    function get_box_default(){
        
        $content = '';
        
        if($this->view_total_rating && !$this->user_can['vote'])
            $content .= '<span class="vote-heart"><i class="fa fa-heartbeat" aria-hidden="true"></i></span>';
        
        $content .= $this->get_html_button($this->buttons['minus']);

        if($this->view_total_rating)
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
        
        if(!$comment || !is_object($comment) || !isset($comment->rating_total)) 
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
        global $comment;  
        return (rcl_get_option('rating_overall_comment'))? $comment->rating_votes: $comment->rating_total;
    }
    
    function get_post_total(){
        global $post;
        return $post->rating_total;
    }
    
    function get_encode_string($type, $rating_value = false){
        
        $args = array(
            'object_id' => $this->object_id,
            'object_author' => $this->object_author,
            'rating_type' => $this->rating_type
        );
        
        if($rating_value){
            $args['rating_value'] = $rating_value;
        }
        
        if($type != 'view'){
            $args['user_id'] = $this->user_id;
        }
        
        return rcl_encode_data_rating($type,$args);
        
    }
    
    function get_html_total_rating(){
        
        if(!$this->total_rating || !$this->user_can['view_history']){
            return '<span class="rating-value">'.rcl_format_rating($this->total_rating).'</span>';
        }
        
        return '<span class="rating-value rating-value-view" title="'.__('See history','wp-recall').'" data-rating="'.$this->get_encode_string('view').'" onclick="rcl_view_list_votes(this);">'.rcl_format_rating($this->total_rating).'</span>';
    }
    
    function get_html_button($args){
        
        if(!$this->user_can['vote']) return false;
        
        $title = ($this->user_vote)? __('Cancel vote','wp-recall'): __('Vote','wp-recall');
        
        return '<span class="'.$this->get_class_vote_button($args['type']).' '.$args['class'].'" data-rating="'.$this->get_encode_string($args['type']).'" onclick="rcl_edit_rating(this);" title="'.$title.'">'
                    . '<i class="fa '.$args['icon'].'" aria-hidden="true"></i>'
                . '</span>';
        
    }
    
    function get_html_total_stars($args){
        
        if(!$this->total_rating || !$this->user_can['view_history']){
            return '<span class="rating-value">'.rcl_format_rating(round($args['rating_value'], 1)).'</span>';
        }
        
        return '<span class="rating-value rating-value-view" title="'.__('See history','wp-recall').'" data-rating="'.$this->get_encode_string('view').'" onclick="rcl_view_list_votes(this);">'.rcl_format_rating(round($args['rating_value'], 1)).'</span>';
        
    }
    
    function get_html_stars($args){

        $title = ($this->user_vote)? __('Cancel vote','wp-recall'): __('Vote','wp-recall');
        
        $classes = array('stars-wrapper');
        
        if($this->user_can['vote']){
            $classes[] = $this->user_vote? 'user-vote': 'must-vote';
        }
        
        $content = '<span class="'.(implode(' ',$classes)).'">';

        for($a = 1; $a <= $this->item_count; $a ++){
            
            $itemValue = round($a * $args['item_value'], 1);
            
            if($itemValue == $args['average_rating'] || $itemValue < $args['average_rating']){
                $procent = 100;
            }else if(!$args['rating_value'] || round(($itemValue - $args['item_value']),1) >= $args['average_rating']){
                $procent = 0;
            }else{
                $procent = round($args['average_rating'] - $itemValue, 1) * 100 / $args['item_value'];
                if($procent < 0) $procent += 100;
            }
            
            $stars = '<span class="fa fa-star stars__out" aria-hidden="true">'
                        . '<span class="fa fa-star stars__in" style="width:'.$procent.'%;" aria-hidden="true"></span>'
                    . '</span>';
            
            if($this->user_can['vote']){

                $content .= '<span class="'.$this->get_class_vote_button('star').' vote-star" data-value="'.$itemValue.'" data-rating="'.$this->get_encode_string('plus', $itemValue).'" onclick="rcl_edit_rating(this);" title="'.$title.'">'
                                . $stars
                            . '</span>';
            
            }else{
                
                $content .= '<span class="vote-star" data-value="'.$itemValue.'">'
                                . $stars
                            . '</span>';
                
            }
            
        }
        
        $content .= '</span>';
        
        return $content;
        
    }
    
    function get_stars_shema(){
        global $post;
        
        $metatags = apply_filters('rcl_rating_shema_metatags', array(
            'name' => $post->post_title,
            'itemReviewed' => $post->post_title,
            'bestRating' => $this->vote_max,
            'ratingValue' => $this->average_rating,
            'ratingCount' => $this->vote_count
        ), $this);
        
        if(!$metatags) return false;

        $content = '<span itemscope="" itemtype="http://schema.org/AggregateRating">';
            
        foreach($metatags as $itemprop => $value){
            $content .= '<meta itemprop="'.$itemprop.'" content="'.$value.'">';
        }
        
        $content .= '</span>';
        
        return $content;
        
    }
    
}
