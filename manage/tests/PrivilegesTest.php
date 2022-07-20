<?php

require_once "a_main.php";

use PHPUnit\Framework\TestCase;
use Bsik\Privileges as Priv;

class PrivilegesTest extends TestCase
{
    
    public static function setUpBeforeClass() : void {

    }

    public static function tearDownAfterClass() : void {
        
    }

    public function setUp() : void {

    }
    public function tearDown() : void {

    }

    //Check serialization of definitions:
    public function testSerializationOfPrivileges() : void {
        $required   = new Priv\RequiredPrivileges();
        $mypriv     = new Priv\GrantedPrivileges();
        $required->define(
            new Priv\PrivUsers(
                edit : true, 
                create : false, 
                delete: true
            ),
            new Priv\PrivGod(grant : false),
            new Priv\PrivAccess(
                manage : true,
                front : false,
                product : false
            )
        );
        $mypriv->define(
            new Priv\PrivUsers(
                edit    : false, 
                create  : true, 
                delete  : true
            ),
            new Priv\PrivGod(grant : false),
            new Priv\PrivCore(view:true)
        );
        $gate = serialize($required);
        $ask  = serialize($mypriv);
        unset($mypriv);
        unset($required);
        $gate = Priv\RequiredPrivileges::safe_unserialize($gate);
        $ask  = Priv\GrantedPrivileges::safe_unserialize($ask);
        
        $this->assertEquals(false, is_null($gate));
        $this->assertEquals(false, is_null($ask));
    }
    
    //Check for simple privileges met check:
    public function testAccessCheck() : void {
        $required   = new Priv\RequiredPrivileges();
        $required->define(
            new Priv\PrivAccess(manage : true)
        );
        $mypriv_ok = new Priv\GrantedPrivileges();
        $mypriv_ok->define(
            new Priv\PrivAccess(manage : true)
        );
        $mypriv_no = new Priv\GrantedPrivileges();
        $mypriv_no->define(
            new Priv\PrivAccess(manage : false)
        );

        $this->assertTrue($required->has_privileges($mypriv_ok), "Access granted expected but was rejected");
        $this->assertFalse($required->has_privileges($mypriv_no), "Access should have been declined. Instead was granted.");
    }

    //Check for simple privileges met check:
    public function testIndividualTagCheck() : void {
        $policy   = new Priv\GrantedPrivileges();
        $policy->define(
            new Priv\PrivUsers(
                view : true,
                edit : false
            )
        );
        $this->assertTrue($policy->group(Priv\PrivUsers::NAME)->is_allowed("view"), "Expected view to be allowed but was rejected.");
        $this->assertFalse($policy->group(Priv\PrivUsers::NAME)->is_allowed("edit"), "Expected edit to be declined but was granted.");
        $this->expectExceptionCode(E_PLAT_WARNING);
        $policy->group(Priv\PrivUsers::NAME)->is_allowed("nodefinedtag");
    }

    //Check Definition Simple Update from Objects:
    public function testDefinitionUpdatingFromObjects() : void {
        $required = new Priv\RequiredPrivileges();
        $mypriv = new Priv\GrantedPrivileges();
        $required->define(
            new Priv\PrivUsers(
                edit : true, 
                create : false, 
                delete: true
            ),
            new Priv\PrivGod(grant : false),
            new Priv\PrivAccess(
                manage : true,
                front : false,
                product : false
            )
        );
        $mypriv->define(
            new Priv\PrivUsers(
                edit    : false, 
                create  : true, 
                delete  : true
            ),
            new Priv\PrivGod(grant : true),
            new Priv\PrivCore(view:true)
        );
        $required->update($mypriv);
        unset($mypriv);
        $granted = trim(Priv\PrivDefinition::str_tag_list($required, "", " "));
        $this->assertEquals(
            "users > create,delete god > grant access > manage core > view", 
            $granted, 
            "It seems privileges are not updating as required!"
        );
    }

    //Check updating definitions with array and json:
    public function testDefinitionUpdatingFromArraysAndObjects() : void {
        $required = new Priv\RequiredPrivileges();
        
        $required->update_from_arr([
            "users"     => [],
            "core"      => [],
            "content"   => [],
        ]);
        // defined but none allowed:
        $defined_but_empty = trim(Priv\RequiredPrivileges::str_tag_list($required, "", " "));
        $this->assertEmpty($defined_but_empty, "should not have any granted tags.");
        
        $required->update_from_arr([
            "users"     => [ "view"     => true   ],
            "core"      => [ "install"  => true   ],
            "content"   => [ "notdefined" => true ], // This should be defined but none granted
            "access"    => [ "manage" => true     ]
        ]);
        // added permissions some ignored:
        $granted_some_ignored = trim(Priv\RequiredPrivileges::str_tag_list($required, "", " ")); 
        $this->assertEquals(
            "users > view core > install access > manage",
            $granted_some_ignored,
            "update from array failed."
        );

        $required->update_from_json(json_encode([
            "users"     => [ "view"     => true   ],
            "core"      => [ "install"  => false  ],
            "access"    => [ "manage" => true     ]
        ]));
        // removed permissions:
        $some_removed = trim(Priv\RequiredPrivileges::str_tag_list($required, "", " ")); 
        $this->assertEquals(
            "users > view access > manage",
            $some_removed,
            "update from json failed - maybe not removing privileges."
        );
        
        $new_from_other = new Priv\RequiredPrivileges();
        $new_from_other->define(new Priv\PrivModules(
            view:true
        ));
        $new_from_other->define(new Priv\PrivUsers(
            view : false
        ));
        $required->update($new_from_other);
        // removed permissions:
        $more_removed_from_obj = trim(Priv\RequiredPrivileges::str_tag_list($required, "", " ")); 
        $this->assertEquals(
            "access > manage modules > view",
            $more_removed_from_obj,
            "update from object failed - tested remove and add privileges by update."
        );
    }

    //test get all methods of definitions:
    public function testGetAllMethodsOfDefinitions() : void {
        $new_from_other = new Priv\RequiredPrivileges();
        $new_from_other->define(new Priv\PrivModules(
            view:true
        ));
        $new_from_other->define(new Priv\PrivUsers(
            view : false
        ));

        $all = $new_from_other->all_privileges();
        $this->assertEqualsCanonicalizing(
            [
                "modules" => [
                    "view"      => true,
                    "install"   => null,
                    "activate"  => null,
                    "settings"  => null
                ],
                "users" => [
                    "view"      => false,
                    "edit"      => null,
                    "create"    => null,
                    "delete"    => null,
                    "interact"  => null
                ]
            ],
            $all,
            "failed get all privileges from definition"
        );
        $granted = $new_from_other->all_granted();
        $this->assertEqualsCanonicalizing(
            ["modules" => ["view"]],
            $granted,
            "failed get all granted privileges from definition"
        );
        $defined = $new_from_other->all_defined();
        $this->assertEqualsCanonicalizing(
            [
                "modules" => [
                    "view" => true,
                ],
                "users" => [
                    "view" => false,
                ]
            ],
            $defined,
            "failed get all defined and set privileges from definition"
        );
    }

    //test if then conditions:
    public function testIfThenPrivilegesConditions() : void {
        $policy = new Priv\GrantedPrivileges();
        $policy->define(    
            new Priv\PrivModules(
                view        : true,
                install     : true,
                activate    : false
            ),
            new Priv\PrivUsers(
                view        : true,
                interact    : true
            )
        );
        //Simple return value example:
        $simple_return = $policy->if("modules.view", "users.view")->then("granted", "declined");
        $this->assertEquals("granted", $simple_return, "failed IF->THEN simple string return value");

        //Callback return granted example:
        $callback_return = $policy->if("modules.view", "users.view")->then(function($name) { return "granted for {$name}"; }, "declined", ["name" => "siktec"]);
        $this->assertEquals("granted for siktec", $callback_return,  "failed IF->THEN DO callback return");
        
        //Callback return decline example:
        $callback_return = $policy->if("modules.activate", "users.view")->then("granted", function($name) { return "declined for {$name}"; }, ["name" => "siktec"]);
        $this->assertEquals("declined for siktec", $callback_return, "failed IF->THEN ELSE callback return");
    }

    //test extending privileges:
    public function testExtendingPrivilegesMethod() : void {
        $module = new Priv\RequiredPrivileges();
        $module->define(    
            new Priv\PrivModules(
                view        : true,
                settings    : false
            ),
            new Priv\PrivUsers(
                view        : true
            )
        );
        $view = new Priv\RequiredPrivileges();
        $view->define(    
            new Priv\PrivGod(
                grant       : true
            ),
            new Priv\PrivModules(
                settings        : true
            ),
            new Priv\PrivUsers(
                interact    : true
            )
        );
        $view->extends($module);
        $result = trim(Priv\RequiredPrivileges::str_tag_list($view, "", " "));
        $this->assertEquals(
            "god > grant modules > view,settings users > view,interact",
            $result,
            "failed extending policy method"
        );
    }
}