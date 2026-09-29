<?php

    require './common.php';

    //压缩封面

    while (true)
    {
        $gameUpdater->gameSourceManager->compressScreenShotImage($imagePath);
        $gameUpdater->gameSourceManager->deleteErrorScreenShotImage($imagePath);

        echo "等 $wait S";
        echo PHP_EOL;
        sleep($wait);
        exit();
    }
