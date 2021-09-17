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
require_once PLAT_PATH_CORE.DS.'BsikModule.class.php';
require_once PLAT_PATH_CORE.DS.'APageComponents.class.php';

$ModuleBlockRender = function(APage $APage, Admin $Admin = null, BsikModule &$Module = null) {

    //Load additional libs:
    //$APage->include("head", "js", "link", ["name" => "http://unpkg.com/tableexport.jquery.plugin@1.10.22/tableExport.min.js"]);
    //$APage->include("body", "js", "link", ["name" => PLAT_FULL_DOMAIN."/manage/lib/required/bootstrap-table/extensions/export/bootstrap-table-export.js"]);
    //$APage->include("head", "js", "link", ["name" => PLAT_FULL_DOMAIN."/manage/lib/js/tableExport.js"]);

    //Use BsikModule for better coding:
    $Module->set_views(["modules"], "modules");

    /******************************  Overview Content  *****************************/
    $Module->view("modules", function (APage $APage, Admin $Admin = null) {
    
        
    $count_installed    = $APage::$db->getValue("admin_modules", "count(*)");
    $count_activated    = $APage::$db->where("status", 1)->getValue("admin_modules", "count(*)");
    $count_notactive    = $APage::$db->where("status", 0)->getValue("admin_modules", "count(*)");
    $count_hasupdate    = $APage::$db->where("status", 2)->getValue("admin_modules", "count(*)");

    $stat_installed     = APageComponents::stat_card("Installed", $count_installed, "fas fa-gem", "yellow");
    $stat_activated     = APageComponents::stat_card("Activated",    $count_activated, "fas fa-store", "info");
    $stat_notactive     = APageComponents::stat_card("Disabled", $count_notactive, "fas fa-store-slash", "warning");
    $stat_hasupdate     = APageComponents::stat_card("Has Updates", $count_hasupdate, "fas fa-history", "danger");

    //Controls:
    $stats = <<<HTML
        <div class="container pt-3 pb-3">
            <div class="row">
                <div class="col-xl-3 col-lg-6">
                    {$stat_installed}
                </div>
                <div class="col-xl-3 col-lg-6">
                    {$stat_activated}
                </div>
                <div class="col-xl-3 col-lg-6">
                    {$stat_notactive}
                </div>
                <div class="col-xl-3 col-lg-6">
                    {$stat_hasupdate}
                </div>
            </div>
        </div>
    HTML;

    //Template:
        return <<<HTML
        <div class='container'>
            {$stats}
        </div>

    HTML;
    });


};
