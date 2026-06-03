<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('app.api_token', '');
        $provided = (string) ($request->bearerToken() ?: $request->header('X-App-Token', ''));

        if ($token === '') {
            return response()->json([
                'code' => 400,
                'message' => 'API token 未配置',
                'data' => null,
            ], 500);
        }

        if ($provided === '' || ! hash_equals($token, $provided)) {
            return response()->json([
                'code' => 401,
                'message' => '未授权的 App 请求',
                'data' => null,
            ], 401);
        }

        return $next($request);
    }
}
