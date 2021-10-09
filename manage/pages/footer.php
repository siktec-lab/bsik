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

    ];

    //Extend settings:
    $block_setting = array_merge_recursive($block_defaults, $Values);

    //Top Includes:
    ob_start();
        $APage->render_libs("js", "body");
    $end_includes = ob_get_clean();
    //Additional html:
    $add_html = implode("\n",$APage->html_container);
    //Global additional content:
$content = <<<HTML
        <!-- START : body includes -->
        {$add_html}
        <!-- END : body includes -->
        <!-- START : body includes -->
        {$end_includes}
        <!-- END : body includes -->
    </body>
</html>

HTML;

return $content;

};
