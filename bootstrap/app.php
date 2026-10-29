<?php

use App\Http\Middleware\EnsureUserHasStaffRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

require_once __DIR__.'/../app/Support/PdoMysqlCompatibility.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();

        $middleware->alias([
            'staff' => EnsureUserHasStaffRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidSignatureException $exception, Request $request) {
            if ($request->is('email/verify/*')) {
                return redirect('/verify-email?error=invalid_or_expired');
            }

            return null;
        });
        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if ($request->is('email/verify/*')) {
                return redirect('/verify-email?error=invalid_or_expired');
            }

            return null;
        });
    })->create();
