<?php

    use Coco\tableManager\TableRegistry;

    require './common.php';

//    $method = TableRegistry::makeMethod($gameManager->getGameTable()->getFieldsSqlMap());
    $method = TableRegistry::makeMethod($gameUpdater->gameManager->getGameTable()->getFieldsSqlMap());

    print_r($method);
