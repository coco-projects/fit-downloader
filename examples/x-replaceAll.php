<?php

    require './common.php';

    $replace = [

//        "dev6084"        => "dev6085",
//        "http://dev6084" => "http://dev6085",
//        "/var/www/6084/" => "/var/www/6085/",

//        "dev6084"        => "www.xxx.com",
//        "http://dev6084" => "https://www.xxx.com",
//        "/var/www/6084/" => "/www/wwwroot/www.xxx.com/",

"www.xxx.com"               => "dev6084",
"https://www.xxx.com"       => "http://dev6084",
"/www/wwwroot/www.xxx.com/" => "/var/www/6084/",

    ];

    $gameUpdater->wpManager->replaceAll($replace);
