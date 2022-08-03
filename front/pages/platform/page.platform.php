<?php

/******************************  includes  *****************************/
require_once BSIK_AUTOLOAD;

use \Bsik\Std;
use \Bsik\Render\Template;
use \Bsik\Render\FPage;
use \Bsik\Users\User;
use \Bsik\Privileges as Priv;

//Register this class as a Page implementation and all expected alias:
FPage::register_page("platform",  "PlatformPage");



class PlatformPage extends FPage {

    public function __construct(bool $logger, ?User $User = null) {

        //This page has access policy:
        $page_policy = new Priv\RequiredPrivileges();
        $page_policy->define(new Priv\PrivAccess(product: true));

        //FPge constructor:
        parent::__construct(
            enable_logger : $logger, 
            user : $User,
            policy : $page_policy
        );

        $this->engine = new Template(
            cache : self::$paths["global-templates"].DS."cache"
        );

        $this->engine->addFolders([
            self::$paths["page-templates"],
            self::$paths["global-templates"],
        ]);

        //define required metas of this page
        $this->meta->define([
            "lang"          => "",
            "charset"       => "",
            "viewport"      => "",
            "author"        => "",
            "description"   => "",
            "title"         => "",
            "icon"          => "",
            "api"           => self::$index_page_url."/api/".self::$request->page,
            "globapi"       => self::$index_page_url."/front/api",
            "page"          => self::$request->page,
            "page-menu"     => self::$request->which
        ]);

        //We create a ALL settings array since its a dynamic multi page template
        // foreach (self::$pages as $name => $data) {
        //     $this->all_pages_settings[$name] = Std::$str::parse_json($data["settings"], []);
        // }
        // $this->all_pages_settings["__defaults"] = self::$settings->defaults;
        
    }

    public function build() {

        //Set metas and title based on dynamic settings:
        $this->meta->set("lang",         self::$settings->get("lang",        ""))
                    ->set("charset",     self::$settings->get("charset",     ""))
                    ->set("viewport",    self::$settings->get("viewport",    "")) //, minimum-scale=1
                    ->set("title",       self::$settings->get("title",       ""))
                    ->set("author",      self::$settings->get("author",      ""))
                    ->set("description", self::$settings->get("description", ""));

        //Generic stuff:
        $this->store("doctype", "html");
        $this->store("logo", Std::$fs::path_url(self::$paths["global-lib-url"], "img", "logos", self::$settings->get("logo", "")));
        $this->body_tag("style=''");

        //Some js data to be used:
        //$this->meta->data_object($this->all_pages_settings, "pages-meta");

        //Set css includes:
        $this->include_asset("head", "css", "global", ["bootstrap", "bootstrap.5.min.css"]);
        $this->include("head", "css", "link", ["name" => "https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"]);
        $this->include_asset("head", "css",  "core",     ["required/perfect-scrollbar/css/perfect-scrollbar.css"]);
        $this->include_asset("head", "css", "global", ["tombet-icons.css"]);
        $this->include_asset("head", "css", "global", ["sikSlider.css"]);
        $this->include_asset("head", "css", "global", ["platform.css"]);
        

        //set js includes:
        $this->include_asset("head", "js",  "core",     ["required/zingtouch/dist/zingtouch.min.js", "jquery", "bsik"]);
        $this->include_asset("head", "js",  "core",     ["required/perfect-scrollbar/dist/perfect-scrollbar.min.js"]);
        $this->include_asset("body", "js",  "global",   ["bootstrap.5.min.js"   ]);
        $this->include_asset("body", "js",  "global",   ["main.module.js"       ]);
        $this->include_asset("body", "js",  "page",     ["page.module.js"       ]);

    }

    public function sports_menu() {
        return [
            ["text" => "Basketball",    "icon" => "basketball", "sport" => "basketball",    "title" => "Load all basketball leagues",   "selected" => false    ],
            ["text" => "Hockey",        "icon" => "hockey",     "sport" => "hockey",        "title" => "Load all hockey leagues",       "selected" => false    ],
            ["text" => "Baseball",      "icon" => "baseball",   "sport" => "baseball",      "title" => "Load all baseball leagues",     "selected" => false    ],
            ["text" => "Football",      "icon" => "football",   "sport" => "football",      "title" => "Load all football leagues",     "selected" => false    ],
            ["text" => "Soccer",        "icon" => "soccer",     "sport" => "soccer",        "title" => "Load all soccer leagues",       "selected" => false    ],
            ["text" => "Tennis",        "icon" => "tennis",     "sport" => "tennis",        "title" => "Load all tennis leagues",       "selected" => false    ],
        ];
    }

    public function user_menu() {
        return [
            [ 
                "class"     => "",
                "text"      => "Hello ".ucfirst($this->user->user_data["first_name"]),
                "expanded"  => true,
                "icon"      => "icon-tombet icon-user-round",
                "list" => [
                    ["href" => "#", "icon" => "icon-tombet icon-user", "text" => "Profile"],
                    ["href" => "#", "icon" => "icon-tombet icon-key", "text" => "Change Password"],
                    ["href" => "#", "icon" => "icon-tombet icon-tag", "text" => "Subscription Plan"],
                    ["href" => "#", "icon" => "icon-tombet icon-pricing", "text" => "Invoices"],
                ]
            ],
            [ 
                "class"     => "select-bookmakers",
                "text"      => "Bookmakers",
                "expanded"  => false,
                "icon"      => "icon-tombet icon-dashboard",
                "list" => [
                    ["checkbox" => true, "logo" => "tes", "text" => "PS3838",       "id" => 1238],
                    ["checkbox" => true, "logo" => "tes", "text" => "BETONLINE",    "id" => 1238],
                    ["checkbox" => true, "logo" => "tes", "text" => "BOOKMAKER",    "id" => 1238],
                    ["checkbox" => true, "logo" => "tes", "text" => "BET365",       "id" => 1238]
                ]
            ],
            [ 
                "class"     => "",
                "text"      => "Settings",
                "expanded"  => false,
                "icon"      => "icon-tombet icon-cog",
                "list" => [
                    [],
                ]
            ],
        ];
    }
    public function app_controls() {
        return [
            ["filter" => "today",       "text" => "Today",      "icon" => "icon-tombet icon-calendar"],
            ["filter" => "favorites",   "text" => "Favorites",  "icon" => "icon-tombet icon-star"]
        ];
    }
    public function payment_currencies() {
        $base = Std::$fs::path_url(self::$paths["global-lib-url"], "img", "crypto", "color")."/";
        return [
            ["value" => "btc",  "icon" => $base."btc.svg",  "name" => "Bitcoin"],
            ["value" => "eth",  "icon" => $base."eth.svg",  "name" => "Ethereum"],
            ["value" => "trx",  "icon" => $base."trx.svg",  "name" => "Tron"],
            ["value" => "usdt", "icon" => $base."usdt.svg", "name" => "Tether"]
        ];
    }

    public function render() {

        $this->build();

        //Header:
        print $this->render_block("global", "header", "HeaderBlock", [
            "favicon"   => [
                "name" => "favicon", 
                "path" => Std::$fs::path_url(self::$paths["global-lib-url"], "img", "fav")
            ]
        ]);

        //Content:
        print $this->render_template("platform", [
            "current"           => self::$request->page,
            "main_logo"         => $this->get("logo"),
            "sports_menu"       => $this->sports_menu(),
            "user_menu_id"      => "user-menu",
            "user_menu"         => $this->user_menu(),
            "controls_id"      => "app-controls",
            "controls"         => $this->app_controls(),
        ]);

        //Add modals:
        //print $this->render_block("global", "modals", "ModalsBlock", $this->page_modals());

        //Footer:
        print $this->render_block("global", "footer", "FooterBlock");

    }
}