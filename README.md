Pages - Nested Menu (pages_nested_menu.pi)
=============================

This plugin creates a list of your Pages (created using the native Pages module). Basic syntax is as follows:

{exp:pages_nested_menu root="/about/" include_ul="no" include_root="no" depth="1"}
    <a href="{pnm_page_url}" title="{pnm_title}">{pnm_title}</a>
{/exp:pages_nested_menu}

Another example showing how to style the 'current' page:

{exp:pages_nested_menu root="/about/" include_ul="no" include_root="no" depth="1"}
    <a href="{pnm_page_url}" title="{pnm_title}" {if "{segment_2}" == "{pnm_url_title}"} class="current"{/if}>{pnm_title}</a>
{/exp:pages_nested_menu}