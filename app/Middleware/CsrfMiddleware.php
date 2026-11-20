<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Csrf;
use App\MiddlewareInterface;
use App\Request;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        Csrf::check($request);
    }
}
