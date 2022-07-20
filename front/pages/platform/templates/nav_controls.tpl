<!-- START Controls Nav -->
<ul class="app-controls" id="{{ controls_id }}">
  {% for control in controls %}
    <li class="control-item" data-filter="{{ control.filter|e('html_attr') }}">
        <div class="control-filter {{ control.selected ? "selected" }}">
            <i class="{{ control.icon ? control.icon|e('html_attr') }}"></i>
            <span class="text">{{ control.text ? control.text }}</span>
        </div>
    </li>
  {% endfor %}
</ul>
<!-- START Controls Nav -->