<?php
/**
 * Renders a user avatar SVG placeholder.
 *
 * This component generates an accessible SVG icon. It can be styled
 * with standard Tailwind CSS text and size utility classes.
 *
 * @param array $attributes An associative array of attributes.
 * - 'class' (string): CSS classes to apply to the SVG element (e.g., 'h-8 w-8 text-text-muted').
 * - 'username' (string): The username, used for the SVG's title for accessibility.
 */
function user_avatar(array $attributes = []): void
{
    $classes = htmlspecialchars($attributes['class'] ?? 'h-8 w-8 text-text-muted');
    $username = htmlspecialchars($attributes['username'] ?? 'User');

    echo <<<SVG
<svg class="{$classes}" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" role="img" aria-labelledby="avatar-title">
    <title id="avatar-title">Avatar for {$username}</title>
    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
</svg>
SVG;
}
?>

