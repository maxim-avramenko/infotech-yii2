<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int|null $book_id
 * @property string $phone
 * @property string $text
 * @property string $status
 * @property int $attempts
 * @property string|null $last_error
 * @property string $next_attempt_at
 * @property string $created_at
 * @property string|null $sent_at
 */
class SmsNotification extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';

    public static function tableName(): string
    {
        return '{{%sms_notification}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }
}
