<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponseTrait
 * Provides standardized API response methods with proper status codes
 */
trait ApiResponseTrait
{
    /**
     * Return a success response
     */
    public function successResponse($data = [], $message = 'Success', $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return a created response (201)
     */
    public function createdResponse($data = [], $message = 'Resource created'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    /**
     * Return an error response
     */
    public function errorResponse($message = 'Error', $error = null, $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
        ], $code);
    }

    /**
     * Return a validation error response (422)
     */
    public function validationErrorResponse($errors, $message = 'Validation error'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return an unauthorized response (401)
     */
    public function unauthorizedResponse($message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Unauthorized',
        ], 401);
    }

    /**
     * Return a forbidden response (403)
     */
    public function forbiddenResponse($message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Forbidden',
        ], 403);
    }

    /**
     * Return a not found response (404)
     */
    public function notFoundResponse($message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Not found',
        ], 404);
    }

    /**
     * Return a server error response (500)
     */
    public function serverErrorResponse($message = 'Server error', $error = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error ?? 'Internal server error',
        ], 500);
    }

    /**
     * Return a paginated response
     */
    public function paginatedResponse($items, $total, $perPage, $currentPage, $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'total_pages' => ceil($total / $perPage),
            ],
        ], 200);
    }
}

