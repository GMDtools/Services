<?php

namespace App\GeometryDashProxy\Models;

use App\GeometryDashProxy\Controllers\GeometryDashSongProxyController;
use App\GeometryDashProxy\Exceptions\GeometryDashProxyException;
use App\Proxy\Services\ProxyService;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\Objects\GeometryDashSongObjectDefinitions;
use EndlessSpikeStudio\GeometryDashLibrary\Services\GeometryDashObjectService;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeometryDashSong extends Model
{
    protected $fillable = ['song_id', 'name', 'artist_id', 'artist_name', 'size', 'video_id', 'youtube_url', 'is_verified', 'prriority', 'download_link', 'type', 'extra_artist_ids', 'is_new', 'new_type', 'extra_artist_mappings', 'download_soundtrack_override', 'is_disabled', 'is_outdated'];

    /**
     * @throws GeometryDashProxyException
     */
    public static function fromObject(array $object, bool $outdated = false): GeometryDashSong
    {
        if (
            empty($object[GeometryDashSongObjectDefinitions::ID->value]) ||
            empty($object[GeometryDashSongObjectDefinitions::NAME->value]) ||
            empty($object[GeometryDashSongObjectDefinitions::ARTIST_NAME->value]) ||
            empty($object[GeometryDashSongObjectDefinitions::SIZE->value])
        ) {
            throw new GeometryDashProxyException('上游返回的歌曲结构无效')
                ->withData([
                    'object' => $object,
                ]);
        }

        return GeometryDashSong::query()
            ->updateOrCreate([
                'song_id' => $object[GeometryDashSongObjectDefinitions::ID->value],
            ], [
                'name' => $object[GeometryDashSongObjectDefinitions::NAME->value],
                'artist_id' => $object[GeometryDashSongObjectDefinitions::ARTIST_ID->value] ?? null,
                'artist_name' => $object[GeometryDashSongObjectDefinitions::ARTIST_NAME->value],
                'size' => $object[GeometryDashSongObjectDefinitions::SIZE->value],
                'video_id' => $object[GeometryDashSongObjectDefinitions::VIDEO_ID->value] ?? null,
                'youtube_url' => $object[GeometryDashSongObjectDefinitions::YOUTUBE_URL->value] ?? null,
                'is_verified' => $object[GeometryDashSongObjectDefinitions::IS_VERIFIED->value] ?? null,
                'prriority' => $object[GeometryDashSongObjectDefinitions::PRIORITY->value] ?? null,
                'download_link' => $object[GeometryDashSongObjectDefinitions::DOWNLOAD_URL->value] ?? null,
                'type' => $object[GeometryDashSongObjectDefinitions::TYPE->value] ?? null,
                'extra_artist_ids' => $object[GeometryDashSongObjectDefinitions::EXTRA_ARTIST_IDS->value] ?? null,
                'is_new' => $object[GeometryDashSongObjectDefinitions::IS_NEW->value] ?? null,
                'new_type' => $object[GeometryDashSongObjectDefinitions::NEW_TYPE->value] ?? null,
                'extra_artist_mappings' => $object[GeometryDashSongObjectDefinitions::EXTRA_ARTIST_INFORMATION->value] ?? null,
                'download_soundtrack_override' => $object[GeometryDashSongObjectDefinitions::DOWNLOAD_SOUNDTRACK_OVERRIDE->value] ?? null,
                'is_disabled' => false,
                'is_outdated' => $outdated,
            ]);
    }

    public function toArray()
    {
        $data = $this->toArray();

        if (empty($song['download_link']) || $song->is_disabled) {
            unset($data['download_link']);
        } else {
            $data['original_download_link'] = $song->download_link;
            $data['download_link'] = URL::action([GeometryDashSongProxyController::class, 'download'], [
                'id' => $song->song_id,
            ]);
        }

        return $data;
    }

    /**
     * @throws GeometryDashProxyException
     */
    public function toDownload(): StreamedResponse
    {
        try {
            $url = urldecode($this->download_link);

            $proxyService = app(ProxyService::class);

            return $proxyService->forwardStreamDownload(
                $proxyService->create([
                    RequestOptions::STREAM => true,
                ])->get($url)
            );
        } catch (ConnectionException $e) {
            throw new GeometryDashProxyException('歌曲下载链接异常', previous: $e);
        }
    }

    public function toObject()
    {
        $downloadUrl = urlencode(
            URL::action([GeometryDashSongProxyController::class, 'download'], [
                'id' => $this->song_id,
            ])
        );

        if ($this->song_id > 10000000) {
            $downloadUrl = null;
        }

        return app(GeometryDashObjectService::class)->merge(Arr::where([
            GeometryDashSongObjectDefinitions::ID->value => $this->song_id,
            GeometryDashSongObjectDefinitions::NAME->value => $this->name,
            GeometryDashSongObjectDefinitions::ARTIST_ID->value => $this->artist_id,
            GeometryDashSongObjectDefinitions::ARTIST_NAME->value => $this->artist_name,
            GeometryDashSongObjectDefinitions::SIZE->value => $this->size,
            GeometryDashSongObjectDefinitions::VIDEO_ID->value => $this->video_id,
            GeometryDashSongObjectDefinitions::YOUTUBE_URL->value => $this->youtube_url,
            GeometryDashSongObjectDefinitions::IS_VERIFIED->value => $this->is_verified,
            GeometryDashSongObjectDefinitions::PRIORITY->value => $this->prriority,
            GeometryDashSongObjectDefinitions::DOWNLOAD_URL->value => $downloadUrl,
            GeometryDashSongObjectDefinitions::TYPE->value => $this->type,
            GeometryDashSongObjectDefinitions::EXTRA_ARTIST_IDS->value => $this->extra_artist_ids,
            GeometryDashSongObjectDefinitions::IS_NEW->value => $this->is_new,
            GeometryDashSongObjectDefinitions::NEW_TYPE->value => $this->new_type,
            GeometryDashSongObjectDefinitions::EXTRA_ARTIST_INFORMATION->value => $this->extra_artist_mappings,
            GeometryDashSongObjectDefinitions::DOWNLOAD_SOUNDTRACK_OVERRIDE->value => $this->download_soundtrack_override,
        ], function ($value) {
            return ! empty($value);
        }), GeometryDashSongObjectDefinitions::GLUE);
    }

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_new' => 'boolean',
            'is_disabled' => 'boolean',
        ];
    }
}
