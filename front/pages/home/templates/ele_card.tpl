<div class="sport-cards-container">
    {#
    ["sport" => "soccer", "icon" => "icon-soccer", "leagues" => [
        [ "name" => "UEFA",     "description" => "Champions League",                "logo" => self::$std::fs_path_url($leagues_url, "champions-league.svg")],
        [ "name" => "FIFA",     "description" => "Club Wold Cup",                   "logo" => self::$std::fs_path_url($leagues_url, "world-cup.png")]
    ]]
    #}
    {% if sport_cards is iterable %}
        {% for card in sport_cards %}
            <div class="sport-card">
                <div class="card-content">
                    {% if card.sport is defined and card.icon is defined %}
                        <i class="icon-tombet {{ card.icon }}"></i>
                        <h2>{{ card.sport|capitalize }}</h2>
                    {% endif %}
                    {% if card.leagues is iterable %}
                        <ul class="card-leagues">
                            {% for league in card.leagues %}
                                <li class="league-row">
                                    <img  class="league-logo" src="{{ league.logo|e('html_attr') }}" />
                                    <span class="league-name">{{ league.name|upper }} - </span>
                                    <span class="league-description">{{ league.description|capitalize }}</span>
                                </li>
                            {% endfor %}
                        </ul>
                    {% endif %}
                </div>
            </div>
        {% endfor %}
    {% endif %}
</div>