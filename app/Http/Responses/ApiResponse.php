<?php

namespace App\Http\Responses;

use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    /**
     * Success response with data
     */
    public static function success($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response
     */
    public static function error($message = 'Error', $code = 400, $errors = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Paginated response
     */
    public static function paginated($paginator, $message = 'Success')
    {
        if (!$paginator instanceof AbstractPaginator) {
            return self::error('Invalid paginator', 500);
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'jumlah' => $paginator->total(),
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Not found response
     */
    public static function notFound($message = 'Data not found')
    {
        return self::error($message, 404);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        return self::error($message, 401);
    }

    /**
     * Validation error response — errors diratakan menjadi array of strings
     *agar konsisten dengan format $request->validate() dari Laravel.
     *
     * Format: { "status": false, "message": "...", "errors": { "field": ["pesan"] } }
     */
    public static function validation($errors, $message = 'Data tidak valid.')
    {
        // Normalisasi: pastikan setiap value adalah array
        $normalized = [];
        foreach ($errors as $field => $msgs) {
            if (is_array($msgs)) {
                $normalized[$field] = $msgs;
            } else {
                $normalized[$field] = [$msgs];
            }
        }

        return self::error($message, 422, $normalized);
    }

    /**
     * Server error response
     */
    public static function serverError($message = 'Internal server error')
    {
        return self::error($message, 500);
    }
}
