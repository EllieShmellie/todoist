<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            static fn (Request $request): ?string => $request->is('api/*') ? null : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiRequest = static fn (Request $request): bool => $request->is('api/*');

        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, Throwable $exception): bool => $isApiRequest($request) || $request->expectsJson()
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json(
                    ['message' => $exception->getMessage() ?: 'This action is unauthorized.'],
                    Response::HTTP_FORBIDDEN,
                );
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json(['message' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json(['message' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                $status = $exception->getStatusCode();

                return response()->json([
                    'message' => $exception->getMessage() ?: (Response::$statusTexts[$status] ?? 'HTTP error.'),
                ], $status, $exception->getHeaders());
            }
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => config('app.debug') ? $exception->getMessage() : 'Server error.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });
    })->create();
