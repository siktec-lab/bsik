<?php
/******************************  intellisense  *****************************/
if (!isset($APage)) $APage = new APage();
if (!isset($Admin)) $Admin = new Admin();

/******************************  Guard  *****************************/
if (!isset($conf)) {
    include_once PLAT_PATH_MANAGE."/error/?p=main&code=10";
    die();
}

/******************************  Set Meta - required  *****************************/
$APage->meta("lang",         $APage->settings["lang"]       ?? "en")
      ->meta("charset",      $APage->settings["charset"]    ?? "utf8")
      ->meta("title",        $APage->settings["title"]      ?? "")
      ->meta("author",       $APage->settings["author"]     ?? "")
      ->meta("description",  $APage->settings["desc"]       ?? "");
Trace::add_trace("Required META set done.", __FILE__.__LINE__);

/******************************  Set Meta - optional  *****************************/
foreach($APage->settings["addmeta"] ?? [] as $opm) 
    $APage->op_meta($opm);
Trace::add_trace("Optional META extend done.", __FILE__.__LINE__, "Total: ".count($APage->settings["addmeta"] ?? []));

/******************************  Store Important values  *****************************/
$APage->store("plat-logo", PLAT_FULL_DOMAIN."/manage/lib/img/logo.svg");

/******************************  Set Body tag  *****************************/
/* SH: added - 2021-03-06 => connect to cms */
$APage->body_tag("style=''");

/******************************  Set Includes  *****************************/
//Auto load global libs + required module libs:
$loaded_libs = $APage->load_libs($global = true);

//Module content:
/******************************  Module content  *****************************/

//Empty on errors / Exception will be logged by the method:
$module_content = $APage->render_module("", $Admin);

//Load themes files:
/* SH: added - 2021-04-03 => Add theme control from DB */
if ($generic_lib = $APage::$std::fs_file_exists("themes", ["base-dark", "theme.css"])) {
    $APage->include("head", "css", "link", ["name" => $generic_lib["url"]]);
    Trace::add_trace("Loaded theme plat stylesheet.", __FILE__.__LINE__);
}
//Load module generic files (js, css):
if ($generic_lib = $APage::$std::fs_file_exists("modules", [$APage->module->name, "module.css"])) {
    $APage->include("head", "css", "link", ["name" => $generic_lib["url"]]);
    Trace::add_trace("Loaded generic module stylesheet.", __FILE__.__LINE__);
}
if ($generic_lib = $APage::$std::fs_file_exists("modules", [$APage->module->name, "module.js"])) {
    $APage->include("head", "js", "link", ["name" => $generic_lib["url"]]); // Always keep in head - gives more control on needed predefined function declaration
    Trace::add_trace("Loaded generic module script.", __FILE__.__LINE__);
}
if ($generic_lib = $APage::$std::fs_file_exists("modules", [$APage->module->name, "logic.module.js"])) {
    $APage->include("head", "js", "link", ["name" => $generic_lib["url"]]); // Always keep in head - gives more control on needed predefined function declaration
    Trace::add_trace("Loaded generic module script.", __FILE__.__LINE__);
}


/******************************  Set Side Menu  *****************************/
$APage->load_menu();
Trace::add_trace("Parsed defined menu entries ", __FILE__.__LINE__);


/******************************  Render Page  *****************************/
//Build html / Head / Meta / includes:
/* SH: added - 2021-03-16 => Change to register inside the class page as object entries */
require_once PLAT_PATH_MANAGE.DS."pages".DS."header.php";
$doc_head = $CoreBlockRender($APage, []);
Trace::add_trace("Loaded & Render Header structure", __FILE__.__LINE__);

//Close document + bottom includes:
require_once PLAT_PATH_MANAGE.DS."pages".DS."footer.php";
$doc_end =  $CoreBlockRender($APage, []);
Trace::add_trace("Loaded & Render End of document structure", __FILE__.__LINE__);

//Top bar:
require_once PLAT_PATH_MANAGE.DS."pages".DS."topbar.php";
$doc_admin_bar =  $CoreBlockRender($APage, []);
Trace::add_trace("Loaded & Render Admin Top Bar", __FILE__.__LINE__);

//Side Menu:
require_once PLAT_PATH_MANAGE.DS."pages".DS."menu.php";
$doc_side_menu = $CoreBlockRender($APage, []);
Trace::add_trace("Loaded & Render side-menu structure", __FILE__.__LINE__);

//Module header:
require_once PLAT_PATH_MANAGE.DS."pages".DS."module-header.php";
$doc_module_header = $CoreBlockRender($APage, [
    "title"     => $APage->module->header["title"],
    "which"     => $APage->module->header["which"],
    "sub-title" => $APage->module->header["sub-title"],
    "actions"   => [
        ["button.dropdown-item", ["type" => "button", "action" => "none"], "Action 1"],
        ["button.dropdown-item", ["type" => "button", "action" => "none"], "Action 2"],
        ["button.dropdown-item", ["type" => "button", "action" => "none"], "Action 3"]
    ] 
]);
Trace::add_trace("Loaded & Render module content header", __FILE__.__LINE__);

$doc_tpl = <<<HTML
    %s
    <div class="container-fluid p-0">
        <div class="container-bar noselect">%s</div>
        <div class="content-wrapper">
            <div class="container-side-menu noselect">%s</div>
            <div class="container-module">%s</div>
        </div>
        <div class="container-footer">
            %s
        <div class="console-messages">
            <span class="user-select-none">
                <i class="fas fa-comment-alt"></i>
                &nbsp;&nbsp;Console Log
                <em>0</em>
                <i class="fas fa-angle-up open-icon"></i>
            </span>
            <ul>
                <!-- <li><span class='notify-info'>some messages words not wrappped lksjdflkj jsdf kjsdfkjj slkdfjlsdf lkjsdf<i class="fas fa-times"></i></span></li>
                <li><span class='notify-warn'>some messages words not wrappped<i class="fas fa-times"></i></span></li>
                <li><span class='notify-error'>some messages words not wrappped lksjdflkj jsdf kjsdfkjj slkdfjlsdf lkjsdf<i class="fas fa-times"></i></span></li> -->
            </ul>
        </div></div>
    </div>
    %s
HTML;

printf($doc_tpl,
    $doc_head,
    $doc_admin_bar,
    $doc_side_menu,
    $doc_module_header.$module_content,
    "BSik by SIKTEC - Version: 1.0.1",
    $doc_end
);