<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;


class VideoUrlRule implements ValidationRule
{
   
    private const ALLOWED_DOMAINS_WITH_WWW = [
        'youtube.com',
        'vimeo.com',
    ];

   
    private const ALLOWED_DOMAINS_EXACT = [
        'youtu.be',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('رابط الفيديو يجب أن يكون رابطًا صحيحًا من يوتيوب أو فيميو.');
            return;
        }

        $parsed = parse_url($value);

       
        if (! isset($parsed['scheme'], $parsed['host'])) {
            $fail('رابط الفيديو يجب أن يكون رابطًا صحيحًا من يوتيوب أو فيميو.');
            return;
        }

        if (! in_array(strtolower($parsed['scheme']), ['http', 'https'], strict: true)) {
            $fail('رابط الفيديو يجب أن يكون رابطًا صحيحًا من يوتيوب أو فيميو.');
            return;
        }

        $host = strtolower($parsed['host']);

       
        foreach (self::ALLOWED_DOMAINS_WITH_WWW as $domain) {
            if ($host === $domain || $host === 'www.' . $domain) {
                return;
            }
        }

     
        foreach (self::ALLOWED_DOMAINS_EXACT as $domain) {
            if ($host === $domain) {
                return;
            }
        }

        $fail('رابط الفيديو يجب أن يكون رابطًا صحيحًا من يوتيوب أو فيميو.');
    }
}
