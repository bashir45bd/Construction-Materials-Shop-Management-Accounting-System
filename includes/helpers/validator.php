<?php
/**
 * Server-Side Validation Helper
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Helpers;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, string $label): self
    {
        $val = $this->data[$field] ?? null;
        if ($val === null || trim((string)$val) === '') {
            $this->errors[$field][] = "{$label} is required.";
        }
        return $this;
    }

    public function numeric(string $field, string $label, float $min = null, float $max = null): self
    {
        if (isset($this->data[$field]) && trim((string)$this->data[$field]) !== '') {
            $val = $this->data[$field];
            if (!is_numeric($val)) {
                $this->errors[$field][] = "{$label} must be a valid number.";
            } else {
                $num = (float)$val;
                if ($min !== null && $num < $min) {
                    $this->errors[$field][] = "{$label} cannot be less than {$min}.";
                }
                if ($max !== null && $num > $max) {
                    $this->errors[$field][] = "{$label} cannot exceed {$max}.";
                }
            }
        }
        return $this;
    }

    public function minLength(string $field, string $label, int $min): self
    {
        if (!empty($this->data[$field]) && mb_strlen((string)$this->data[$field]) < $min) {
            $this->errors[$field][] = "{$label} must be at least {$min} characters.";
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "{$label} must be a valid email address.";
        }
        return $this;
    }

    public function in(string $field, string $label, array $allowed): self
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field][] = "{$label} contains an invalid selection.";
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }
}
