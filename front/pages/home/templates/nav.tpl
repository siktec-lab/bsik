
<div class="container-fluid">
    <div class="row tb-nav align-items-end">
        <div class="col-12 col-md-6 col-lg-auto logo-wrapper">
            <img src="{{ main_logo }}" alt="logo" class="main-logo animate__animated animate__slideInLeft">
        </div>
        
        <div class="col-12 col-md-6 col-lg-auto button-wrapper">
            {% for entry in menu_buttons %}
                    <button 
                        type="button" 
                        class="btn btn-{{ entry.color|e('html_attr') }} force-bold" 
                        data-action="{{ entry.action == 'redirect' ? entry.action : 'calltoaction' }}" 
                        data-load="{{ entry.action == 'redirect' ? '' : entry.action|e('html_attr') }}"
                        {{ entry.attrs is defined ? render_as_attributes(entry.attrs)|raw }} 
                        style="{{ entry.hidden ? 'display:none;'|raw }}"
                    >
                    {% if entry.icon %}
                        <i class="{{ entry.icon|e('html_attr') }}"></i>
                    {% endif %}
                        {{ entry.text }}
                    </button>
            {% endfor %}
        </div>
        <div class="col-12 col-xl-auto tabs-wrapper">
            <ul class="pages-tabs list-inline">
                {% for entry in menu %}
                    <li class="page-tab list-inline-item {{ entry.selected ? 'tab-selected' }}">
                        <a href="{{ entry.link|e('html_attr') }}" data-action="navigate" data-tab="{{ entry.action|e('html_attr') }}">
                            <i class="icon-tombet icon-{{ entry.icon|e('html_attr') }}"></i>
                            <span>{{ entry.text|capitalize }}</span>
                        </a>
                    </li>
                {% endfor %}
            </ul>
        </div>
    </div>
</div>