<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.2
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial
********************************************************************************/

namespace Bsik\Privileges;

use \Exception;

/**
 * PrivGod
 * a special group that sets god privileges
 */
class PrivGod extends PrivGroup {

	public const NAME = "god";
	//The group meta
	public const ICON  			= "fa-unlock-alt";
	public const DESCRIPTION  	= "Grants all privileges and overwrites any restrictions on the system";
	public array  $privileges = [
		"grant" => null
	];
	public function __construct(?bool $grant = null)
	{
		$this->set_priv("grant", $grant);
	}
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
RegisteredPrivGroup::register("\Bsik\Privileges\PrivGod");

/**
 * PrivGod
 * a special group that sets god privileges
 */
class PrivAccess extends PrivGroup {

	public const NAME = "access";
	//The group meta
	public const ICON  			= "fa-door-open";
	public const DESCRIPTION  	= "Grants access to the 3 core places in the system.";
	public array  $privileges = [
		"manage" 	=> null,
		"front"  	=> null,
		"product"  	=> null
	];
	public function __construct(?bool $manage = null, ?bool $front = null, ?bool $product = null)
	{
		$this->set_priv("manage", 	$manage);
		$this->set_priv("front", 	$front);
		$this->set_priv("product", 	$product);
	}
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
RegisteredPrivGroup::register("\Bsik\Privileges\PrivAccess");
/**
 * PrivUsers
 * a user specific privileges
 */
class PrivUsers extends PrivGroup {

	public const NAME = "users";
	//The group meta
	public const ICON  			= "fa-user-lock";
	public const DESCRIPTION  	= "Grants privileges to perform operations related to users (not admins) management across the platform.";
	public array  $privileges = [
		"view" 		=> null,
		"edit" 		=> null,
		"create" 	=> null,
		"delete" 	=> null,
		"interact" 	=> null
	];
	public function __construct(?bool $view = null, ?bool $edit = null, ?bool $create = null, ?bool $delete = null, ?bool $interact = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("edit", 	$edit);
		$this->set_priv("create", 	$create);
		$this->set_priv("delete", 	$delete);
		$this->set_priv("interact", $interact);
	}
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
RegisteredPrivGroup::register("\Bsik\Privileges\PrivUsers");
/**
 * PrivAdmins
 * a admins user specific privileges
 */
class PrivAdmins extends PrivGroup {

	public const NAME = "admins";
	//The group meta
	public const ICON  			= "fa-user-lock";
	public const DESCRIPTION  	= "Grants privileges to perform operations related to admins management across the platform - 'grant' will enable privileges management.";
	public array  $privileges = [
		"view" 		=> null,
		"edit" 		=> null,
		"create" 	=> null,
		"delete" 	=> null,
		"grant" 	=> null
	];
	public function __construct(?bool $view = null, ?bool $edit = null, ?bool $create = null, ?bool $delete = null, ?bool $grant = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("edit", 	$edit);
		$this->set_priv("create", 	$create);
		$this->set_priv("delete", 	$delete);
		$this->set_priv("grant", 	$grant);
	}
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION,
		];
	} 
}
RegisteredPrivGroup::register("\Bsik\Privileges\PrivAdmins");
/**
 * PrivAdmins
 * a admins user specific privileges
 */
class PrivRoles extends PrivGroup {

	public const NAME = "roles";
	//The group meta
	public const ICON  			= "fa-shield-alt";
	public const DESCRIPTION  	= "Grants privileges perform core roles related tasks";
	public array  $privileges = [
		"view" 		=> null,
		"edit" 		=> null,
		"create" 	=> null,
		"delete" 	=> null,
		"grant" 	=> null
	];
	public function __construct(?bool $view = null, ?bool $edit = null, ?bool $create = null, ?bool $delete = null, ?bool $grant = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("edit", 	$edit);
		$this->set_priv("create", 	$create);
		$this->set_priv("delete", 	$delete);
		$this->set_priv("grant", 	$grant);
	}
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION,
		];
	} 
}
RegisteredPrivGroup::register("\Bsik\Privileges\PrivRoles");
/**
 * PrivContent
 * a content specific privileges
 */
class PrivContent extends PrivGroup {

	public const NAME = "content";
	//The group meta
	public const ICON  			= "fa-book";
	public const DESCRIPTION  	= "Grants privileges to perform operations related to content published and managed on the platform.";
	public array  $privileges = [
		"view" 		=> null,
		"edit" 		=> null,
		"create" 	=> null,
		"delete" 	=> null,
		"upload" 	=> null,
		"download" 	=> null,
		"cache" 	=> null
	];
	public function __construct(?bool $view = null, ?bool $edit = null, ?bool $create = null, ?bool $delete = null, ?bool $upload = null, ?bool $download = null, ?bool $cache = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("edit", 	$edit);
		$this->set_priv("create", 	$create);
		$this->set_priv("delete", 	$delete);
		$this->set_priv("upload", 	$upload);
		$this->set_priv("download",	$download);
		$this->set_priv("cache", 	$cache);
	}
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
RegisteredPrivGroup::register("\Bsik\Privileges\PrivContent");
/**
 * PrivModules
 * a module specific privileges
 */
class PrivModules extends PrivGroup {

	public const NAME = "modules";
	//The group meta
	public const ICON  			= "fa-puzzle-piece";
	public const DESCRIPTION  	= "Grants privileges to manage modules on the platform.";
	public array  $privileges = [
		"view" 		=> null,
		"install" 	=> null,
		"activate" 	=> null,
		"settings" 	=> null,
		"endpoints" => null
	];
	public function __construct(?bool $view = null, ?bool $install = null, ?bool $activate = null, ?bool $settings = null, ?bool $endpoints = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("install", 	$install);
		$this->set_priv("activate", $activate);
		$this->set_priv("settings", $settings);
		$this->set_priv("endpoints", $endpoints);
	}
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
RegisteredPrivGroup::register("\Bsik\Privileges\PrivModules");
/**
 * PrivCore
 * a core operations specific privileges
 */
class PrivCore extends PrivGroup {

	public const NAME = "core";
	//The group meta
	public const ICON  			= "fa-code";
	public const DESCRIPTION  	= "Grants privileges to perform sensible platform core operations.";
	public array  $privileges = [
		"view" 		=> null,
		"install" 	=> null,
		"activate" 	=> null,
		"settings" 	=> null,
		"update" 	=> null
	];
	public function __construct(?bool $view = null, ?bool $install = null, ?bool $activate = null, ?bool $settings = null, ?bool $update = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("install", 	$install);
		$this->set_priv("activate", $activate);
		$this->set_priv("settings", $settings);
		$this->set_priv("update", 	$update);
	}
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
RegisteredPrivGroup::register("\Bsik\Privileges\PrivCore");
