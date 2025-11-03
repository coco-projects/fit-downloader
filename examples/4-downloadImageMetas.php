<?php

    require './common.php';

    //采集所有剧照图集链接到图片库、
    while (true)
    {
        $gameUpdater->downloadImageMetas();
        exit();
        echo "等 $wait S";
        echo PHP_EOL;
        sleep($wait);
    }
