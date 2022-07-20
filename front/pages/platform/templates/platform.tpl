{# the platform page tpl #}
<section class="tb-main-wrapper">
    <section class="nav">
        <div class="sports-menu">
            {{ include('sports_menu.tpl') }}
        </div>
        <div class="server-menu">
            {{ include('server_menu.tpl') }}
        </div>
        <div class="user-menu-wrapper">
            {{ include('nav_user.tpl') }}
        </div>
    </section>
    <section class="controls">
        {{ include('nav_controls.tpl') }}
    </section>
    <section class="odds">
        {{ include('odds_table.tpl') }}
        {#odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
         odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />
        odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br />odds<br /> #}
    </section>
    <section class="panel">
        {{ include('app_tab.tpl') }}
    </section>
</section>
