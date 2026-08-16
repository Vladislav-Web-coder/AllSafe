<?php

return [

    /*
     * Возможные значения:
     * fake
     * ollama
     * llama_cpp
     */
    'client' => env('AI_CLIENT', 'fake'),

    'analysis' => [
        'max_chars' => (int) env('AI_ANALYSIS_MAX_CHARS', 20000),
    ],

    'ollama' => [
        'base_url' => env('AI_OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('AI_OLLAMA_MODEL', 'qwen2.5:7b'),
        'temperature' => (float) env('AI_OLLAMA_TEMPERATURE', 0.1),
        'timeout' => (int) env('AI_OLLAMA_TIMEOUT', 180),
    ],

    'llama_cpp' => [
        'base_url' => env('AI_LLAMA_CPP_BASE_URL', 'http://127.0.0.1:8080'),
        'api_key' => env('AI_LLAMA_CPP_API_KEY'),
        'model' => env('AI_LLAMA_CPP_MODEL', 'local'),
        'temperature' => (float) env('AI_LLAMA_CPP_TEMPERATURE', 0.1),
        'max_tokens' => (int) env('AI_LLAMA_CPP_MAX_TOKENS', 2048),
        'timeout' => (int) env('AI_LLAMA_CPP_TIMEOUT', 180),
    ],
    'embedding' => [
        'base_url' => env('AI_EMBEDDING_BASE_URL', 'http://127.0.0.1:8001'),
        'timeout' => (int) env('AI_EMBEDDING_TIMEOUT', 60),
    ],

];
