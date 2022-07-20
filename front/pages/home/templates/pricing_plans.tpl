


{#
[
    "name"   => "trial",
    "title"  => "3 Days trial",
    "price"  => 0,
    "yearly" => 0.85, 
    "price_display" => "Free", // only if we overwrite default
    "tag"   => "best",
    "perks" => [
        [ "highlight" => true, "text" => "5 Minutes delay"      ],
        [ "highlight" => true, "text" => "6 Sports supported"   ],
        [ "highlight" => true, "text" => "7 Bookmakers"         ],
        [ "highlight" => true, "text" => "All features & tools" ],
    ]
],
#}
<div  id="{{ pricing_plans.pricing_id }}" class="toggle-monthly {{ pricing_plans.pricing_class }}">
    <div class="toggle-label">
        Monthly
    </div>
    <div class="toggle-plans">
        <div class="form-check form-switch">
            <input class="form-check-input select-yearly-payment" type="checkbox" />
        </div>
    </div>
    <div class="toggle-label">
        Yearly
    </div>
</div>
<div class="plans-container {{ pricing_plans.pricing_class }}">
    {% if pricing_plans.plans is iterable %}
        <ul class="plans-list">
            {% for plan in pricing_plans.plans %}
                <li class="{{ plan.selected ? 'selected' }}"
                    data-plan="{{ plan.name|e('html_attr') }}"  
                    data-yearly="{{ plan.yearly|e('html_attr') }}" 
                    data-price="{{ plan.price|e('html_attr') }}"
                    {{ plan.price_display is defined ? "" : "data-update='update'" }}
                >
                    <h3 class="plan-title">{{ plan.title }}</h3>
                    {% if plan.price_display is defined %}
                        <p class="plan-description">{{ plan.price_display }}</p>
                    {% else %}
                        <p class="plan-description">$ {{ plan.price }} / Month </p>
                    {% endif %}
                    {% if plan.perks is iterable %}
                        {% for perk in plan.perks %}
                            <span class="plan-line">
                                <i class="icon-tombet {{ perk.highlight ? 'icon-check-round-round' : 'icon-check-round'}}"></i>
                                {{ perk.text}}
                            </span>
                        {% endfor %}
                    {% endif %}
                    <p class="select-plan-wrapper">
                        <button class="btn btn-full-white force-bold" data-action="open-register-modal-with-plan">Subscribe</button>
                    </p>
                    {% if plan.checkbox %}
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="check-select-plan" {{ plan.selected ? "checked='checked'" }} />
                    </div>
                    {% endif %}
                    {% if plan.tag is defined %}
                        <div class="ribbon-primary ribbon-right-bottom d-xxl-none">
                            <span>{{ plan.tag }}</span>
                        </div>
                        <div class="ribbon-primary ribbon-left-top d-none d-xxl-block">
                            <span>{{ plan.tag }}</span>
                        </div>
                    {% endif %}
                </li>
            {% endfor %}
        </ul>
    {% endif %}
</div>