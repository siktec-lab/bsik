<?php
/******************************  intellisense  *****************************/
if (!isset($APage)) { $APage = new APage(); } 
if (!isset($Admin)) { $Admin = new Admin(); } 

$APage->include("head", "css", "path", ["name" => PLAT_FULL_DOMAIN."/manage/lib/required/font-awesome/css/all.min.css"]);
$APage->include("head", "css", "path", ["name" => PLAT_FULL_DOMAIN."/manage/lib/required/bootstrap/css/bootstrap.css"]);
$APage->include("head", "css", "path", ["name" => PLAT_FULL_DOMAIN."/manage/lib/css/global.css"]);
$APage->include("head", "css", "path", ["name" => PLAT_FULL_DOMAIN."/manage/lib/css/login.css"]);
$APage->include("head", "js",  "path", ["name" => PLAT_FULL_DOMAIN."/manage/lib/required/jquery/jquery.min.js"]);


/******************************  Basic login Form values  *****************************/
$APage->store("form-title", "ADMIN PANEL LOGIN");
$APage->store("form-logo", PLAT_FULL_DOMAIN."/manage/lib/img/logo.svg");
$APage->store("plat-info", "BSik by SIKTEC - Version: ".APP_VERSION);
$APage->store("form-user-label", "USERNAME");
$APage->store("form-user-pass", "PASSWORD");
$APage->store("form-btn-login", "LOGIN");
$APage->store("login-message", "", false);
//Add a message if exists?
if (!empty($Admin->errors["login"] ?? "")) {
    switch ($Admin->errors["login"]) {
        case "error":
            $APage->store("login-message", "Incorrect username or password  - Try Again.", false);
            break;
        case "session":
            $APage->store("login-message", "Page needed refresh first - Now you can continue and login .", false);
            break;
    }
}

?>
<html data-docby="BSIK Platfom">
    <head>
        <meta charset="utf8">
        <meta name="viewport" content="">
        <meta name="author" content="SIKTEC">
        <meta name="description" content="PHP made simple the correct way to build fast and powerful PHP web apps">
        <meta http-equiv="X-UA-Compatible" content="IE=7">
        <?php print $APage->token["meta"]; ?>
        <?php $APage->render_favicon(PLAT_FULL_DOMAIN."/manage/lib/img/fav"); ?>
        <title>SIK Framework - Login Page</title>
        <link rel="icon" href="">
        <!-- START : Head includes -->
        <?PHP
            $APage->render_libs("css", "head");
            $APage->render_libs("js", "head");
        ?>
        <!-- END : Head includes -->
    </head>
    <body style="">        
        <!-- END : body includes -->
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-2"></div>
                <div class="col-lg-6 col-md-8 login-box">
                    <div class="col-lg-12 plat-logo">
                        <?php print "<img  src='".$APage->get("form-logo")."' />".PHP_EOL; ?>
                    </div>
                    <div class="col-lg-12 login-title">
                        <?php print $APage->get("form-title"); ?>
                    </div>

                    <div class="col-lg-12 login-form">
                        <div class="col-lg-12 login-form">
                            <?php
                                if (!empty($APage->get("login-message"))) {
                                    print '<div class="login-message">'.PHP_EOL;
                                    print $APage->get("login-message");
                                    print '</div>'.PHP_EOL;
                                } 
                            ?>
                            <form method="post">
                                <input type="hidden" name="csrftoken" value="<?php print $APage->token["csrf"]; ?>" />
                                <div class="form-group">
                                    <label class="form-control-label">
                                        <i class='fas fa-user'></i>&nbsp;&nbsp;
                                        <?php print $APage->get("form-user-label"); ?>
                                    </label>
                                    <input name="username" type="text" class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">
                                        <i class='fas fa-key'></i>&nbsp;&nbsp;
                                        <?php print $APage->get("form-user-pass"); ?>
                                    </label>
                                    <input name="password" type="password" class="form-control" />
                                </div>
                                <div class="col-12 text-center mb-4">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <?php print $APage->get("form-btn-login"); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-2"></div>
                <div class="col-12 plat-info">
                    <?php print $APage->get("plat-info"); ?>
                </div>
            </div>
        </div>
    </body>
</html>



