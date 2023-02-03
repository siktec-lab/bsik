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

class ModuleEvent {
    /**
     * Module events name : 
     *  me-install, 
     *  me-uninstall, 
     *  me-activate, 
     *  me-deactivate, 
     *  me-update, 
     *  core-update,
     *  other-install,
     *  other-uninstall,
     *  other-activate,
     *  other-deactivate,
     *  other-update,
     *  signal-module,
     *  signal-core
     */
    public array $on            = []; 
    public ?\Closure $method    = null; // All methods has first arg Event_name string....

    // Construct the event:
    public function __construct(
        array $on_events = [],
        ?\Closure $event_method = null
    ) {

        //Set legal view name:
        $this->on = $on_events;
        $this->method = $event_method;
    
    }
}
