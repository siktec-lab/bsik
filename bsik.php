<?php

define("BUILD_ON",      "PHP 8");
define("BSIK_VERSION",   "1.0.1");
define('DS', DIRECTORY_SEPARATOR);
define("ROOT_PATH", dirname(__FILE__));

require_once ROOT_PATH.DS."conf.php"; // Conf..
require_once ROOT_PATH.DS."vendor".DS."autoload.php";
//require_once ROOT_PATH.DS."manage".DS."core".DS."CoreSettings.php"; // CoreSettings

use \Bsik\Settings\CoreSettings;
CoreSettings::init();
