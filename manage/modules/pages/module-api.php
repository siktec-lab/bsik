<?php
//Extending the Api of manage:
require_once PLAT_PATH_AUTOLOAD;

use \Bsik\Api\ApiEndPoint;
use \Bsik\Api\AdminApi;

/****************************************************************************/
/* COMPONENTS:   ************************************************************/
/****************************************************************************/
require_once "includes".DS."pages-components.php";

/****************************************************************************/
/* PAGES related endpoints:   
 * pages.get_pages_names    => [] get all pages names.
 * pages.page_name_valid    => [name] checks if a given name is valid to register as new.
 * pages.delete_page        => [name] deletes a page entry.
 * pages.change_page_status => [name] toggles page status.
 ****************************************************************************/
require_once "includes".DS."pages-api.php";

/****************************************************************************/
/* PAGE SETTINGS related endpoints:   
 * pages.get_settings     => [of,name,for,object] get pages settings global or page.
 * pages.save_settings    => [of,settings,name]   saves page revised settings.
/****************************************************************************/
require_once "includes".DS."settings-api.php";

/****************************************************************************/
/* PAGE TEMPLATES related endpoints:   
 * pages.get_page_templates    => [type] returns all available templates of type (file, dynamic)
 * 
/****************************************************************************/
require_once "includes".DS."templates-api.php";

