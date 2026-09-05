<?php

namespace app\components;

use app\exceptions\SmsSendException;
use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class SmsSender extends Component
{
    public bool $useFileTransport = true;
    public string $filePath = '@runtime/sms';
    public string $apiUrl = 'https://smspilot.ru/api.php';
    public string $apiKey = '';
    public string $from = '';
    public bool $test = true;
    public string $callbackUrl = '';
    public string $callbackMethod = 'post';

    /** @var callable|null fn(string $url, array $payload): string */
    public $httpClient = null;

    public function send(string $phone, string $text): bool
    {
        if ($this->useFileTransport) {
            return $this->saveToFile($phone, $text);
        }

        return $this->sendViaGateway($phone, $text);
    }

    private function sendViaGateway(string $phone, string $text): bool
    {
        if ($this->apiKey === '') {
            throw new SmsSendException('SMS_API_KEY is not configured.');
        }

        $payload = [
            'send' => $text,
            'to' => $phone,
            'apikey' => $this->apiKey,
            'format' => 'json',
            'charset' => 'UTF-8',
        ];
        if ($this->from !== '') {
            $payload['from'] = $this->from;
        }
        if ($this->test) {
            $payload['test'] = 1;
        }
        if ($this->callbackUrl !== '') {
            $payload['callback'] = $this->callbackUrl;
            $payload['callback_method'] = $this->callbackMethod !== '' ? $this->callbackMethod : 'post';
        }

        $decoded = $this->decodeResponse($this->request($payload));
        if (isset($decoded['error'])) {
            throw new SmsSendException($this->errorMessage($decoded['error']), $this->errorCode($decoded['error']));
        }
        if (!isset($decoded['send'][0]['server_id'])) {
            throw new SmsSendException('SMS gateway did not accept the message.');
        }

        return true;
    }

    private function request(array $payload): string
    {
        $client = $this->httpClient ?? [$this, 'postForm'];
        $response = $client($this->apiUrl, $payload);
        if (!is_string($response) || $response === '') {
            throw new SmsSendException('SMS gateway returned an empty response.');
        }

        return $response;
    }

    private function postForm(string $url, array $payload): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            throw new SmsSendException('Unable to reach SMS gateway.');
        }

        return $result;
    }

    private function decodeResponse(string $body): array
    {
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new SmsSendException('SMS gateway returned invalid JSON.');
        }

        return $decoded;
    }

    private function errorMessage(mixed $error): string
    {
        if (is_array($error)) {
            return (string) ($error['description_ru'] ?? $error['description'] ?? 'SMS gateway error');
        }

        return (string) $error;
    }

    private function errorCode(mixed $error): int
    {
        if (is_array($error)) {
            return (int) ($error['code'] ?? 0);
        }

        return 0;
    }

    private function saveToFile(string $phone, string $text): bool
    {
        $dir = Yii::getAlias($this->filePath);
        FileHelper::createDirectory($dir);
        $file = $dir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.txt';
        $payload = sprintf("To: %s\nSent: %s\n\n%s\n", $phone, date('c'), $text);

        if (file_put_contents($file, $payload) === false) {
            throw new SmsSendException('Unable to write SMS file.');
        }

        return true;
    }
}
