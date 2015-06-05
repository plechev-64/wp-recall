<?php
class Rcl_Order {

    public $order_id;

    function __construct(){

    }

    function chek_requared_fields($get_fields,$key=false){
        $requared = true;
        if($get_fields){
            //print_r($_POST);
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
                                        $requared = false;
                                }else{
                                        $requared = true;
                                        break;
                                }
                        }
                    }else{
                        if($_POST[$slug]=='undefined'||!$_POST[$slug]){
                            $requared = false;
                            break;
                        }
                    }
                }
            }
        }
        return $requared;
    }

    function chek_amount(){
        global $rmag_options;
        $false_amount = false;
        if($rmag_options['products_warehouse_recall']==1){ //если включен учет наличия товара
            if(isset($_SESSION['cart'])){
                foreach($_SESSION['cart'] as $prod_id=>$val){
                    if(get_post_meta($prod_id, 'availability_product', 1)=='empty'){ //если товар цифровой
                        $true_amount[$prod_id] = $val['number'];
                    }else{
                        $amount = get_post_meta($prod_id, 'amount_product', 1);
                        if($amount>0){
                            $new_amount = $amount - $val['number'];
                            if($new_amount>=0){
                                    $true_amount[$prod_id] = $val['number'];
                            }else{
                                    $false_amount[$prod_id] = $val['number'];
                            }
                        }
                    }
                }
            }

        }
        return $false_amount;
    }

    function get_order_id(){
        global $wpdb;
        $num_max = $wpdb->get_var("SELECT MAX(order_id) FROM ".RMAG_PREF ."orders_history");
        if($num_max) $this->order_id = $num_max+1;
        else $this->order_id = rand(0,100);
        return $this->order_id;
    }

    function insert_order($order_id,$user_id=false){
        global $wpdb,$user_ID,$rmag_options,$active_addons;

        if(!$user_id) $user_id = $user_ID;

        $cart = $_SESSION['cart'];

        $cart = apply_filters('cart_values_rcl',$cart);

        if(!$cart) return false;

        foreach($cart as $prod_id=>$val){

            $status = 1;
            $metas = rcl_get_postmeta_array($prod_id);

            $price = $val['price'];

            if(isset($active_addons['users-market'])&&$metas['availability_product']=='empty'){ //если товар цифровой
                if(!$price) $status = 3;
            }else{

                if(!$price) $status = 2;
                $amount = $metas['amount_product'];
                if($rmag_options['products_warehouse_recall']==1&&$amount){ //формируем резерв товара
                    $reserve = $metas['reserve_product'];
                    if($reserve) $reserve = $reserve + $val['number'];
                            else $reserve = $val['number'];
                    $amount = $amount - $val['number'];
                    update_post_meta($prod_id, 'amount_product', $amount);
                    update_post_meta($prod_id, 'reserve_product', $reserve);
                }

            }
            $res = $wpdb->insert( RMAG_PREF ."orders_history",
                array(
                    'order_id' => $order_id,
                    'user_id' => $user_id,
                    'product_id' => $prod_id,
                    'product_price' => $price,
                    'numberproduct' => $val['number'],
                    'order_date' => current_time('mysql'),
                    'order_status' => $status
                    )
                );

        }
        do_action('insert_order_rcl',$user_id,$order_id);

        session_destroy();

        return $res;
    }

    function detail_order($get_fields,$user_id=false){

        $order_custom_field = '<p><b>IP-address:</b> '.$this->get_ip().'</p>';
        $cf = new Rcl_Custom_Fields();

        foreach((array)$get_fields as $custom_field){
            $slug = $custom_field['slug'];

            if($user_id&&$custom_field['order']!=1) continue;

            if($user_id) $val = get_the_author_meta($slug,$user_id);
            $val = $_POST[$slug];

            $order_custom_field .= $cf->get_field_value($custom_field,$val);
        }
        return $order_custom_field;
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

    function insert_detail_order($get_fields){
        global $wpdb;

        $order_custom_field = $this->detail_order($get_fields);

        $res = $wpdb->insert(
                RMAG_PREF ."details_orders",
                array(
                    'order_id'=>$this->order_id,
                    'details_order'=>$order_custom_field
                )
        );
        return $order_custom_field;
    }

    function send_mail($order_custom_field,$table_order,$user_id=false,$args=false){
        global $user_ID,$rmag_options,$rcl_options;

        if(!$user_id) $user_id = $user_ID;

		$reg_user = ($rmag_options['noreg_order'])? false: true;

        $subject = 'Данные заказа №'.$this->order_id;

        $textmail = '
        <p>Пользователь сформировал заказ в магазине "'.get_bloginfo('name').'".</p>
        <h3>Информация о пользователе:</h3>
        <p><b>Имя</b>: '.get_the_author_meta('display_name',$user_id).'</p>
        <p><b>Email</b>: '.get_the_author_meta('user_email',$user_id).'</p>
        <h3>Данные полученные при оформлении:</h3>
        '.$order_custom_field.'
        <p>Заказ №'.$this->order_id.' получил статус "Не оплачено".</p>
        <h3>Детали заказа:</h3>
        '.$table_order.'
        <p>Ссылка для управления заказом в админке:</p>
        <p>'.admin_url('admin.php?page=manage-rmag&order='.$this->order_id).'</p>';

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

        $email = get_the_author_meta('user_email',$user_id);

        $textmail = '';

        if($args&&$reg_user){
            $subject = 'Данные вашего аккаунта и заказа №'.$this->order_id;

            if($rcl_options['confirm_register_recall']==1){
                $url = get_bloginfo('wpurl').'/?rglogin='.$args['user_login'].'&rgpass='.$args['user_password'].'&rgcode='.md5($args['user_login']);

                $textmail .= '<h3>Вы были зарегистрированы</h3>
                <p>Подтвердите свою электронную почту на сайте перейдя по ссылке ниже:</p>
                <p><a href="'.$url.'">'.$url.'</a></p>
                <p>Не получается активировать аккаунт?</p>
                <p>Скопируйте текст ссылки ниже, вставьте его в адресную строку вашего браузера и нажмите Enter</p>';
            }

            $textmail .= '<h3>Данные аккаунта</h3>
            <p>Для вас был создан личный кабинет покупателя, где вы сможете следить за сменой статусов ваших заказов, формировать новые заказы и оплачивать их доступными способами</p>
            <p>Ваши данные для авторизации в вашем личном кабинете:</p>
            <p>Логин: '.$args['user_login'].'</p>
            <p>Пароль: '.$args['user_password'].'</p>
            <p>В дальнейшем используйте свой личный кабинет для новых заказов на нашем сайте.</p>';
        }

        $textmail .= '
        <p>Вы сформировали заказ в магазине "'.get_bloginfo('name').'".</p>
        <h3>Детали заказа</h3>
        <p>Заказ №'.$this->order_id.' получил статус "Не оплачено".</p>
        '.$table_order;

        $link = rcl_format_url(get_author_posts_url($user_id),'order');
        $textmail .= '<p>Ссылка для управления заказами: <a href="'.$link.'">'.$link.'</a></p>';

        $mail = array(
            'email'=>$email,
            'user_id'=>$user_id,
            'content'=>$textmail,
            'subject'=>$subject
        );

        $maildata = apply_filters('mail_insert_order_rcl',$mail,$this->order_id);

        rcl_mail($maildata['email'], $maildata['subject'], $maildata['content']);
    }

    function get_summ($order_data){
        foreach((array)$order_data as $sing_order){ $sumprise += "$sing_order->price"*"$sing_order->count"; }
        return $sumprise;
    }

}
