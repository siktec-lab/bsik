<?php
define("E_PLAT_NOTICE", E_USER_NOTICE);
define("E_PLAT_WARNING", E_USER_WARNING);
define("E_PLAT_ERROR", E_USER_ERROR);

class SIKErrorStruct {
    private $obj;
    public function __construct($class, $code, $mes, $file, $line, $trace = [])
    {
        $this->obj = array(
            "class"     => $class, 
            "code"      => $code, 
            "mes"       => $mes, 
            "file"      => $file, 
            "line"      => $line, 
            "trace"     => $trace
        );
    }
    public function get_class()     { return $this->obj["class"];   }
    public function getCode()       { return $this->obj["code"];    }
    public function getMessage()    { return $this->obj["mes"];     }
    public function getFile()       { return $this->obj["file"];    }
    public function getLine()       { return $this->obj["line"];    }
    public function getTrace()      { return $this->obj["trace"];   }
    public function str() {
        return sprintf($this->obj["mes"]." : File[".$this->obj["file"].":".$this->obj["line"]."] ");
    }
}

class FrameWorkExcepHandler
{  
    public static function handleException(Throwable $e)
    {
        //Log the Exception:
        switch ($e->getCode()) {
            case 0:
            case E_CORE_ERROR:
            case E_ERROR:
            case E_PARSE:
            case E_NOTICE:
            case E_PLAT_ERROR: {
                $to_log = new SIKErrorStruct("ERROR",$e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
                error_log($to_log->str(), 0);
            } break;
        }
        //Expose:
        if (ERROR_METHOD == 'inline') {
            print self::render($e);
        } elseif (ERROR_METHOD == 'redirect') {
            header("Location: ".
                str_replace('\\', '/', URL_DOMAIN.PATH_REQUIRED_PAGES."error.php?"."&pack=".base64_encode(self::json_pack($e))), 
                true, 
                301
            );
            exit();
        }
        return true;
    }
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        //Log first:
        switch ($errno) {
            case 0:
            case E_CORE_ERROR:
            case E_ERROR:
            case E_PARSE:
            case E_NOTICE:
            case E_PLAT_NOTICE:
            case E_PLAT_ERROR: {
                $to_log = new SIKErrorStruct("NOTICE",$errno, $errstr, $errfile, $errline);
                error_log($to_log->str(), 0);
            } break;
        }
        if (ERROR_METHOD == 'inline') {
            switch ($errno) {
                /*Notice*/
                case E_NOTICE:
                case E_USER_NOTICE:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                case E_STRICT:
                case E_PLAT_NOTICE:
                    print self::render(new SIKErrorStruct("NOTICE",$errno, $errstr, $errfile, $errline, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),true);
                break;
                /*Warning*/
                case E_WARNING:
                case E_USER_WARNING:
                case E_PLAT_WARNING:
                    print self::render(new SIKErrorStruct("WARNING",$errno, $errstr, $errfile, $errline, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),true);
                    break;
                /*Fatal*/
                case E_ERROR:
                case E_USER_ERROR:
                case E_PLAT_ERROR:
                    print self::render(new SIKErrorStruct("FATAL",$errno, $errstr, $errfile, $errline, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),true);
                    exit(1);
                break;
                default:
                    print self::render(new SIKErrorStruct("UNKNOWN",$errno, $errstr, $errfile, $errline, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),true);
                break;
            }
        } else {
            return false;
        }
        /* Don't execute PHP internal error handler */
        return true;
    }
    private static function json_pack($e) {
        return json_encode(array(
            "class" => get_class($e),
            "code"  => $e->getCode(),
            "message"  => $e->getMessage(),
            "file"     => $e->getFile(),
            "line"     => $e->getLine(),
            "trace"    => $e->getTrace()   
        ));
    }
    private static function render($e, $class = false)
    {
        if (
            ini_get('display_errors') === "off" || 
            ini_get('display_errors') === 0 || 
            ini_get('display_errors') === false
        ) return "";
        $style_con = "color:black; display:block; border: 1px solid #cf7474;background-color: #fff5f5;padding: 15px;font-size: 11px;width: 500px;font-family: monospace;";
        $style_header = "margin: 0;text-decoration: underline;font-size: 20px; font-weight:bold; direction:ltr;";
        $style_list_mes = "margin: 0px 15px; direction: ltr;";
        $style_list_trace = "margin: 0px 15px; direction: ltr;";
        $style_list_ele =  "direction: ltr; margin:0;";
        $finalmes = "<div class='error_con' style='".$style_con."'>".
                    "<h2 style='".$style_header."'>".($class == false?get_class($e):$e->get_class())." - SIK Framework Error!</h2>".
                    "<ul style='".$style_list_mes."'>".
                        "<li style='".$style_list_ele."'>Code:    ".$e->getCode()."</li>".
                        "<li style='".$style_list_ele."'>Message: ".$e->getMessage()."</li>".
                        "<li style='".$style_list_ele."'>File:    ".$e->getFile()."</li>".
                        "<li style='".$style_list_ele."'>Line:    ".$e->getLine()."</li>".
                    "</ul>".
                    "<h3 style='".$style_header." font-size:16px;'>Back Trace:</h3>".
                    "<ul style='".$style_list_trace."'>";
        foreach($e->getTrace() as $t) {      
            $finalmes .= "<li style='".$style_list_ele."'>";        
            $finalmes .= (isset($t['file'])?$t['file']:"Unknown-File")." ";
            $finalmes .= "line " .(isset($t['line'])?$t['line']:"Unknown-Line")." ";
            $finalmes .= "calls " .(isset($t['function'])?$t['function']:"Unknown-Func")."()";
            $finalmes .= "</li>";
        }
        $finalmes .="</ul></div>";
        return $finalmes;
    }
}

set_exception_handler(array("FrameWorkExcepHandler", "handleException"));
set_error_handler(array("FrameWorkExcepHandler", "handleError"), E_ALL);

class SIKErrorPage {
    public $name;
    public $code;
    public $mes;
    public function __construct($name, $arr)
    {
        $this->name = $name;
        $this->code = $arr["code"];
        $this->mes = $arr["mes"];
    }
}

class SIKErrorPages {
    const ERRORS = [
        "g_login_no_code"       => [ "code" => 234256, "mes" => "Bad login request while using your google account."],
        "g_login_db_error"      => [ "code" => 234277, "mes" => "Internal error when trying to login with G+."],
        "page_request_notfound" => [ "code" => 982753, "mes" => "The page you request can't be found."] 
    ]; 
    static public function get_by_name($name) {
        if (isset(SIKErrorPages::ERRORS[$name]))
        return new SIKErrorPage($name, SIKErrorPages::ERRORS[$name]);
    }
    static public function get_by_code($code) {
        foreach (SIKErrorPages::ERRORS as $_name => $_arr) 
            if ($_arr["code"] === $code)
                return new SIKErrorPage($_name, $_arr);
    }
}