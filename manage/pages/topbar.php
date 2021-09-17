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


    
$content = <<<HTML
    <div class="container-fluid">
        <div class="admin-logo">
            <img src="{$APage->get('plat-logo')}" />
        </div>
        <div class="admin-notification">
        </div>
    </div>
HTML;

return $content;

};
