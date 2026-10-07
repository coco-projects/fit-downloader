<?php

//    https://en.riotpixels.com/search/two?bust=1745828493521.3901

    require '../vendor/autoload.php';

    $headerStr = <<<AAA
sec-ch-ua: "Google Chrome";v="153", "Not_A Brand";v="8", "Chromium";v="153"
sec-ch-ua-mobile: ?0
sec-ch-ua-full-version: "153.0.8010.53"
sec-ch-ua-arch: "x86"
sec-ch-ua-platform: "Windows"
sec-ch-ua-platform-version: "7.0.0"
sec-ch-ua-model: ""
sec-ch-ua-bitness: "64"
sec-ch-ua-full-version-list: "Google Chrome";v="153.0.8010.53", "Not_A Brand";v="8.0.0.0", "Chromium";v="153.0.8010.53"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: same-site
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Referer: https://ru.riotpixels.com/
Accept-Language: zh-CN,zh;q=0.9
Cookie: tmr_lvid=d7a506bbf84ba76dd79742d05a41717f; tmr_lvidTS=1770020759034; _ym_uid=1770020759755697874; _ym_d=1786286890; rp_utc_offset=28800; rp_cdn_tld=net; adtech_uid=97d238f4-61ee-4858-a042-41d18e6054ae%3Ariotpixels.com; top100_id=t1.2946854.435900933.1786286895058; perf_dv6Tr4n=1; __utmc=87815244; rp_language_suggestion_skip=1; popdown=0; _ym_visorc=b; rp_cdn_check=1; _ym_isad=1; cf_clearance=gSgRfoIENXo2erG8t_2hmZAu2hzrZfAnRhjWsxYu3x0-1790090328-1.2.1.1-4TuTMoiXNf7r1hq7ybfONv3LDeNWSY8QWxJGMsF26xtODB8g6ZyjJRSEI8p_AdR7fUvpGh0IJWMQlw8mwBeBoe4Aix3Aq0AnUef7OvVNMLb5cyunDoVfxZVm0EGj3kQ6P3yxe._Xt8HVjda0bx9TxI_FwBm9A_WTnYL53buZUwQfXjZcaHMBVoiOu6p_5SDRzJ_tY1pslPIIXsDMDnyPlDalzYNY8uqAlnb2FIVo1jTbc.ZAlqrmLEl3gzD6OXeId336G46YYV5sf5jUohiz8haB4vm1pagwH34dvR0N4JAT1Uw0vab_K9RKg4UIcer8mtFfnKTE0h.qlXEm1lBffaEj6cUJEwd.bKttOft.0LSOBA63u8CALYjWwb4NY9wl5RRtU8l7Yt8run4u7xdK..Zjh.FRcgQR56qDqlwmelEf21HkGu.IVtHPWEZv26s1ZT1hDP2LBxKgFzDy1RXIg34QPMQ1dB.wCBlaEpBAU5PQ_O6nDvn5aY0neUXp9NxQ; rp_session=eyIuY3NyZiI6eyIgYiI6IlpURXhaak0wTlRZd1pqUTNORGN5TVRnNE5XWmlNVFkyT1RJeE4yVmlaVGM9In19.HZQt5Q.mS_NLWYcOsj4bmTKVmZ2-zf-5VQ; __utma=87815244.393941936.1786286926.1789531037.1790090320.4; __utmz=87815244.1790090320.4.2.utmcsr=ru.riotpixels.com|utmccn=(referral)|utmcmd=referral|utmcct=/; __utmt=1; __utmb=87815244.2.9.1790090320; t3_sid_2946854=s1.729512012.1790090183153.1790090320514.4.6.4.1...0; domain_sid=o-ik46k06TFCSp-McQWN9%3A1790090322758; tmr_detect=0%7C1790090323332

AAA;

    $config = [
        /**********************************/
        'mysqlUsername'       => 'root',
        'mysqlPassword'       => 'root',
        'mysqlDbName'         => 'wordpress_hohoho',
        //        'mysqlDbName'   => 'wordpress_game_cn_translate',
        'mysqlHost'           => '127.0.0.1',
        'mysqlPort'           => 3306,

        /**********************************/
        'postTgBotToken'      => '8056835067:AAFIqZ8feQtKgw_m0_ADnHswh_eYRgqfsw8',
        'postTgChatId'        => 5314592797,
        'processPath'         => './process',
        'postTgSleepMin'      => 6,
        'postTgSleepMax'      => 10,

        /**********************************/
        'backupImageBotToken' => '8056835067:AAFIqZ8feQtKgw_m0_ADnHswh_eYRgqfsw8',
        'backupImageChatId'   => [
            5314592797,
        ],
        'backupImageSleepMin' => 6,
        'backupImageSleepMax' => 10,

        /**********************************/
        'cachePath'           => './downloadCache',

        'imageBaseUrl'        => 'https://static.hohohogames.com/game-images/',
        'imagePath'           => '/var/game-images',

//        'imageBaseUrl'        => 'http://dev6026/coco-fitDownloader/examples/data/',
//        'imagePath'           => 'data',

        'mainSite' => 'https://www.hohohogames.com/',

        'debug'          => true,
        'concurrency'    => 5,
        'redisLogEnable' => true,
        'proxy'          => 'http://192.168.0.111:1080',
        'websiteTitle'   => 'HohohoGames',
        'headerStr'      => $headerStr,
        "infoUrlMap"     => [
            "https://en.riotpixels.com/games/aliens-vs-predator-2010"                => "https://en.riotpixels.com/games/aliens-vs-predator/",
            "https://en.riotpixels.com/games/vanquish-2010"                          => "https://en.riotpixels.com/games/vanquish-ii-2010/",
            "https://en.riotpixels.com/games/oddworld-abes-oddysee-new-n-tasty"      => "https://en.riotpixels.com/games/oddworld-new-n-tasty/",
            "https://en.riotpixels.com/games/spintires-mudrunner-american-wilds"     => "https://en.riotpixels.com/games/mudrunner-american-wilds/",
            "https://en.riotpixels.com/games/heavy-duty-challenge"                   => "https://en.riotpixels.com/games/offroad-truck-simulator-heavy-duty-challenge/",
            "https://en.riotpixels.com/games/call-of-duty-modern-warfare-2"          => "https://en.riotpixels.com/games/call-of-duty-modern-warfare-2-i-2009/",
            "https://en.riotpixels.com/games/golf-club-2019"                         => "https://en.riotpixels.com/games/golf-club-2019-featuring-pga-tour/",
            "https://en.riotpixels.com/games/bavarian-tale-totgeschwiegen"           => "https://en.riotpixels.com/games/inspector-schmidt-a-bavarian-tale/",
            "https://en.riotpixels.com/games/uncertain-episode-1-the-last-quiet-day" => "https://en.riotpixels.com/games/uncertain-last-quiet-day/",
            "https://en.riotpixels.com/games/doom-ii-2016"                           => "https://en.riotpixels.com/games/doom-iii-2016/",
            "https://en.riotpixels.com/games/raiders-of-the-broken-planet"           => "https://en.riotpixels.com/games/spacelords/",
            "https://en.riotpixels.com/games/crookz"                                 => "https://en.riotpixels.com/games/crookz-the-big-heist/",
            "https://en.riotpixels.com/games/resident-evil-4"                        => "https://en.riotpixels.com/games/resident-evil-4-i-2005/",
            "https://en.riotpixels.com/games/lumote"                                 => "https://en.riotpixels.com/games/lumote-the-mastermote-chronicles/",
            "https://en.riotpixels.com/games/rad-rodgers-world-one"                  => "https://en.riotpixels.com/games/rad-rodgers/",
            "https://en.riotpixels.com/games/snowrunner-a-mudrunner-game"            => "https://en.riotpixels.com/games/snowrunner/",
            "https://en.riotpixels.com/games/tales-of-graces-f"                      => "https://en.riotpixels.com/games/tales-of-graces/",
            "https://en.riotpixels.com/games/bulwark-falconeer-chronicles"           => "https://en.riotpixels.com/games/bulwark-evolution-falconeer-chronicles/",

            "https://en.riotpixels.com/games/ravens-cry"                     => "https://en.riotpixels.com/games/vendetta-curse-of-ravens-cry/",
            "https://en.riotpixels.com/games/formula-fusion"                 => "https://en.riotpixels.com/games/pacer/",
            "https://en.riotpixels.com/games/gold-rush-the-game/screenshots" => "https://en.riotpixels.com/games/gold-mining-simulator/screenshots/",
        ],
    ];

    $gameUpdater = new \Coco\fitDownloader\GameUpdater($config);
    $gameUpdater->setLangEn();

//    $imagePath = '/var/game-images/';
    $imagePath = 'data';

    $wait = 2;
    //1,跑 demo1
    //2,跑 demo2
    //3,同时跑 demo3,demo4
    //4,demo3 可跑 demo5
    //5,demo4 可跑 demo6
