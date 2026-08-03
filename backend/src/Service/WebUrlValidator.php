<?php

declare(strict_types=1);

namespace App\Service;

final readonly class WebUrlValidator
{
    public function isValid(string $url): bool
    {
        $parts = parse_url($this->normalize($url));

        if ($parts === false) {
            return false;
        }

        $scheme = isset($parts['scheme']) ? mb_strtolower($parts['scheme']) : null;

        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return isset($parts['host']) && trim($parts['host']) !== '';
    }

    public function normalize(string $url): string
    {
        return str_replace(' ', '%20', trim($url));
    }
}
