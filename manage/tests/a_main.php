<?php
define('DS', DIRECTORY_SEPARATOR);
define("ROOT_PATH", dirname(__FILE__).DS.'..'.DS.'..' );
define('USE_BSIK_ERROR_HANDLERS', false);
require_once ROOT_PATH.DS.'conf.php';
require_once PLAT_PATH_AUTOLOAD;
require_once PLAT_PATH_CORE.DS.'Excep.class.php';