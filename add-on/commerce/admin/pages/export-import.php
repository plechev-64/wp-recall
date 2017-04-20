<?php

global $wpdb;

$table_price .='<style>table{min-width:500px;width:50%;margin:20px 0;}table td{border:1px solid #ccc;padding:3px;}</style>';
$postmeta = $wpdb->get_results("SELECT meta_key FROM ".$wpdb->prefix ."postmeta GROUP BY meta_key ORDER BY meta_key");
$table_price .='<h2>'.__('Export/import data','wp-recall').'</h2><form method="post" action="">
'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
<p><input type="checkbox" name="post_title" checked value="1"> '.__('Add a title','wp-recall').'</p>
<p><input type="checkbox" name="post_content" checked value="1"> '.__('Add a description','wp-recall').'</p>
<p><input type="checkbox" name="post_excerpt" value="1"> '.__('Add a short description','wp-recall').'</p>
<h3>'.__('Optional fields','wp-recall').':</h3><table><tr>';

$fields = array(
    'price-products'=>__('The price of the product in the main currency','wp-recall'),
    'type_currency'=>__('The currency value of goods','wp-recall'),
    'outsale'=>'1 - '.__('the item is no longer available','wp-recall'),
    'related_products_recall'=>__('product category ID for display of recommended or similar products','wp-recall'),
);

$fields = apply_filters('products_field_list',$fields);

foreach($fields as $key=>$name){
        $table_price .= '<b>'.$key.'</b> - '.$name.'<br />';
}

if($postmeta){
    $n=1;
    foreach ($postmeta as $key){
        if(!isset($fields[$key->meta_key])) continue;
        if (strpos($key->meta_key, "goods_id") === FALSE && strpos($key->meta_key , "_") !== 0){
            $n++;
            $check = (isset($fields[$key->meta_key]))?1:0;
            $table_price .= '<td><input '.checked($check,1,false).' type="checkbox" name="'.$key->meta_key.'" value="1"> '.$key->meta_key.'</td>';
            if($n%2) $table_price .= '</tr><tr>';
        }
    }
}

$table_price .='</tr><tr><td colspan="2" align="right">'
        . '<input type="submit" name="get_csv_file" value="'.__('Upload products to a file','wp-recall').'"></td></tr></table>
'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
</form>';

$table_price .='<form method="post" action="" enctype="multipart/form-data">
'.wp_nonce_field('add-file-csv','_wpnonce',true,false).'
<p>
<input type="file" name="file_csv" value="1">
<input type="submit" name="add_file_csv" value="'.__('Import products from a file','wp-recall').'"><br>
<small><span style="color:red;">'.__('Attention','wp-recall').'!</span> '.__('Blank cells in XML file do not participate in the update of product characteristics','wp-recall').'<br>
'.__('The values of product fields deleted should be replaced in the file with an asterisk (*)','wp-recall').'</small>
</p>
</form>';
echo $table_price;

if($_FILES['file_csv']&&wp_verify_nonce( $_POST['_wpnonce'], 'add-file-csv' )){
    $file_name = $_FILES['file_csv']['name'];
    $rest = substr($file_name, -4);//получаем расширение файла
    if($rest=='.xml'){
        $filename = $_FILES['file_csv']['tmp_name'];
        $f1 = current(wp_upload_dir()) . "/" . basename($filename);
        copy($filename,$f1);

        $handle = fopen($f1, "r");
        $posts = array();
        if ($handle){
            while ( !feof($handle) ){

                $string = rtrim(fgets($handle));

                if ( false !== strpos($string, '<post>') ){
                        $post = '';
                        $doing_entry = true;
                        continue;
                }
                if ( false !== strpos($string, '</post>') ){
                        $doing_entry = false;
                        $posts[] = $post;
                        continue;
                }
                if ( $doing_entry ){
                        $post .= $string . "\n";
                }
            }
        }
        fclose($handle);

        $posts_columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->posts}");
        $updated = 0;
        $emptyFields = array();

        foreach((array)$posts as $value){
            $ID = false;
            $prodcat = false;
            $product_tag = false;
            $data = array();
            $args = array();
            $post = array();
            //echo $updated.': '.$value.'<br>';
            if (preg_match_all('|<(.+?)><!\[CDATA\[(.*?)\]\]></.+?>|s', $value, $m1)||preg_match_all('|<(.+?)>(.*?)</.+?>|s', $value, $m1) ){
                    foreach ($m1[1] as $n => $key){
                        if ($key == "prodcat"){
                            $prodcat = html_entity_decode($m1[2][$n]);
                            continue;
                        }
                        if ($key == "product_tag"){
                            $product_tag = html_entity_decode($m1[2][$n]);
                            continue;
                        }
                        $data[$key] = html_entity_decode($m1[2][$n]);
                        flush();
                    }
            }

            reset($posts_columns);
            foreach ($posts_columns as $col){
                if ( isset($data[$col->Field]) ){
                    if ($col->Field == "ID"){
                        $ID	= $data[$col->Field];
                    }else{
                        $post[$col->Field] = "$col->Field = '".$data[$col->Field]."'";
                        $args[$col->Field] = "{$data[$col->Field]}";
                    }
                    unset($data[$col->Field]);
                    flush();
                }
            }

            if(!$ID){
                $args['post_type'] = 'products';
                $ID = wp_insert_post($args);
                $action = __('has been created and added','wp-recall');
            }else{
                if (count($post)>0){    

                    $sql = "UPDATE $wpdb->posts SET ".implode(",",$post)." WHERE ID = '$ID'";
                    $res = $wpdb->query($sql);
                    if($res) $action = __('has been updated','wp-recall');
                    else $action = __('has not been updated','wp-recall');
                }
            }
            unset($post);

            if (count($data)){
                foreach ($data as $key => $value){
                    if($value!='*') update_post_meta($ID, $key, $value);
                    else $emptyFields[$key][] = $ID;
                }
            }

            //$args = array();
            if($prodcat){
                //$args['tax_input'] = array('prodcat'=>explode(',',$prodcat));
                wp_set_post_terms( $ID, explode(',',$prodcat), 'prodcat' );
            }
            if($product_tag){
                //$args['tax_input'] = array('product_tag'=>explode(',',$product_tag));
                wp_set_post_terms( $ID, explode(',',$product_tag), 'product_tag' );
            }

            do_action('rcl_upload_product_data',$ID,$data);

            unset($data);
            $updated++;
            echo "{$updated}. ".__('Product','wp-recall')." {$ID} $action<br>";
            flush();
        }

        if($emptyFields){
            foreach($emptyFields as $key=>$ids){
                $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_key='%s' AND post_id IN (".rcl_format_in($ids).")",$key,$ids));
            }
        }

    }else{
        echo '<div class="error">'.__('Invalid format of the downloaded file! Only XML valid','wp-recall').'</div>';
    }
}

