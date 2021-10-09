<?php
require_once PLAT_PATH_CORE.DS."APageComponents.class.php";

APageComponents::register("helloworld", function($name) {
    return "Hello {$name}";
});

/**
 * html_ele - Builds an html element defined by a selector.
 * @param string $selector
 * @param array $add_attrs
 * @param string $content
 * @return array [string openTag, string content, string closingTag]
 */
APageComponents::register("html_ele", function(string $selector = "div", array $add_attrs = [], string $content = "") {
    $parts = explode(".", $selector);
    $tag   = explode("#", array_shift($parts));
    $id    = $tag[1] ?? null;
    $tag   = $tag[0];
    $attrs = [];
    // has classes:
    $attrs["class"] = !empty($parts) ? $parts : [];
    if (isset($add_attrs["class"])) {
        array_push($attrs["class"], ...explode(".",$add_attrs["class"]));
        unset($add_attrs["class"]);
    }
    // has id:
    if (!empty($id)) $attrs["id"] = $id;
    //merge additional attributes:
    $attrs = array_merge($attrs, $add_attrs);
    $attrs_str = [];
    foreach ($attrs as $a => $value) {
        if (!empty($value))
            $attrs_str[] = $a.'="'.(is_array($value) ? implode(" ", $value) : $value).'"';
    }
    $ele = [sprintf("<%s %s>", $tag, implode(" ", $attrs_str)), $content , ""];
    if (!in_array($tag, [
        "meta","area","base","br","call","command","embed","hr",
        "img","input","keygen","link","param","source","track","wbr"
    ])) {
        $ele[2] = "</$tag>";
    }
    return $ele;
});


/**
 * title - Builds an basic title element.
 * @param string    $text  => element text.
 * @param int       $size  => 1 - 7 the H ele size
 * @param string    $attrs => optional attributes to add.
 * @return array [string openTag, string content, string closingTag]
 */
APageComponents::register("title", function(string $text, int $size = 2, array $attrs = []) {
        return implode(APageComponents::html_ele("h{$size}", $attrs, $text));
});


/** Alert Component html renderer.
 *  @param string $text     => any string to be added - will not escape HTML
 *  @param string $color    => color of the modal use naming convention.
 *  @param string $icon     => icon classes such as 'fas fa-user'.
 *  @param bool $dismiss    => dismissible alert?.
 *  @param array $classes   => array of class names to append to the alert DIV element.
 *  @return string          => HTML of the dropdown
*/
APageComponents::register("alert", function(
    string $text = "alert message", 
    string $color = "",
    string $icon = "",
    bool $dismiss = false,
    array  $classes = [],
) {
    $tmpl = '<div class="alert %s" role="alert">
                <span class="bg-icon">%s</span>
                %s
                %s
            </div>';
    if (!empty($icon))  array_unshift($classes, "add-icon");
    if ($dismiss)       array_unshift($classes, "alert-dismissible");
    array_unshift($classes, "alert-".(!empty($color) ? trim($color) : "light"));
    
    return sprintf($tmpl,
        implode(" ", $classes),
        !empty($icon) ? '<i class="'.$icon.'"></i>' : "", 
        $text,
        $dismiss ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' : "",
    );
});

/** Modal Component.
 *  @param string $id => the modal unique id
 *  @param string $title => the modal title - can be HTML too.
 *  @param string $body => the modal body - can be HTML too.
 *  @param string $footer => the modal footer - can be HTML too.
 *  @param array  $buttons => the modal main buttons - expect button structure with html_ele structure.
 *  @param array  $set => the modal set attributes - [backdrop, keyboard, close, size].
 *  @return string => HTML of the modal
*/
APageComponents::register("modal", function(string $id, string $title = "Modal", string $body = "", string $footer = "", array $buttons = [], array $set = []) {

    //Create buttons:
    $btns = [];
    foreach ($buttons as $button) {
        if (count($button) < 4 || empty($button[0]) || !is_string($button[0])) {
            continue;
        }
        if (!isset($button[1]["type"]) && APageComponents::$std::str_starts_with($button[0], "button"))
            $button[1]["type"] = "button";
        if (!isset($button[1]["data-bs-dismiss"]) && $button[3])
            $button[1]["data-bs-dismiss"] = "modal";
        $btns[] = implode(APageComponents::html_ele($button[0], $button[1], $button[2]));
    }
    $btns = implode($btns);
    //Backdrop:
    $backdrop = "";
    if (isset($set["backdrop"])) {
        $backdrop = "data-bs-backdrop='{$set["backdrop"]}'";
    }
    //keyboard:
    $keyboard = "";
    if (isset($set["keyboard"])) {
        $keyboard = "data-bs-keyboard='{$set["keyboard"]}'";
    }
    //close:
    $close = "";
    if ($set["close"] ?? false) {
        $close = '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
    }
    //size:
    $size = "";
    if ($set["size"] ?? false) {
        $size = "modal-{$set["size"]}";
    }
    return <<<HTML
        <div class="bsik-modal modal fade" id="{$id}" {$backdrop} {$keyboard} tabindex="-1" aria-labelledby="{$id}Label" aria-hidden="true">
            <div class="modal-dialog {$size}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="{$id}Label">{$title}</h5>
                        {$close}
                    </div>
                    <div class="modal-body">
                        {$body}
                    </div>
                    <div class="modal-footer">
                        {$footer}
                        {$btns}
                    </div>
                </div>
            </div>
        </div>
    HTML;
});

APageComponents::register("confirm", function(){
    $confirm_modal = APageComponents::modal(
        "bsik-confirm-modal", 
        "Confirmation required",
        "Confirmation body",
        "",
        [
            ["button.confirm-action-modal.btn.btn-secondary",    [],     "Confirm", false],
            ["button.reject-action-modal.btn.btn-primary",      [],     "Cancel", false],
        ],
        [
            "close"    => false,
            "size"     => "md",
            "backdrop" => "static",
            "keyboard" => "false",
        ]
    );
    return $confirm_modal;
});

/** Global dynamic table options.
*/
APageComponents::register("defaults_dynamic_table", [ //NULL omits field option
    "visible"           => true,
    "field"             => null,        // Field name in db
    "title"             => null,        // Display Title string 
    "sortable"          => false,       // Set sort control
    "searchable"        => true,        // Set search control
    "rowspan"           => 1,           // Header rowspan
    "colspan"           => 1,           // Header colspan
    "align"             => "center",    // Align header
    "valign"            => 'middle',    // Vertical align header
    "clickToSelect"     => true,        // Enable / Disable header clickable
    "checkbox"          => false,       // Add row select checkbox
    "events"            => null,        // Events are sent to? a function.
    "formatter"         => null,        // Column formatter function
    "footerFormatter"   => null         // Footer Formatter function
]);

/** dynamic_table Component html renderer.
 *  @param string $id => table unique id
 *  @param string $ele_selector => the table element selector.
 *  @param array $option_attributes => all the tables options extends `defaults_dynamic_table`.
 *  @param string $api => api endpoint to get the results from.
 *  @param string $table => the database table or view name.
 *  @param array  $fields => fields definition object.
 *  @param array $operations => main operations to attach to the table
 *  @return string => HTML of the table with js inline
*/
APageComponents::register("dynamic_table", function(
    string $id, 
    string $ele_selector, 
    array $option_attributes,
    string $api, 
    string $table, 
    array $fields, 
    array $operations = []
) {
    //Table js:
    $tpl_js   = "
        %s
        <script>
            document.addEventListener('DOMContentLoaded', function(event) {
                $('#%s').bootstrapTable({
                    ajax: function(params) {
                        params.data['fields'] = %s;
                        params.data['table_name'] = '%s';
                        Bsik.dataTables.get('%s', 'get_for_datatable', params);
                    },
                    columns: %s
                });
            });
            %s
        </script>".PHP_EOL;
    $tpl_operations = "function %s() { return '%s'; }";
    $operate_formatter_name = APageComponents::$std::str_filter_string($id)."_operateFormatter";
    //Build columns:
    $columns = [];
    foreach ($fields as $field) {
        if (!empty($field["field"])) {
            $merged = array_merge(APageComponents::defaults_dynamic_table(), $field);
            if ($field["field"] === "operate")
                $merged["formatter"] = $operate_formatter_name;
            $columns[] = array_filter($merged,fn($v) => !is_null($v));
        }
    }
    $columns = json_encode($columns);
    //Build Operations:
    //["name" => "like", "title" => "Like me", "icon" => "fa fa-heart"],
    $eles_operation = [];
    foreach ($operations as $op) {
        $eles_operation[] = implode(APageComponents::html_ele(
            "a.".($op["name"] ?? "notset"), 
            array_merge(["href" => "javascript:void(0)"], APageComponents::$std::arr_filter_out($op, ["name", "href"])),
            implode(APageComponents::html_ele("i", ["class" => $op["icon"] ?? "no-icon"]))
        ));
    }
    return sprintf(
        $tpl_js,
        implode(APageComponents::html_ele($ele_selector, $option_attributes)).PHP_EOL,
        $id,
        json_encode(array_filter(array_column($fields, 'field'), fn($v) => $v !== "operate")),
        $table,
        $api,
        preg_replace('/"@js:([\w.]+)"/m', '$1', $columns), // Fixed a bug this allows to define object names and functions 
        sprintf($tpl_operations, $operate_formatter_name, implode($eles_operation))
    );
});

/** dropdown Component html renderer.
 *  @param array $buttons => a buttons (of the list) definition array that uses the html_ele structure
 *  @param string $text => the button open text.
 *  @param string $id => the id of the button that open the dropdown.
 *  @param array $class_main => array of class names to append to the main button.
 *  @param array $class_list => array of class names to append to the list of buttons.
 *  @return string => HTML of the dropdown
*/
APageComponents::register("dropdown", function(
    array $buttons, 
    string $text = "dropdown", 
    string $id = "", 
    array $class_main = [], 
    array $class_list = []
) {
    $tmpl = '<div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle %s" type="button" id="%s" data-bs-toggle="dropdown" aria-expanded="false">
            %s
        </button>
        <ul class="dropdown-menu %s" aria-labelledby="%s">
            %s
        </ul>
    </div>';
    $buttons_html = '';
    foreach ($buttons as $b) {
        $buttons_html .= "<li>".implode(APageComponents::html_ele(...$b))."</li>".PHP_EOL;
    }
    return sprintf($tmpl, 
        implode(" ", $class_main), 
        $id, 
        $text,
        implode(" ", $class_list),
        $id, 
        $buttons_html
    );
});


/**
 * html_ele - Builds an html element defined by a selector.
 * @param string $title     => card title keep it short
 * @param mixed $number     => the stats number
 * @param string $icon      => Icon to add
 * @param string $bg_icon   => bg of Icon to add
 * @param array $trend      => trend line to show expect ["dir","change","text"]
 * @return string           => HTML
 */
APageComponents::register("stat_card", function(string $title, mixed $number, string $icon, string $bg_icon, mixed $trend = null) {
    $trend = "";
    if (!empty($trend) && is_array($trend)) {
        $trend = APageComponents::$std::arr_get_from($trend, ["dir","change","text"], "");
        $trend = <<<HTML
            <p class="mt-3 mb-0 text-muted text-sm">
                <span class="text-danger mr-2"><i class="fas fa-arrow-{$trend['dir']}"></i>{$trend["change"]}</span>
                <span class="text-nowrap">{$trend["text"]}</span>
            </p>
        HTML;
    }
    return <<<HTML
        <div class="card card-stats mb-4 mb-xl-0">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title text-uppercase text-muted mb-0">{$title}</h5>
                        <span class="h2 font-weight-bold text-highlight mb-0">{$number}</span>
                    </div>
                    <div class="col-auto">
                        <div class="icon icon-shape bg-{$bg_icon} text-white rounded-circle shadow">
                            <i class="{$icon}"></i>
                        </div>
                    </div>
                </div>
                {$trend}
            </div>
        </div>
    HTML;
});