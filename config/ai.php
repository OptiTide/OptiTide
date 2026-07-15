<?php

/**
 * Live-chat AI assistant. When an Anthropic API key is present the chat answers
 * with Claude; otherwise it uses a helpful built-in fallback responder. Either
 * way a human can take over the conversation from the admin.
 */
return [
    'anthropic_key' => env('ANTHROPIC_API_KEY', ''),
    'model'         => env('CHAT_AI_MODEL', 'claude-haiku-4-5-20251001'),
    'enabled'       => env('CHAT_AI_ENABLED', true),
];
