<?php

namespace Services\AI\Providers;

/**
 * Resolves which AI provider to use based on .env configuration
 *
 * Set AI_PROVIDER=gemini or AI_PROVIDER=anthropic to choose explicitly.
 * If AI_PROVIDER is not set, auto-detects: Gemini first (free tier),
 * then Anthropic.
 */
class ProviderFactory
{
    public static function make(): ChatProviderInterface
    {
        $provider = strtolower(trim(getenv('AI_PROVIDER') ?: ''));

        switch ($provider) {
            case 'gemini':
                return new GeminiProvider();

            case 'anthropic':
            case 'claude':
                return new AnthropicProvider();

            case '':
                // Auto-detect: prefer Gemini (free tier) when both keys exist
                if (getenv('GEMINI_API_KEY')) {
                    return new GeminiProvider();
                }
                if (getenv('ANTHROPIC_API_KEY')) {
                    return new AnthropicProvider();
                }
                throw new \RuntimeException(
                    'No AI provider configured. Set GEMINI_API_KEY or ANTHROPIC_API_KEY in your .env file '
                    . '(and optionally AI_PROVIDER=gemini|anthropic to choose explicitly).'
                );

            default:
                throw new \RuntimeException(
                    "Unknown AI_PROVIDER '{$provider}' in .env — use 'gemini' or 'anthropic'."
                );
        }
    }
}
