<?php

    use Coco\tableManager\TableRegistry;

    require './common.php';

    $method = TableRegistry::makeMethod($gameUpdater->gameSourceManager->getGameImagesTable()->getFieldsSqlMap());

    print_r($method);
