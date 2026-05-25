<?php

namespace App\Services;

class KeywordService
{
    public function all(): array
    {
        $path = storage_path('keywords.json');

        return json_decode(
            file_get_contents($path),
            true
        );
    }
    public function __construct()
    {
        //
    }
}
