<!-- START User Nav Menu -->
{#
["checkbox" => true, "logo" => "tes", "text" => "BET365",       "id" => 1238]
#}
<ul class="fmenu" id="{{ user_menu_id }}">
  {% for user_item in user_menu %}
    <li class="fmenu-item {{ user_item.class|e('html_attr') }}">
        <div class="trigger-menu {{ user_item.expanded ? "expanded" }}">
            <i class="{{ user_item.icon ? user_item.icon|e('html_attr') }}"></i>
            <span class="text">{{ user_item.text ? user_item.text }}</span>
        </div>
        <ul class="floating-menu">
            {% for list_item in user_item.list %}
                {% if list_item.href is defined %}
                    <li>
                        <a href="{{ list_item.href|e('html_attr') }}" {{ list_item.attrs is defined ? render_as_attributes(list_item.attrs)|raw }} >
                            <i class="{{ list_item.icon|e('html_attr') }}"></i>{{ list_item.text }}
                        </a>
                    </li>
                {% elseif list_item.checkbox is defined %}
                    <li>
                        <div class="form-check">
                            <label class="form-check-label">
                                <input class="form-check-input enable-bookmaker" type="checkbox" value="{{ list_item.id|e('html_attr') }}" {{ list_item.checkbox ? "checked" }} />
                                <img src="{{ list_item.checkbox|e('html_attr') }}" class="bookmaker-logo" />
                                {{ list_item.text }}
                            </label>
                        </div>
                    </li>
                {% endif %}
            {% endfor %}
        </ul>
    </li>
  {% endfor %}
</ul>
<!-- END User Nav Menu -->