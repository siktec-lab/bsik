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
    if (!isset($APage)) $Page = new APage();
    
    //Core Block Defaults:
    $block_defaults = [

    ];

    //Extend settings:
    $block_setting = array_merge_recursive($block_defaults, $Values);

    //Top Includes:
    ob_start();
        $APage->render_libs("js", "body");
    $end_includes = ob_get_clean();

$content = <<<HTML
        <!-- START : body includes -->
        {$end_includes}
        <!-- END : body includes -->
    </body>
</html>

HTML;

return $content;

};
