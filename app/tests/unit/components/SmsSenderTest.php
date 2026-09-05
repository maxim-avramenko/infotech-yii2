<?php

declare(strict_types=1);

namespace tests\unit\components;

use app\components\SmsSender;
use app\exceptions\SmsSendException;
use Yii;

class SmsSenderTest extends \Codeception\Test\Unit
{
    public function testFileTransportWritesPayload(): void
    {
        $dir = Yii::getAlias('@runtime/sms-test');
        $sender = new SmsSender(['useFileTransport' => true, 'filePath' => $dir]);
        verify($sender->send('79001234567', 'Hello'))->true();

        $files = glob($dir . '/*.txt') ?: [];
        verify($files)->notEmpty();
        $contents = file_get_contents($files[array_key_last($files)]);
        verify($contents)->stringContainsString('To: 79001234567');
        verify($contents)->stringContainsString('Hello');
    }

    public function testTestContainerKeepsFileTransportAndGatewayDefaults(): void
    {
        $sender = Yii::$app->smsSender;
        verify($sender)->instanceOf(SmsSender::class);
        verify($sender->useFileTransport)->true();
        verify($sender->apiUrl)->equals('https://smspilot.ru/api.php');
        verify($sender->callbackMethod)->equals('post');
        verify($sender->test)->true();
    }

    public function testGatewayModeThrowsWhenApiKeyMissing(): void
    {
        $sender = new SmsSender(['useFileTransport' => false, 'apiKey' => '']);
        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('SMS_API_KEY is not configured.');
        $sender->send('7900', 'x');
    }

    public function testGatewayPostsToSmsPilot(): void
    {
        $captured = [];
        $sender = $this->gatewaySender(static function (string $url, array $payload) use (&$captured): string {
            $captured = ['url' => $url, 'payload' => $payload];

            return json_encode([
                'send' => [['server_id' => '1000', 'phone' => $payload['to'], 'status' => '0']],
                'balance' => '0',
            ], JSON_THROW_ON_ERROR);
        }, [
            'from' => 'INFO',
            'test' => true,
            'callbackUrl' => 'https://example.com/sms-status',
            'callbackMethod' => 'post',
        ]);

        verify($sender->send('79001234567', 'New book'))->true();
        verify($captured['url'])->equals('https://smspilot.ru/api.php');
        verify($captured['payload']['send'])->equals('New book');
        verify($captured['payload']['to'])->equals('79001234567');
        verify($captured['payload']['apikey'])->equals('test-key');
        verify($captured['payload']['from'])->equals('INFO');
        verify($captured['payload']['test'])->equals(1);
        verify($captured['payload']['callback'])->equals('https://example.com/sms-status');
        verify($captured['payload']['callback_method'])->equals('post');
        verify($captured['payload']['format'])->equals('json');
        verify($captured['payload']['charset'])->equals('UTF-8');
    }

    public function testGatewayOmitsOptionalFieldsInLiveMode(): void
    {
        $payload = [];
        $sender = $this->gatewaySender(static function (string $url, array $body) use (&$payload): string {
            $payload = $body;

            return '{"send":[{"server_id":"1","phone":"79001234567","status":"0"}]}';
        }, [
            'from' => '',
            'test' => false,
            'callbackUrl' => '',
        ]);

        verify($sender->send('79001234567', 'Live'))->true();
        verify(isset($payload['from']))->false();
        verify(isset($payload['test']))->false();
        verify(isset($payload['callback']))->false();
        verify(isset($payload['callback_method']))->false();
    }

    public function testGatewayDefaultsEmptyCallbackMethodToPost(): void
    {
        $payload = [];
        $sender = $this->gatewaySender(static function (string $url, array $body) use (&$payload): string {
            $payload = $body;

            return '{"send":[{"server_id":"1"}]}';
        }, [
            'callbackUrl' => 'https://example.com/hook',
            'callbackMethod' => '',
        ]);

        $sender->send('79001234567', 'Hook');
        verify($payload['callback_method'])->equals('post');
    }

    public function testGatewayThrowsOnApiError(): void
    {
        $sender = $this->gatewaySender(static fn (): string => json_encode([
            'error' => [
                'code' => '111',
                'description' => 'Invalid phone',
                'description_ru' => 'Неправильный номер телефона',
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $sender->send('bad', 'x');
            $this->fail('Expected SmsSendException');
        } catch (SmsSendException $exception) {
            verify($exception->getMessage())->equals('Неправильный номер телефона');
            verify($exception->getCode())->equals(111);
        }
    }

    public function testGatewayUsesEnglishErrorWhenRussianMissing(): void
    {
        $sender = $this->gatewaySender(static fn (): string => '{"error":{"description":"Invalid phone"}}');
        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('Invalid phone');
        $sender->send('bad', 'x');
    }

    public function testGatewayAcceptsStringError(): void
    {
        $sender = $this->gatewaySender(static fn (): string => '{"error":"gateway down"}');
        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('gateway down');
        $sender->send('7900', 'x');
    }

    public function testGatewayThrowsOnEmptyResponse(): void
    {
        $sender = $this->gatewaySender(static fn (): string => '');
        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('SMS gateway returned an empty response.');
        $sender->send('7900', 'x');
    }

    public function testGatewayThrowsOnInvalidJson(): void
    {
        $sender = $this->gatewaySender(static fn (): string => 'not-json');
        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('SMS gateway returned invalid JSON.');
        $sender->send('7900', 'x');
    }

    public function testGatewayThrowsWhenServerIdMissing(): void
    {
        $sender = $this->gatewaySender(static fn (): string => '{"send":[{"phone":"7900"}]}');
        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('SMS gateway did not accept the message.');
        $sender->send('7900', 'x');
    }

    private function gatewaySender(callable $httpClient, array $config = []): SmsSender
    {
        return new SmsSender(array_merge([
            'useFileTransport' => false,
            'apiUrl' => 'https://smspilot.ru/api.php',
            'apiKey' => 'test-key',
            'httpClient' => $httpClient,
        ], $config));
    }
}
