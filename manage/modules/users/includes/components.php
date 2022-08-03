<?php
/*
 * Simply a local module php file that will extend module and api
 */
require_once BSIK_AUTOLOAD;

use \Bsik\Builder\Components;
use \Bsik\Render\Template;
use \Bsik\Privileges as Priv;


/****************************************************************************/
/******** component return an empty priv definition group *******************/
/****************************************************************************/
Components::register_once("all_empty_privileges", function() {
    $all = new Priv\GrantedPrivileges();
    foreach (Priv\RegisteredPrivGroup::$registered as $group) {
        $all->define(new $group);
    }
    return $all;
}, true);

/****************************************************************************/
/******** component that renders a for of the privileges ********************/
/****************************************************************************/
Components::register_once("privileges_form_check", function(Template $engine, string $id, array $privileges = [], array $groups_meta = []) {
    return $engine->render("privileges-form-check", [
        "form_id"       => $id,
        "privileges"    => $privileges,
        "groups_meta"   => $groups_meta
    ]);
}, true);
