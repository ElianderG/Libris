<?php

return [

    'affiliate' => [
        'commission_percent' => (int) env('AFFILIATE_COMMISSION_PERCENT', 10),
        'cookie_days' => (int) env('AFFILIATE_COOKIE_DAYS', 7),
    ],

];
