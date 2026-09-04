<?php

function env(string $name, mixed $default = null): mixed
{
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }

    $normalized = strtolower($value);
    if (in_array($normalized, ['true', '(true)', '1'], true)) {
        return true;
    }
    if (in_array($normalized, ['false', '(false)', '0'], true)) {
        return false;
    }
    if (in_array($normalized, ['empty', '(empty)'], true)) {
        return '';
    }
    if (in_array($normalized, ['null', '(null)'], true)) {
        return null;
    }

    return $value;
}
