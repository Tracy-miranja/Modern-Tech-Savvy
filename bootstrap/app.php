<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\SetCurrentBusiness;
use App\Http\Middleware\TriggerDueLeaveAccruals;
use App\Services\RoleHomeRouteService;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->group(base_path('routes/requests.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('channels')
                ->group(base_path('routes/channels.php'));
        },
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(SetCurrentBusiness::class);
        $middleware->appendToGroup('web', TriggerDueLeaveAccruals::class);
        // Biometric device push receivers - external hardware firmware,
        // no session/CSRF token to send.
        $middleware->validateCsrfTokens(except: [
            'device-webhook/*',
            'iclock/*',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleOrImpersonation::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'role_or_permission_or_impersonation' => \App\Http\Middleware\RoleOrPermissionOrImpersonation::class,
            'ensure_role' => \App\Http\Middleware\EnsureCorrectRole::class,
            'ensure_module' => \App\Http\Middleware\EnsureBusinessModuleActive::class,
            'ensure_project_member' => \App\Http\Middleware\EnsureProjectMember::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Spatie's RoleMiddleware/PermissionMiddleware/RoleOrPermissionMiddleware
        // (aliases 'role'/'permission'/'role_or_permission') and our own
        // RoleOrImpersonation/RoleOrPermissionOrImpersonation all throw this
        // on a 403 - route non-AJAX requests to somewhere the user actually
        // has access to instead of a bare JSON error, matching
        // EnsureCorrectRole's own handling.
        $exceptions->render(function (UnauthorizedException $e, $request) {
            return app(RoleHomeRouteService::class)->forbiddenResponse($request, $request->user(), $e->getMessage());
        });
    })->create();
