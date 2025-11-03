<?php

    require './common.php';

    echo '更新浏览量';
    echo PHP_EOL;
    $gameUpdater->wpManager->updateAllPostView(1, 50, false);

    echo PHP_EOL;
    echo PHP_EOL;

    echo '更新发布时间';
    echo PHP_EOL;
    $begin = '2021-2-5';
    $end   = date('Y-m-d');
    $times = 800;
    $gameUpdater->wpManager->updateAllPostPublishTime($begin, $end, $times,true);