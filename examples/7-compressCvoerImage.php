<?php

    require './common.php';

    //压缩封面

    while (true)
    {
        $gameUpdater->compressCvoerImage();

        echo "等 $wait S";
        echo PHP_EOL;
        sleep($wait);
        exit();
    }
