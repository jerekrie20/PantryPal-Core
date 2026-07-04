<?php

namespace Services\AI\Providers;

/**
 * Contract for AI chat providers (Anthropic Claude, Google Gemini, etc.)
 */
interface ChatProviderInterface
{
    /**
     * Send a chat conversation to the provider and get a response
     *
     * @param string $systemPrompt System instructions for the model
     * @param array $messages Conversation history as [['role' => 'user'|'assistant', 'content' => string], ...]
     * @return array ['content' => string, 'usage' => ['input_tokens' => int, 'output_tokens' => int]]
     * @throws \Exception on API errors
     */
    public function chat(string $systemPrompt, array $messages): array;

    /**
     * Provider name for logging/debugging (e.g. 'anthropic', 'gemini')
     */
    public function name(): string;
}
