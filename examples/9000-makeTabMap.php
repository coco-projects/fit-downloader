<?php

    use Coco\tableManager\TableRegistry;

    require './common.php';

    $sql = <<<'SQL'

CREATE TABLE `wp_game_images` (
  `id` BIGINT (10) UNSIGNED NOT NULL,
  `game_id` BIGINT (10) UNSIGNED NOT NULL COMMENT '游戏表id',
  `path` VARCHAR (60000) COLLATE utf8mb4_unicode_ci COMMENT '图片路径',
  `type` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '0:Screenshot，1:Game Cover，2:Game Illustration，',
  `add_time` INT (10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE = INNODB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '' ;

SQL;

    $arrDefine = TableRegistry::makeFieldsSqlMap($sql);

    print_r($arrDefine);
