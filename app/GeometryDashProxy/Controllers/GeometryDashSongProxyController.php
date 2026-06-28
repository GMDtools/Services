<?php

namespace App\GeometryDashProxy\Controllers;

use App\GeometryDashProxy\Exceptions\GeometryDashProxyException;
use App\GeometryDashProxy\Services\GeometryDashSongProxyService;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\GeometryDashResponses;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeometryDashSongProxyController
{
    public function __construct(
        protected GeometryDashSongProxyService $geometryDashSongProxyService
    ) {}

    /**
     * @throws GeometryDashProxyException
     */
    public function info(string $id): array
    {
        return $this->geometryDashSongProxyService->resolve($id)->toArray();
    }

    /**
     * @throws GeometryDashProxyException
     */
    public function handleGameInfoEndpoint(Request $request): string
    {
        $data = $request->all();

        if (empty($data['songID'])) {
            throw new GeometryDashProxyException('缺失 songID');
        }

        $song = $this->geometryDashSongProxyService->resolve($data['songID']);

        if ($song->is_disabled) {
            return GeometryDashResponses::SONG_DISABLED->value;
        }

        return $song->toObject();
    }

    /**
     * @throws GeometryDashProxyException
     */
    public function object(string $id): string
    {
        return $this->geometryDashSongProxyService->resolve($id)->toObject();
    }

    /**
     * @throws GeometryDashProxyException
     */
    public function download(string $id): Response|StreamedResponse
    {
        $song = $this->geometryDashSongProxyService->resolve($id);

        if ($song->download_link === null || $song->is_disabled) {
            return new Response(status: 404);
        }

        return $song->toDownload();
    }
}
