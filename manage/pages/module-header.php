<?php
/******************************************************************************/
// Created by: Shlomo Hassid.
// Release Version : 1.0.1
// Creation Date: 17/05/20202
// Copyright 2020, Shlomo Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->intial, Creation
*******************************************************************************/
require_once PLAT_PATH_CORE.DS."APageComponents.class.php";

$CoreBlockRender = function(APage $APage, array $Values = []) {

    //Core Block Defaults:
    $block_defaults = [
        "title"     => "Module Title",
        "which"     => "",
        "sub-title" => "",
        "btn-text"  => "settings",
        "actions"   => [] 
    ];

    //Extend settings:
    $block_setting = array_merge($block_defaults, $Values);
    //Send to debugger:
    Trace::reg_var("module header settings", $block_setting);
    $module_which_menu  = implode(APageComponents::html_ele("span.module-title-which",[], $block_setting["which"]));
    $module_title       = implode(APageComponents::html_ele("h1.module-title.float-start",[], $block_setting["title"].$module_which_menu));
    $module_desc        = implode(APageComponents::html_ele("span.module-desc.float-start",[], $block_setting["sub-title"]));
    $module_control     = implode(APageComponents::html_ele(
        "div.float-end", [], 
        APageComponents::dropdown($block_setting["actions"], $block_defaults["btn-text"])
    ));
    
$content = <<<HTML
    <!-- START: DYNAMIC SIDE MENU -->
    <div class='container'>
        <div class='row'>
            <div class='col-12 module-header sik-form-init'>
                {$module_control}
                {$module_title}
                {$module_desc}
            </div>
        </div>
    </div>
    <!-- END: DYNAMIC SIDE MENU -->
HTML;

return $content;

};
