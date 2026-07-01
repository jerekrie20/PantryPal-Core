<?php

namespace Services\Pantry;

/**
 * Normalize an ingredient / pantry item name into a concise search term.
 *
 * Handles: stripping quotes, parenthetical clarifiers, packaging descriptors,
 * cultivar names (Honeycrisp -> apple), meat cuts, and length capping.
 * Used by recipe search and any place that needs to turn "Honeycrisp Apples, sliced"
 * into "apple" for lookup.
 */
class PantryTermNormalizer
{
    /** Descriptor / packaging stopwords that get stripped. */
    private const STOPWORDS = [
        'raw', 'california', 'with', 'and', 'or', 'value', 'pack',
        'bottle', 'bottles', 'enhancing', 'minerals', 'purified',
        'drinking', 'boneless', 'skinless', 'shredded', 'sliced',
        'ground', 'fresh', 'large', 'small', 'organic', 'original',
        'classic',
    ];

    /** Multi-token phrases canonicalized to a single base ingredient. */
    private const EXACT_MAP = [
        'milk chocolate' => 'chocolate',
        'milk chocolate chips' => 'chocolate',
        'semi sweet chocolate' => 'chocolate',
        'semisweet chocolate' => 'chocolate',
        'dark chocolate' => 'chocolate',
        'white chocolate' => 'chocolate',
        'chocolate chips' => 'chocolate',
        'honeycrisp apples' => 'apple',
        'honeycrisp apple' => 'apple',
    ];

    /** Apple cultivars — any of these followed by "apple(s)" collapse to "apple". */
    private const APPLE_CULTIVARS = [
        'honeycrisp', 'gala', 'fuji', 'granny smith', 'pink lady',
        'ambrosia', 'mcintosh', 'golden delicious', 'red delicious',
        'braeburn', 'jonagold',
    ];

    /** Meat cuts — kept as "<meat> <cut>" (last two tokens) when 3+ tokens present. */
    private const MEAT_CUTS = [
        'thighs', 'breast', 'breasts', 'legs', 'drumsticks',
        'wings', 'tenderloins', 'steak', 'steaks', 'loin', 'loins', 'ribs',
    ];

    public static function normalize(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';

        // Strip surrounding quotes and parenthetical clarifiers.
        $s = preg_replace("/^['\"]+|['\"]+$/", '', $s);
        $s = preg_replace('/\([^\)]*\)/', '', $s);

        // Take the first meaningful comma-separated segment.
        if (strpos($s, ',') !== false) {
            foreach (array_map('trim', explode(',', $s)) as $p) {
                if ($p !== '') { $s = $p; break; }
            }
        }

        // Normalize dashes/slashes to spaces, lowercase.
        $s = str_replace(['–', '—', '-', '/'], ' ', $s);
        $s = strtolower($s);

        // Drop stopwords and pack/quantity tokens.
        $tokens = preg_split('/\s+/', $s);
        $clean = [];
        foreach ($tokens as $t) {
            $t = trim($t);
            if ($t === '') continue;
            if (preg_match('/^\d+[a-zA-Z-]*$/', $t)) continue;
            if (in_array($t, self::STOPWORDS, true)) continue;
            $clean[] = $t;
        }
        $s = $clean
            ? implode(' ', $clean)
            : trim(preg_replace('/\s+/', ' ', $s));

        // Canonicalize known multi-token phrases to their base ingredient.
        if (isset(self::EXACT_MAP[$s])) {
            $s = self::EXACT_MAP[$s];
        } else {
            if (str_contains($s, 'apple')) {
                foreach (self::APPLE_CULTIVARS as $cv) {
                    if ($s === $cv
                        || str_starts_with($s, $cv . ' ')
                        || str_starts_with($s, $cv . ' apple')
                        || str_starts_with($s, $cv . ' apples')
                    ) {
                        $s = 'apple';
                        break;
                    }
                }
            }
            if ($s === 'apples') $s = 'apple';
        }

        // For meats: if we ended with 3+ tokens and the last is a cut, keep the last two.
        $parts = preg_split('/\s+/', $s);
        if (count($parts) >= 3) {
            $last = end($parts);
            if (in_array($last, self::MEAT_CUTS, true)) {
                $s = $parts[count($parts) - 2] . ' ' . $parts[count($parts) - 1];
            }
        }

        $s = trim(preg_replace('/\s+/', ' ', $s));
        if (strlen($s) > 64) $s = substr($s, 0, 64);

        return $s;
    }
}
