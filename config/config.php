<?php

return [
    // When the value is false, then captcha will be disabled
    'enableCaptcha' => env('ENABLE_CAPTCHA_IN_SURVEY', false),
    'throotleTrigger' => [
        'interval' => 5, // in minutes
        'maxPostCount' => 10, // The maximum number of posts can be stored within spesified interval
    ],
];