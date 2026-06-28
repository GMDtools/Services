<?php

namespace App\GeometryDashProxy\Controllers;

use App\GeometryDashProxy\Exceptions\GeometryDashProxyException;
use App\GeometryDashProxy\Services\GeometryDashProxyService;
use Illuminate\Http\Request;

class GeometryDashProxyController
{
    public function __construct(
        protected GeometryDashProxyService $geometryDashProxyService
    ) {}

    /**
     * @throws GeometryDashProxyException
     */
    public function handleGameEndpoint(Request $request, string $endpoint): string
    {
        $data = $request->all();

        return $this->geometryDashProxyService->post(ltrim($endpoint, '/').'.php', $data)->body();
    }
}
