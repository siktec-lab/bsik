<div class="tb-tab-left">
    <h1>Take your betting to the next level</h1>
    <p>All in one Web App solution for Pro and New bettors. Collect, Compare and track odds in a single place.</p>
    <button class="btn btn-full-white force-bold" data-action="open-register-modal">Lets Get Started</button>
    <button id="btn1" class="btn btn-full-white force-bold button-state" data-action="test-self-api">Test Self</button>
    <button id="btn2" class="btn btn-full-white force-bold button-state button-state-loading" data-action="test-admin-api">Test Admin</button>
</div>
<div class="tb-tab-right">
    <div class="application-slider">
        {% for slide in application.slides %}
            <div>
                {% if slide.title is defined %}
                    <h2>{{ slide.title }}</h2>
                {% endif %}
                {% if slide.img is defined %}
                    <img src="{{ slide.img|e('html_attr') }}" />
                {% endif %}
                {% if slide.caption is defined %}
                    <p>{{ slide.caption }}</p>
                {% endif %}
            </div>
        {% endfor %}
    </div>
</div>