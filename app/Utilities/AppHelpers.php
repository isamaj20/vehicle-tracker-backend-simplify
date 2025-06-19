<?php

namespace App\Utilities;

use Illuminate\Http\JsonResponse;

class AppHelpers
{
    /**
     * API response helper function
     * @param  $data
     * @param bool $error
     * @param int $code
     * @param string $message
     * @param array $errors
     * @return JsonResponse
     */
    public static function apiResponse($data, bool $error = false, int $code = 200, string $message = 'Success!', array $errors = []): JsonResponse
    {
        return response()->json([
            'error' => $error,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $code);
    }
}
