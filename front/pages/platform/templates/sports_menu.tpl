
<!-- START Sports Menu -->
<ul class="menu-items">
{% if sports_menu is defined %}
    {% for item in sports_menu %}
        <li 
            class="item {{ item.selected ? "selected" }}" 
            title="{{ item.title|e('html_attr') }}"
            data-sport="{{ item.sport|e('html_attr') }}"
        >
            <i class="drop-shadow icon-tombet icon-{{ item.icon|e('html_attr') }}"></i>
            <span class="text drop-shadow">{{ item.text }}</span>
        </li>
    {% endfor %}
{% endif %}
</ul>
<!-- END Sports Menu -->