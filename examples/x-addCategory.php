<?php

    require './common.php';

    $len = 20;
    for ($i = 1; $i <= $len; $i++)
    {
        $gameUpdater->wpManager->addCategory('分类' . $i);
    }
