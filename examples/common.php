<?php

//    https://en.riotpixels.com/search/two?bust=1745828493521.3901

    require '../vendor/autoload.php';

    $headerStr = <<<AAA
sec-ch-ua: "Chromium";v="154", "Google Chrome";v="154", "Not A(Brand";v="99"
sec-ch-ua-mobile: ?0
sec-ch-ua-full-version: "154.0.8037.99"
sec-ch-ua-arch: "x86"
sec-ch-ua-platform: "Windows"
sec-ch-ua-platform-version: "7.0.0"
sec-ch-ua-model: ""
sec-ch-ua-bitness: "64"
sec-ch-ua-full-version-list: "Chromium";v="154.0.8037.99", "Google Chrome";v="154.0.8037.99", "Not A(Brand";v="99.0.0.0"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: none
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Accept-Language: zh-CN,zh;q=0.9
Cookie: tmr_lvid=d7a506bbf84ba76dd79742d05a41717f; tmr_lvidTS=1770020759034; _ym_uid=1770020759755697874; _ym_d=1786286890; rp_utc_offset=28800; rp_cdn_tld=net; adtech_uid=97d238f4-61ee-4858-a042-41d18e6054ae%3Ariotpixels.com; top100_id=t1.2946854.435900933.1786286895058; rp_language_suggestion_skip=1; __utmz=87815244.1790090320.4.2.utmcsr=ru.riotpixels.com|utmccn=(referral)|utmcmd=referral|utmcct=/; rp_session=eyIuY3NyZiI6eyIgYiI6Ill6SmlPVGd4TnpVMU9HTXdOV0l6TnpWa01UZ3hZakJtWldVNVpUZzJNRFk9In19.HafiiQ.kQXFzEKvwy0Dj19-_9uRIDOpbAM; popdown=0; __utma=87815244.393941936.1786286926.1790090320.1791381692.5; __utmc=87815244; __utmt=1; cf_clearance=HhqrZ2wE8vBX6bxd2z3AH21jwiYDqLoI7QxU.2.CRQQ-1791381779-1.2.1.1-fnNn8WHOyRAVZI_J5ijFIH26Pb6mDm0e6S5MO0RR_2XIadpErur2xEOfzCgzGq9F4gbvz7THZanmJu_BN_8RNuWza0t6fS9fUw5VR6T5G.RJyT2lBLPjWLaO4tKDiWLV1xu8P608S_MIW_jPdklwWRL.nP8gM6w.jjFHGRVwKxHKhsiVT2jfkqx5iQm7dLeNAT8pA.6X3b2uGJbmuybphMmGuMiaPcF5QYvxeCgHAfC3oD17LKwyVSWzKvW5wE9z0U.oLWy4Ew0C9VSMMh3FGAeOqQMGoeCl7h0sRPGctSdiKQLzXpZtz.rxDjYhPrh6MG_JrkU1crW2Ul1t6mdi5Pro2Fr4d5Dz6Hgm6cMmjCaPJB7DWpRtIczqLkgRDjX4PBw1yBJyx54p97o1aXXWGHQrMMCgnTFEtmBlQlQFN7Eug_GRWwLBZxytZDQfZA9up2kkxLsuQOUc37M7Sfk_5ASLcio0KrxZ6Irq3d.zEm.X.O46UJ2in4Pn2hQURHp1aT.rvsuvjMCO9Wvzq_64lQ; rp_cdn_check=1; tmr_detect=1%7C1791381696986; perf_dv6Tr4n=1; _ym_isad=1; _ym_visorc=b; __utmb=87815244.4.9.1791381704706; t3_sid_2946854=s1.2013214073.1791381699482.1791381714483.5.3.1.1...0


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
