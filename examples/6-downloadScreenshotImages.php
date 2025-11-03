<?php

    require './common.php';

    //下载剧照
    while (true)
    {
        $gameUpdater->downloadScreenshotImages($imagePath);

        echo "等 $wait S";
        echo PHP_EOL;
        sleep($wait);
    }
