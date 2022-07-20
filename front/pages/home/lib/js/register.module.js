class Register {
    steps = {};
    tabContainer  = null;
    modal = {};
    defaults = {
        signup_form     : null,
        signup_button   : null,
        signup_plans    : null,
        default_plan    : "starter"
    };
    options = {};
    el = {
        signup_form     : null,
        signup_button   : null,
        signup_plans    : null,
    };
    signup_data = {
        first_name         : "",
        last_name          : "",
        account_email      : "",
        set_password       : "",
        confirm_password   : "",
        agreement          : false,
        plan               : "",
        period             : "monthly"
    };
    constructor(identifier, modal, opt = {}) {
        //Set options:
        this.setOptions(opt);

        //Load steps:
        var _this = this;
        this._initiateTabs(identifier);
        this._storeTabs();
        this.modal = {
            el  : $(modal._element),
            obj : modal
        };

        //load elements:
        this.loadElements();

        //Set payment toggler:
        this._togglePaymentMethod();

        //Attach handlers:
        this.attachHandlers();
    }
    setOptions(opt) {
        this._extend(this.options, this.defaults, opt);
    }
    loadElements() {
        for (const [key, ele] of Object.entries(this.el)) {
            if (this.options.hasOwnProperty(key)) {
                this.el[key] = this.modal.el.find(this.options[key]);
            }  else {
                this.el[key] = $();
            }
        }
    }
    attachHandlers() {
        //Signup procedure:
        this.el.signup_button.on("click", this.finishStepSignup.bind(this));
    }
    finishStepSignup() {
        if (this.el.signup_form.length) {
            let mapping = {
                "register-confirm-password" : "confirm_password",
                "register-email"            : "account_email",
                "register-first-name"       : "first_name",
                "register-last-name"        : "last_name",
                "register-password"         : "set_password",
                "register-terms-agree"      : "agreement"
            };
            let data = this._serializeForm(this.el.signup_form, [], mapping);
            data["period"] = this.el.signup_plans.find(".select-yearly-payment").prop("checked") ? "yearly" : "monthly";
            data["plan"]   = this.el.signup_plans.next(".plans-container").find("li.selected").data("plan") ?? this.options.default_plan;
            
            //Validate basic:
            let validation = this._validateSignupForm(data, mapping);
            this.el.signup_form.find(".is-invalid").removeClass("is-invalid");
            for (const value of validation) {
                this.el.signup_form.find(`#${value}`).addClass("is-invalid");
            }
            console.log(data, validation);
            if (validation.length === 0) {
                //send to server validate and get price or invalid:
                //Load payment with the needed price:
            }
        }
    }
    loadStepPayment() {

    }
    finishStepPayment() {

    }
    _initiateTabs(identifier) {
        this.tabContainer = $(identifier);
        var triggerTabList = [].slice.call(this.tabContainer[0].querySelectorAll('button'))
        triggerTabList.forEach(function (triggerEl) {
          var tabTrigger = new bootstrap.Tab(triggerEl);
          triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
          });
        });
    }
    _storeTabs() {
        var _this = this;
        this.tabContainer.find("button").each(function(){
            let name = Bsik.core.helpers.trimChars($(this).data("bs-target"), "#");
            _this.steps[name] = bootstrap.Tab.getInstance(this);
        });
    }
    _togglePaymentMethod() {
        var _this = this;
        this.modal.el.find("button[data-toggle-form]").on("click", function(){
            if (!$(this).hasClass("selected")) {
                let form = $('#' + $(this).data("toggle-form"));
                $(this).closest("ul").find("button[data-toggle-form]").removeClass("selected");
                $(this).addClass("selected");
                _this.modal.el.find(".payment-forms > div").removeClass("active");
                form.addClass("active");
            }
            
        });
    }
    _validateSignupForm(data, map) {
        let validation = [];
        for ( const [key, value] of Object.entries(data)) {
            switch (key) {
                case "first_name": {
                    if (value.trim().length < 2) {
                        validation.push(this._getKeyByValue(map, key));
                    }
                } break;
                case "last_name": {
                    if (value.trim().length < 2) {
                        validation.push(this._getKeyByValue(map, key));
                    }
                } break;
                case "account_email": {
                    if (value.trim().length < 6) {
                        validation.push(this._getKeyByValue(map, key));
                    }
                } break;
                case "set_password": {
                    if (value.trim().length < 8) {
                        validation.push(this._getKeyByValue(map, key));
                    }
                } break;
                case "confirm_password": {
                    if (value.trim() !==  data["set_password"].trim()) {
                        validation.push(this._getKeyByValue(map, key));
                    }
                } break;
                case "agreement": {
                    if (!value) {
                        validation.push(this._getKeyByValue(map, key));
                    }
                } break;
            }
        }
        return validation;
    }
    setPlan(plan = "none") {
        let $plan = this.modal.el.find(`li[data-plan='${plan}']`);
        if ($plan.length) {
            $plan.trigger("click");
        }
    }
    loadStep(step = "none") {
        if (this.steps.hasOwnProperty(step)) {
            this.steps[step].show();
        }
    }
    openModal(step = null, plan = "none") {
        this.setPlan(plan);
        this.loadStep(step);
        this.modal.obj.show();
    }
    closeModal() {
        this.modal.obj.hide();
    }
    _extend() {
        for(var i=1; i<arguments.length; i++)
            for(var key in arguments[i])
                if(arguments[i].hasOwnProperty(key))
                    arguments[0][key] = arguments[i][key];
        return arguments[0];
    }
    _serializeForm(form, exclude, map) {
        exclude || (exclude = []);
        map || (map = {});
        let obj = {},
            $form = $(form);
        if ($form.length) {
            $form
                .find("input, select, textarea") // Loop all input fields 
                .not(':input[type=button], :input[type=submit], :input[type=reset]') // We don't want those:
                .each(function(i, e) {
                    let _name = (e.name) ? e.name : e.id; //Make sure we have names otherwise use the ID:
                    if (_name.length && exclude.indexOf(_name) === -1) { //If not excluded:
                        //Map the name:
                        if (map.hasOwnProperty(_name)) {
                            if ($(e).attr("type") === "checkbox") {
                                obj[map[_name]] = $(e).prop("checked");
                            } else {
                                obj[map[_name]] = $(e).val() || "";
                            }
                            
                        }
                    }
                });
        }
        return obj;
    }
    _getKeyByValue(object, value) {
        return Object.keys(object).find(key => object[key] === value);
    }

}


export { Register }