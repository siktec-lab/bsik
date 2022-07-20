<form class="form-dark" autocomplete="off" id="user-signup-form" action="post">
    <input style="display: none" type="text" name="login-username" />
    <input style="display: none" type="password" name="password" />
    <div class="row mb-3">
        <div class="col-12 col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="register-first-name" placeholder="First Name" autocomplete="new-register-first-name" />
                <label for="register-first-name">
                    <span class="material-icons">account_circle</span>First Name:
                </label>
                <div class="invalid-feedback">
                    <span class="material-icons">error_outline</span>Please type your name.
                </div>
            </div>
        </div>
        <div class="mt-3 mt-md-0 col-12 col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="register-last-name" placeholder="First Name" autocomplete="new-register-last-name" />
                <label for="register-last-name">
                    <span class="material-icons">account_circle</span>Last Name:
                </label>
                <div class="invalid-feedback">
                    <span class="material-icons">error_outline</span>Please type your last name.
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col">
            <div class="form-floating">
                <input type="email" class="form-control" id="register-email" placeholder="account@mail.com" autocomplete="new-register-email" />
                <label for="register-email">
                    <span class="material-icons">mail_outline</span>Email Address:
                </label>
                <div class="invalid-feedback">
                    <span class="material-icons">error_outline</span>Please type a valid Email address.
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-12 col-md-6">
            <div class="form-floating">
                <input type="password" class="form-control" id="register-password" placeholder="password" />
                <label for="register-password">
                    <span class="material-icons">vpn_key</span>Password:
                </label>
                <div class="invalid-feedback">
                    <span class="material-icons">error_outline</span>Please use upper and lower case with digits and special symbols.
                </div>
            </div>
        </div>
        <div class="mt-3 mt-md-0 col-12 col-md-6">
            <div class="form-floating">
                <input type="password" class="form-control" id="register-confirm-password" placeholder="confirm password" />
                <label for="register-confirm-password">
                    <span class="material-icons">vpn_key</span>Confirm Password:
                </label>
                <div class="invalid-feedback">
                    <span class="material-icons">error_outline</span>Confiramtion password don't match.
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="register-terms-agree">
                <label class="form-check-label" for="register-terms-agree">I agree to the user terms - <a href='#' target='_blank'>platform terms</a>.</label>
            </div>
        </div>
    </div>
    <div class="row mt-4 d-none d-xl-block">
        <div class="col text-center">
            <button type="button" class="btn btn-full-white force-bold" id="signup-continue">Continue</button>
            <br />
            <br />
            <a href="#" class="link-light">Allready have an account?</a> 
        </div>
    </div>
</form>