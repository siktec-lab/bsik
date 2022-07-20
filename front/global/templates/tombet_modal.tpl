<!-- Modal -->
<div class="modal fade tombet-modal {{ add_class }}" id="{{ id|e('html_attr')}}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog {{ size }}">
        <div class="modal-content">
            <div class="modal-header">
                {% if dismiss_button %}
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                {% endif %}
            </div>
            <div class="modal-body">
                <div class="container-fluid p-0 {{ body_classes }}">
                    {% if body is defined %}
                        {{ body|raw }}
                    {% else %}
                        <div class="content-layout">
                            <div class="modal-left-content {{ left_col_classes }}">
                                {{ left|raw }}
                            </div>
                            <div class="modal-right-content {{ right_col_classes }}">
                                {{ right|raw }}
                            </div>
                        </div>
                    {% endif %}
                </div>
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>