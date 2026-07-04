<!-- AI Cooking Assistant Chat Component - PantryPal Themed -->
<?php
// Detect current page context (recipe or item)
$pageContext = [
    'type' => null,
    'id' => null,
    'data' => null
];

// Check if we're on a recipe page
if (isset($recipe) && is_array($recipe)) {
    $pageContext['type'] = 'recipe';
    $pageContext['id'] = $recipe['id'] ?? $recipe['db_id'] ?? null;
    $pageContext['data'] = [
        'title' => $recipe['title'] ?? 'Unknown Recipe',
        'ingredients' => $recipe['usedIngredients'] ?? $recipe['missedIngredients'] ?? [],
        'instructions' => $recipe['instructions_list'] ?? null,
        'servings' => $recipe['servings'] ?? null
    ];
}

// Check if we're on an item page
if (isset($item) && is_array($item)) {
    $pageContext['type'] = 'item';
    $pageContext['id'] = $item['id'] ?? null;
    $pageContext['data'] = [
        // 'name' is the assembled shape (PantryItemAssembler::detail); the rest cover raw-row/edit-form shapes
        'name' => $item['name'] ?? $item['ingredient_name'] ?? $item['product_title'] ?? $item['entered_name'] ?? 'Unknown Item',
        'quantity' => $item['quantity'] ?? null,
        'unit' => $item['unit'] ?? null,
        'expiration_date' => $item['expiration_date'] ?? null
    ];
}
?>

<div id="ai-chat-widget" class="fixed bottom-4 right-4 z-50 font-body"
     data-page-context='<?php echo htmlspecialchars(json_encode($pageContext), ENT_QUOTES, 'UTF-8'); ?>'>
    <!-- Chat Button (collapsed state) -->
    <button
        id="ai-chat-toggle"
        class="rounded-full pl-4 pr-5 py-3 transition-all duration-200 flex items-center gap-2 font-semibold text-sm"
        style="background-color: var(--color-cta); color: var(--color-text-on-cta); box-shadow: var(--shadow-lg);"
        onmouseover="this.style.backgroundColor='var(--color-cta-hover)'"
        onmouseout="this.style.backgroundColor='var(--color-cta)'"
        onclick="toggleAIChat()"
        aria-label="Open AI Assistant"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
        </svg>
        <span>Ask AI Chef</span>
    </button>

    <!-- Chat Window (expanded state) -->
    <div
        id="ai-chat-window"
        class="hidden absolute bottom-20 right-0 w-96 max-w-[calc(100vw-2rem)] h-[500px] flex flex-col overflow-hidden"
        style="background-color: var(--color-bg-component); border: 1px solid var(--color-border-default); border-radius: var(--radius-xl); box-shadow: var(--shadow-xl);"
    >
        <!-- Header -->
        <div class="px-4 py-3 flex justify-between items-center" style="background-color: var(--color-cta); color: var(--color-text-on-cta);">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <h3 class="font-semibold text-sm">AI Chef</h3>
            </div>
            <button
                onclick="toggleAIChat()"
                class="p-1 transition-colors duration-200"
                style="border-radius: var(--radius-md);"
                onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'"
                onmouseout="this.style.backgroundColor='transparent'"
                aria-label="Close chat"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Usage Info (for free users) -->
        <div id="ai-usage-info" class="px-4 py-2 text-sm" style="background-color: var(--color-info-bg); color: var(--color-info-text); border-bottom: 1px solid var(--color-info-border);">
            <div class="flex items-center justify-between">
                <span id="ai-usage-text">Loading...</span>
            </div>
        </div>

        <!-- Page Context Toggle (shown when on recipe/item page) -->
        <div id="ai-context-toggle" class="hidden px-4 py-2 text-sm" style="background-color: var(--color-success-bg); color: var(--color-success-text); border-bottom: 1px solid var(--color-success-border);">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    id="ai-use-page-context"
                    class="rounded"
                    style="color: var(--color-cta); border-color: var(--color-border-input);"
                    onfocus="this.style.boxShadow='0 0 0 3px var(--color-cta-focus-ring)'"
                    onblur="this.style.boxShadow='none'"
                    checked
                >
                <span id="ai-context-label">Ask about this recipe</span>
            </label>
        </div>

        <!-- Chat Messages -->
        <div id="ai-chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3">
            <!-- Welcome message -->
            <div class="flex gap-2">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: var(--color-bg-subtle);">
                        <svg class="w-5 h-5" style="color: var(--color-cta);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="px-4 py-2 text-sm" style="background-color: var(--color-bg-subtle); color: var(--color-text-base); border: 1px solid var(--color-border-default); border-radius: var(--radius-lg);">
                        <p>Hi! I'm your AI cooking assistant. I can help with:</p>
                        <ul class="mt-2 space-y-1 list-disc list-inside">
                            <li>Measurement conversions</li>
                            <li>Ingredient substitutions</li>
                            <li>Recipe suggestions from your pantry</li>
                            <li>Cooking techniques & tips</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3" style="border-top: 1px solid var(--color-border-default);">
            <form id="ai-chat-form" class="flex gap-2" onsubmit="sendAIMessage(event)">
                <input
                    type="text"
                    id="ai-chat-input"
                    placeholder="Ask me anything about cooking..."
                    class="flex-1 px-3 py-2 text-sm"
                    style="border: 1px solid var(--color-border-input); border-radius: var(--radius-lg); background-color: var(--color-bg-component); color: var(--color-text-base);"
                    onfocus="this.style.borderColor='var(--color-border-input-focus)'; this.style.boxShadow='0 0 0 2px var(--color-cta-focus-ring)';"
                    onblur="this.style.borderColor='var(--color-border-input)'; this.style.boxShadow='none';"
                    required
                />
                <button
                    type="submit"
                    id="ai-send-btn"
                    class="px-4 py-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background-color: var(--color-cta); color: var(--color-text-on-cta); border-radius: var(--radius-lg);"
                    onmouseover="if(!this.disabled) this.style.backgroundColor='var(--color-cta-hover)'"
                    onmouseout="if(!this.disabled) this.style.backgroundColor='var(--color-cta)'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let aiChatHistory = [];
let isAIChatOpen = false;
let pageContext = null;

// Load page context from data attribute
(function() {
    const widget = document.getElementById('ai-chat-widget');
    if (widget) {
        try {
            const contextData = widget.getAttribute('data-page-context');
            if (contextData) {
                pageContext = JSON.parse(contextData);

                // Show context toggle if we have page context
                if (pageContext && pageContext.type) {
                    const toggleDiv = document.getElementById('ai-context-toggle');
                    const label = document.getElementById('ai-context-label');

                    if (toggleDiv && label) {
                        toggleDiv.classList.remove('hidden');

                        if (pageContext.type === 'recipe') {
                            label.textContent = `Ask about: ${pageContext.data.title}`;
                        } else if (pageContext.type === 'item') {
                            label.textContent = `Ask about: ${pageContext.data.name}`;
                        }
                    }
                }
            }
        } catch (e) {
            console.error('Failed to parse page context:', e);
        }
    }
})();

function toggleAIChat() {
    const chatWindow = document.getElementById('ai-chat-window');
    const chatButton = document.getElementById('ai-chat-toggle');

    isAIChatOpen = !isAIChatOpen;

    if (isAIChatOpen) {
        chatWindow.classList.remove('hidden');
        chatButton.classList.add('hidden');
        loadAIUsage();
    } else {
        chatWindow.classList.add('hidden');
        chatButton.classList.remove('hidden');
    }
}

async function loadAIUsage() {
    try {
        const response = await fetch('/api/ai/usage');
        const data = await response.json();

        if (data.success) {
            const { remaining, limit, isPremium } = data.usage;
            const usageInfo = document.getElementById('ai-usage-info');
            const usageText = document.getElementById('ai-usage-text');

            if (isPremium) {
                usageInfo.style.backgroundColor = 'var(--color-brand-100)';
                usageInfo.style.color = 'var(--color-brand-800)';
                usageInfo.style.borderColor = 'var(--color-brand-200)';
                usageText.textContent = '✨ Pro — unlimited AI queries';
            } else {
                usageText.textContent = `${remaining} of ${limit} free queries left today`;
            }
        }
    } catch (error) {
        console.error('Failed to load AI usage:', error);
    }
}

async function sendAIMessage(event) {
    event.preventDefault();

    const input = document.getElementById('ai-chat-input');
    const message = input.value.trim();

    if (!message) return;

    // Clear input
    input.value = '';

    // Add user message to chat
    appendMessage('user', message);

    // Disable send button
    const sendBtn = document.getElementById('ai-send-btn');
    sendBtn.disabled = true;

    // Show loading indicator
    const loadingId = appendMessage('assistant', 'Thinking...');

    try {
        // Get CSRF token
        const csrfToken = '<?php echo $_SESSION["csrf_token"] ?? ""; ?>';

        // Check if page context should be included
        const usePageContext = document.getElementById('ai-use-page-context')?.checked;
        const contextToSend = (usePageContext && pageContext && pageContext.type) ? pageContext : null;

        const response = await fetch('/api/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                message: message,
                history: aiChatHistory,
                pageContext: contextToSend
            })
        });

        const data = await response.json();

        // Remove loading message
        document.getElementById(loadingId)?.remove();

        if (data.success) {
            // Update conversation history
            aiChatHistory = data.history;

            // Add assistant response
            appendMessage('assistant', data.message);

            // Update usage count
            if (data.remaining_queries !== undefined) {
                const usageText = document.getElementById('ai-usage-text');
                if (usageText && !usageText.textContent.includes('Premium')) {
                    const match = usageText.textContent.match(/of (\d+)/);
                    if (match) {
                        usageText.textContent = `${data.remaining_queries} of ${match[1]} free queries remaining today`;
                    }
                }
            }
        } else {
            appendMessage('error', data.error || 'Sorry, something went wrong. Please try again.');

            // Show debug info if available
            if (data.debug) {
                console.error('Debug info:', data.debug);
            }

            if (data.limit_info) {
                appendMessage('error', 'Tip: Upgrade to Premium for unlimited AI queries!');
            }
        }
    } catch (error) {
        document.getElementById(loadingId)?.remove();
        let errorMsg = 'Failed to send message. ';
        if (error.message) {
            errorMsg += error.message;
        } else {
            errorMsg += 'Please check your connection and try again.';
        }
        appendMessage('error', errorMsg);
        console.error('AI chat error:', error);
    } finally {
        sendBtn.disabled = false;
    }
}

function appendMessage(role, content) {
    const messagesContainer = document.getElementById('ai-chat-messages');
    const messageId = 'msg-' + Date.now();

    const messageDiv = document.createElement('div');
    messageDiv.id = messageId;
    messageDiv.className = 'flex gap-2';

    if (role === 'user') {
        messageDiv.innerHTML = `
            <div class="flex-1"></div>
            <div class="flex-shrink-0 max-w-[80%]">
                <div class="px-4 py-2 text-sm" style="background-color: var(--color-cta); color: var(--color-text-on-cta); border-radius: var(--radius-lg);">
                    ${escapeHtml(content)}
                </div>
            </div>
        `;
    } else if (role === 'error') {
        messageDiv.innerHTML = `
            <div class="flex-1">
                <div class="px-4 py-2 text-sm" style="background-color: var(--color-danger-bg); border: 1px solid var(--color-danger-border); color: var(--color-danger-text); border-radius: var(--radius-lg);">
                    ${escapeHtml(content)}
                </div>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: var(--color-bg-subtle);">
                    <svg class="w-5 h-5" style="color: var(--color-cta);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <div class="px-4 py-2 text-sm" style="background-color: var(--color-bg-subtle); color: var(--color-text-base); border: 1px solid var(--color-border-default); border-radius: var(--radius-lg);">
                    ${escapeHtml(content).replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
    }

    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    return messageId;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
