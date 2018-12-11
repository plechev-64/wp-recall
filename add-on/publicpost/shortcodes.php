<?php

add_shortcode('public-form','rcl_publicform');
function rcl_publicform($atts, $content = null){

    if(rcl_is_gutenberg()) return false;

$Table = new Rcl_Table(array(
    'cols' => array(
        array(
            'title' => __('Объекты'),
            'align' => 'center',
            'width' => 40,
            'total' => __('ИТОГО')
        ),
        array(
            'title' => __('Параметр 1'),
            'align' => 'left',
            'width' => 20,
            'totalsum' => true,
            'sort' => 'param1' //идентификатор сортировки
        ),
        array(
            'title' => __('Параметр 2'),
            'align' => 'center',
            'width' => 20,
            'totalsum' => true,
            'sort' => 'param2' //идентификатор сортировки
        ),
        array(
            'title' => __('Параметр 3'),
            'align' => 'right',
            'width' => 20,
            'totalsum' => true,
            'sort' => 'param3' //идентификатор сортировки
        )
    ),
    'zebra' => true,
    'border' => array(
        'table',
        'cols',
        'rows'
    ),
    'total' => true //указываем необходимость формирования строки ИТОГО
));

//заполняем таблицу данными
$Table->add_row(array('Объект 1', '11', '32', '23'));
$Table->add_row(array('Объект 2', '31', '22', '33'));
$Table->add_row(array('Объект 3', '21', '34', '13'));

//выводим таблицу
echo $Table->get_table();

    $form = new Rcl_Public_Form($atts);

    return $form->get_form();
}
