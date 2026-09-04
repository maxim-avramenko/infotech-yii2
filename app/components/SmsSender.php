<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

class SmsSender extends Component
{
    public bool $useFileTransport = true;
    public string $filePath = '@runtime/sms';

    public function send(string $phone, string $text): bool
    {
        if ($this->useFileTransport) {
            return $this->saveToFile($phone, $text);
        }

        throw new InvalidConfigException('SMS gateway is not configured. Set SMS_USE_FILE_TRANSPORT=1 or implement a provider.');
    }

    private function saveToFile(string $phone, string $text): bool
    {
        $dir = Yii::getAlias($this->filePath);
        FileHelper::createDirectory($dir);
        $file = $dir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.txt';
        $payload = sprintf("To: %s\nSent: %s\n\n%s\n", $phone, date('c'), $text);

        return file_put_contents($file, $payload) !== false;
    }
}
