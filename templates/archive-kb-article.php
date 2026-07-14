<?php

defined('ABSPATH') || exit;

use RRZE\Knowledgebase\Output;

get_header();

$current_object = get_queried_object();

$output = new Output($current_object);
echo wp_kses_post($output->render_archive());

get_footer();