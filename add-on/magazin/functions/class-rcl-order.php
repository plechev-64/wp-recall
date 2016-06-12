<?php

class Rcl_Order{
    
    public $buyer_register;
    public $user_id = 0;
    public $userdata = array();
    public $orders_page = 0;
    public $is_error = 0;
    public $check_amount;
    public $order_id;
    public $amount = array('success','error');
    
    function __construct(){
        global $rmag_options,$user_ID;
        $this->user_id = $user_ID;
        $this->buyer_register = (isset($rmag_options['buyer_register']))? $rmag_options['buyer_register']: 1;
        $this->check_amount = (isset($rmag_options['products_warehouse_recall']))? $rmag_options['products_warehouse_recall']: 0;
    }
    
    function error($code,$error){
        $this->is_error = $code;
        $wp_errors = new WP_Error();
        $wp_errors->add( $code, $error );
        return $wp_errors;
    }
    
    function insert_order(){
        global $wpdb,$user_ID,$rmag_options,$active_addons,$order;
        
        if(!$user_ID){
            $result = $this->register_user();
            if($this->is_error) return $result;
        }
        
        $fields = get_option( 'rcl_cart_fields' );
        $required = $this->chek_require_fields($fields);

        if(!$required){
            return $this->error('required_fields',__('Please fill in all mandatory fields!','wp-recall'));
        }
        
        //проверяем наличие товара
        $this->amount = $this->chek_amount();
        
        if($this->amount['error']){
            return $this->error('amount_false',__('It is not enough goods in stock!','wp-recall'));
        }

        $cart = $_SESSION['cart'];

        $cart = apply_filters('cart_values_rcl',$cart);

        if(!$cart){
            return $this->error('cart_empty',__('Your basket is empty!','wp-recall'));
        }
        
        $this->order_id = $this->next_order_id();

        foreach($cart as $product_id=>$val){

            $metas = rcl_get_postmeta_array($product_id);
            
            $status = 1;
            $price = $val['price'];
            $number = $val['number'];
            $amount = $metas['amount_product'];
            $reserve = $metas['reserve_product'];

            $this->update_amount($product_id,
                    array(
                        'price'=>$price,
                        'in_cart'=>$number,
                        'amount'=>$amount,
                        'reserve'=>$reserve
                    ));
            
            if(isset($active_addons['users-market'])&&$metas['availability_product']=='empty'){ //если товар цифровой
                if(!$price) $status = 3;
            }else{
                if(!$price) $status = 2;
            }
            
            $data = array(
                'order_id' => $this->order_id,
                'user_id' => $this->user_id,
                'product_id' => $product_id,
                'product_price' => $price,
                'numberproduct' => $number,
                'order_date' => current_time('mysql'),
                'order_status' => $status
            );

            $res = $wpdb->insert( RMAG_PREF ."orders_history", $data );
            
            if(!$res){
                return $this->error('insert_product',__('An error occurred while saving the goods!','wp-recall'));
            }

        }
        
        $order_details = $this->insert_order_details();
        
        $order = rcl_get_order($this->order_id);
        
        $this->send_mail($order_details);
        
        do_action('insert_order_rcl',$this->user_id,$this->order_id);

        session_destroy();

        return $order;
    }
    
    function next_order_id(){
        global $wpdb;

        $pay_max = $wpdb->get_var("SELECT MAX(order_id) FROM ".RMAG_PREF ."orders_history");

        if($pay_max) $order_id = $pay_max+1;
        else $order_id = rand(0,100);

        return $order_id;
    }
    
    function update_amount($product_id,$args){
        
        $reserve = $args['reserve'];
        $amount = $args['amount'];
        $in_cart = $args['in_cart'];
        $price = $args['price'];
        
        if($this->check_amount&&$amount){ //формируем резерв товара
            
            if($price){
                if($reserve) $reserve = $reserve + $in_cart;
                else $reserve = $in_cart;
                update_post_meta($product_id, 'reserve_product', $reserve);
            }
            
            $amount = $amount - $in_cart;
            update_post_meta($product_id, 'amount_product', $amount);
            
        }
    }
    
    function chek_amount(){
        
        $amount = array('success','error');
        
        if($this->check_amount){ //если включен учет наличия товара

            if(isset($_SESSION['cart'])){
                foreach($_SESSION['cart'] as $prod_id=>$val){
                    if(get_post_meta($prod_id, 'availability_product', 1)=='empty'){ //если товар цифровой
                        $amount['success'][$prod_id] = $val['number'];
                    }else{
                        $prod_amount = get_post_meta($prod_id, 'amount_product', 1);
                        if($prod_amount==''){ //если у товара учет наличия не ведется
                            $amount['success'][$prod_id] = $prod_amount;
                        }else{ //если у товара указано наличие
                            if($prod_amount>0){ //если товар в наличии
                                $new_amount = $prod_amount - $val['number'];
                                if($new_amount>=0){ //если товара в наличии недостаточно
                                    $amount['success'][$prod_id] = $prod_amount;
                                }else{
                                    $amount['error'][$prod_id] = $prod_amount;
                                }
                            }else{
                                $amount['error'][$prod_id] = $prod_amount;
                            }
                        }
                    }
                }
            }

        }
        
        return $amount;
    }

    function register_user(){
        global $rcl_options;
        
        $profile_fields = get_option( 'rcl_profile_fields' );
        $required = $this->chek_require_fields($profile_fields,'profile');

        if(!$required){
            return $this->error('required_fields',__('Please fill in all mandatory fields!','wp-recall'));
        }
        
        $user_email = sanitize_text_field($_POST['email_new_user']);
        
        $res_email = email_exists( $user_email );
        $res_login = username_exists($user_email);
        $correctemail = is_email($user_email);
        $valid = validate_username($user_email);
        
        if($this->buyer_register){
            if(!$valid||!$correctemail){
                return $this->error('email_invalid',__('You have entered an invalid email!','wp-recall'));
            }
            
            if($res_login||$res_email){
                return $this->error('email_used',__('This email is already used! If this is your email, then log in and proceed with the order.','wp-recall'));
            }
            
            if(!$this->user_id){

                $user_password = wp_generate_password( 12, false );
                $user_name = sanitize_text_field($_POST['fio_new_user']);

                $this->userdata = array(
                    'user_pass'=>   $user_password,
                    'user_login'=>  $user_email,
                    'user_email'=>  $user_email,
                    'display_name'=>$user_name
                );

                $this->user_id = rcl_insert_user($this->userdata);

                if(!$this->user_id){
                    return $this->error('buyer_registered',__('An error occurred while registering the buyer!','wp-recall'));
                }
            
            }
            
        }else{
            if(!$correctemail||!$valid){
                if(!$valid||!$correctemail){
                    return $this->error('email_invalid',__('You have entered an invalid email!','wp-recall'));
                }
            }
            
            $user = get_user_by('email', $user_email);
            if($user) $this->user_id = $user->ID;
        }
        
        if($this->user_id){
            if($profile_fields){
                $cf = new Rcl_Custom_Fields();
                $cf->register_user_metas($this->user_id);
            }
            
            $confirm = (isset($rcl_options['confirm_register_recall']))? $rcl_options['confirm_register_recall']: false;
            
            //Сразу авторизуем пользователя
            if($this->buyer_register&&!$confirm){

                $creds = array();
                $creds['user_login'] = $user_email;
                $creds['user_password'] = $user_password;
                $creds['remember'] = true;
                $user = wp_signon( $creds, false );
                $this->orders_page = rcl_format_url(get_author_posts_url($this->user_id),'orders');

            }
        }
        
        return $this->user_id;
        
    }
    
    function chek_require_fields($get_fields,$key=false){
        $required = true;
        if($get_fields){

            foreach($get_fields as $custom_field){

                if($key=='profile'&&$custom_field['order']!=1) continue;

                $slug = $custom_field['slug'];
                if($custom_field['requared']==1){
                    if($custom_field['type']=='checkbox'){
                        $chek = explode('#',$custom_field['field_select']);
                        $count_field = count($chek);
                        for($a=0;$a<$count_field;$a++){
                            $slug_chek = $slug.'_'.$a;
                            if($_POST[$slug_chek]=='undefined'){
                                    $required = false;
                            }else{
                                    $required = true;
                                    break;
                            }
                        }
                    }else{
                        if($_POST[$slug]=='undefined'||!$_POST[$slug]){
                            $required = false;
                            break;
                        }
                    }
                }
            }
        }
        return $required;
    }
    
    function get_order_details($order_id){
        
    }

    function get_ip(){
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        }elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    function insert_order_details(){
        global $wpdb;
        
        $fields = get_option( 'rcl_cart_fields' );

        $order_details = '<p><b>IP-address:</b> '.$this->get_ip().'</p>';
        
        if($fields){
            
            $cf = new Rcl_Custom_Fields();

            foreach($fields as $custom_field){
                $value = (isset($_POST[$custom_field['slug']]))? $_POST[$custom_field['slug']]: false;
                $order_details .= $cf->get_field_value($custom_field,$value);
            }
            
        }

        $wpdb->insert(
            RMAG_PREF ."details_orders",
            array(
                'order_id'=>$this->order_id,
                'details_order'=>$order_details
            )
        );
        
        return $order_details;
    }
    
    function send_mail($order_details){
        global $rmag_options,$rcl_options,$order;
        
        $table_order = rcl_get_include_template('order.php',__FILE__);

        $subject = __('Order data','wp-recall').' №'.$this->order_id;

        $textmail = '
        <p>'.__('This user has formed a purchase','wp-recall').' "'.get_bloginfo('name').'".</p>
        <h3>'.__('Information about the customer','wp-recall').':</h3>
        <p><b>'.__('Name','wp-recall').'</b>: '.get_the_author_meta('display_name',$this->user_id).'</p>
        <p><b>'.__('Email','wp-recall').'</b>: '.get_the_author_meta('user_email',$this->user_id).'</p>
        <h3>'.__('The data obtained at registration','wp-recall').':</h3>
        '.$order_details.'
        <p>'.sprintf(__('Order №%d received the status of "%s"','wp-recall'),$this->order_id,rcl_get_status_name_order(1)).'.</p>
        <h3>'.__('Order details','wp-recall').':</h3>
        '.$table_order.'
        <p>'.__('Link to control order','wp-recall').':</p>
        <p>'.admin_url('admin.php?page=manage-rmag&order-id='.$this->order_id).'</p>';

        $admin_email = $rmag_options['admin_email_magazin_recall'];
        if($admin_email){
                rcl_mail($admin_email, $subject, $textmail);
        }else{
            $users = get_users( array('role' => 'administrator') );
            foreach((array)$users as $userdata){
                $email = $userdata->user_email;
                rcl_mail($email, $subject, $textmail);
            }
        }

        $email = get_the_author_meta('user_email',$this->user_id);

        $textmail = '';

        if($this->userdata&&$this->buyer_register){
            $subject = __('Your account information and order','wp-recall').' №'.$this->order_id;

            if($rcl_options['confirm_register_recall']==1){
                $url = get_bloginfo('wpurl').'/?rglogin='.$this->userdata['user_login'].'&rgpass='.$this->userdata['user_pass'].'&rgcode='.md5($this->userdata['user_login']);

                $textmail .= '<h3>'.__('You have been registered','wp-recall').'</h3>
                <p>'.__('Confirm your email on the site by clicking on the link below','wp-recall').':</p>
                <p><a href="'.$url.'">'.$url.'</a></p>
                <p>'.__('It is impossible to activate your account?','wp-recall').'</p>
                <p>'.__('Copy the text of the link below , paste it into the address bar of your browser and press Enter','wp-recall').'</p>';
            }

            $textmail .= '<h3>'.__('Account data','wp-recall').'</h3>
            <p>'.__('Personal account of the buyer has been created for you , where you can watch the changing of the status of your orders , create new orders and pay for them means available','wp-recall').'</p>
            <p>'.__('Your authorization data in your personal account','wp-recall').':</p>
            <p>'.__('Login','wp-recall').': '.$this->userdata['user_login'].'</p>
            <p>'.__('Password','wp-recall').': '.$this->userdata['user_pass'].'</p>
            <p>'.__('In the future, use your personal cabinet in new orders on our website','wp-recall').'.</p>';
        }

        $textmail .= '
        <p>'.__('You have formed a purchase','wp-recall').' "'.get_bloginfo('name').'".</p>
        <h3>'.__('Order details','wp-recall').'</h3>
        <p>'.sprintf(__('Order №%d received the status of "%s"','wp-recall'),$this->order_id,rcl_get_status_name_order(1)).'.</p>
        '.$table_order;

        $link = rcl_format_url(get_author_posts_url($this->user_id),'orders');
        $textmail .= '<p>'.__('Link to control order','wp-recall').': <a href="'.$link.'">'.$link.'</a></p>';

        $mail = array(
            'email'=>$email,
            'user_id'=>$this->user_id,
            'content'=>$textmail,
            'subject'=>$subject
        );

        $maildata = apply_filters('mail_insert_order_rcl',$mail,$this->order_id);

        rcl_mail($maildata['email'], $maildata['subject'], $maildata['content']);
    }
}
