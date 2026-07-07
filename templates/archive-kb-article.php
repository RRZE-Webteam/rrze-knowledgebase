<?php

use RRZE\Knowledgebase\Output;

get_header();

$current_object = get_queried_object();

$output = new Output($current_object);
echo $output->render();

get_footer();