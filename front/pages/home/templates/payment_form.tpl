<div id="">
    <div class="payment-methods-select">
        <ul>
            {% if paypal_enable %}
                <li>
                    <button type="button" data-toggle-form="payment-paypal" class="selected">
                        <i class="icon-tombet icon-paypal"></i>
                        PayPal
                    </button>
                </li>
            {% endif %}
            {% if crypto_enable %}
                <li>
                    <button type="button" data-toggle-form="payment-crypto">
                        <i class="icon-tombet icon-bitcoin"></i>
                        Crypto Payment
                    </button>
                </li>
            {% endif %}
        </ul>
    </div>
    <div class="payment-forms">
        <div id="payment-paypal" class="active">
            <form id="payment-form-paypal" class="form-dark">
                <ul class="payment-summary">
                    <li class="plan-describe">
                        <span class="line-text text-bold">Monthly Pro Account</span>
                        <span class="line-total">500 USD</span>
                    </li>
                    <li class="plan-subtotal">
                        <span class="line-text">Subtotal</span>
                        <span class="line-total">500 USD</span>
                    </li>
                    <li class="plan-discount">
                        <span class="line-text">Discount</span>
                        <span class="line-total">0%</span>
                    </li>
                    <li class="plan-total">
                        <span class="line-text text-bold">Total</span>
                        <span class="line-total text-bold">500 USD</span>
                    </li>
                </ul>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="auto-renew-paypal">
                    <label class="form-check-label" for="auto-renew-paypal">
                        Auto renewal subscription
                        <span>Your Paypal account will be charged at the end of the subscription period time.</span>
                    </label>
                </div>
                <button class="btn btn-full-white force-bold checkout-btn" >
                    Checkout
                </button>
            </form>
        </div>
        <div id="payment-crypto">
            <form id="payment-form-crypto" class="form-dark">
                <ul class="payment-summary">
                    <li class="plan-describe">
                        <span class="line-text text-bold">Monthly Pro Account</span>
                        <span class="line-total">500 USD</span>
                    </li>
                    <li class="plan-subtotal">
                        <span class="line-text">Subtotal</span>
                        <span class="line-total">500 USD</span>
                    </li>
                    <li class="plan-crypto">
                        <span class="line-text">Crypto Currency</span>
                        <div class="dropdown sik-dropdown" id="crypto-select">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            ...
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                {% for currency in currencies %}
                                    <li>
                                        <span class="dropdown-item" data-value="{{ currency.value }}">
                                            <img class="crypto-icon" src="{{ currency.icon }}" alt="{{ currency.value }}" />
                                            {{ currency.name }}
                                        </span>        
                                    </li>
                                {% endfor %}
                            </ul>
                        </div>
                    </li>
                    <li class="plan-discount">
                        <span class="line-text">Price in <em>BTC</em></span>
                        <span class="line-total">0.0054 BTC</span>
                    </li>
                    <li class="plan-discount">
                        <span class="line-text">Discount</span>
                        <span class="line-total">0%</span>
                    </li>
                    <li class="plan-total">
                        <span class="line-text text-bold">Total</span>
                        <span class="line-total text-bold">500 BTC</span>
                    </li>
                </ul>
                <button class="btn btn-full-white force-bold checkout-btn" >
                    Checkout
                </button>
            </form>
        </div>
    </div>
</div>