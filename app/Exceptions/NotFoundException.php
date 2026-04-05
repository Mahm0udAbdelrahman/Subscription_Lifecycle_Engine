<?php

namespace App\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage() ?: 'Not found',
                'type' => 'error',
                'code' => $this->getCode() ?: 400,
            ], $this->getCode() ?: 400);
        }


    }
}
