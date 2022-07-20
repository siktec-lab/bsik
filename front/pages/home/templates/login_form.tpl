

<h2>TOMBET&nbsp;&nbsp;Login</h2>
<form id="user-login-form" class="form-dark smart-form" autocomplete="off">
    <input style="display: none" type="text" name="login-username" class="smart-form-exclude" />
    <input style="display: none" type="password" name="password" class="smart-form-exclude" />
    <div class="general-feedback">
        <span class="material-icons show-invalid">error_outline</span>
        <span class="material-icons show-valid">check_circle_outline</span>
        <span class="message">testing smart forms</span>
    </div>
    <div class="mb-3">
        <div class="form-floating">
            <input type="email" class="form-control" id="login-user-name" placeholder="account@mail.com" autocomplete="new-account" />
            <label for="login-user-name">
                <span class="material-icons">account_circle</span>Account:
            </label>
            <div class="invalid-feedback">
                <span class="material-icons">error_outline</span>Please type a valid Email address.
            </div>
        </div>
    </div>
    <div class="mb-3">
        <div class="form-floating">
            <input type="password" class="form-control" id="login-password" placeholder="example" />
            <label for="login-password">
                <span class="material-icons">vpn_key</span>Password:
            </label>
            <div class="invalid-feedback">
                <span class="material-icons">error_outline</span>Password is invalid.
            </div>
        </div>
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="login-set-rem">
        <label class="form-check-label" for="login-set-rem">Remmember me.</label>
    </div>
    <button type="button" class="btn btn-full-white force-bold button-state" data-action="user-login">
        Continue
    </button>
    <a href="#" class='link-light d-block mt-3 text-center'>Don't have an account yet?</a>
</form>