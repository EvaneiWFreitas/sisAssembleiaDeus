<?php
declare(strict_types=1);

namespace App;

use App\Request;

interface MiddlewareInterface
{
    public function handle(Request $request): void;
}
