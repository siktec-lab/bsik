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
$CoreBlockRender = function(APage $APage, array $Values = []) {

    //Core Block Defaults:
    $block_defaults = [

    ];

    //Extend settings:
    $block_setting = array_merge_recursive($block_defaults, $Values);

    $menu_tpl = "<li class='menu-entry %s %s %s' data-menuact='%s' title='%s'><a href='%s'><span><i class='%s'></i>%s</span></a>%s</li>".PHP_EOL;
    $build_list = "<ul class='admin-menu'>".PHP_EOL;
    $current = $APage->module->name;
    $current_sub_menu = $APage->module->which;
    foreach ($APage->menu as $entry) {
        $sub_menu = "<ul class='entry-sub-menu'>".PHP_EOL;
        $parts = $APage::$std::arr_get_from($entry, ["text", "title", "desc", "icon", "action", "sub"], "");
        $is_loaded = strtolower($parts["action"]) === $current;
        //Save title + desc for later:
        if ($is_loaded) {
            $APage->module->header["sub-title"] = $parts["desc"];
            $APage->module->header["which"] = "";
            $APage->module->header["title"] = $parts["title"];
        }
        //Create module base url:
        $url = $APage::$conf["path"]["site_admin_url"]."/".strtolower($parts["action"]);
        //If sub menu is defined?
        if (!empty($parts["sub"])) {
            foreach ($parts["sub"] as $sub) {
                $sub_parts = $APage::$std::arr_get_from($sub, ["text", "title",  "desc","icon", "action"], "");
                $is_sub_loaded = $is_loaded && strtolower($sub_parts["action"]) === $current_sub_menu;
                if ($is_sub_loaded) {
                    $APage->module->header["sub-title"] = $sub_parts["desc"];
                    $APage->module->header["which"] = $sub_parts["title"];
                }
                $sub_menu .= sprintf($menu_tpl,
                    "",
                    "",
                    $is_sub_loaded ? "is-loaded" : "",
                    $sub_parts["action"], 
                    $sub_parts["title"],
                    $url."/".$sub_parts["action"], 
                    $sub_parts["icon"], 
                    $sub_parts["text"],
                    ""
                );
            }
            $sub_menu .= "</ul>".PHP_EOL;
        } else {
            $sub_menu = "";
        }
        //Build Entry:
        $build_list .= sprintf($menu_tpl,
            !empty($sub_menu) ? "has-submenu" : "", 
            $is_loaded ? "is-loaded" : "",
            !empty($sub_menu) && $is_loaded ? "open-menu" : "",
            $parts["action"], 
            $parts["title"],
            !empty($sub_menu) ? "javascript:void(0)" : $url,
            $parts["icon"], 
            $parts["text"],
            $sub_menu
        );
    }
    $build_list .= "</ul>";
    
$content = <<<HTML
    <!-- START: DYNAMIC SIDE MENU -->
    $build_list
    <!-- END: DYNAMIC SIDE MENU -->
HTML;

return $content;

};
