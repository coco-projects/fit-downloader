<?php

    require './common.php';

    $gameUpdater->wpManager->deleteAllTags();
    $gameUpdater->wpManager->purgePostMeta();
    $gameUpdater->wpManager->deleteAllPost();
    $gameUpdater->wpManager->deleteAllTags();
