<?php

namespace App\Services\Yandex;

use App\Http\Controllers\APIOnlineController;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Core\AlgorithmManagerFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AuthService
{
    protected string $tokensUrl;
    protected string $keyJsonPath;
    protected array $keyData;
    protected string $taskId;

    public function __construct($taskId)
    {
        $this->taskId = $taskId;
        // URL API для получения IAM-токена
        $this->tokensUrl = 'https://iam.api.cloud.yandex.net/iam/v1/tokens';

        // Путь к JSON-файлу с ключами
        $this->keyJsonPath = base_path('keys/key.json');
        APIOnlineController::sendLog(
            'AuthService',
            ['message' => "keyJsonPath: {$this->keyJsonPath}"]
        );
        // Загружаем JSON-файл с ключами
        if (!file_exists($this->keyJsonPath)) {
            APIOnlineController::sendLog(
                'Key file not found',
                ['keyJsonPath' => $this->keyJsonPath]
            );
            throw new \Exception("Key file not found: {$this->keyJsonPath}");
        }
        $this->keyData = json_decode(file_get_contents($this->keyJsonPath), true);

        // Инициализируем Guzzle клиент

    }

    public function getIamToken()
    {
        APIOnlineController::sendLog(
            'getIamToken',
            ['message' => "keyJsonPath: pre cache iam_token"]
        );
        // Проверяем кеш
        if (Cache::has('iam_token')) {
            $key = Cache::get('iam_token');
            APIOnlineController::sendLog('IAM GOOD Cache has', ['iamToken' => $key]);

            return Cache::get('iam_token');
        }

        // Генерируем JWT


        try {
            $jwt = $this->generateJwt();
            APIOnlineController::sendLog('jwt', ['jwt' => $jwt]);

            $client = new Client();
            $response = $client->request('POST', $this->tokensUrl, [
                'json' => ['jwt' => $jwt]
            ]);
            APIOnlineController::sendLog('IAM response', ['response' => $response]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            APIOnlineController::sendLog('IAM responseData', ['responseData' => $responseData]);
            $iamToken = $responseData['iamToken'];
            APIOnlineController::sendLog('IAM GOOD Cache generate', ['iamToken' => $iamToken]);

            // Сохраняем токен в кэш на 11 часов 55 минут (чтобы обновлять заранее)
            Cache::put('iam_token', $iamToken, now()->addHours(11)->addMinutes(55));
            APIOnlineController::sendLog('IAM GOOD', ['iamToken' => 'yo']);

            return $iamToken;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            APIOnlineController::sendLog('IAM Error', ['error' => $e->getMessage(), 'from' => 'get IAM token']);
            Redis::set("transcription:{$this->taskId}:status", "error");
            Redis::set("transcription:{$this->taskId}:error", "Проблемы с tokens");
            return null;
        }
    }

    private function generateJwt(): string
    {
        try {
            $keyId = $this->keyData['id'];
            $algorithm = 'PS256';
            $PEM = $this->keyData['private_key'];
            // $privateKeyPEM = preg_replace('/^PLEASE DO NOT REMOVE THIS LINE!.*\n/', '', $this->keyData['private_key']);
            $startPrivateKey = strpos($PEM, "-----BEGIN PRIVATE KEY-----");
            $endPrivateKey = strpos($PEM, "-----END PRIVATE KEY-----") + strlen("-----END PRIVATE KEY-----");
            $privateKeyPEM = substr($PEM, $startPrivateKey, $endPrivateKey - $startPrivateKey);


            APIOnlineController::sendLog('IAM GOOD', ['privateKeyPEM' => $privateKeyPEM]);


            APIOnlineController::sendLog('keyId', ['keyId' => $keyId]);
            // Генерируем ключ
            $key = JWKFactory::createFromKey($privateKeyPEM, null, [
                'alg' => $algorithm,
                'kid' => $keyId,
                'use' => 'sig',
                'kty' => 'RSA'
            ]);
            APIOnlineController::sendLog('JWKFactory', ['key' => $key]);
            // Создаем алгоритм подписания
            $algorithmManagerFactory = new AlgorithmManagerFactory();
            $algorithmManagerFactory->add($algorithm, new PS256());
            $signatureAlgorithmManager = $algorithmManagerFactory->create([$algorithm]);

            // Подготовка JWS
            $jwsBuilder = new JWSBuilder($signatureAlgorithmManager);
            $payload = json_encode([
                'iss' => $this->keyData['service_account_id'],
                'aud' => $this->tokensUrl,
                'iat' => time(),
                'exp' => time() + 3600, // 1 час
            ]);
            APIOnlineController::sendLog('JWKFactory', ['payload' => $payload]);
            // Подписываем JWS
            $jws = $jwsBuilder->create()
                ->withPayload($payload)
                ->addSignature($key, ['alg' => $algorithm, 'kid' => $keyId])
                ->build();


            return (new CompactSerializer())->serialize($jws, 0);
        } catch (\Exception $e) {
            APIOnlineController::sendLog('JWK error', ['error' => $e->getMessage()]);
            Redis::set("transcription:{$this->taskId}:status", "error");
            Redis::set("transcription:{$this->taskId}:error", "Проблемы с JWK");
            throw $e;
        }
    }
}
