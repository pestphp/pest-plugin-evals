<?php

declare(strict_types=1);

return [

    'ai' => [
        'scoring' => [
            'provider' => env('EVAL_SCORING_PROVIDER', 'openai'),
            'model' => env('EVAL_SCORING_MODEL', 'gpt-5.4-nano'),
        ],
        'embedding' => [
            'provider' => env('EVAL_EMBEDDING_PROVIDER', 'openai'),
            'model' => env('EVAL_EMBEDDING_MODEL', 'text-embedding-3-small'),
        ],
    ],

];
