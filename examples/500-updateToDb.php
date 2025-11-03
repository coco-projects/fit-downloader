<?php

    require './common.php';

    $gameUpdater->updateToDb(function($post) use ($gameUpdater) {

        $gameTable = $gameUpdater->gameManager->getGameTable();

        $tags          = explode(',', $post[$gameTable->getTagsField()]);
        $downloadLinks = json_decode($post[$gameTable->getDownloadLinksField()], 1);

        // ------------------------------------------------------
        // 没下载链接的
        $hasDownloadLink = false;
        foreach ($downloadLinks as $k => $v)
        {
            if (count($v))
            {
                $hasDownloadLink = true;
                break;
            }
        }

        if (!$hasDownloadLink)
        {
            return 0;
        }

        // ------------------------------------------------------
        //指定标签的
        if (in_array(' Adult', $tags))
        {
            return rand(8, 15);
        }

        // ------------------------------------------------------
        //根据文件大小给价格
        //超过这个大小就收费
        $baseSize = $gameUpdater::convertToBytes('4G');

        //文件大小
        $size     = preg_replace('/([\d.,]+)\s*([MG])/imu', '$1$2', $post[$gameTable->getOriginalSizeField()]);
        $byteSize = $gameUpdater::convertToBytes($size);

        //如果文件大于指定
        if ($byteSize > $baseSize)
        {
            //指定概率生成随机价格
            if (rand(1, 100) <= 80)
            {
                return rand(6, 10);
            }
        }

        // ------------------------------------------------------
        return 0;
    });
