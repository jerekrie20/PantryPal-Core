<?php

/**
 * Renders a styled form input field with a label and optional error message.
 *
 * @param string $id The id and name attribute for the input.
 * @param string $label The text for the <label> element.
 * @param string $type The input type (e.g., 'text', 'email', 'date', 'number').
 * @param array $options An associative array of optional attributes.
 * - 'placeholder' (string): The placeholder text.
 * - 'required' (bool): Whether the input is required.
 * - 'value' (string): The pre-filled value.
 * - 'error' (string): An error message to display.
 * - 'step' (string): The step attribute for number inputs.
 */
function form_input(string $id, string $label, string $type = 'text', array $options = []): void
{
    $placeholder = htmlspecialchars($options['placeholder'] ?? '');
    $required = $options['required'] ?? false;
    $value = htmlspecialchars($options['value'] ?? '');
    $error = $options['error'] ?? null;
    $step = isset($options['step']) ? 'step="' . htmlspecialchars($options['step']) . '"' : '';

    $requiredAttr = $required ? 'required aria-required="true"' : '';
    $errorClass = $error ? 'border-danger-border' : '';
    $errorAria = $error ? 'aria-invalid="true" aria-describedby="' . $id . '-error"' : '';

    echo <<<HTML
    <div>
        <label for="$id" class="block text-sm font-medium mb-1" {$requiredAttr}>
            $label 
        </label>
        <input 
            type="$type" 
            id="$id" 
            name="$id" 
            class="w-full {$errorClass}" 
            placeholder="$placeholder" 
            value="$value"
            {$step}
            {$requiredAttr}
            {$errorAria}
        >
    HTML;

    if ($error) {
        echo '<p id="' . $id . '-error" class="text-xs text-danger-text-strong mt-1">' . htmlspecialchars($error) . '</p>';
    }

    echo '</div>';
}


/**
 * Renders a styled form button.
 *
 * @param string $text The text content of the button.
 * @param string $style 'cta', 'secondary', or 'danger'.
 * @param array $options An associative array of optional attributes.
 * - 'type' (string): The button's type attribute (e.g., 'submit', 'button'). Defaults to 'submit'.
 * - 'size' (string): 'sm', 'md', or 'lg'. Defaults to 'md'.
 * - 'fullWidth' (bool): Whether the button should span the full width. Defaults to true.
 */
function form_button(string $text, string $style = 'cta', array $options = []): void
{
    $type = $options['type'] ?? 'submit';
    $size = $options['size'] ?? 'md';
    $fullWidth = $options['fullWidth'] ?? true;

    $styleClass = "btn-{$style}";
    $sizeClass = "btn-{$size}";
    $widthClass = $fullWidth ? 'w-full' : '';

    echo <<<HTML
    <button type="$type" class="btn {$styleClass} {$sizeClass} {$widthClass} flex justify-center">
        $text
    </button>
    HTML;
}
?>
