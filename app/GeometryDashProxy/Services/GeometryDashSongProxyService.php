<?php

namespace App\GeometryDashProxy\Services;

use App\GeometryDashProxy\Exceptions\GeometryDashProxyException;
use App\GeometryDashProxy\Models\GeometryDashSong;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\GeometryDashResponses;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\GeometryDashSecrets;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\Objects\GeometryDashLevelObjectDefinitions;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\Objects\GeometryDashSongObjectDefinitions;
use EndlessSpikeStudio\GeometryDashLibrary\Services\GeometryDashObjectService;
use Illuminate\Support\Arr;

class GeometryDashSongProxyService
{
    public function __construct(
        protected GeometryDashProxyService $geometryDashProxyService,
        protected GeometryDashObjectService $geometryDashObjectService
    ) {}

    /**
     * @throws GeometryDashProxyException
     */
    public function resolve(string $id, ?array $object = null): GeometryDashSong
    {
        $song = GeometryDashSong::query()
            ->where('song_id', $id)
            ->first();

        if (empty($song)) {
            return GeometryDashSong::fromObject($object ?? $this->resolveObjectFromOfficialServer($id), $object !== null);
        }

        if ($song->is_outdated) {
            return GeometryDashSong::fromObject(
                $this->resolveObjectFromOfficialServer($id)
            );
        }

        return $song;
    }

    /**
     * @throws GeometryDashProxyException
     */
    protected function resolveObjectFromOfficialServer(string $id): array
    {
        try {
            return $this->resolveObjectFromOfficialServerSongInfoApi($id);
        } catch (GeometryDashProxyException $e) {
            if ($e->getCode() !== GeometryDashResponses::SONG_DISABLED->value) {
                throw $e;
            }
        }

        return $this->resolveObjectFromOfficialServerGetLevelsApi($id);
    }

    /**
     * @throws GeometryDashProxyException
     */
    protected function resolveObjectFromOfficialServerSongInfoApi(string $id): array
    {
        $response = $this->geometryDashProxyService->post('getGJSongInfo.php', [
            'songID' => $id,
            'secret' => GeometryDashSecrets::COMMON->value,
        ]);

        if ($response->body() === '-2') {
            GeometryDashSong::created(function (GeometryDashSong $song) use ($id) {
                if ($song->song_id == $id) {
                    $song->update([
                        'is_disabled' => true,
                    ]);
                }
            });

            throw new GeometryDashProxyException('歌曲被禁用', GeometryDashResponses::SONG_DISABLED->value);
        }

        return $this->geometryDashObjectService->split($response, GeometryDashSongObjectDefinitions::GLUE);
    }

    /**
     * @throws GeometryDashProxyException
     */
    protected function resolveObjectFromOfficialServerGetLevelsApi(string $id): array
    {
        $response = $this->geometryDashProxyService->post('getGJLevels21.php', [
            'song' => $id,
            'customSong' => true,
            'secret' => GeometryDashSecrets::COMMON->value,
        ]);

        return explode(GeometryDashLevelObjectDefinitions::SEGMENTATION, $response)
                |> (function (array $parts) {
                    return Arr::get($parts, 2);
                })
                |> (function (string $part) {
                    return $this->geometryDashObjectService->split($part, GeometryDashSongObjectDefinitions::GLUE);
                });
    }
}
