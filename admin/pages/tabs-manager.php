<?php

rcl_sortable_scripts();

require_once RCL_PATH.'admin/classes/class-rcl-tabs-manager.php';

$areaType = (isset($_GET['area-type']))? $_GET['area-type']: 'area-menu';

$tabsManager = new Rcl_Tabs_Manager($areaType,
                    array(
                        'meta-key'=>false,
                        'select-type'=>false,
                        'placeholder'=>false,
                        'sortable'=>true
                    )
                );

$content = '<h2>'.__('Personal account tabs manager','wp-recall').'</h2>';

$content .= '<p>'.__('On this page you can create new tabs personal account '
        . 'with arbitrary content and manage existing tabs in different areas '
        . 'of the personal account').'</p>';

$content .= $tabsManager->form_navi();

$content .= $tabsManager->active_fields_box();

echo $content;