
import { SmartForm }    from '/bsik/manage/lib/js/SmartForm.module.js';
import { ButtonState }  from '/bsik/manage/lib/js/ButtonState.module.js';

class Login {

    bsik = null;
    modal = {};
    defaults = {
    };
    options = {};
    loginForm = null;
    loginBtn  = null;
    el = {
        form      : null,
        button    : null
    };
    
    constructor(_loginForm, _btn, modal, opt = {}) {

        //Depends on check:
        if (window.hasOwnProperty("Bsik")) {
            this.bsik = window.Bsik;
        } else {
            console.warn("Login requires the use of 'Bsik' plugin!");
            return;
        }

        //Depends on ButtonState:
        _btn = typeof _btn === 'string' ? document.querySelector(_btn) : _btn;
        if (typeof _btn === 'object' && _btn.hasOwnProperty('ButtonState')) {
            this.el.btn = $(_btn);
            this.loginBtn = _btn.ButtonState;
        } else if (typeof _btn === 'object' && _btn instanceof ButtonState) {
            this.el.btn = $(_btn.el.btn);
            this.loginBtn = _btn;
        } else {
            console.warn("Login Btn need to be of type ButtonState");
            return;
        }

        //Depends on smartform:
        if (!(_loginForm instanceof SmartForm)) {
            console.warn("Login form need to be of type SmartForm");
            return;
        } else {
            this.loginForm = _loginForm;
            this.el.form = $(this.loginForm.el.form);
        }

        //Set options:
        this.setOptions(opt);

        //Modal
        this.modal = {
            el  : $(modal._element),
            obj : modal
        };

        //Attach handlers:
        this.attachHandlers();
    }
    
    setOptions(opt) {
        this._extend(this.options, this.defaults, opt);
    }

    attachHandlers() {
        //Signup procedure:
        this.el.btn.on("click", this.performLogin.bind(this));
    }
    
    performLogin() {
        console.log("login procedure");
        if (this.loginForm && this.loginForm.validate()) {
            
            this.loginBtn.state("loading");
            //The data to send:
            let data = this.loginForm.getData();

            //send to server validate and login:
            let module = this;
            this.bsik.core.apiRequest(null, "manage.users.login", data, {
                error: function(jqXhr, textStatus, errorMessage) {
                    if (jqXhr.responseJSON && jqXhr.responseJSON.code && jqXhr.responseJSON.code === 400) {
                        for (const [key, value] of Object.entries(jqXhr.responseJSON.data)) {
                            if (!module.bsik.core.isEmptyObj(value))
                                module.loginForm.setFieldState(key, "valid", false);
                        }
                    }
                    console.log("noooo", jqXhr.responseJSON);
                },
                success: function(res) {
                    console.log("yess", res);
                    if (res.data && res.data.login) {
                        module.loginForm.feedback(true, "Login successfull redirecting page.");
                        module.bsik.core.reloadPage(500);
                    } else if (res.data && res.data.login === false) {
                        module.loginForm.feedback(false, "Login failed - please check you account username and password.");
                    } else {
                        module.loginForm.feedback(false, "Login failed - Something went wrong refresh the page and try again.");
                    }
                },
                complete: function() {
                    module.loginBtn.state("active");
                }
            });
        } else {
            this.loginBtn.state("active");
        }
    }
    openModal(step = null, plan = "none") {
        this.modal.obj.show();
    }
    closeModal() {
        this.loginForm.resetForm();
        this.modal.obj.hide();
    }
    _extend() {
        for(var i=1; i<arguments.length; i++)
            for(var key in arguments[i])
                if(arguments[i].hasOwnProperty(key))
                    arguments[0][key] = arguments[i][key];
        return arguments[0];
    }
}


export { Login }