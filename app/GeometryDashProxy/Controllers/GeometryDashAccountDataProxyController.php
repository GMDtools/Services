<?php

namespace App\GeometryDashProxy\Controllers;

use App\GeometryDashProxy\Exceptions\GeometryDashProxyException;
use App\GeometryDashProxy\Services\GeometryDashProxyService;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\GeometryDashAccountDataTypes;
use EndlessSpikeStudio\GeometryDashLibrary\Enums\GeometryDashSecrets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class GeometryDashAccountDataProxyController
{
    public function __construct(
        protected GeometryDashProxyService $geometryDashProxyService
    ) {}

    /**
     * @throws GeometryDashProxyException
     */
    public function handleGetURL(Request $request): string
    {
        $data = $request->all();

        if (empty($data['accountID'])) {
            throw new GeometryDashProxyException('缺失 accountID');
        }

        if (empty($data['type'])) {
            throw new GeometryDashProxyException('缺失 type');
        }

        if (GeometryDashAccountDataTypes::tryFrom($data['type']) === null) {
            throw new GeometryDashProxyException('type 无效');
        }

        $url = URL::action([GeometryDashAccountDataProxyController::class, 'handle'], [
            'accountID' => $data['accountID'],
            'type' => $data['type'],
            'endpoint' => '/',
        ]);

        return substr($url, 0, strlen($url) - 6);
    }

    /**
     * @throws GeometryDashProxyException
     */
    public function handle(Request $request, string $accountID, string $type, string $endpoint): string
    {
        $data = $request->all();

        if (GeometryDashAccountDataTypes::tryFrom($type) === null) {
            throw new GeometryDashProxyException('type 无效');
        }

        $upstream = implode('|', [__CLASS__, __METHOD__, $accountID.':'.$type, 'upstream'])
                |> sha1(...)
                |> (function (string $key) use ($accountID, $type) {
                    return Cache::rememberForever($key, function () use ($accountID, $type) {
                        return $this->geometryDashProxyService->post('getAccountURL.php', [
                            'accountID' => $accountID,
                            'type' => $type,
                            'secret' => GeometryDashSecrets::COMMON->value,
                        ])->body();
                    });
                });

        return $this->geometryDashProxyService->post(rtrim($upstream, '/').'/'.ltrim($endpoint, '/').'.php', $data)->body();
    }
}
