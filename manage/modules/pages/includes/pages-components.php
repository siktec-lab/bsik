<?php
//Extending the Api of manage:
require_once PLAT_PATH_AUTOLOAD;

use Bsik\Builder\Components;
use Bsik\Render\Template;
use Bsik\Objects\SettingsObject;

/****************************************************************************/
/*******************  Custom filters / Validators     ***********************/
/****************************************************************************/

//A component to generat settings html form
Components::register_once("settings_object_form", function(
    SettingsObject $settings,
    array $attrs, 
    Template $engine, 
    string $template
) {
    $diff = $settings->diff_summary();
    $parts = $settings->dump_parts(false, "values-merged", "options", "descriptions");
    $context = array_merge($parts, $diff);
    //Add special attributes:
    $context["attrs"] = $attrs;
    return $engine->render(
        name    : $template, 
        context : $context
    );
});
