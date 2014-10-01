<?php

\Nos\I18n::current_dictionary('novius_renderers::default');

return array(
    // Default available models
    'models' => array(
        'Nos\Page\Model_Page' => __('Page'),
    ),
    // Maximum number of suggestions (unlimited if empty value)
    'suggestion_limit' => null,
);
