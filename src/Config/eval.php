<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the providers and models powering your ai eval runs.
    | The scoring section sets the LLM-as-judge provider and model that will
    | grade agent responses, while embedding handles similarity scoring.
    |
    */

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
