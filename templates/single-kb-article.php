<?php

defined('ABSPATH') || exit;

use RRZE\Knowledgebase\Output;

get_header();

$rrze_kb_current_object = get_queried_object();

$rrze_kb_output = new Output($rrze_kb_current_object);

$allowed_html = wp_kses_allowed_html( 'post' );

$allowed_html['select'] = [
    'name'     => true,
    'id'       => true,
    'class'    => true,
    'multiple' => true,
    'disabled' => true,
    'style'    => true,
];

$allowed_html['option'] = [
    'value'    => true,
    'selected' => true,
    'disabled' => true,
];

$allowed_html['form'] = [
    'method'    => true,
    'class' => true,
];

echo wp_kses($rrze_kb_output->render_single(), $allowed_html);

get_footer();