{# Footer global template #}
        <!-- START : body HTML includes -->
            {{ extra_html|join|raw }}
        <!-- END : body HTML includes -->
        <!-- START : body LIBS includes -->
            {{ js_body|raw }}
        <!-- END : body LIBS includes -->
    </body>
</html>
