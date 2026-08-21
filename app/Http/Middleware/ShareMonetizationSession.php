<?php

namespace App\Http\Middleware;

use App\Services\MonetizationSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareMonetizationSession
{
    public function __construct(
        private readonly MonetizationSettingsService $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->settings->syncToSession();

        return $next($request);
    }
}
