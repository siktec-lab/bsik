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
$CoreBlockRender = function($APage, array $Values = []) {

    // Intellisense support
    if (!isset($APage)) $APage = new APage();
    
    //Core Block Defaults:
    $block_defaults = [
        "doc-type"   => "html",
        "html-attr" => "data-docby='BSIK Platform'"
    ];

    //Extend settings:
    $block_setting = array_merge_recursive($block_defaults, $Values);

    //Extended meta:
    $ex_meta = implode(PHP_EOL, $APage->additional_meta).PHP_EOL;

    ob_start();
        $APage->render_favicon(PLAT_URL_MANAGE."/lib/img/fav");
    $head_favicon = ob_get_clean();

    //Top Includes:
    ob_start();
        $APage->render_libs("css", "head");
        $APage->render_libs("js", "head");
    $top_includes = ob_get_clean();

    //Body css include:
    ob_start();
        $APage->render_libs("css", "bold");
    $body_includes = ob_get_clean();
    
$content = <<<HTML

<!doctype {$block_setting['doc-type']}>
<html {$block_setting['html-attr']}> 
    <head>
        <meta charset="{$APage->meta('charset')}" />
        <meta name="viewport"               content="{$APage->meta('viewport')}" />
        <meta name="author"                 content="{$APage->meta('author')}" />
        <meta name="description"            content="{$APage->meta('description')}" />
        <meta http-equiv="X-UA-Compatible"  content="IE=7" />
        {$APage->token["meta"]}
        <meta name="api"            content="{$APage->meta('api')}" />
        <meta name="module"            content="{$APage->meta('module')}" />
        <meta name="module-sub"            content="{$APage->meta('module-sub')}" />
        $head_favicon
        $ex_meta
        <title>{$APage->meta("title")}</title>
        <!-- START : Head includes -->
        $top_includes
        <!-- END : Head includes -->
        
    </head>
    <body {$APage->body_tag()} >
        <!-- START : Body includes -->
        $body_includes
        <!-- END : Body includes -->

HTML;

return $content;

};
