<?php

global $wpdb;

$postmeta = $wpdb->get_results("SELECT meta_key FROM ".$wpdb->prefix ."postmeta GROUP BY meta_key ORDER BY meta_key");

$fields = array(
    'price-products'=>__('The price of the product in the main currency','wp-recall'),
    'outsale'=>'1 - '.__('the item is no longer available','wp-recall')
);

$fields = apply_filters('products_field_list',$fields);

$content = '<style>';
$content .= '#migration-box{'
        . ''
        . '}'
        . '#migration-step{'
            . 'font-weight: bold;'
        . '}'
        . '#migration-progress-box{'
            . 'border: 1px solid #ccc;
               margin: 20px 10px 20px 0;'
        . '}'
        . '#migration-progress{'
            . 'height: 30px;
               background: #ffaf36;
               width: 0;'
        . '}'
        . '#migration-log{'
            . 'margin: 20px 0;
                background: #fff;
                padding: 10px;
                font-size: 12px;
                max-height: 200px;
                overflow: auto;
                box-shadow: 2px -2px 6px 0px #ccc inset;
                border: 1px solid #ccc;'
        . '}'
        . '#migration-log span{'
        . '}'
        . '#migration-log .error{'
            . 'color:red;'
        . '}'
        . '#migration-manager{'
        . ''
        . '}';
$content .= '</style>';

$content .='<style>table{min-width:500px;width:50%;margin:20px 0;}table td{border:1px solid #ccc;padding:3px;}</style>';

$content .='<h2>'.__('Export/import data','wp-recall').'</h2><form method="post" action="">
'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
<p><input type="checkbox" name="product[fields][]" checked value="post_title"> '.__('Add a title','wp-recall').'</p>
<p><input type="checkbox" name="product[fields][]" checked value="post_content"> '.__('Add a description','wp-recall').'</p>
<p><input type="checkbox" name="product[fields][]" value="post_excerpt"> '.__('Add a short description','wp-recall').'</p>
<h3>'.__('Optional fields','wp-recall').':</h3><table><tr>';

foreach($fields as $key=>$name){
    $content .= '<b>'.$key.'</b> - '.$name.'<br />';
}

if($fields){
    $n=1;
    foreach ($fields as $key => $desc){
        $n++;
        $content .= '<td><input type="checkbox" name="product[meta][]" value="'.$key.'"> '.$key.'</td>';
        if($n%2) $content .= '</tr><tr>';
    }
}

$content .='</tr><tr><td colspan="2" align="right">'
        . '<input type="submit" name="get_csv_file" value="'.__('Upload products to a file','wp-recall').'"></td></tr></table>
'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
</form>';

$content .='<form method="post" action="" enctype="multipart/form-data">
'.wp_nonce_field('rcl-import-products-nonce','_wpnonce',true,false).'
<p>
<input type="file" name="rcl-import-products" value="1">
<input type="submit" value="'.__('Import products from a file','wp-recall').'"><br>
<small><span style="color:red;">'.__('Attention','wp-recall').'!</span> '.__('Пустые значения произвольных полей в файле иморта удаляют их из базы данных','wp-recall').'</small>
</p>
</form>';

if($_FILES['rcl-import-products'] && wp_verify_nonce( $_POST['_wpnonce'], 'rcl-import-products-nonce' )){
        
    $file_name = $_FILES['rcl-import-products']['name'];

    $rest = substr($file_name, -4);//получаем расширение файла

    if($rest=='.xml'){

        $filename = $_FILES['rcl-import-products']['tmp_name'];

        $filepath = wp_normalize_path(current(wp_upload_dir()) . "/" . basename($filename));

        copy($filename,$filepath);

        $content .= '<script>rcl_init_import_products("'.$filepath.'");</script>';
        $content .= '<div id="migration-box">';
        $content .= '<div id="migration-step">Ожидание...</div>';
        $content .= '<div id="migration-progress-box">';
        $content .= '<div id="migration-progress"></div>';
        $content .= '</div>';
        $content .= '<div id="migration-log"></div>';
        $content .= '</div>';

    }else{
        echo '<div class="error">'.__('Неверное расширение загружаемого файла. Ожидается формат XML!').'</div>';
    }
}
    
    
echo $content;
