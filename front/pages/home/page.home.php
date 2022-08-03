<?php

/******************************  includes  *****************************/
require_once BSIK_AUTOLOAD;

use \Bsik\Std;
use \Bsik\Render\Template;
use \Bsik\Render\FPage;
use \Bsik\Trace;
use \Bsik\Users\User;

//Register this class as a Page implementation and all expected alias:
FPage::register_page("home",    "HomePage");
FPage::register_page("sports",  "HomePage");
FPage::register_page("pricing", "HomePage");
FPage::register_page("contact", "HomePage");
FPage::register_page("trial",   "HomePage");

class HomePage extends FPage {

    public array $all_pages_settings = [];

    public function __construct(bool $logger, ?User $User = null) {
        parent::__construct(enable_logger : $logger, user : $User);

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
        foreach (self::$pages as $name => $data) {
            $this->all_pages_settings[$name] = Std::$str::parse_json($data["settings"], []);
        }
        $this->all_pages_settings["__defaults"] = self::$settings->defaults;
        
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
        $this->meta->data_object($this->all_pages_settings, "pages-meta");

        //Set css includes:
        $this->include_asset("head", "css", "global", ["bootstrap", "bootstrap.5.min.css"]);
        $this->include("head", "css", "link", ["name" => "https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"]);
        $this->include("head", "css", "link", ["name" => "https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"]);
        $this->include("head", "css", "link", ["name" => "https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.min.css"]);
        $this->include_asset("head", "css", "global", ["tombet-icons.css"]);
        $this->include_asset("head", "css", "global", ["main.css"]);
        

        //set js includes:
        $this->include_asset("head", "js",  "core",     ["required/zingtouch/dist/zingtouch.min.js", "jquery", "bsik"]);
        $this->include("head", "js", "link", ["name" => "https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"]);
        $this->include_asset("body", "js",  "global",   ["bootstrap.5.min.js"]);
        $this->include_asset("body", "js",  "global",   ["main.module.js"]);
        $this->include_asset("body", "js",  "page",     ["page.module.js"]);

    }

    public function menu() {
        return [
            ["text" => "application",   "icon" => "app",        "link" => $this::$index_page_url."/home/",      "action" => "home",     "selected" => self::$request->page == "home"    ],
            ["text" => "sports",        "icon" => "football-2", "link" => $this::$index_page_url."/sports/",    "action" => "sports",   "selected" => self::$request->page == "sports"  ],
            ["text" => "pricing",       "icon" => "pricing",    "link" => $this::$index_page_url."/pricing/",   "action" => "pricing",  "selected" => self::$request->page == "pricing" ],
            ["text" => "contact",       "icon" => "mail",       "link" => $this::$index_page_url."/contact/",   "action" => "contact",  "selected" => self::$request->page == "contact" ],
            ["text" => "free trial",    "icon" => "question",   "link" => $this::$index_page_url."/trial/",     "action" => "trial",    "selected" => self::$request->page == "trial"   ]
        ];
    }

    public function appplication_slides() {
        return [
            [
                "title"     => "Odds Scanner", 
                "caption"   => "The best application to copare odds offerings from top Bookmakers all done in a single smart dashboard.", 
                "img"       => Std::$fs::path_url(self::$paths["global-lib-url"], "img", "carusel", "car-1-min.png")
            ],
            [   
                "title"     => "Track Odds trends", 
                "caption"   => "Odds progress over tim, Lowest \ Highest peeks. Set alerts to capture changes.", 
                "img"       => Std::$fs::path_url(self::$paths["global-lib-url"], "img", "carusel", "car-2-min.png")
            ],
            [
                "title"     => "Capture and get notified", 
                "caption"   => "Set alerts on a lines of you choice to dynamically capture the optimal entry point.", 
                "img"       => Std::$fs::path_url(self::$paths["global-lib-url"], "img", "carusel", "car-3-min.png")
            ],
            [
                "title"     => "Save and highlight your lines", 
                "caption"   => "Keep selected lines in a focus view to always see their progress.", 
                "img"       => Std::$fs::path_url(self::$paths["global-lib-url"], "img", "carusel", "car-4-min.png")
            ],
            [
                "title"     => "Historical data and Stats", 
                "caption"   => "Focus any game / event and get the related statistics, summary and latest news.", 
                "img"       => Std::$fs::path_url(self::$paths["global-lib-url"], "img", "carusel", "car-5-min.png")
            ]
        ];
    }
    public function sport_cards() {
        $leagues_url = Std::$fs::path_url(self::$paths["global-lib-url"], "img", "leagues");
        return [
            ["sport" => "basketball", "icon" => "icon-basketball-2", "leagues" => [
                [ "name" => "NBA",      "description" => "National Basketball Association", "logo" => Std::$fs::path_url($leagues_url, "nba.svg")],
                [ "name" => "NCAA",     "description" => "National Collegiate Association", "logo" => Std::$fs::path_url($leagues_url, "nba.svg")]
            ]],
            ["sport" => "football", "icon" => "icon-football-2", "leagues" => [
                [ "name" => "NFL",      "description" => "National Football League",        "logo" => Std::$fs::path_url($leagues_url, "nfl.svg")],
                [ "name" => "NCAA",     "description" => "National Collegiate Association", "logo" => Std::$fs::path_url($leagues_url, "nfl.svg")]
            ]],
            ["sport" => "hockey", "icon" => "icon-hockey-2", "leagues" => [
                [ "name" => "NHL",      "description" => "National Hockey League",          "logo" => Std::$fs::path_url($leagues_url, "nhl.svg")],
                [ "name" => "NCAA",     "description" => "National Collegiate Association", "logo" => Std::$fs::path_url($leagues_url, "nhl.svg")]
            ]],
            ["sport" => "baseball", "icon" => "icon-baseball-2", "leagues" => [
                [ "name" => "MLB",      "description" => "National Leahue Baseball",        "logo" => Std::$fs::path_url($leagues_url, "mlb.svg")],
                [ "name" => "NCAA",     "description" => "National Collegiate Association", "logo" => Std::$fs::path_url($leagues_url, "mlb.svg")]
            ]],
            ["sport" => "soccer", "icon" => "icon-soccer-2", "leagues" => [
                [ "name" => "UEFA",     "description" => "Champions League",                "logo" => Std::$fs::path_url($leagues_url, "champions-league.svg")],
                [ "name" => "FIFA",     "description" => "Club Wold Cup",                   "logo" => Std::$fs::path_url($leagues_url, "world-cup.png")]
            ]],
            ["sport" => "tennis", "icon" => "icon-tennis-2", "leagues" => [
                [ "name" => "Wimbledon",     "description" => "The Championships",          "logo" => Std::$fs::path_url($leagues_url, "wimbledon.svg")]
            ]]
        ];
    }

    public function price_plans($class = "", $id = "", $checkboxes = false) {
        //TODO: made a module to set those pricing plans:
        return [
            "pricing_class" => $class,
            "pricing_id"    => $id,
            "plans" => [
                [
                    "name"   => "trial",
                    "title"  => "3 Days trial",
                    "price"  => 0,
                    "yearly" => 0.85, 
                    "price_display" => "Free", // only if we overwrite default
                    "tag"   => "Try Me!",
                    "selected"  => false,
                    "checkbox" => $checkboxes,
                    "perks" => [
                        [ "highlight" => true, "text" => "5 Minutes delay"      ],
                        [ "highlight" => false, "text" => "6 Sports supported"   ],
                        [ "highlight" => false, "text" => "7 Bookmakers"         ],
                        [ "highlight" => false, "text" => "All features & tools" ],
                    ]
                ],
                [
                    "name"   => "starter",
                    "title"  => "Starter",
                    "price"  => 150,
                    "yearly" => 0.85,
                    "selected"  => false,
                    "checkbox" => $checkboxes,
                    "perks" => [
                        [ "highlight" => true, "text" => "2 Minutes delay"      ],
                        [ "highlight" => false, "text" => "6 Sports supported"   ],
                        [ "highlight" => false, "text" => "7 Bookmakers"         ],
                        [ "highlight" => false, "text" => "All features & tools" ],
                    ]
                ],
                [
                    "name"      => "pro",
                    "title"     => "Pro",
                    "price"     => 210,
                    "yearly"    => 0.85,
                    "tag"       => "hot!",
                    "selected"  => true,
                    "checkbox" => $checkboxes,
                    "perks" => [
                        [ "highlight" => true, "text" => "20 Seconds delay"     ],
                        [ "highlight" => false, "text" => "6 Sports supported"   ],
                        [ "highlight" => false, "text" => "7 Bookmakers"         ],
                        [ "highlight" => false, "text" => "All features & tools" ],
                    ]
                ],
                [
                    "name"   => "expert",
                    "title"  => "Expert",
                    "price"  => 270,
                    "yearly" => 0.85,
                    "selected"  => false,
                    "checkbox" => $checkboxes,
                    "perks" => [
                        [ "highlight" => true, "text" => "5 Seconds delay"     ],
                        [ "highlight" => false, "text" => "6 Sports supported"   ],
                        [ "highlight" => false, "text" => "7 Bookmakers"         ],
                        [ "highlight" => false, "text" => "All features & tools" ],
                    ]
                ]
            ]
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
    public function page_modals() {
        /*TODO: some of those need to be set directly from manage -> payment information pricing plans etc.... */
        $payment_information = "
            A deferred payment agreement is an arrangement with the Council which enables people to use the value of their home as security for a loan
            from the Council to help pay care costs. Conditions apply and the person will have to sign an agreement with the Council. 
            A copy of the agreement is appended to policy. 
            The loan has to be repaid but people can delay repayment until they are ready to sell their home or until after their death. 
            Deferred payment agreements are just one option for paying for care costs. 
            We recommend that people seek independent financial advice about the various options for paying for care. 
            The Council has commissioned the Care Advice Line to provide an information and advice service about care and support and how to pay for it. 
            Advice is provided by telephone and is free, confidential and personalized.
        ";
        return [ "modals" => [
            [
                "id"                => "user-login",
                "add_class"         => "",
                "size"              => "modal-xl modal-dialog-centered",
                "dismiss_button"    => true,
                "left_col_classes"  => "",
                "right_col_classes" => "login-bg",
                "left"              => $this->render_template("login_form"),
                "right"             => ""
            ],
            [
                "id"                => "user-register",
                "add_class"         => "",
                "size"              => "modal-xl modal-dialog-centered",
                "dismiss_button"    => true,
                "body_classes"      => "user-register p-2 p-md-4",
                "body"              => $this->render_template("register_procedure", [
                    "pricing_plans"         => $this->price_plans("in-modal", "register-select-plan", true),
                    "payment_information"   =>  $payment_information,
                    "paypal_enable"         => true,
                    "crypto_enable"         => true,
                    "currencies"            => $this->payment_currencies()
                ])
            ],
        ]];
    }
    public function contact_us() {
        return [

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
        print $this->render_template("home", [
            "current"   => self::$request->page,
            "menu"      => $this->menu(),
            "user"      => [
                "signed"    => $this->user->is_signed,
                "fname"     => $this->user->is_signed ? $this->user->user_data["first_name"] : "",
                "lname"     => $this->user->is_signed ? $this->user->user_data["last_name"] : "",
                "email"     => $this->user->is_signed ? $this->user->user_data["email"] : ""
            ],
            "menu_buttons" => [
                [   
                    "text"      => "Login",         
                    "action"    => "login",    
                    "color"     => "outline-white",     
                    "icon"      => false,
                    "attrs"     => ["data-bs-toggle" => "modal", "data-bs-target" => "#user-login"],
                    "hidden"    => $this->user->is_signed
                ],
                [
                    "text"      => "GET IT NOW!",   
                    "action"    => "getit",    
                    "color"     => "outline-orange",    
                    "icon"      => false,
                    "attrs"     => ["data-bs-toggle" => "modal", "data-bs-target" => "#user-register"],
                    "hidden"    => $this->user->is_signed
                ],
                [
                    "text"      => "Logout",   
                    "action"    => "redirect",    
                    "color"     => "outline-white",    
                    "icon"      => false,
                    "attrs"     => [ "data-link" => self::$index_page_url."/logout"],
                    "hidden"    => !$this->user->is_signed
                ],
                [
                    "text"      => "Goto TOMBET",
                    "action"    => "redirect",
                    "color"     => "full-white",
                    "icon"      => "icon-tombet icon-dashboard",
                    "attrs"     => [ "data-link" => self::$index_page_url."/platform" ],
                    "hidden"    => !$this->user->is_signed
                ]
            ],
            "main_logo" => $this->get("logo"),
            "application" => [
                "slides" => $this->appplication_slides(),
            ],
            "sport_cards" => $this->sport_cards(),
            "pricing_plans" => $this->price_plans(),
            "contact"  => $this->contact_us()
        ]);

        //Add modals:
        print $this->render_block("global", "modals", "ModalsBlock", $this->page_modals());

        //Footer:
        print $this->render_block("global", "footer", "FooterBlock");

    }
}