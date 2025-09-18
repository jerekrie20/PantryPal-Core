<?php

/**
 * Renders a styled form input field with a label.
 *
 * @param string $id The id and name attribute for the input.
 * @param string $type The input type (e.g., 'text', 'email', 'password').
 * @param string $label The text for the <label> element.
 * @param string $placeholder The placeholder text for the input.
 * @param bool $required Whether the input is required.
 * @param string $value The pre-filled value of the input.
 */
function form_input(string $id, string $type, string $label, string $placeholder = '', bool $required = true, string $value = ''): void {
    $requiredAttr = $required ? 'required' : '';
    echo <<<HTML
    <div>
        <label for="$id" class="block mb-[var(--spacing-2)] text-sm font-medium text-[var(--color-text-primary)]">
            $label
        </label>
        <input 
            type="$type" 
            id="$id" 
            name="$id" 
            class="w-full" 
            placeholder="$placeholder" 
            value="$value"
            $requiredAttr
        >
    </div>
    HTML;
}

/**
 * Renders a styled form button.
 *
 * @param string $text The text content of the button.
 * @param string $type The button's type attribute (e.g., 'submit', 'button').
 * @param string $size 'md' or 'lg' for padding size.
 * @param bool $fullWidth Whether the button should span the full width.
 */
function form_button(string $text, string $type = 'submit', string $size = 'md', bool $fullWidth = true): void {
    $sizeClasses = $size === 'lg' ? 'px-[var(--spacing-6)] py-[var(--spacing-3)]' : 'px-[var(--spacing-4)] py-[var(--spacing-2)]';
    $widthClass = $fullWidth ? 'w-full' : '';

    // For hover, you need to add the hover:btn-primary class in your HTML.
    // We will apply it here directly since it's a component.
    echo <<<HTML
    <button 
        type="$type" 
        class="btn btn-primary hover:btn-primary $sizeClasses $widthClass flex justify-center"
    >
        $text
    </button>
    HTML;
}

?>
