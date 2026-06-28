<?php

namespace App\GeometryDashProxy\Controllers;

use App\GeometryDashProxy\Exceptions\GeometryDashProxyException;
use App\GeometryDashProxy\Services\GeometryDashProxyService;
use App\Proxy\Services\ProxyService;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeometryDashCustomContentProxyController
{
    public function __construct(
        protected ProxyService $proxyService,
        protected GeometryDashProxyService $geometryDashProxyService
    ) {}

    /**
     * @throws GeometryDashProxyException
     */
    public function handleGetURL(): string
    {
        return URL::action([GeometryDashCustomContentProxyController::class, 'handle'], [
            'path' => '/',
        ]);
    }

    /**
     * @throws GeometryDashProxyException
     */
    public function handle(string $path): StreamedResponse
    {
        try {
            $upstream = implode('|', [__CLASS__, __METHOD__, 'upstream'])
                    |> sha1(...)
                    |> (function (string $key) {
                        return Cache::rememberForever($key, function () {
                            return $this->geometryDashProxyService->post('getCustomContentURL.php')->body();
                        });
                    });

            $url = rtrim($upstream, '/').'/'.ltrim($path, '/');

            return $this->proxyService->forwardStreamDownload(
                $this->proxyService->create([
                    RequestOptions::STREAM => true,
                ])->get($url)
            );
        } catch (ConnectionException $e) {
            throw new GeometryDashProxyException('自定义内容下载链接异常', previous: $e);
        }
    }
}
