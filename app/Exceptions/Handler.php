<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Send visitors hitting an undefined URL back to the home page instead of a 404 error page.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof NotFoundHttpException && ! $request->expectsJson()) {
            return redirect('/');
        }

        return parent::render($request, $e);
    }
}
