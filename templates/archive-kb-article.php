<?php

defined('ABSPATH') || exit;

use RRZE\Knowledgebase\Output;

get_header();

$rrze_kb_current_object = get_queried_object();

$rrze_kb_output = new Output($rrze_kb_current_object);
//echo wp_kses_post($rrze_kb_output->render_archive());
echo $rrze_kb_output->render_archive();

get_footer();