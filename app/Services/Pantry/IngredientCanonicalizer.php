<?php

namespace Services\Pantry;

use Models\IngredientSearchTerms;
use Services\AI\Providers\ChatProviderInterface;
use Services\AI\Providers\ProviderFactory;

/**
 * Turns grocery product names into concise recipe-search terms.
 *
 * "Red Seedless Grapes"              -> "grapes"
 * "Mexican Style Finely Shredded..." -> "shredded cheese"
 *
 * Uses the configured AI provider for the translation and caches every
 * mapping permanently in ingredient_search_terms, so each unique name
 * costs at most one AI call ever. Falls back to the rule-based
 * PantryTermNormalizer when the AI is unavailable or fails — search
 * always works, just less smart.
 */
class IngredientCanonicalizer
{
    private ?ChatProviderInterface $provider;
    private IngredientSearchTerms $cache;

    public function __construct()
    {
        $this->cache = new IngredientSearchTerms();
        try {
            $this->provider = ProviderFactory::make();
        } catch (\Exception $e) {
            error_log('IngredientCanonicalizer: no AI provider available, using rule-based fallback: ' . $e->getMessage());
            $this->provider = null;
        }
    }

    /**
     * Canonicalize a batch of raw pantry names.
     *
     * @param string[] $rawNames
     * @return array<string, string> raw name => search term (order preserved, blanks dropped)
     */
    public function canonicalizeAll(array $rawNames): array
    {
        $rawNames = array_values(array_unique(array_filter(array_map(
            static fn($n) => trim((string)$n),
            $rawNames
        ), static fn($n) => $n !== '')));

        if (empty($rawNames)) {
            return [];
        }

        // 1. Cached mappings (one query, no AI cost)
        $result = [];
        try {
            $result = $this->cache->getForNames($rawNames);
        } catch (\Throwable $e) {
            // Table missing or DB hiccup — degrade gracefully
            error_log('IngredientCanonicalizer cache read failed: ' . $e->getMessage());
        }

        $missing = array_values(array_diff($rawNames, array_keys($result)));

        // 2. One AI call for all uncached names
        if (!empty($missing) && $this->provider !== null) {
            $aiMap = $this->translateWithAI($missing);
            if (!empty($aiMap)) {
                try {
                    $this->cache->storeMany($aiMap);
                } catch (\Throwable $e) {
                    error_log('IngredientCanonicalizer cache write failed: ' . $e->getMessage());
                }
                $result += $aiMap;
                $missing = array_values(array_diff($missing, array_keys($aiMap)));
            }
        }

        // 3. Rule-based fallback for anything the AI didn't cover (not cached,
        //    so a later AI recovery can still improve these)
        foreach ($missing as $name) {
            $term = PantryTermNormalizer::normalize($name);
            if ($term !== '') {
                $result[$name] = $term;
            }
        }

        // Return in input order
        $ordered = [];
        foreach ($rawNames as $name) {
            if (isset($result[$name]) && $result[$name] !== '') {
                $ordered[$name] = $result[$name];
            }
        }
        return $ordered;
    }

    /**
     * Canonicalize a single name (e.g. a typed search query).
     */
    public function canonicalize(string $rawName): string
    {
        $map = $this->canonicalizeAll([$rawName]);
        return $map[trim($rawName)] ?? PantryTermNormalizer::normalize($rawName);
    }

    /**
     * Ask the AI to translate a batch of product names.
     *
     * @param string[] $names
     * @return array<string, string> name => term (only valid entries)
     */
    private function translateWithAI(array $names): array
    {
        $system = <<<PROMPT
You convert grocery product names into short ingredient terms for searching a recipe database.

Rules:
- Reply with ONLY a JSON object mapping each input name (exactly as given) to its search term. No markdown, no explanations.
- Terms are lowercase, 1-2 words, the core cooking ingredient.
- Strip brands, varieties, sizes, and marketing words.
- Examples: "Red Seedless Grapes" -> "grapes"; "Mexican Style Finely Shredded Cheeses" -> "shredded cheese"; "Honeycrisp Apples" -> "apple"; "Boneless Skinless Chicken Thighs" -> "chicken thighs"; "Cherry Coke Zero Sugar" -> "cola"; "Banana" -> "banana".
- If an item is not really a cooking ingredient (gum, bottled water), still give its simplest generic term (e.g. "gum", "water").
PROMPT;

        try {
            $response = $this->provider->chat($system, [
                ['role' => 'user', 'content' => json_encode(array_values($names), JSON_UNESCAPED_UNICODE)],
            ]);

            $text = trim($response['content'] ?? '');
            // Strip markdown code fences if the model added them anyway
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
            $decoded = json_decode(trim($text), true);
            if (!is_array($decoded)) {
                error_log('IngredientCanonicalizer: AI returned non-JSON response');
                return [];
            }

            $map = [];
            foreach ($names as $name) {
                $term = $decoded[$name] ?? null;
                if (is_string($term)) {
                    $term = strtolower(trim($term));
                    if ($term !== '' && mb_strlen($term) <= 64) {
                        $map[$name] = $term;
                    }
                }
            }
            return $map;

        } catch (\Throwable $e) {
            error_log('IngredientCanonicalizer AI translation failed: ' . $e->getMessage());
            return [];
        }
    }
}
