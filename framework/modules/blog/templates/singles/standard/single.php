<?php

baker_edge_get_single_post_format_html($blog_single_type);

baker_edge_get_module_template_part('templates/parts/single/author-info', 'blog');

baker_edge_get_module_template_part('templates/parts/single/single-navigation', 'blog');

baker_edge_get_module_template_part('templates/parts/single/comments', 'blog');

baker_edge_get_module_template_part('templates/parts/single/related-posts', 'blog', '', $single_info_params);

