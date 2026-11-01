<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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

        // A stale page's CSRF token (session expired, or the PWA was reopened
        // after being suspended in the background for a while) used to dead-end
        // on Laravel's bare "Page Expired" screen — the user had to manually
        // reload to recover. Sending them back instead re-renders that same
        // page fresh, with a new token already embedded, so the very next
        // submit just works — no manual refresh needed.
        if ($e instanceof TokenMismatchException && ! $request->expectsJson()) {
            $message = 'That took a bit long and your session needs refreshing — please try again.';

            return redirect()->back()
                ->withErrors(['login' => $message])
                ->with('status', $message)
                ->with('error', $message)
                ->withInput($request->except(['password', 'password_confirmation', '_token']));
        }

        // Keep APP_DEBUG disabled in production, while still giving signed-in
        // administrators a useful, safe diagnostic instead of Laravel's plain
        // "Server Error" page. The full trace remains in the application log;
        // it is intentionally never exposed in the browser.
        if (! config('app.debug')
            && ! $request->expectsJson()
            && $this->shouldShowAdminDiagnostic($request, $e)) {
            $cause = $this->rootCause($e);

            return response()->view('errors.admin-error', [
                'exception' => class_basename($cause),
                'message' => $cause->getMessage() ?: 'An unexpected error occurred.',
                'file' => basename($cause->getFile()),
                'line' => $cause->getLine(),
            ], 500);
        }

        return parent::render($request, $e);
    }

    /** Show production diagnostics only to authenticated administrators. */
    private function shouldShowAdminDiagnostic($request, Throwable $e): bool
    {
        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            return false;
        }

        return auth()->check() && auth()->user()?->isAdmin();
    }

    /** A ViewException wraps the useful underlying application exception. */
    private function rootCause(Throwable $e): Throwable
    {
        while ($e->getPrevious() instanceof Throwable) {
            $e = $e->getPrevious();
        }

        return $e;
    }
}
