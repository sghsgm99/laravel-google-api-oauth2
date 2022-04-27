<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponseService
{
    public static function success(
        string $message = 'Success',
        $data = null,
        $status = 200
    ): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data ?? []
        ], $status);
    }

    public static function successCreate(string $message = 'Success', $data = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data ?? []
        ], 201);
    }

    public static function clientError(
        string $message = 'Error',
        array $data = [],
        $status = 400
    ): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function clientNotAllowed(string $message = 'Error'): JsonResponse
    {
        return response()->json([
            'message' => $message
        ], 403);
    }

    public static function serverError(string $message = 'Error', $status = 500): JsonResponse
    {
        return response()->json([
            'message' => $message
        ], $status);
    }
}
