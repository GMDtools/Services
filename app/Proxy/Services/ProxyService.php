<?php

namespace App\Proxy\Services;

use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProxyService
{
    public function create(array $options = []): PendingRequest
    {
        return Http::withOptions([
            RequestOptions::PROXY => config('services.proxy.through'),

            ...$options,
        ])->retry(
            config('services.proxy.retry.times'),
            config('services.proxy.retry.delay')
        );
    }

    public function forwardStreamDownload(HttpResponse $response): StreamedResponse
    {
        return Response::streamDownload(
            function () use ($response) {
                $body = $response->getBody();

                while (true) {
                    if ($body->eof()) {
                        break;
                    }

                    echo $body->read(8192);

                    flush();
                }

                $body->close();
            },
            headers: [
                'Content-Type' => $response->getHeader('Content-Type'),
                'Content-Length' => $response->getHeader('Content-Length'),
            ]
        );
    }
}
