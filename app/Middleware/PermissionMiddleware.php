<?php
declare(strict_types=1);

namespace App\Middleware;

use App\MiddlewareInterface;
use App\Permission;
use App\Request;

final class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $modulo,
        private readonly string $acao = 'ver'
    ) {
    }

    public function handle(Request $request): void
    {
        Permission::requirePermission($this->modulo, $this->acao);
    }
}
