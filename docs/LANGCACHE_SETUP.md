# LangCache Setup Guide

## What is LangCache?

LangCache is a **semantic caching service** that saves money by:
- Recognizing similar questions (not just identical ones)
- Returning cached responses instantly (no API call needed)
- Reducing Claude API costs significantly

**Example:**
- User asks: "How do I convert cups to ml?"
- LangCache stores the response
- Later, user asks: "What is 1 cup in milliliters?"
- LangCache recognizes similarity and returns cached response (no Claude API call!)

## Setup Instructions

### 1. Get Your LangCache Credentials

You mentioned you have a LangCache API key. You'll need:

1. **API Key** - You already have this
2. **Host URL** - Provided by LangCache (e.g., `https://your-instance.langcache.ai`)
3. **Cache ID** - Create a cache in LangCache dashboard

### 2. Add to `.env` File

```bash
# LangCache Configuration
LANGCACHE_API_KEY=your_api_key_here
LANGCACHE_HOST=https://your-instance.langcache.ai
LANGCACHE_CACHE_ID=your_cache_id
LANGCACHE_ENABLED=true
```

### 3. Test the Setup

Visit: `http://localhost/pantrypal_core/__internal/ai-test`

This will verify:
- ✓ LangCache client initialized
- ✓ API credentials valid
- ✓ Cache accessible

### 4. How It Works

**First Question:**
```
User: "How many tablespoons in a cup?"
→ LangCache: No match found
→ Claude API: Generates response ($$$)
→ LangCache: Stores response with semantic embedding
→ User: Gets response (1-2 seconds)
```

**Similar Question Later:**
```
User: "Convert cups to tablespoons"
→ LangCache: Semantic match found! (similarity: 0.95)
→ User: Gets cached response instantly (FREE, <100ms)
→ No Claude API call needed!
```

## Features in Your Implementation

### Attribute-Based Scoping

Responses are cached with context:
```php
$attributes = [
    'userId' => '123',
    'pageType' => 'recipe',
    'pageId' => '456'
];
```

This means:
- Responses about "Spaghetti Bolognese" won't mix with "Chocolate Cake"
- Each user's questions are tracked separately
- Context-aware caching (recipe page vs general questions)

### Response Format

When a cached response is used, you'll get:
```json
{
    "success": true,
    "message": "The AI response text...",
    "cached": true,
    "similarity": 0.96,
    "usage": {}
}
```

- `cached: true` = No API cost!
- `similarity: 0.96` = 96% semantic match (very high)
- `usage: {}` = Empty because no Claude API call

## Cost Savings Example

**Without LangCache:**
- 1000 user questions/day
- $0.003 per question (Claude API)
- **Cost: $3/day = $90/month**

**With LangCache (50% cache hit rate):**
- 500 questions hit cache (free)
- 500 questions call Claude API ($1.50)
- **Cost: $1.50/day = $45/month**
- **Savings: $45/month (50%)**

Higher cache hit rates = more savings!

## Monitoring Cache Performance

The response includes `similarity` score:
- **0.90-1.00** = Very similar (excellent match)
- **0.80-0.89** = Similar (good match)
- **< 0.80** = Different (may not use cached response)

You can track:
- Cache hit rate (how often `cached: true`)
- Average similarity scores
- Cost savings

## Cache Management

### Clear Cache for Specific Recipe
```php
$langCache->deleteByAttributes(['pageType' => 'recipe', 'pageId' => '123']);
```

### Clear All Cache for User
```php
$langCache->deleteByAttributes(['userId' => '456']);
```

### Clear Entire Cache
⚠️ **Warning:** This deletes everything!
```php
$langCache->deleteByAttributes([]); // Deletes ALL cached responses
```

## Benefits for PantryPal

1. **Lower Costs** - 30-70% API cost reduction typical
2. **Faster Responses** - Cached responses return in <100ms
3. **Better UX** - Instant answers to common questions
4. **Scalability** - Handle more users without linear cost increase
5. **Smart Matching** - Works even if users ask questions differently

## Troubleshooting

### Cache not working?
1. Check `.env` file has correct credentials
2. Visit `/__internal/ai-test` to verify setup
3. Check `storage/logs/error.log` for errors
4. Ensure `LANGCACHE_ENABLED=true`

### Getting too many cache hits?
- LangCache may be too aggressive
- Users getting wrong context
- Solution: Add more specific attributes

### Not getting enough cache hits?
- Questions too unique
- Similarity threshold too high
- Solution: Monitor similarity scores in responses

## Redis vs LangCache

You asked: "Is it better to use Redis for usage tracking?"

**Use Both!**

- **Redis** = Rate limiting (counts queries per day)
- **LangCache** = Semantic caching (saves API costs)

They serve different purposes:

| Feature | Redis | LangCache |
|---------|-------|-----------|
| Rate limiting | ✅ Perfect | ❌ Not designed for this |
| Query counting | ✅ Simple counters | ❌ Overkill |
| Semantic caching | ❌ Only exact matches | ✅ Similar questions match |
| Cost savings | ❌ No API integration | ✅ Major savings |
| Response time | ⚡ ~1ms | ⚡ ~50ms |

**Recommendation:**
- ✅ Redis for rate limiting (already implemented with session fallback)
- ✅ LangCache for AI response caching (now implemented!)
- ✅ Both work together perfectly

## Next Steps

1. Add your LangCache credentials to `.env`
2. Set `LANGCACHE_ENABLED=true`
3. Test with a few questions
4. Monitor cache hit rates
5. Watch your API costs drop! 📉

---

**Questions?** Check the LangCache documentation or test at `/__internal/ai-test`
