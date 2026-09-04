<?php

declare(strict_types=1);

namespace app\models;

enum UserRole: string
{
    case Administrator = 'ADMINISTRATOR';
    case User = 'USER';
}
