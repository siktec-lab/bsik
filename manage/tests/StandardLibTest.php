<?php

require_once "a_main.php";

use PHPUnit\Framework\TestCase;
use Bsik\Std;

class StandardLibTest extends TestCase
{

    public static function setUpBeforeClass() : void
    {
    }

    public static function tearDownAfterClass() : void
    {
    }

    public function setUp() : void
    {
    }

    public function tearDown() : void
    {

    }

    // arr_validate() - test:
    public function testArrayValidateHelper() : void {
        $usecase = [
            "name"      => "siktec",
            "usable"    => true,
            "child"     => "",
            "data"      => [
                "one" => 1,
                "two" => 2,
                "three" => null
            ]
        ];
        $simple = Std::$arr::validate([
            "name"   => "string", 
            "usable" => "boolean",
            "child"  => "any"
        ], $usecase);
        $this->assertTrue($simple, "failed simple array validate helper std");

        $empty = Std::$arr::validate([
            "child" => "string:empty"
        ], $usecase);
        $this->assertFalse($empty, "failed empty check array validate helper std");

        $traversal = Std::$arr::validate([
            "data.two"   => "integer",
            "data.three" => "NULL",
        ], $usecase);
        $this->assertTrue($traversal, "failed nested check array validate helper std");

        $fn = [
            "onlystringortrue" => function($v) { return $v ? true : false; }
        ];
        $withcustom = Std::$arr::validate([
            "usable"   => "integer|boolean:onlystringortrue"
        ], $usecase, $fn);
        $this->assertTrue($withcustom, "failed check array with custom functions and mixed datatypes");

    }


    //arr_path_get()
    public function testArrayPathGetterHelper() : void {

        $usecase = [
            "go"    => ["there" => "siktec1"],
            "num"   => [
                ["test" => "siktec2"],
                ["siktec3"]
            ],
            "colors" => [
                ["color" => "blue"],
                ["color" => "red"]
            ]
        ];

        $test1 = Std::$arr::path_get("go.there", $usecase);
        $this->assertEquals("siktec1", $test1, "failed simple path traversal get");

        $test2 = Std::$arr::path_get("num.0.test", $usecase);
        $this->assertEquals("siktec2", $test2, "failed path traversal get with numeric indexes");

        $test3 = Std::$arr::path_get("num.1.0", $usecase);
        $this->assertEquals("siktec3", $test3, "failed path traversal get with numeric indexes combined");

        $test4 = Std::$arr::path_get("colors.*.color", $usecase);
        $this->assertEqualsCanonicalizing(["blue", "red"], $test4, "failed path traversal get with wildcard extract");

        $test5 = Std::$arr::path_get("colors.name", $usecase, false);
        $this->assertFalse($test5, "failed default return value in path traversal array helper");
    }

    //str_strip_comments()
    public function testStripCommentsFromString() : void {
        $input = <<<'EOD'
            /* comment */
            {
                //Set Name:
                "name" : "siktec" //testing
            }
            EOD;
        $expected = <<<'EOD'
            
            {
                
                "name" : "siktec" 
            }
            EOD;
        $this->assertEquals($expected, Std::$str::strip_comments($input), "failed striping comments - crucial for jsonc parsing");
    }

    //fs_get_json_file() 
    public function testGetLocalJsonFile() : void {
        $test1 = Std::$fs::get_json_file(__DIR__.DS."resources".DS."test.jsonc") ?? [];
        $this->assertEquals("siktec", $test1["name"] ?? "fail", "failed loading local jsonc");

        $test2 = Std::$fs::get_json_file("resources\\test.jsonc") ?? "not-found";
        $this->assertEquals("not-found", $test2, "failed gracefully failing loading local jsonc");
    }
}