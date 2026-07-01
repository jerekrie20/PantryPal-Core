<?php

namespace Helpers;

use PDO;

/**
 * A general-purpose validation class for handling form and API data.
 *
 * This class takes a data source (like $_POST) and a set of rules,
 * validates the data against the rules, and stores any error messages.
 * It is designed to be easily extensible with new validation rules.
 */
class Validator
{
    /**
     * @var array Holds the validation error messages.
     */
    private array $_errors = [];

    /**
     * @var array The source data to validate (e.g., $_POST).
     */
    private array $_source;

    /**
     * @var PDO|null The database connection object, required for 'unique' rule.
     */
    private ?PDO $_pdo;

    /**
     * Constructor for the Validator.
     *
     * @param array $source The data source to validate.
     * @param PDO|null $pdo A PDO database connection object (optional, but required for the 'unique' rule).
     */
    public function __construct(array $source, ?PDO $pdo = null)
    {
        $this->_source = $source;
        $this->_pdo = $pdo;
    }

    /**
     * Performs the validation against a set of rules.
     *
     * @param array $rules An associative array where keys are field names and values are arrays of validation rules.
     * @return self Returns the current instance for method chaining.
     *
     * Example of $rules array:
     * [
     * 'username' => ['required' => true, 'min' => 3, 'max' => 50, 'unique' => 'users'],
     * 'email'    => ['required' => true, 'email' => true],
     * 'quantity' => ['numeric' => true, 'min' => 1]
     * ]
     */
    public function check(array $rules): self
    {
        foreach ($rules as $field => $fieldRules) {
            $value = trim($this->_source[$field] ?? '');

            foreach ($fieldRules as $rule => $ruleValue) {
                // If the field is not required and has no value, skip other rules.
                if (empty($value) && $rule !== 'required') {
                    continue;
                }

                switch ($rule) {
                    case 'required':
                        if ($ruleValue === true && empty($value)) {
                            $this->addError($field, "The {$field} field is required.");
                        }
                        break;

                    case 'min':
                        if (strlen($value) < $ruleValue) {
                            $this->addError($field, "The {$field} field must be a minimum of {$ruleValue} characters.");
                        }
                        break;

                    case 'max':
                        if (strlen($value) > $ruleValue) {
                            $this->addError($field, "The {$field} field must be a maximum of {$ruleValue} characters.");
                        }
                        break;

                    case 'email':
                        if ($ruleValue === true && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "The {$field} field must be a valid email address.");
                        }
                        break;

                    case 'numeric':
                        if ($ruleValue === true && !is_numeric($value)) {
                            $this->addError($field, "The {$field} field must be a numeric value.");
                        }
                        break;

                    case 'string':
                        if ($ruleValue === true && !is_string($value)) {
                            $this->addError($field, "The {$field} field must be a string.");
                        }
                        break;
                    case 'date':
                        if ($ruleValue === true) {
                            // Enforce strict YYYY-MM-DD (zero-padded) format
                            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                                $this->addError($field, "The {$field} field must be a valid date (YYYY-MM-DD).");
                                break;
                            }

                            $year = (int)substr($value, 0, 4);
                            $month = (int)substr($value, 5, 2);
                            $day = (int)substr($value, 8, 2);

                            if (!checkdate($month, $day, $year)) {
                                $this->addError($field, "The {$field} field must be a valid date.");
                            }
                        }
                        break;
                    case 'matches':
                        if ($value !== ($this->_source[$ruleValue] ?? '')) {
                            $this->addError($field, "The {$field} field must match the {$ruleValue} field.");
                        }
                        break;

                    case 'unique':
                        if (!$this->_pdo) {
                            throw new \RuntimeException("Database connection is required for the 'unique' validation rule.");
                        }
                        $check = $this->_pdo->prepare("SELECT COUNT(*) FROM `{$ruleValue}` WHERE `{$field}` = ?");
                        $check->execute([$value]);
                        if ($check->fetchColumn() > 0) {
                            $this->addError($field, "The {$field} has already been taken.");
                        }
                        break;

                    // Add other rules here as needed (e.g., 'date', 'url', 'alpha_numeric')
                }
            }
        }
        return $this;
    }

    /**
     * Adds an error message to the errors array for a specific field.
     * Only adds the first error encountered for a field.
     *
     * @param string $field The field name.
     * @param string $message The error message.
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->_errors[$field])) {
            $this->_errors[$field] = $message;
        }
    }

    /**
     * Checks if the validation passed (i.e., no errors).
     *
     * @return bool True if validation passed, false otherwise.
     */
    public function passed(): bool
    {
        return empty($this->_errors);
    }

    /**
     * Returns the array of validation errors.
     *
     * @return array The associative array of errors.
     */
    public function errors(): array
    {
        return $this->_errors;
    }

    /**
     * Returns the first error message for a specific field.
     *
     * @param string $field The field name.
     * @return string|null The error message, or null if no error for that field.
     */
    public function getError(string $field): ?string
    {
        return $this->_errors[$field] ?? null;
    }
}