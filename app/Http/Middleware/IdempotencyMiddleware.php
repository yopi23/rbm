<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only run idempotency check for data-modifying HTTP methods
        if (!in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            return $next($request);
        }

        $key = $request->header('X-Idempotency-Key');

        if (!$key) {
            return $next($request);
        }

        $cacheKey = "idempotency:{$key}";

        // Attempt to retrieve cached status for this idempotency key
        $cachedValue = Cache::get($cacheKey);

        if ($cachedValue) {
            if ($cachedValue['status'] === 'processing') {
                return response()->json([
                    'success' => false,
                    'message' => 'Permintaan sedang diproses. Silakan coba sesaat lagi.'
                ], 409);
            }

            if ($cachedValue['status'] === 'resolved') {
                // Reconstruct response from cache
                $response = response($cachedValue['body'], $cachedValue['statusCode']);
                foreach ($cachedValue['headers'] as $name => $values) {
                    if (in_array(strtolower($name), ['connection', 'transfer-encoding', 'content-length', 'keep-alive'])) {
                        continue;
                    }
                    $response->header($name, implode(', ', $values));
                }
                
                // Add header to indicate response was served from cache
                $response->header('X-Cache-Lookup', 'HIT (Idempotency)');
                return $response;
            }
        }

        // Set as processing with a 5-minute timeout lock
        Cache::put($cacheKey, ['status' => 'processing'], 300);

        try {
            $response = $next($request);

            // Cache successful or client-side error responses (2xx and 4xx) for 24 hours
            if ($response->getStatusCode() < 500) {
                Cache::put($cacheKey, [
                    'status' => 'resolved',
                    'statusCode' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                    'headers' => $response->headers->all()
                ], 86400); // 24 hours
            } else {
                // Delete lock on server errors to allow client retries
                Cache::forget($cacheKey);
            }

            return $response;
        } catch (\Throwable $e) {
            // Remove processing lock on exception
            Cache::forget($cacheKey);
            throw $e;
        }
    }
}
