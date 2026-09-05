<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\BookAuthorSubscription;
use yii\db\IntegrityException;

class BookAuthorSubscriptionRepository
{
    public function findByAuthorAndPhone(int $authorId, string $phone): ?BookAuthorSubscription
    {
        return BookAuthorSubscription::findOne([
            'book_author_id' => $authorId,
            'phone' => $phone,
        ]);
    }

    /**
     * @param int[] $authorIds
     * @return string[]
     */
    public function findDistinctPhonesByAuthorIds(array $authorIds): array
    {
        if ($authorIds === []) {
            return [];
        }

        return BookAuthorSubscription::find()
            ->select('phone')
            ->distinct()
            ->andWhere(['book_author_id' => $authorIds])
            ->column();
    }

    public function save(BookAuthorSubscription $subscription): void
    {
        try {
            if (!$subscription->save()) {
                $errors = implode('; ', $subscription->getFirstErrors());
                throw new \RuntimeException($errors !== '' ? $errors : 'Unable to save subscription.');
            }
        } catch (IntegrityException) {
            $existing = $this->findByAuthorAndPhone((int) $subscription->book_author_id, $subscription->phone);
            if ($existing === null) {
                throw new \RuntimeException('Unable to save subscription.');
            }
        }
    }
}
