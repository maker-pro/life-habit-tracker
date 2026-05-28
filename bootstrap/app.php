<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'code' => 400,
                    'message' => collect($exception->errors())->flatten()->first() ?: '参数验证失败',
                    'data' => null,
                ], 400);
            }

            return null;
        });

        $exceptions->render(function (ModelNotFoundException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json(['code' => 400, 'message' => '数据不存在', 'data' => null], 404);
            }

            return null;
        });

        $exceptions->render(function (Throwable $exception, $request) {
            if ($request->is('api/*')) {
                $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
                $message = $status >= 500 ? '服务器内部错误' : ($exception->getMessage() ?: '请求失败');

                return response()->json(['code' => 400, 'message' => $message, 'data' => null], $status);
            }

            return null;
        });
    })->create();
