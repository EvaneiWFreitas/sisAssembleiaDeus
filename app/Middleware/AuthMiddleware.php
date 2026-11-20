<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Auth;
use App\Exceptions\HttpException;
use App\MiddlewareInterface;
use App\Request;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            throw new HttpException(403, 'É necessário estar autenticado para acessar esta área.');
        }
    }
}
