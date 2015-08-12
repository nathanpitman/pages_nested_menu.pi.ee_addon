'Pages - Nested Menu' for ExpressionEngine
=============================

This plugin creates a list of your Pages (created using the native Pages module). Basic syntax is as follows:

	{exp:pages_nested_menu root="/about/" include_ul="no" include_root="no" depth="1"}
		<a href="{pnm_page_url}" title="{pnm_title}">{pnm_title}</a>
	{/exp:pages_nested_menu}

The current page is wrapped with a list item with a class of 'active'.

Original development by [Mark Huot](https://github.com/markhuot). Updated for EE2.x by Nathan Pitman.
