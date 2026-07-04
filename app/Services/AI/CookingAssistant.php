<?php

namespace Services\AI;

use Models\Items;
use Models\Recipes;
use Models\Ingredients;
use Services\AI\LangCacheClient;
use Services\AI\Providers\ChatProviderInterface;
use Services\AI\Providers\ProviderFactory;

/**
 * AI Cooking Assistant - Context-aware helper for pantry and recipe questions
 * Limits responses to cooking, recipes, measurements, and pantry management only
 */
class CookingAssistant
{
    private ChatProviderInterface $provider;
    private ?int $userId;
    private ?array $pageContext;
    private array $conversationHistory = [];
    private ?LangCacheClient $langCache;

    public function __construct(?int $userId = null, ?array $pageContext = null)
    {
        // Resolves Gemini or Claude based on AI_PROVIDER / available keys in .env
        $this->provider = ProviderFactory::make();
        $this->userId = $userId;
        $this->pageContext = $pageContext;

        // Initialize LangCache if enabled
        try {
            $this->langCache = new LangCacheClient();
        } catch (\Exception $e) {
            error_log('LangCache initialization failed: ' . $e->getMessage());
            $this->langCache = null;
        }
    }
    
    /**
     * Send a message to the AI assistant with full context
     */
    public function chat(string $userMessage): array
    {
        // Get user context (pantry items and saved recipes)
        $context = $this->buildUserContext();

        // Build system prompt that restricts AI to app-related topics
        $systemPrompt = $this->buildSystemPrompt($context);

        // Add user message to history
        $this->conversationHistory[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        try {
            // Check LangCache first for semantic match
            $cached = null;
            $attributes = ['userId' => (string)$this->userId];
            if ($this->pageContext && isset($this->pageContext['type'])) {
                $attributes['pageType'] = $this->pageContext['type'];
                if (isset($this->pageContext['id'])) {
                    $attributes['pageId'] = (string)$this->pageContext['id'];
                }
            }

            if ($this->langCache && $this->langCache->isEnabled()) {
                $cached = $this->langCache->searchCache($userMessage, $attributes);
            }

            if ($cached && isset($cached['response'])) {
                // Use cached response (semantic match found!)
                $response = [
                    'content' => $cached['response'],
                    'usage' => [],
                    'cached' => true,
                    'similarity' => $cached['similarity'] ?? null
                ];
            } else {
                // Call the configured AI provider (Gemini or Claude)
                $response = $this->provider->chat($systemPrompt, $this->conversationHistory);
                $response['cached'] = false;

                // Store response in LangCache for future semantic matches
                if ($this->langCache && $this->langCache->isEnabled()) {
                    $this->langCache->storeResponse($userMessage, $response['content'], $attributes);
                }
            }

            // Add assistant response to history
            $this->conversationHistory[] = [
                'role' => 'assistant',
                'content' => $response['content']
            ];

            return [
                'success' => true,
                'message' => $response['content'],
                'usage' => $response['usage'] ?? [],
                'cached' => $response['cached'] ?? false,
                'similarity' => $response['similarity'] ?? null
            ];

        } catch (\Exception $e) {
            error_log('CookingAssistant error: ' . $e->getMessage());

            // Transient capacity errors (Gemini 503 "high demand", 429, 529) get a friendlier message
            $msg = $e->getMessage();
            $isBusy = str_contains($msg, '503')
                || str_contains($msg, '429')
                || stripos($msg, 'high demand') !== false
                || stripos($msg, 'overloaded') !== false;

            return [
                'success' => false,
                'error' => $isBusy
                    ? 'The AI service is busy right now — please try again in a few seconds.'
                    : 'Sorry, I encountered an error. Please try again.',
                'debug' => $msg
            ];
        }
    }
    
    /**
     * Build system prompt that restricts AI to cooking/pantry topics only
     */
    private function buildSystemPrompt(array $context): string
    {
        $prompt = <<<PROMPT
You are the PantryPal AI Assistant, a helpful cooking and pantry management expert.

**IMPORTANT RESTRICTIONS:**
- You ONLY help with: cooking, recipes, ingredients, measurements, conversions, food storage, nutrition, meal planning, and pantry management
- You MUST refuse politely if asked about ANY other topics (politics, general knowledge, math, coding, etc.)
- Keep responses concise and practical
- When suggesting recipes, prefer ingredients the user already has

**USER'S PANTRY:**
PROMPT;

        if (!empty($context['pantry_items'])) {
            $prompt .= "\nThe user currently has these items:\n";
            foreach ($context['pantry_items'] as $item) {
                $qty = $item['quantity'] ?? '';
                $unit = $item['unit'] ?? '';
                $exp = $item['expiration_date'] ? " (expires: {$item['expiration_date']})" : '';
                $prompt .= "- {$item['name']} {$qty} {$unit}{$exp}\n";
            }
        } else {
            $prompt .= "\nThe user's pantry is currently empty.\n";
        }
        
        if (!empty($context['saved_recipes'])) {
            $prompt .= "\n**SAVED RECIPES:**\n";
            foreach (array_slice($context['saved_recipes'], 0, 10) as $recipe) {
                $prompt .= "- {$recipe['title']}\n";
            }
        }

        // Add page context if viewing a specific recipe or item
        if ($this->pageContext && !empty($this->pageContext['type'])) {
            $pageType = $this->pageContext['type'];
            $pageData = $this->pageContext['data'] ?? [];

            if ($pageType === 'recipe' && !empty($pageData['title'])) {
                $prompt .= "\n**CURRENT RECIPE (User is viewing):**\n";
                $prompt .= "Recipe: {$pageData['title']}\n";

                if (!empty($pageData['servings'])) {
                    $prompt .= "Servings: {$pageData['servings']}\n";
                }

                if (!empty($pageData['ingredients'])) {
                    $prompt .= "Ingredients:\n";
                    foreach ($pageData['ingredients'] as $ing) {
                        $name = is_array($ing) ? ($ing['name'] ?? 'unknown') : $ing;
                        $prompt .= "  - $name\n";
                    }
                }

                $prompt .= "\n**IMPORTANT**: When the user asks questions without specifying, assume they are asking about '{$pageData['title']}'.\n";
            }

            if ($pageType === 'item' && !empty($pageData['name'])) {
                $prompt .= "\n**CURRENT ITEM (User is viewing):**\n";
                $prompt .= "Item: {$pageData['name']}";

                if (!empty($pageData['quantity']) && !empty($pageData['unit'])) {
                    $prompt .= " ({$pageData['quantity']} {$pageData['unit']})";
                }

                if (!empty($pageData['expiration_date'])) {
                    $prompt .= "\nExpires: {$pageData['expiration_date']}";
                }

                $prompt .= "\n\n**IMPORTANT**: When the user asks questions without specifying, assume they are asking about '{$pageData['name']}'.\n";
            }
        }

        $prompt .= <<<PROMPT

**YOUR CAPABILITIES:**
- Measurement conversions (cups to grams, etc.)
- Ingredient substitutions
- Cooking techniques and tips
- Recipe suggestions based on pantry
- Food storage advice
- Nutritional information
- Meal planning help

Answer naturally and helpfully. If asked something outside cooking/food/pantry topics, politely decline and redirect to what you can help with.
PROMPT;

        return $prompt;
    }
    
    /**
     * Gather user's pantry items and saved recipes for context
     */
    private function buildUserContext(): array
    {
        $context = [
            'pantry_items' => [],
            'saved_recipes' => []
        ];
        
        if ($this->userId === null) {
            return $context;
        }
        
        try {
            // Get user's pantry items (use findAll with high limit to get all items)
            $itemsModel = new Items();
            $result = $itemsModel->findAll($this->userId, 1, 500);
            $items = $result['items'] ?? [];

            foreach ($items as $item) {
                // Determine display name from joined data or entered_name
                $displayName = $item['ingredient_name']
                    ?? $item['product_title']
                    ?? $item['entered_name']
                    ?? 'Unknown';

                $context['pantry_items'][] = [
                    'name' => $displayName,
                    'quantity' => $item['quantity'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'expiration_date' => $item['expiration_date'] ?? null
                ];
            }
            
            // Get user's saved recipes
            $recipesModel = new Recipes();
            $recipes = $recipesModel->getSavedForUser($this->userId, 15);
            
            foreach ($recipes as $recipe) {
                $context['saved_recipes'][] = [
                    'title' => $recipe['title'] ?? 'Untitled Recipe',
                    'id' => $recipe['id'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            error_log('Error building user context: ' . $e->getMessage());
        }
        
        return $context;
    }
    
    /**
     * Clear conversation history (start fresh)
     */
    public function clearHistory(): void
    {
        $this->conversationHistory = [];
    }
    
    /**
     * Get current conversation history
     */
    public function getHistory(): array
    {
        return $this->conversationHistory;
    }
    
    /**
     * Set conversation history (for resuming conversations)
     */
    public function setHistory(array $history): void
    {
        $this->conversationHistory = $history;
    }
}
