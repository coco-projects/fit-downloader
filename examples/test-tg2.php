<?php

    require './common.php';

// ========== 配置 ==========
    //8056835067:AAFIqZ8feQtKgw_m0_ADnHswh_eYRgqfsw8
    $botToken  = '8056835067:AAFIqZ8feQtKgw_m0_ADnHswh_eYRgqfsw8';
    $chatId    = '5314592797';             // 可以是用户ID、群组ID、频道ID
    $imagePath = 'data/1.png';             // 本地图片完整路径

// ==========================

   $res= $gameUpdater->tgManager->sendImageMessage(
        [
            'data/c/2026/09-22/0c/6942184606842.jpg',
            'data/c/2026/09-22/c3/6328385095059.jpg',
        ],
        $chatId,
        $botToken,
        'tests'

    );
/*
 *
Array
(
    [data/c/2026/09-22/0c/6942184606842.jpg] => AgACAgUAAxUHarUXsl7eqCbViTE7mAEsXcdQ3AYAAvISaxtzYahVQMtonbuyPwoBAAMCAAN4AAM9BA
    [data/c/2026/09-22/c3/6328385095059.jpg] => AgACAgUAAxUHarUXsuqx_hr80ZbuYmTW4yO2QXgAAvESaxtzYahVpbCIQrdvIOUBAAMCAAN4AAM9BA
)
 * */
   print_r($res);