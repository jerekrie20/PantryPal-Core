# AI Cooking Assistant

The AI Cooking Assistant is a context-aware chatbot that helps users with cooking, recipes, measurements, and pantry management.

## Features

- **Context-Aware**: Automatically knows what's in the user's pantry and their saved recipes
- **Cooking-Focused**: Restricted to only answer questions about cooking, recipes, ingredients, and food
- **Measurement Conversions**: Convert between units (cups to grams, tablespoons to ml, etc.)
- **Ingredient Substitutions**: Suggest alternatives when you're missing ingredients
- **Recipe Suggestions**: Recommend recipes based on what's in your pantry
- **Cooking Tips**: Answer questions about techniques, storage, and preparation
- **Rate Limited**: Free users get 10 queries/day, Premium users get unlimited

## Setup

The assistant supports two providers, selected via `.env`:

| Provider | Cost | Get a key |
|----------|------|-----------|
| **Google Gemini** (default) | Free tier — no card required, ~1,500 requests/day | https://aistudio.google.com/apikey |
| **Anthropic Claude** | Paid — Haiku 4.5 at ~$0.005/query | https://console.anthropic.com/ |

### 1. Get an API Key

For development, grab a free Gemini key from Google AI Studio.

### 2. Configure Environment

Add to your `.env` file:

```bash
# 'gemini' or 'anthropic' — omit to auto-detect (Gemini preferred)
AI_PROVIDER=gemini

GEMINI_API_KEY=your_gemini_key_here
# GEMINI_MODEL=gemini-flash-latest        # optional override

ANTHROPIC_API_KEY=your_anthropic_key_here
# ANTHROPIC_MODEL=claude-haiku-4-5        # optional override
```

### 3. Add Premium Column (Optional)

To enable premium features, add the `is_premium` column to your users table:

```sql
ALTER TABLE users ADD COLUMN is_premium TINYINT(1) DEFAULT 0;
```

## Usage

### Include the Chat Widget

Add the chat component to any authenticated page:

```php
<?php require VIEW_PATH . '/Components/ai_chat.php'; ?>
```

The widget appears as a floating button in the bottom-right corner of the page.

### Example Queries

Users can ask questions like:
- "How many cups is 250 grams of flour?"
- "What can I substitute for eggs in baking?"
- "What recipes can I make with chicken and rice?"
- "How long can I store cooked pasta in the fridge?"
- "What's the best way to dice an onion?"
- "My milk expires tomorrow, what can I make with it?"

### Restricted Topics

The AI will politely decline questions about:
- Politics, current events
- General knowledge (history, science, etc.)
- Mathematics, coding
- Any non-food/cooking related topics

## Rate Limiting

The system uses Redis to track usage:
- **Free Users**: 10 queries per day
- **Premium Users**: Unlimited queries
- Rate limits reset at midnight (based on server date)

## Cost Considerations

**Gemini (free tier):** $0 up to ~1,500 requests/day on Flash models. Good for
development and small user bases; upgrade to a paid Gemini tier or switch to
Claude when you outgrow the rate limits.

**Claude Haiku 4.5** (`claude-haiku-4-5`, $1/$5 per MTok):
- Average cost: ~$0.005 per query (pantry context in, 1024 max tokens out)
- For 100 premium users making 10 queries/day: ~$5/day, ~$150/month
- Pricing at $9.99/month premium = profitable

LangCache semantic caching (if enabled) reduces costs further on both
providers by serving repeated/similar questions without an API call.

## Files

### Backend
- `app/Services/AI/CookingAssistant.php` - Core AI service
- `app/Controllers/AIController.php` - API endpoints
- `app/routes/web.php` - Route definitions

### Frontend
- `app/Views/Components/ai_chat.php` - Chat widget UI

### API Endpoints
- `POST /api/ai/chat` - Send message to AI
- `GET /api/ai/usage` - Get usage stats

## Customization

### Change Rate Limits

Edit `AIController.php`:

```php
$limit = 10; // Change to desired limit
```

### Modify System Prompt

Edit `CookingAssistant.php` method `buildSystemPrompt()` to adjust the AI's behavior and restrictions.

### Change AI Model or Provider

Set in `.env` — no code changes needed:

```bash
AI_PROVIDER=anthropic              # switch provider (gemini | anthropic)
GEMINI_MODEL=gemini-flash-latest   # override Gemini model
ANTHROPIC_MODEL=claude-haiku-4-5   # override Claude model
```

Provider implementations live in `app/Services/AI/Providers/`.

## Troubleshooting

### "No AI provider configured" / "GEMINI_API_KEY not configured"
- Ensure `.env` file has `GEMINI_API_KEY` or `ANTHROPIC_API_KEY`
- Restart your web server after adding the key

### Anthropic "credit balance is too low"
- Your Anthropic account needs credits (https://console.anthropic.com/settings/billing)
- Or set `AI_PROVIDER=gemini` to use the free Gemini tier instead

### Rate limit not working
- Check Redis connection is active
- Verify Redis is running: `redis-cli ping`

### AI not seeing user's pantry
- Check that user is logged in (`$_SESSION['user_id']`)
- Verify items exist in database for that user

## Security

- API key is stored in `.env` (never committed to git)
- All requests require authentication
- Rate limiting prevents abuse
- AI responses are content-filtered by system prompt
