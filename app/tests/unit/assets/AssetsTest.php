<?php

declare(strict_types=1);

namespace tests\unit\assets;

use app\assets\AppAsset;
use app\assets\TomSelectAsset;

class AssetsTest extends \Codeception\Test\Unit
{
    public function testBundlesExposeFiles(): void
    {
        $app = new AppAsset();
        verify($app->css)->equals(['css/site.css']);
        verify($app->depends)->notEmpty();

        $tom = new TomSelectAsset();
        verify($tom->js)->equals(['js/tom-select.complete.min.js']);
        verify($tom->css)->equals(['css/tom-select.bootstrap5.min.css']);
    }
}
