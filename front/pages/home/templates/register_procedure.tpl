
<!-- Procedures -->
<ul class="nav nav-tabs" id="registration-procedure" role="tablist">
    <li class="nav-item" role="presentation">
        <button 
            class="nav-link active" 
            id="proc-signup-tab" 
            data-bs-toggle="tab" 
            data-bs-target="#proc-signup" 
            type="button" 
            role="tab" 
            aria-controls="signup" 
            aria-selected="true"
        >
            Sign Up
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button 
            class="nav-link" 
            id="proc-payment-tab" 
            data-bs-toggle="tab" 
            data-bs-target="#proc-payment" 
            type="button" 
            role="tab" 
            aria-controls="payment" 
            aria-selected="false"
        >
            Payment
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button 
            class="nav-link" 
            id="proc-success-tab" 
            data-bs-toggle="tab" 
            data-bs-target="#proc-success" 
            type="button" 
            role="tab" 
            aria-controls="success" 
            aria-selected="false"
        >
            Enjoy!
        </button>
    </li>
</ul>
<!-- Procedures -->
<div class="tab-content" id="myTabContent">


    <!-- Sign Up -->
    <div class="tab-pane fade show active" id="proc-signup" role="tabpanel" aria-labelledby="sign-up-tab">
        <div class="inner-tab">
            <div class="inner-col col-left">
                <p class="tab-description">
                    Create your TOMBET Account Today and enhance our platform to make better betting decisions!
                </p>
                {{ include("register_form.tpl") }}
            </div>
            <div class="inner-col col-right in-modal ps-0 ps-md-3">
                <h2 class="tab-title">Choose Your Plan</h2>
                {{ include("pricing_plans.tpl") }}
            </div>
            <div class="container-fluid mt-4 d-block d-xl-none">
                <div class="col text-center">
                    <button class="btn btn-full-white force-bold" data-action="register-step1-continue">Continue</button>
                    <br />
                    <br />
                    <a href="#" class="link-light">Allready have an account?</a> 
                </div>
            </div>
        </div>
    </div>


    <!-- Payment -->
    <div class="tab-pane fade" id="proc-payment" role="tabpanel" aria-labelledby="payment-tab">
        <div class="inner-tab">
            <div class="inner-col col-left">
                {{ include("payment_form.tpl") }}
            </div>
            <div class="inner-col col-right in-modal ps-0 ps-md-3">
                <h2 class="tab-title">Payment Information</h2>
                <p class="tab-description">
                    {{ payment_information }}
                </p>
            </div>
        </div>
    </div>


    <!-- Success -->
    <div class="tab-pane fade" id="proc-success" role="tabpanel" aria-labelledby="success-tab">
        <div class="inner-tab">
            <div class="inner-col col-left">
                {{ include("payment_success.tpl") }}
            </div>
            <div class="inner-col col-right in-modal ps-0 ps-md-3">
            </div>
        </div>
    </div>


</div>