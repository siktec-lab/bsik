<?php
//Extending the Api of manage:

//For intellisense support:
// require_once PLAT_PATH_CORE.DS.'BsikApi.class.php';
// if (!isset($AApi)) { $AApi = new BsikApi(Base::get_session("csrftoken")); }
require_once PLAT_PATH_AUTOLOAD;

/****************************************************************************/
/**********************  GET SCRIPT STATUS       ****************************/
/****************************************************************************/
$AApi->register_endpoint(new BsikApiEndPoint(
    $name = "install_module_file", 
    $params = [],
    $filters = [],
    $validation = [],
    //The method to execute -> has Access to BsikApi
    function(BsikApi $Api, array $args) {

        //Perform file upload:
        [$status, $ret] = $Api->file(
            "module_file", 
            PLAT_PATH_MANAGE.DS."uploaded", 
            $Api::$std::fs_format_size_to(10, "MB", "B"),
            ["zip"]
        );

        //Set response:
        if (!$status) {
            $Api->update_answer_status(200, $ret);
        } else {
            $Api->request->answer->data["uploaded"] = $ret;
            //Perform installation:
            try {
                $Installer = new ModuleInstaller($ret);
                //Open zip file:
                $Installer->open_zip();
                //Validate in zip:
                $validation = $Installer->validate_required_files_in_zip();
                if (!empty($validation)) {
                    $Installer->close_zip();
                    $Api->update_answer_status(200, $validation[0]);
                    $Installer->delete_files($ret);
                    return false;
                }
                //Extract to destination - temp extract:
                $Installer->extract();
                $Installer->close_zip();
                //install the module or pack:
                [$status, $errors, $installed] = $Installer->install($Api->get_user("id"));
                $Api->request->answer->data["errors"] = $errors;
                if (!$status) {
                    $Installer->delete_files($ret);
                    $Installer->clean();
                    $Api->update_answer_status(200, $errors);
                } 
                $Api->request->answer->data["installed"] = $installed;
            } catch (Exception $e) {
                $Api->update_answer_status(200, $e->getMessage());
                $Installer->delete_files($ret);
                $Installer->clean();
                return false;
            }
            //finish:
            $Installer->clean();
            $Installer->delete_files($ret);
            $Api->update_answer_status(200);
        }
        return true;
    }
));