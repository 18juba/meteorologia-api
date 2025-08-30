<?php

namespace App\Validations;

abstract class Validation
{
    protected static function response(int $code, string $message)
    {
        return response()->json([
            'status' => [
                'code'      => $code,
                'message'   => $message
            ]
        ], $code);
    }
}
