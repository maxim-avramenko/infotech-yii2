<?php

declare(strict_types=1);

namespace app\commands;

use app\services\UserService;
use Throwable;
use yii\base\Module;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class UserController extends Controller
{
    public function __construct(
        string $id,
        Module $module,
        private readonly UserService $userService,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionCreateAdmin(string $email, string $phone, string $password): int
    {
        try {
            $user = $this->userService->createAdmin($email, $phone, $password);
        } catch (Throwable $exception) {
            $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Administrator #{$user->id} created ({$user->email}, {$user->phone})." . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
