<?php

namespace App\GeometryDashProxy\Controllers;

use App\GeometryDashProxy\Exceptions\GeometryDashProxyException;
use App\GeometryDashProxy\Services\GeometryDashProxyService;
use App\GeometryDashProxy\Services\GeometryDashSongProxyService;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\GeometryDashSpecialSongDownloadUrls;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\Objects\GeometryDashLevelObjectDefinitions;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\Objects\GeometryDashSongObjectDefinitions;
use EndlessSpikeStudio\GeometryDashLibrary\Services\GeometryDashObjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GeometryDashLevelProxyController
{
    public function __construct(
        protected GeometryDashProxyService $geometryDashProxyService,
        protected GeometryDashSongProxyService $geometryDashSongProxyService,
        protected GeometryDashObjectService $geometryDashObjectService,
    ) {}

    /**
     * @throws GeometryDashProxyException
     */
    public function handleGameDownloadEndpoint(Request $request): string
    {
        $data = $request->all();

        $response = $this->geometryDashProxyService->post('downloadGJLevel22.php', $data)->body();

        try {
            if (! Str::contains($response, GeometryDashLevelObjectDefinitions::SEGMENTATION)) {
                throw new GeometryDashProxyException('缺失分隔');
            }

            $parts = explode(GeometryDashLevelObjectDefinitions::SEGMENTATION, $response);

            if (empty($parts[4])) {
                throw new GeometryDashProxyException('缺失歌曲部分');
            }

            $songs = explode(GeometryDashSongObjectDefinitions::SEPARATOR, $parts[4]);
            $replaceSongs = [];

            foreach ($songs as $song) {
                $object = $this->geometryDashObjectService->split($song, GeometryDashSongObjectDefinitions::GLUE);

                $replaceSongs[] = $this->geometryDashSongProxyService->resolve($object[GeometryDashSongObjectDefinitions::ID->value], $object)->toObject();
            }

            $parts[4] = implode(GeometryDashSongObjectDefinitions::SEPARATOR, $replaceSongs);

            return implode(GeometryDashLevelObjectDefinitions::SEGMENTATION, $parts);
        } catch (GeometryDashProxyException $e) {
            report($e);

            return $response;
        }
    }

    /**
     * @throws GeometryDashProxyException
     */
    public function handleGameListEndpoint(Request $request): string
    {
        $data = $request->all();

        $response = $this->geometryDashProxyService->post('getGJLevels21.php', $data)->body();

        try {
            if (! Str::contains($response, GeometryDashLevelObjectDefinitions::SEGMENTATION)) {
                throw new GeometryDashProxyException('缺失分隔');
            }

            $parts = explode(GeometryDashLevelObjectDefinitions::SEGMENTATION, $response);

            if (empty($parts[2])) {
                throw new GeometryDashProxyException('缺失歌曲部分');
            }

            $songs = explode(GeometryDashSongObjectDefinitions::SEPARATOR, $parts[2]);
            $replaceSongs = [];

            foreach ($songs as $song) {
                $object = $this->geometryDashObjectService->split($song, GeometryDashSongObjectDefinitions::GLUE);

                $replaceSongs[] = $this->geometryDashObjectService->replace(
                    $this->geometryDashSongProxyService->resolve($object[GeometryDashSongObjectDefinitions::ID->value], $object)->toObject(),
                    GeometryDashSongObjectDefinitions::GLUE,
                    [
                        GeometryDashSongObjectDefinitions::DOWNLOAD_URL->value => GeometryDashSpecialSongDownloadUrls::DISABLED->value,
                    ]
                );
            }

            $parts[2] = implode(GeometryDashSongObjectDefinitions::SEPARATOR, $replaceSongs);

            return implode(GeometryDashLevelObjectDefinitions::SEGMENTATION, $parts);
        } catch (GeometryDashProxyException $e) {
            report($e);

            return $response;
        }
    }
}
