<?php

use RRZE\Knowledgebase\Output;

get_header();

$current_category = get_queried_object();

$output = new Output($current_category);
echo $output->render();

get_footer();