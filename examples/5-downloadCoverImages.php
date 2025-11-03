<?php

    require './common.php';

    //下载封面图
    while (true)
    {
        $gameUpdater->downloadCoverImages($imagePath);

        echo "等 $wait S";
        echo PHP_EOL;
        sleep($wait);
    }
