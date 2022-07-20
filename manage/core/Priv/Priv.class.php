<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.4
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial
1.0.2:
    -> added update capabilities to merge definitions.
	-> added some rendering tags helper to definitions to give some easy dump for debugging.
	-> included core platform by definition -> this may change in the future:
1.0.3:
    -> now groups has metadat -> icon, description.
	-> entire groups are dynamically registered and evaluated.
	-> added support for updating and extending based on arrays and json
1.0.4:
	-> fixed bug and error in RegisteredPrivGroup - was accepting empty names and not checking correctly if was allready registered.
	-> Improved register - performance wise.
	-> group has improved to support null values of privileges tags.
	-> function names -> allowed() in groups is now is_allowed(). 
	-> function names -> groups() in definitions is now defined_groups(). 
	-> added group method called defined() that returns both true and false tags.
	-> all_granted() improved - now accepts a boolean to implode results, also filters out empty tags.
	-> fixed null overriding on update - now on update null tags will be ignored.
1.0.5
	-> intreduced in definition helper methods if() -> then() for quick inline checks.
    -> added a helper called can() that is shorter for if()->then() to get boolean response.
1.0.6
	-> fixed bug with god flags not set when serializing.
********************************************************************************/

/**
 * PrivGroup - defines a a group of privileges tags of some type
 */
namespace Bsik\Privileges;

use \Exception;
use Throwable;

class RegisteredPrivGroup {

	public static array $registered = [];
	
	public static function register(mixed $group) {
		if (
			!empty($group::NAME) &&									// Not empty name which will break everything ?
			class_exists($group) &&  								// Known class ?
			!array_key_exists($group::NAME, self::$registered)     // Not registered ?
		)
			self::$registered[$group::NAME] = $group;
	}

	public static function dump() {
		var_dump(self::$registered);
	}

	public static function get_class(string $name) : string|null {
		return self::$registered[$name] ?? null;
	}

}

abstract class PrivGroup {

	//The group name
	public const NAME  			= null;
	//The group meta
	public const ICON  			= null;
	public const DESCRIPTION  	= null;

	//Defind tags and their states:
	public array  $privileges;
		
	/**
	 * is
	 * checks what is this group name:
	 * @param  string $group_name
	 * @return bool
	 */
	public function is(string $group_name) : bool {
		return static::NAME === $group_name;
	}
		
	/**
	 * meta
	 * default return meta implementation - can and should be overide in groups that has more / less meta tags.
	 * @return array -> associative array meta - value.
	 */
	public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
	/**
	 * has
	 * check if the group has a specific tag defined
	 * that does no take in account the state of this tag
	 * @param  string $tag
	 * @return bool
	 */
	public function has(string $tag) : bool {
		return array_key_exists($tag, $this->privileges); // We use array_key_exists to allow null values.
	}

	/**
	 * isset
	 * check if the group has a specific tag defined
	 * AND that it has a state - which mean it is not null.
	 * @param  string $tag
	 * @return bool
	 */
	public function isset(string $tag) : bool {
		return array_key_exists($tag, $this->privileges) && !is_null($this->privileges[$tag]); // We use array_key_exists to allow null values.
	}

	/**
	 * set
	 * sets all those tags with a specific grant value
	 * @param  bool|string|int|null $grant - all tags will have this grant value
	 * @param  array $tags 
	 * @return void
	 * @throws Exception /E_PLAT_ERROR => when tag is not defined in this group
	 */
	public function set(bool|string|int|null $grant = null, string ...$tags) : void {
		foreach ($tags as $tag) {
			$this->set_priv($tag, $grant);
		}
	}
	
	/**
	 * set_from_array
	 * grant defined allowed tags from an associative array
	 * will ignore unknown tags
	 * @param  array $tags
	 * @return void
	 */
	public function set_from_array(array $tags) : void {
		foreach ($tags as $tag => $state) {
			if (is_string($tag) && $this->has($tag))
				$this->set_priv($tag, $state);
		}
	}
	
	/**
	 * set_from_json
	 * grant defined allowed tags from a json representation pf tags
	 * will ignore unknown tags 
	 * @param  string $json
	 * @return bool - false if decode failed.
	 */
	public function set_from_json(string $json) : bool {
		$tags = json_decode($json, true);
		if (is_array($tags)) {
			$this->set_from_array($tags);
			return true;
		}
		return false;
	}

	/**
	 * set_priv
	 * set a specific tag and its grant status
	 * @param  string $tag
	 * @param  bool|string|int $grant - ['true', 'TRUE', true, 1] are considered as boolean true ? We accept those true to support all the optional returned types when using remote data.
	 * @return void
	 * @throws Exception /E_PLAT_ERROR => when tag is not defined
	 */
	public function set_priv(string $tag, bool|string|int|null $grant) : void {
		if ($this->has($tag)) {
			//We accept those true to support all the optional returned types when using remote data.
			if (in_array($grant, ['true', 'TRUE', true, 1], true))
				$this->privileges[$tag] = true;
			elseif (in_array($grant, ['false', 'FALSE', false, 0], true))
				$this->privileges[$tag] = false;
			else 
				$this->privileges[$tag] = null;
		} else {
            throw new Exception("tried to grant an unknown privilege tag [{$tag}]", E_PLAT_ERROR);
        }
	}
	
	/**
	 * tags
	 * get all defined tags - only names without values
	 * @return array
	 */
	public function tags() : array {
		return array_keys($this->privileges);
	}
	
	/**
	 * all
	 * get all tags with with there statuses - will include null tags also
	 * @return array
	 */
	public function all() : array {
		return $this->privileges;
	}

	/**
	 * all
	 * get all tags with with there statuses - only true false tags
	 * @return array
	 */
	public function defined() : array {
		return array_filter($this->privileges, fn($v) => !is_null($v));
	}

	/**
	 * granted
	 * get all defined tags that are granted
	 * @return array
	 */
	public function granted() : array {
		return array_keys($this->privileges, true);
	}

	/**
	 * denied
	 * get all defined tags that are denied
	 * @return array
	 */
	public function denied() : array {
		return array_keys($this->privileges, false);
	}

	/**
	 * allowed
	 * check if a specific tag is granted
	 * @param  string $tag
	 * @return bool
	 * @throws Exception /E_PLAT_WARNING => when tag is not defined
	 */
	public function is_allowed(string $tag) : bool {
		if ($this->has($tag)) {
			return $this->privileges[$tag] ? true : false; // we use this condition to force boolean return as null is avalid tag status.
		} 
		throw new Exception("tried to check an unknown privilege tag", E_PLAT_WARNING);
		return false;
	}


}

/**
 * PrivDefinition
 * a definition group which sets a bundle of privileges groups
 */
abstract class PrivDefinition {

	//Holds all the defined groups - group-name => PrivGroup (of some type)
	public array $groups = [];
	
	//Special flag that sets god-mode enabled 
	public ?bool $god = null;
	
	//Condition Flag:
	private bool $_allow = false;

	/**
	 * defined
	 * return all defined group names in this definition
	 * @return array
	 */
	public function defined_groups() : array {
		return array_keys($this->groups);
	}
	
	/**
	 * all_granted 
	 * returns an array of group with string of tags granted privileges 
	 * @example returns => ["users" => "edit,view"]
	 * @param bool $as_str => will implode tags as a list.
	 * @return array
	 */
	public function all_granted(bool $as_str = false) : array {
		$tags = []; 
		foreach ($this->groups as $name => $group) {
			$tags[$name] = $as_str ? implode(',', $group->granted()) : $group->granted();
		}
		return array_filter($tags);
	}

	/**
	 * all_defined 
	 * returns an array of all granted or declined tags
	 * @example returns => ["users" => ["edit" => true, "view" => false]
	 * @return array
	 */
	public function all_defined() : array {
		$tags = []; 
		foreach ($this->groups as $name => $group) {
			$tags[$name] = $group->defined();
		}
		return $tags;
	}
	
	/**
	 * all_privileges
	 * get an array of all defined groups and their tags + state - only tags that are not null
	 * @return array
	 */
	public function all_privileges() : array {
		$tags = []; 
		foreach ($this->groups as $name => $group) {
			$tags[$name] = $group->all();
		}
		return $tags;
	}
	
	/**
	 * all_meta
	 * get an array of all defined groups and their meta attributes
	 * @return array
	 */
	public function all_meta() : array {
		$groups = []; 
		foreach ($this->groups as $name => $group) {
			$groups[$name] = $group::meta();
		}
		return $groups;
	}

	/**
	 * update 
	 * $with definition overwrite this definition privileges.
	 * will also inherit new group if they are not defined
	 * @param  PrivDefinition|null $with
	 * @return void
	 */
	public function update(?PrivDefinition $with) {
		if (is_null($with))
			return;
		foreach ($with->groups as $name => $group) {
			if ($this->is_defined($name)) {
				$current = $this->group($name);
				foreach ($group->privileges as $tag => $state) {
					if (!is_null($state))
						$current->set_priv($tag, $state);
				} 
			} else {
				$this->define($group);
			}
		}
		if (!is_null($with->god))
			$this->god = $with->god;
	}

	/**
	 * extends 
	 * $defaults are inherited only if they are not allready defined.
	 * @param  PrivDefinition|null $defaults
	 * @return void
	 */
	public function extends(?PrivDefinition $defaults) {
		if (is_null($defaults))
			return;
		foreach ($defaults->groups as $name => $group) {
			if ($this->is_defined($name)) {
				$current = $this->group($name);
				foreach ($group->privileges as $tag => $state) {
					if (!is_null($state) && !$current->isset($tag))
						$current->set_priv($tag, $state);
				} 
			} else {
				$this->define($group);
			}
		}
		if (is_null($this->god))
			$this->god = $defaults->god;
	}
	/**
	 * update_from_arr
	 * take groups defined as array and updates this definition
	 * @param array $groups 
	 * @return void
	 */
	public function update_from_arr(array $groups) : void {
        foreach ($groups as $group => $tags) {
			if (!is_array($tags))
				continue;
			if ($this->is_defined($group)) {
				$this->group($group)->set_from_array(is_array($tags) ? $tags : []);
			} elseif ($set = RegisteredPrivGroup::get_class($group)) {
				$new_group = new $set;
				$new_group->set_from_array($tags);
				$this->define($new_group);
			}
        }
    }
	
	/**
	 * update_from_json
	 * take groups defined in json and updates this definition
	 * @param string $json 
	 * @return bool - false if json is not valid
	 */
	public function update_from_json(string $json) : bool {
		$groups = json_decode($json, true);
		if (is_array($groups)) {
			$this->update_from_arr($groups);
			return true;
		}
		return false;
    }

	/**
	 * is_defined
	 * checks if a specific group is defined
	 * @param  string $group
	 * @return bool
	 */
	public function is_defined(string $group) : bool {
		return array_key_exists($group, $this->groups);
	}
	
	/**
	 * define
	 * stores a given set of groups in this definition:
	 * @param  array $groups - PrivGroup array
	 * @return $this
	 */
	public function define(PrivGroup ...$groups) {
		foreach($groups as $group) {
			$this->groups[$group::NAME] = $group;
			if ($group instanceof PrivGod) {
				$this->god = $group->is_allowed("grant");
			}
		}
		return $this;
	}
	
	/**
	 * group
	 * return a defined group object or null
	 * @param  string $name
	 * @return PrivGroup|null
	 */
	public function group(string $name) : PrivGroup|null {
		return $this->groups[$name] ?? null;
	}
	
	/**
	 * check
	 * internal check that checks a gate definition against an ask definition
	 * if ask has enough required privileges it will return true
	 * @param  PrivDefinition $gate
	 * @param  PrivDefinition $ask
	 * @param  array $messages - will be filled with messages of required privileges if they are missing
	 * @return bool
	 */
	protected static function check(PrivDefinition $gate, PrivDefinition $ask, array &$messages) : bool {

		$allowed = true;

		//First check god modes:
		if ($ask->god === true) {
			return true;
		} elseif ($gate->god === true) {
			$messages[] = "'god' privileges is required";
			return false;
		}
		//Iterate over privileges:
		foreach ($gate->groups as $group_name => $gate_group) {
			$gate_tags = $gate_group->granted();
			if (!empty($gate_tags)) {
				$ask_group = $ask->group($group_name);
				if (!is_null($ask_group)) {
					$ask_tags = $ask_group->granted();
					$needed   = implode(',',array_diff($gate_tags, $ask_tags));
					if (!empty($needed)) {
						$messages[] = "required '$needed' privileges tags of group '$group_name'";
						$allowed = false;
					}
				} else {
					$messages[] = "'$group_name' group privileges is required";
					$allowed = false;
				}
			}
		}
		return $allowed;
	}
	
	/**
	 * has_privileges
	 * @param  PrivDefinition $against
	 * @param  array $messages - will be filled with messages of required privileges if they are missing
	 * @return bool
	 */
	public function has_privileges(PrivDefinition $against, array &$messages = []) : bool {
		return false;
	}
		
	/**
	 * if
	 * sets and check the required tags - if ok the _allow flag will be raised
	 * @param  array $tags => ["group.tag1", "group.tag3"]
	 * @return PrivDefinition
	 */
	public function if(...$tags) : PrivDefinition {
		//Enforce god:
		if ($this->god === true) {
			$this->_allow = true;
		} else {
			$this->_allow = false; // reset check:
			foreach ($tags as $tagStr) {
				[$group_name, $tag] = explode(".", $tagStr);
				$group = $this->group($group_name);
				try {
					if (is_null($group) || !$group->is_allowed($tag)) {
						return $this;
					}
				} catch(Exception $t) {
					//This will prevent undefined tags to be thrown
					//and consider them as false e.g not met.
					return $this;
				}
			}
			$this->_allow = true;
		}
		return $this;
	}
		
	/**
	 * can
	 * shorter method to check if this definition has some required tags
	 * similar to -> $this->if(...$tags)->then(true, false);
	 * @param  array $tags => ["group.tag1", "group.tag3"]
	 * @return bool
	 */
	public function can(...$tags) : bool {
		return $this->if(...$tags)->then(true, false);
	}
	/**
	 * then
	 * an execution block to be called after if() that will check if _allow is true and 
	 * executes do otherwise else will be executed or returned
	 * @param  mixed $do    - a callable or some other type to be returned
	 * @param  mixed $else	- a callable or some other type to be returned
	 * @param  mixed $args  - arguments to be passed to the do and else blocks
	 * @return mixed
	 */
	public function then(mixed $do = null, mixed $else = null, array $args = []) : mixed {
		if ($this->_allow) {
			return is_callable($do) 	? call_user_func_array($do, $args) : $do;
		} else {
			return is_callable($else) 	? call_user_func_array($else, $args) : $else;
		}
	}

	/**
	 * safe_unserialize
	 * safely unserialize a blob (serialized) and return the Definition object or null
	 * @param  string|null $blob
	 * @return PrivDefinition|null
	 */
	public static function safe_unserialize(?string $blob) : PrivDefinition|null {
		$obj = null;
		try {
			if (!empty($blob))
				$obj = unserialize($blob);
		} catch (Exception $e) {
			return null;
		}
		if ( $obj instanceof PrivDefinition ) {
			//Set god flag if needed:
			if ($obj->can("god.grant")) {
				$obj->god = true;
			}
			return $obj;
		}
		return null;
	}
	
	/**
	 * str_tag_list
	 * handy method to print granted groups with corresponding granted tags:
	 * @param  PrivDefinition $definition
	 * @param  string $prefix
	 * @param  string $suffix
	 * @return string
	 */
	public static function str_tag_list(PrivDefinition $definition, string $prefix = "", string $suffix = "") {
		$group_tags = $definition->all_granted(true);
		array_walk($group_tags, function(&$tags, $group) use ($prefix, $suffix) { 
			$tags = $prefix.$group.' > '.$tags.$suffix;
		});
		return implode($group_tags);
	} 
}

/**
 * GrantedPrivileges
 * a definition used for issuer entities such as users
 */
class GrantedPrivileges extends PrivDefinition {

	/**
	 * has_privileges
	 * return whether this entity can has privileges to use / access the given definition 
	 * @param  PrivDefinition $against
	 * @param  array $messages - will be filled with messages of required privileges if they are missing
	 * @return bool
	 */
	public function has_privileges(PrivDefinition $against, array &$messages = []) : bool {
		return self::check($against, $this, $messages);
	}
}

/**
 * RequiredPrivileges
 * a definition used for providers entities such as api endpoints
 */
class RequiredPrivileges extends PrivDefinition 
{

	/**
	 * has_privileges
	 * return whether a given definition has privileges to access / use this definition 
	 * @param  PrivDefinition $against
	 * @param  array $messages - will be filled with messages of required privileges if they are missing
	 * @return bool
	 */
	public function has_privileges(PrivDefinition $against, array &$messages = []) : bool {
		return self::check($this, $against, $messages);
	}

}


require_once "CorePrivGroups.php";