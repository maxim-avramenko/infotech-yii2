<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class TomSelectAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/tom-select.bootstrap5.min.css',
    ];
    public $js = [
        'js/tom-select.complete.min.js',
    ];
}
