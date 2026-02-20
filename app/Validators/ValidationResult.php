<?php

namespace App\Validators;

class ValidationResult
{
    private bool $isValid;
    private array $errors;
    
    /**
     * Create a new validation result
     * 
     * @param bool $isValid Whether validation passed
     * @param array $errors Array of validation errors (field => message)
     */
    public function __construct(bool $isValid, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }
    
    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }
    
    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->isValid;
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get a specific error message
     * 
     * @param string $field Field name
     * @return string|null
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Check if a specific field has an error
     * 
     * @param string $field Field name
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }
}
