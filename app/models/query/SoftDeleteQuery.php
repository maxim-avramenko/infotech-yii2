<?php

declare(strict_types=1);

namespace app\models\query;

use yii\db\ActiveQuery;

class SoftDeleteQuery extends ActiveQuery
{
    private bool $includeDeleted = false;

    public function includeDeleted(): static
    {
        $this->includeDeleted = true;

        return $this;
    }

    public function prepare($builder)
    {
        if (!$this->includeDeleted) {
            $modelClass = $this->modelClass;
            $this->andWhere([$modelClass::tableName() . '.[[deleted_at]]' => null]);
        }

        return parent::prepare($builder);
    }
}
