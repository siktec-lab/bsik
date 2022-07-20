{# the home page tpl #}
<section class="tb-main-wrapper">
    <section class="tb-top-nav">
        {{ include('nav.tpl') }}
    </section>
    <section class="tb-content">
        {% set tabs = ['home', 'sports', 'pricing', 'contact', 'trial'] %}
        {% for tab in tabs %}
            <div class="tb-content-tab {{ current == tab ? 'current' }}" data-tab-name="{{ tab|raw }}">
                {{ include(tab ~ '_tab.tpl') }}
            </div>
        {% endfor %}
    </section>
    <section class="tb-bottom-bar">
        {{ include('bottom_bar.tpl') }}
    </section>
</section>
