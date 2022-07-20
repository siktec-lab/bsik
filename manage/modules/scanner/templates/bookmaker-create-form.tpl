
{% set icon_required = "<i class='fas fa-asterisk' style='color:#ff9900;font-size:0.5em;vertical-align:super'></i>&nbsp;" %}
{% set icon_invalid = "<i class='fas fa-exclamation-circle'></i>&nbsp;" %}
<div class="container">
    <form id="create-bookmaker-form" autocomplete="off">
        <div class="row mt-2 g-2">
            <div class="col">
                <div class="form-floating">
                    <input type="text" class="form-control" id="create-bookmaker-sys-name" max-type="50" placeholder="Bookmaker Name" />
                    <label for="create-bookmaker-sys-name">
                        {{ icon_required|raw }}<i class="fas fa-id-badge fw-normal"></i>&nbsp;Bookmaker System Name:
                    </label>
                    <div class="invalid-feedback">
                        {{ icon_invalid|raw }} Please type the bookmaker system used name - Minimum 2 chars long.
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-floating">
                    <input type="text" class="form-control" id="create-bookmaker-display-name" max-type="50" placeholder="Bookmaker display" autocomplete="new-last-name" />
                    <label for="create-bookmaker-display-name">
                        {{ icon_required|raw }}<i class="fas fa-id-badge fw-normal"></i>&nbsp;Display name:
                    </label>
                    <div class="invalid-feedback">
                        {{ icon_invalid|raw }} Please type the bookmaker display name - Minimum 2 chars long.
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-floating">
                    <div class="dropdown sik-dropdown" id="create-bookmaker-select-logo-dropdown">
                        <button class="btn btn-lg dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        ...
                        </button>
                        <ul class="dropdown-menu">
                            {% for logo in logos %}
                                <li>
                                    <span class="dropdown-item" data-value="{{ logo|e('html_attr') }}">
                                        <img class="bookmaker-icon" src="{{ logo|e('html_attr') }}" />&nbsp;
                                        {{ logo|split('/')|last }}
                                    </span>        
                                </li>
                            {% endfor %}
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col">
                <h4>
                    <i class="fas fa-cloud-upload-alt"></i>
                    You can upload new bookmaker logo:
                </h4>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col">
                <input type="file" id="bookmakers-upload-thumbs" accept="image/png" ></input>
            </div>
        </div>
    </form>
</div>
