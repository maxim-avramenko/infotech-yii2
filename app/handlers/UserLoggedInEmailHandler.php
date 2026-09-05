<?php

declare(strict_types=1);

namespace app\handlers;

use app\components\MessageBroker;
use app\events\UserLoggedInEvent;
use yii\base\Event;

class UserLoggedInEmailHandler
{
    public function __construct(
        private readonly MessageBroker $messageBroker,
    ) {
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof UserLoggedInEvent) {
            return;
        }

        $email = trim($event->email);
        if ($email === '') {
            return;
        }

        $subject = 'Вход в аккаунт';
        $text = $this->textBody($event);
        $this->messageBroker->email($email, $subject, $text, $this->htmlBody($event));
    }

    private function textBody(UserLoggedInEvent $event): string
    {
        return implode("\n", [
            'Зафиксирован успешный вход в ваш аккаунт.',
            '',
            'Время: ' . $event->loggedInAt,
            'Браузер: ' . $event->browser,
            'IP-адрес: ' . $event->ip,
            '',
            'Если это были не вы, смените пароль.',
        ]);
    }

    private function htmlBody(UserLoggedInEvent $event): string
    {
        return '<p>Зафиксирован успешный вход в ваш аккаунт.</p>'
            . '<ul>'
            . '<li>Время: ' . htmlspecialchars($event->loggedInAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
            . '<li>Браузер: ' . htmlspecialchars($event->browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
            . '<li>IP-адрес: ' . htmlspecialchars($event->ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'
            . '</ul>'
            . '<p>Если это были не вы, смените пароль.</p>';
    }
}
