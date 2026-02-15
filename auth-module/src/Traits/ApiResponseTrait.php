<?php

namespace Modules\AuthModule\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * API Response helper (backward compatibility)
     */
    protected function response($data = null, ?string $message = null, int $status = 200, $errors = null): JsonResponse
    {
        $response = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Başarılı API response
     */
    protected function successResponse(
        $data = null,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message ?? 'İşlem başarılı.',
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Hata API response
     */
    protected function errorResponse(
        ?string $message = null,
        int $status = 400,
        $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message ?? 'Bir hata oluştu.',
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors, ?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Validation hatası.',
            422,
            $errors
        );
    }
}
