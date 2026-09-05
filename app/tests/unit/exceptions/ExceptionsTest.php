<?php

declare(strict_types=1);

namespace tests\unit\exceptions;

use app\exceptions\DuplicateUserException;
use app\exceptions\SmsSendException;

class ExceptionsTest extends \Codeception\Test\Unit
{
    public function testExceptionHierarchy(): void
    {
        verify(new DuplicateUserException('dup'))->instanceOf(\RuntimeException::class);
        verify(new SmsSendException('sms'))->instanceOf(\RuntimeException::class);
    }
}
