{% set icon_required = "<i class='fas fa-asterisk' style='color:#ff9900;font-size:0.5em;vertical-align:super'></i>&nbsp;" %}
{% set icon_invalid = "<i class='fas fa-exclamation-circle'></i>&nbsp;" %}
<div class="container">
    <form id="create-page-form" autocomplete="off">
        <div class="row mt-2">
            <div class="col">
                <div class="form-floating">
                    <input data-action="input-check-name" type="text" max-type="50" class="form-control" id="create-input-page-name" placeholder="unique name" autocomplete="new-create-input-page-name" />
                    <label for="create-input-page-name">
                        {{ icon_required|raw }}Page name:
                    </label>
                    <div class="invalid-feedback">
                        {{ icon_invalid|raw }}Please choose a Unique page name (a-z,0-9,_ minimum 2).
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="form-floating">
                    <select data-action="input-load-templates" class="form-select" id="create-input-page-type">
                        <option value="2" selected>Local File</option>
                        <option value="1">Dynamic</option>
                    </select>
                    <label for="create-input-page-type">Template type</label>
                </div>
            </div>
        </div>
        <div class="row mt-2"> 
            <div class="col">
                <div class="form-floating">
                    <input type="text" class="form-control" id="create-input-page-display-name" max-type="150" placeholder="unique name"  autocomplete="new-create-input-page-display-name" />
                    <label for="create-input-page-display-name">Display name</label>
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col">
                <label for="create-input-page-template" class="form-label fw-bold">
                    <i class="fas fa-drafting-compass"></i>&nbsp;Use Template:
                </label>
                <select class="form-select" size="4" id="create-input-page-template">
                </select>
            </div>
        </div>
    </form>
</div>