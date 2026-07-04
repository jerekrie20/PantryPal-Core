<?php

namespace Controllers;

use Services\AI\CookingAssistant;

/**
 * AI Controller - Handles AI cooking assistant chat requests
 */
class AIController
{
    /**
     * Handle chat message from user
     * POST /api/ai/chat
     */
    public function chat(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        // Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $userId = (int)$_SESSION['user_id'];
        
        // Get request body
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        $history = $input['history'] ?? [];
        $pageContext = $input['pageContext'] ?? null;

        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Message is required']);
            return;
        }

        // Rate limiting check
        $rateLimitResult = $this->checkRateLimit($userId);
        if (!$rateLimitResult['allowed']) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => $rateLimitResult['message'],
                'limit_info' => $rateLimitResult
            ]);
            return;
        }

        try {
            // Initialize AI assistant with page context
            $assistant = new CookingAssistant($userId, $pageContext);

            // Restore conversation history if provided
            if (!empty($history)) {
                $assistant->setHistory($history);
            }

            // Get AI response
            $response = $assistant->chat($message);
            
            if ($response['success']) {
                // Track usage for rate limiting
                $this->trackUsage($userId);
                
                // Return response with updated history
                echo json_encode([
                    'success' => true,
                    'message' => $response['message'],
                    'history' => $assistant->getHistory(),
                    'usage' => $response['usage'] ?? [],
                    'cached' => $response['cached'] ?? false,
                    'remaining_queries' => $rateLimitResult['remaining'] - 1
                ]);
            } else {
                if (!$this->isDebug()) {
                    unset($response['debug']);
                }
                http_response_code(500);
                echo json_encode($response);
            }

        } catch (\Exception $e) {
            error_log('AI chat error: ' . $e->getMessage());
            error_log('AI chat error trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'An error occurred while processing your request',
                'debug' => $this->isDebug() ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * APP_DEBUG comes through as a string ("false" is truthy!) — parse it properly
     */
    private function isDebug(): bool
    {
        $raw = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false';
        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Check if user is within rate limits
     * Free users: 10 queries per day
     * Premium users: unlimited (or high limit like 500/day)
     */
    private function checkRateLimit(int $userId): array
    {
        global $redis;

        // Check if user is premium
        $isPremium = $this->isPremiumUser($userId);

        if ($isPremium) {
            return [
                'allowed' => true,
                'remaining' => 999,
                'limit' => 'unlimited',
                'isPremium' => true
            ];
        }

        // Free user - check Redis OR session for usage
        $key = "ai_usage:{$userId}:" . date('Y-m-d');
        $limit = 10;
        $current = 0;

        // Try Redis first
        try {
            if ($redis && method_exists($redis, 'isConnected') && $redis->isConnected()) {
                $current = (int)$redis->get($key) ?: 0;

                if ($current >= $limit) {
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'limit' => $limit,
                        'message' => 'Daily AI query limit reached. Upgrade to Premium for unlimited queries!',
                        'isPremium' => false
                    ];
                }

                return [
                    'allowed' => true,
                    'remaining' => $limit - $current,
                    'limit' => $limit,
                    'isPremium' => false
                ];
            }
        } catch (\Exception $e) {
            error_log('Redis rate limit check failed: ' . $e->getMessage());
        }

        // Fallback to session-based rate limiting
        $sessionKey = 'ai_usage_' . date('Y-m-d');

        // Reset counter if it's a new day
        if (!isset($_SESSION[$sessionKey]) || !is_array($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'date' => date('Y-m-d')];
        }

        // Check if date changed (new day)
        if ($_SESSION[$sessionKey]['date'] !== date('Y-m-d')) {
            $_SESSION[$sessionKey] = ['count' => 0, 'date' => date('Y-m-d')];
        }

        $current = (int)$_SESSION[$sessionKey]['count'];

        if ($current >= $limit) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'limit' => $limit,
                'message' => 'Daily AI query limit reached. Upgrade to Premium for unlimited queries!',
                'isPremium' => false
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $limit - $current,
            'limit' => $limit,
            'isPremium' => false
        ];
    }

    /**
     * Track AI usage in Redis or Session
     */
    private function trackUsage(int $userId): void
    {
        global $redis;

        $key = "ai_usage:{$userId}:" . date('Y-m-d');

        // Try Redis first
        try {
            if ($redis && method_exists($redis, 'isConnected') && $redis->isConnected()) {
                $redis->incr($key);
                $redis->expire($key, 86400); // Expire after 24 hours
                return;
            }
        } catch (\Exception $e) {
            error_log('Redis usage tracking failed: ' . $e->getMessage());
        }

        // Fallback to session tracking
        $sessionKey = 'ai_usage_' . date('Y-m-d');
        if (isset($_SESSION[$sessionKey]) && is_array($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey]['count']++;
        } else {
            $_SESSION[$sessionKey] = ['count' => 1, 'date' => date('Y-m-d')];
        }
    }
    
    /**
     * Check if user has premium subscription
     * TODO: Implement proper premium check when subscription system is added
     */
    private function isPremiumUser(int $userId): bool
    {
        global $conn;
        
        try {
            $stmt = $conn->prepare("SELECT is_premium FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (bool)($result['is_premium'] ?? false);
        } catch (\Exception $e) {
            // Column doesn't exist yet - return false
            return false;
        }
    }
    
    /**
     * Get user's AI usage stats
     * GET /api/ai/usage
     */
    public function usage(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $userId = (int)$_SESSION['user_id'];
        $rateLimitInfo = $this->checkRateLimit($userId);
        
        echo json_encode([
            'success' => true,
            'usage' => [
                'remaining' => $rateLimitInfo['remaining'],
                'limit' => $rateLimitInfo['limit'],
                'isPremium' => $rateLimitInfo['isPremium']
            ]
        ]);
    }
}
