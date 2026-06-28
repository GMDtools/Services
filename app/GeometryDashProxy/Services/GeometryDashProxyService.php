<?php

namespace App\GeometryDashProxy\Services;

use App\GeometryDashProxy\Exceptions\GeometryDashProxyException;
use App\Proxy\Services\ProxyService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

class GeometryDashProxyService
{
    public function __construct(
        protected ProxyService $proxyService
    ) {}

    /**
     * @throws GeometryDashProxyException
     */
    public function post(string $path, array $data = []): Response
    {
        try {
            $response = $this->proxyService->create()
                ->asForm()
                ->baseUrl(rtrim(config('services.geometry_dash_proxy.upstream.base'), '/').'/')
                ->withUserAgent(false)
                ->post($path, $data);

            if (! $response->successful()) {
                throw new GeometryDashProxyException('上游服务响应失败')
                    ->withData([
                        'status_code' => $response->getStatusCode(),
                    ]);
            }

            return $response;
        } catch (ConnectionException $e) {
            throw new GeometryDashProxyException('上游服务链接异常', previous: $e);
        }
    }
}
