<?php

    require '../vendor/autoload.php';

    $config = [
        'mysqlUsername'  => 'root',
        'mysqlPassword'  => 'root',
        'mysqlDbName'    => 'wordpress_game_cn',
        'mysqlHost'      => '127.0.0.1',
        'mysqlPort'      => 3306,
        'imageBaseUrl'   => 'http://dev6084/fit_game/demo/data/',
        'proxy'          => 'http://192.168.0.111:1080',
        'websiteTitle'   => 'HohohoGames',
        'cachePath'      => '../downloadCache',
        'imagesMaxCount' => 15,
        'concurrency'    => 10,
        'retryTimes'     => 8,
        'debug'          => true,
        'redisLogEnable' => false,

        //必须删除 Accept-Encoding: gzip, deflate, br, zstd
        'headerStr'      => <<<AAA
Connection: keep-alive
Cache-Control: max-age=0
sec-ch-ua: "Chromium";v="136", "Google Chrome";v="136", "Not.A/Brand";v="99"
sec-ch-ua-mobile: ?0
sec-ch-ua-full-version: "136.0.7103.114"
sec-ch-ua-arch: "x86"
sec-ch-ua-platform: "Windows"
sec-ch-ua-platform-version: "7.0.0"
sec-ch-ua-model: ""
sec-ch-ua-bitness: "64"
sec-ch-ua-full-version-list: "Chromium";v="136.0.7103.114", "Google Chrome";v="136.0.7103.114", "Not.A/Brand";v="99.0.0.0"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: same-site
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Referer: https://ru.riotpixels.com/
Accept-Language: zh-CN,zh;q=0.9
Cookie: rp_utc_offset=28800; adtech_uid=8c2ae521-18bd-4cab-bbfd-47bf4b6c4324%3Ariotpixels.com; top100_id=t1.2946854.936623344.1744129651923; rp_cdn_tld=net; _ym_uid=1744129653460490950; _ym_d=1744129653; tmr_lvid=0671aa1fdd692ae4fade43b35ec72622; tmr_lvidTS=1744129653411; rp_language_suggestion_skip=1; rp_games-wallpaper.list=matrix; rp_s.bar_menu=1; __utmc=87815244; popdown=0; cf_clearance=__Iv60J7KdHRf89ZI07aAqoJVOXBgl..DprkiyvZQ5c-1748097661-1.2.1.1-9ucRwX8lvosGwy5SDBXSDWxC1EtJlQH.9aKsz94vhllFtmOF8x.zKqdo6kW9YueDJTGXJgu2p5pEsUpuLOw5q_zT2jk6e6w9uRPdWDZHL34UT64yFUpOG5rkKqJs9xpgrXovvKdfJkdKaNVoMPhEXCQRYfwjMcoGg0yostOKyuU7DtMDiEFDXoC9Rh5Cw5npMhVkWJqMn2heAPi57WaJcN53k3Eqg1N4laYp27g0wsNoUK48oxESPY7f2O_JtXotvoKiRZ.KmnYNqsW2ww774suTc7MYD_6iBKF8nYat_Lt8fJ1STRTWj81c3if6zjHdkOu3xdqwoOGeCrLNSPKXSBfRuEPNPKHZXmOFpJZqHbkpUeSt.A425jEAsPhhYawn; _ym_isad=1; rp_cdn_check=1; _ym_visorc=b; cf_clearance=GSMbLQ2mGytgTL5BBq1pVcR9YLFrKW4X9hypd5y_10w-1748097680-1.2.1.1-PxyFy9GAPlQj76d0ADX42Dw487GJ1ASWonPgjPvU1faqmQ.EfbcvqqEC9fH3ColiItbYgZWXe_83F_xlc2UPL05NbvFmxOwYz4cqI1wb_XEphMPLoUZZdjUu__oAoxYwFJcyZi9ru1y.OdB7rsJc_wyjBKNNl05Fn.n0.X9pUgIerYZgQjMyRvWql0wy3NivWTL7LP1ykPNYLp.k7ZxKNyWQEB5IemNUs_.Ykj8YjKrx72Ir2aKtkazE8OdvzlhRlo1ZkqDiOoqlMgWzQtU4FZLOjcpaXrZhvzNT8daClq02tPdH322FfBvFtqvyM3rjiBIGDT0cTAdYBClyFcR.vl_aIZwYNXthvC1EmAeZIqG0jmkBxx2HwPAJAWsiOd6N; rp_session=eyIuY3NyZiI6eyIgYiI6Ik5UY3hZMll6WXpGak1EY3dOMlpqWm1ZNU5tRTJaVFptWW1ZM1ptWm1PREE9In19.GxNsMw.FXG6hXZaPKzxV7PrWRbzuqJrNFg; __utma=87815244.1658804745.1744198846.1747982955.1748097688.23; __utmz=87815244.1748097688.23.3.utmcsr=ru.riotpixels.com|utmccn=(referral)|utmcmd=referral|utmcct=/; __utmt=1; __utmb=87815244.6.5.1748097688; t3_sid_2946854=s1.835012028.1748097631402.1748097687644.28.7.5.1; tmr_detect=1%7C1748097687702; domain_sid=56qUwdNXEeDSr3mdT2Fvz%3A1748097687916

AAA,
        "infoUrlMap" => [
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
        ],
    ];

    $updater = new \Coco\fitDownloader\GameUpdater($config);