<?php

//    https://en.riotpixels.com/search/two?bust=1745828493521.3901

    require '../vendor/autoload.php';

    $headerStr = <<<AAA
sec-ch-ua: "Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"
sec-ch-ua-mobile: ?0
sec-ch-ua-full-version: "142.0.7444.59"
sec-ch-ua-arch: "x86"
sec-ch-ua-platform: "Windows"
sec-ch-ua-platform-version: "7.0.0"
sec-ch-ua-model: ""
sec-ch-ua-bitness: "64"
sec-ch-ua-full-version-list: "Chromium";v="142.0.7444.59", "Google Chrome";v="142.0.7444.59", "Not_A Brand";v="99.0.0.0"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: none
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Accept-Encoding: gzip, deflate, br, zstd
Accept-Language: zh-CN,zh;q=0.9
Cookie: rp_utc_offset=28800; adtech_uid=8c2ae521-18bd-4cab-bbfd-47bf4b6c4324%3Ariotpixels.com; top100_id=t1.2946854.936623344.1744129651923; rp_cdn_tld=net; _ym_uid=1744129653460490950; tmr_lvid=0671aa1fdd692ae4fade43b35ec72622; tmr_lvidTS=1744129653411; rp_language_suggestion_skip=1; rp_games-wallpaper.list=matrix; rp_s.bar_menu=1; cf_clearance=GSMbLQ2mGytgTL5BBq1pVcR9YLFrKW4X9hypd5y_10w-1748097680-1.2.1.1-PxyFy9GAPlQj76d0ADX42Dw487GJ1ASWonPgjPvU1faqmQ.EfbcvqqEC9fH3ColiItbYgZWXe_83F_xlc2UPL05NbvFmxOwYz4cqI1wb_XEphMPLoUZZdjUu__oAoxYwFJcyZi9ru1y.OdB7rsJc_wyjBKNNl05Fn.n0.X9pUgIerYZgQjMyRvWql0wy3NivWTL7LP1ykPNYLp.k7ZxKNyWQEB5IemNUs_.Ykj8YjKrx72Ir2aKtkazE8OdvzlhRlo1ZkqDiOoqlMgWzQtU4FZLOjcpaXrZhvzNT8daClq02tPdH322FfBvFtqvyM3rjiBIGDT0cTAdYBClyFcR.vl_aIZwYNXthvC1EmAeZIqG0jmkBxx2HwPAJAWsiOd6N; __utmz=87815244.1754276319.32.4.utmcsr=ru.riotpixels.com|utmccn=(referral)|utmcmd=referral|utmcct=/; _ym_d=1760018162; rp_cdn_check=1; __utmc=87815244; domain_sid=56qUwdNXEeDSr3mdT2Fvz%3A1762056133537; perf_dv6Tr4n=1; popdown=0; __utma=87815244.1658804745.1744198846.1762095992.1762134778.52; __utmt=1; _ym_isad=1; _ym_visorc=w; cf_clearance=Rzs13Ax.R9amlU93InBytokkxpnLp7daFgEY.5uaPN0-1762134972-1.2.1.1-gTO0LnV7b6WXnG6MgDdgfAgVWPYcO2GFWp67lmt.j_EEtzi.Qg95GwWEsHKJlSeczV89NwHkiM8Fe2eflmHMvt_K00ElAHR5czLfv0V83WY7QC1R09sxBW7ZEYrtU53HmmnZOjwkOEHhlTvUTBqkHyOpA2tJqrJf8c66GQECXRh.yZFqMQrNhwcbaSWPU8i_MUI_J8uFipPMniMY8He5B3qmOqXAERa4M0ltEsfOkWmy82HW3FOY0KeTEAL_ws01; rp_session=eyIuY3NyZiI6eyIgYiI6IlptUmlabU16WVRrMk9UQXhaRGRrWTJFek56YzNZbUl6WkdWa056SXpaakE9In19.G-mdPQ.0_Oo4eVnFYDcseFzCxPzOWzY8j4; __utmb=87815244.12.8.1762134855765; t3_sid_2946854=s1.1866110303.1762134778656.1762134855835.22.6.4.1..; tmr_detect=0%7C1762134858550

AAA;

    $config = [
        'mysqlUsername'  => 'root',
        'mysqlPassword'  => 'root',
        //                'mysqlDbName'    => 'wordpress_game_cn',
        'mysqlDbName'    => 'wordpress_game_en',
        'mysqlHost'      => '127.0.0.1',
        'mysqlPort'      => 3306,
        'imageBaseUrl'   => 'http://dev6084/fit_game/demo/data/',
        'debug'          => true,


        //clash必须开全局模式以保持稳定
        'proxy'          => 'http://192.168.0.111:1080',
        'websiteTitle'   => 'HohohoGames',
        'cachePath'      => '../downloadCache',
        'imagesMaxCount' => 15,
        'concurrency'    => 20,
        'retryTimes'     => 18,
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

            "https://en.riotpixels.com/games/ravens-cry"     => "https://en.riotpixels.com/games/vendetta-curse-of-ravens-cry/",
            "https://en.riotpixels.com/games/formula-fusion" => "https://en.riotpixels.com/games/pacer/",
        ],
    ];

    $gameUpdater = new \Coco\fitDownloader\GameUpdater($config);

    $imagePath = '/var/game-images/';
//    $imagePath = 'data';

    $wait = 2;
    //1,跑 demo1
    //2,跑 demo2
    //3,同时跑 demo3,demo4
    //4,demo3 可跑 demo5
    //5,demo4 可跑 demo6
