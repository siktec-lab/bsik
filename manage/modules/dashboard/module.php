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

$ModuleBlockRender = function(APage $APage, Admin $Admin = null) {

//Load additional libs:
//$APage->include("head", "js", "link", ["name" => "http://unpkg.com/tableexport.jquery.plugin@1.10.22/tableExport.min.js"]);
//$APage->include("body", "js", "link", ["name" => PLAT_FULL_DOMAIN."/manage/lib/required/bootstrap-table/extensions/export/bootstrap-table-export.js"]);
//$APage->include("head", "js", "link", ["name" => PLAT_FULL_DOMAIN."/manage/lib/js/tableExport.js"]);

$content = "";

/******************************  Overview Content  *****************************/
if (in_array($APage->module->which, ["default"])) {

    $count_listings     = $APage::$db->getValue("listings", "count(*)");
    $count_scraped      = $APage::$db->where("status", 2)->getValue("listings", "count(*)");
    $count_published    = $APage::$db->where("published", 2)->getValue("listings", "count(*)");
    $count_categories   = $APage::$db->where("status", 2)->groupBy("amazon_category")->getValue("listings", "count(*)");

    $stat_imported      = APageComponents::stat_card("imported", $count_listings, "fas fa-chart-bar", "yellow");
    $stat_scraped       = APageComponents::stat_card("scraped",    $count_scraped, "fas fa-chart-bar", "info");
    $stat_published     = APageComponents::stat_card("published", $count_published, "fas fa-chart-bar", "warning");
    $stat_categories    = APageComponents::stat_card("categories", $count_categories, "fas fa-chart-bar", "danger");

    //Controls:
    $stats = <<<HTML
        <div class="container pt-3 pb-3">
            <h2>Scraper Data-Base Summary:</h2>
            <div class="row">
                <div class="col-xl-3 col-lg-6">
                    {$stat_imported}
                </div>
                <div class="col-xl-3 col-lg-6">
                    {$stat_scraped}
                </div>
                <div class="col-xl-3 col-lg-6">
                    {$stat_published}
                </div>
                <div class="col-xl-3 col-lg-6">
                    {$stat_categories}
                </div>
            </div>
        </div>
    HTML;
    
    //Template:
    $content = <<<HTML
        <div class='container'>
            {$stats}
        </div>
    HTML;
/******************************  Users roles content  *****************************/
} else {
    /******************************  Unknown menu entry throw  *****************************/
    throw new Exception("Requested of module menu which is not recognized [{$APage->module->which}].", E_NOTICE);
}

return $content;

};
