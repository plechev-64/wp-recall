<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-rcl-table
 *
 * @author Андрей
 */
class Rcl_Table {

    public $zebra = false;
    public $border = array();
    public $cols = array();
    public $cols_number = 0;
    public $rows = array();
    public $total = false;
    public $table_id = 0;
    public $class = array();
    public $attr_rows = array();

    function __construct($tableProps = false) {

        $this->init_properties($tableProps);

        if(!$this->table_id)

            $this->table_id = 'rcl-table-'.current_time('timestamp');

        if(!$this->cols_number)
            $this->cols_number = count($this->cols);

    }

    function init_properties($args){

        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }

    }

    function setup_string_attrs($attrs){

        $stringAttrs = array();

        foreach($attrs as $name => $value){

            if(!isset($value) || $value === '') continue;

            if(is_array($value)){
                $value = implode(' ', $value);
            }

            $stringAttrs[] = $name.'="'.$value.'"';
        }

        return implode(' ', $stringAttrs);

    }

    function get_current_number(){
        return count($this->rows) + 1;
    }

    function get_table_attrs(){

        $attrs = array(
            'class' => array('rcl-table preloader-parent'),
            'id' => $this->table_id
        );

        if($this->class){
            $attrs['class'][] = $this->class;
        }

        if($this->cols_number){
            $attrs['class'][] = 'rcl-table__type-cell-'.$this->cols_number;
        }

        if($this->zebra){
            $attrs['class'][] = 'rcl-table__zebra';
        }

        if(!isset($this->cols[0]['title'])){
            $attrs['class'][] = 'rcl-table__not-header';
        }

        if($this->border){

            if(in_array('table', $this->border)){
                $attrs['class'][] = 'rcl-table__border';
            }

            if(in_array('cols', $this->border)){
                $attrs['class'][] = 'rcl-table__border-row-right';
            }

            if(in_array('rows', $this->border)){
                $attrs['class'][] = 'rcl-table__border-row-bottom';
            }

        }

        return $this->setup_string_attrs($attrs);

    }

    function get_header_attrs(){

        $attrs = array();
        $attrs['class'][] = 'rcl-table__row';
        $attrs['class'][] = 'rcl-table__row-header';

        return $this->setup_string_attrs($attrs);

    }

    function get_row_attrs($customAttrs = false){

        $attrs = array();

        if($customAttrs)
            $attrs = $customAttrs;

        $attrs['class'][] = 'rcl-table__row';

        return $this->setup_string_attrs($attrs);

    }

    function get_cell_attrs($cellProps = false, $place = false, $contentCell = false){

        $attrs = array(
            'class' => array('rcl-table__cell')
        );

        if($cellProps){

            if(isset($cellProps['width']) && $cellProps['width']){
                $attrs['class'][] = 'rcl-table__cell-w-'.$cellProps['width'];
            }

            if(isset($cellProps['align']) && $cellProps['align']){
                $attrs['class'][] = 'rcl-table__cell-'.$cellProps['align'];
            }

            if(isset($cellProps['title']) && $cellProps['title']){
                $attrs['data-rcl-ttitle'] = $cellProps['title'];
            }

            if(isset($cellProps['sort']) && $cellProps['sort']){
                if($place == 'header'){
                    $attrs['class'][] = 'rcl-table__cell-must-sort';
                    $attrs['data-sort'] = $cellProps['sort'];
                    $attrs['data-route'] = 'desc';
                }else if($place == 'total'){
                    $attrs['class'][] = 'rcl-table__cell-total';
                    //$attrs['data-field'] = $cellProps['sort'];
                }else{
                    $attrs['class'][] = 'rcl-table__cell-sort';
                    $attrs['data-'.$cellProps['sort'].'-value'] = trim(strip_tags($contentCell));
                }
            }

        }

        return $this->setup_string_attrs($attrs);

    }

    function add_row($row, $attrs = array()){
        $this->attr_rows[count($this->rows)] = $attrs;
        $this->rows[] = $row;
    }

    function add_total_row($row){
        $this->total = $row;
    }

    function get_table($rows = false){

        if($rows){
            $this->rows = $rows;
        }

        $content = '<div '.$this->get_table_attrs().'>';

        if($this->cols){

            $titles = array();

            foreach($this->cols as $col){
                if(isset($col['title'])) $titles[] = $col['title'];
            }

            if($titles){
                $content .= $this->header($titles);
            }

        }

        if(is_array($this->rows)){

            foreach($this->rows as $k => $cells){

                $attrs = array('class' => array('rcl-table__row-must-sort'));

                if(isset($this->attr_rows[$k])){
                    foreach($this->attr_rows[$k] as $attr => $value){
                        if(isset($attrs[$attr]))
                            $attrs[$attr] = array_merge($attrs[$attr], $value);
                        else
                           $attrs[$attr] = $value;
                    }
                }

                $content .= $this->row($cells, $attrs);
            }

            if($this->total){
                $content .= $this->get_total_row();
            }

        }else{

            $content .= $this->rows;

        }

        $content .= '</div>';

        $content .= "<script>rcl_init_table('$this->table_id');</script>";

        return $content;
    }

    function get_total_row(){

        $total = ($this->total && is_array($this->total))? $this->total: array();

        if(!$total){

            foreach($this->cols as $k => $col){
                if(isset($col['total']))
                    $total[] = $col['total'];
                else if(isset($col['totalsum']))
                    $total[] = 0;
                else
                    $total[] = '-';
            }

            foreach($this->rows as $row){
                foreach($row as $k => $value){
                    if(isset($this->cols[$k]['totalsum'])){
                        $total[$k] += strip_tags($value);
                    }
                }

            }

        }

        $attrs['class'][] = 'rcl-table__row-total';

        return $this->row($total, $attrs, 'total');

    }

    function header($cells){

        $content = '<div '.$this->get_header_attrs().'>';

        $content .= $this->parse_row_cells($cells, 'header');

        $content .= '</div>';

        return $content;
    }

    function parse_row_cells($cells, $place = false){

        $content = '';

        foreach($cells as $k => $contentCell){

            $cellProps = false;

            if($this->cols && isset($this->cols[$k])){
                $cellProps = $this->cols[$k];
            }

            $content .= $this->cell($contentCell, $cellProps, $place);
        }

        return $content;
    }

    function row($cells, $attrs = false, $place = false){

        $content = '<div '.$this->get_row_attrs($attrs).'>';

        if(is_array($cells)){

            $content .= $this->parse_row_cells($cells, $place);

        }else{

            $content .= $cells;

        }

        $content .= '</div>';

        return $content;
    }

    function cell($contentCell, $cellProps = false, $place = false){
        if(!isset($contentCell) || $contentCell === '') $contentCell = '-';
        return '<div '.$this->get_cell_attrs($cellProps, $place, $contentCell).'>'.$contentCell.'</div>';
    }

}