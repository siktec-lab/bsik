<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->creation - initial
*******************************************************************************/

namespace Bsik\Module;

require_once BSIK_AUTOLOAD;

use \Bsik\Std;
use \Bsik\Privileges as Priv;
use \Bsik\Objects\SettingsObject;
use \Bsik\Render\AModuleRequest;

/** 
 * ModuleView
 * 
 * This defines a module view which is used to render the module in a specific way.
 * 
 * @package Bsik\Module
 * 
 */
class ModuleView {

    public string $name;
    public Priv\RequiredPrivileges $priv;
    public SettingsObject $settings;
    public ?\Closure $render = null;
    
    public function __construct(
        string $name,
        ?Priv\RequiredPrivileges $privileges = null,
        ?SettingsObject $settings = null,
    ) {
        
        //Set legal view name:
        $this->name = Std::$str::filter_string($name, AModuleRequest::$which_pattern);

        //Privileges:
        $this->priv = $privileges ?? new Priv\RequiredPrivileges();

        //Settings:
        $this->settings = $settings ?? new SettingsObject();

    }

}



