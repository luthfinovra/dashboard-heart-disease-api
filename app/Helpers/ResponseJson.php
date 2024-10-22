<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ResponseJson
{
    public static function successResponse(string $message, array|Collection|Model $data = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], ResponseAlias::HTTP_OK);
    }

    public static function pageNotFoundResponse(string $message, array|Collection|Model $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => $message
        ], ResponseAlias::HTTP_NOT_FOUND);
    }

    public static function failedResponse(string $message, array|Collection|Model $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => $message
        ], ResponseAlias::HTTP_BAD_REQUEST);
    }

    public static function validationErrorResponse(string $message, array|Collection|Model $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => $message
        ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function unauthorizeResponse(string $message, array|Collection|Model $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => $message
        ], ResponseAlias::HTTP_UNAUTHORIZED);
    }

    public static function forbidenResponse(string $message = "Forbidden", array|Collection|Model $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => $message
        ], ResponseAlias::HTTP_FORBIDDEN);
    }

    public static function errorResponse(array|Collection|Model $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => 'Server is busy right now!'
        ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
    }
}
