<?php

    namespace Coco\fitDownloader;

    use Coco\wp\ArticleContent;
    use Coco\wp\Manager;
    use Coco\wp\Tag;
    use Coco\wp\WpTag;
    use Coco\simplePageDownloader\Downloader;
    use Spatie\Image\Image;
    use Spatie\ImageOptimizer\OptimizerChainFactory;
    use GuzzleHttp\Exception\ConnectException;
    use GuzzleHttp\Exception\RequestException;
    use Psr\Http\Message\ResponseInterface;
    use Symfony\Component\DomCrawler\Crawler;

    class GameUpdater
    {
        public string $mysqlUsername = 'root';
        public string $mysqlPassword = 'root';
        public string $mysqlDbName   = 'wordpress_game_cn';
        public string $mysqlHost     = '127.0.0.1';
        public int    $mysqlPort     = 3306;

        public string $imageBaseUrl   = '';
        public bool   $debug          = true;
        public string $proxy          = '';
        public string $websiteTitle   = 'Games';
        public string $cachePath      = '../downloadCache';
        public int    $imagesMaxCount = 15;
        public int    $concurrency    = 10;
        public int    $retryTimes     = 8;

        // 复制过来的头删除这个
        // Accept-Encoding: gzip, deflate, br, zstd
        public string $headerStr      = '';
        public string $redisLogName;
        public bool   $redisLogEnable = false;
        public int    $redisDbIndex   = 11;
        public array  $infoUrlMap     = [];

        /**********************************************************************************/
        public Manager     $wpManager;
        public GameManager $gameManager;


        public function __construct(array $config = [])
        {
            foreach ($config as $k => $v)
            {
                if (isset($this->$k))
                {
                    $this->$k = $v;
                }
            }

            $this->redisLogName = $this->mysqlDbName . ':redis_log-';

            Downloader::initClientConfig([
                'timeout' => 10.0,
                'verify'  => false,
                'debug'   => false,
                'proxy'   => $this->proxy,
            ]);

            Downloader::initLogger('download_log', $this->debug, $this->redisLogEnable);
            ini_set('memory_limit', '512M');

            Downloader::setRedis(db: $this->redisDbIndex);

            $this->initWpManager();
            $this->initGameManager();
        }

        public function initWpManager(): void
        {
            $manager = new Manager($this->redisLogName);
            $manager->setRedisConfig('127.0.0.1', '', 6379, $this->redisDbIndex);
            $manager->setMysqlConfig($this->mysqlDbName, $this->mysqlHost, $this->mysqlUsername, $this->mysqlPassword, $this->mysqlPort);

            $manager->setEnableRedisLog($this->redisLogEnable);
            $manager->setEnableEchoLog($this->debug);
            $manager->initServer();
            $manager->initTableStruct();

            $this->wpManager = $manager;
        }

        public function initGameManager(): void
        {
            $manager = new GameManager($this->redisLogName);
            $manager->setRedisConfig('127.0.0.1', '', 6379, $this->redisDbIndex);
            $manager->setMysqlConfig($this->mysqlDbName, $this->mysqlHost, $this->mysqlUsername, $this->mysqlPassword, $this->mysqlPort);

            $manager->setEnableRedisLog($this->redisLogEnable);
            $manager->setEnableEchoLog($this->debug);
            $manager->initServer();
            $manager->initTableStruct();

            $this->gameManager = $manager;
        }

        const IMAGE_STATUS_0 = 0;
        const IMAGE_STATUS_1 = 1;
        const IMAGE_STATUS_2 = 2;

        const IMAGE_TYPE_SCREENSHOT = 0;
        const IMAGE_TYPE_WALLPAPERS = 1;
        const IMAGE_TYPE_ARTWORKS   = 2;

        /**********************************************************************************/
        // fit
        /**********************************************************************************/

        public function downloadArchives($archives): void
        {
            $ins = Downloader::ins();
            $ins->setRetryTimes($this->retryTimes);
            $ins->setEnableCache(true);
            $ins->setCachePath($this->cachePath);
            $ins->baseCacheStrategy();
            $ins->setConcurrency($this->concurrency);

            foreach ($archives as $archive_url_apge1)
            {
                //得到第1页url
                $archive_url_apge1 = preg_replace('%page/\d+/?%im', "", $archive_url_apge1);

                $this->gameManager->getMysqlClient()->logInfo('第一页: ' . $archive_url_apge1);
                //得到最高页数
                $countPage = 1;

                $ins->setSuccessCallback(function(string $contents, Downloader $_this, ResponseInterface $response, $index) use (&$countPage) {
                    preg_match_all('%<a class="page-numbers" href="https://fitgirl-repacks.site/[^"]+">(\d+)</a>%iu', $contents, $matches);

                    if (count($matches[1]))
                    {
                        $countPage = max($matches[1]);
                    }
                });

                $ins->setErrorCallback(function(RequestException $e, Downloader $_this, $index) {
                    $_this->logInfo('出错：' . $e->getMessage());
                });

                $ins->addBatchRequest($archive_url_apge1, 'get', [
                    "proxy" => $this->proxy,
                ]);
                $ins->send();

                $this->gameManager->getMysqlClient()->logInfo('总页数: ' . $countPage);

                $pages = [$archive_url_apge1];
                for ($i = 2; $i <= $countPage; $i++)
                {
                    $pages[] = $archive_url_apge1 . "page/{$i}/";
                }
                $pages = array_reverse($pages);

                foreach ($pages as $k => $url)
                {
                    $ins->setSuccessCallback(function(string $contents, Downloader $_this, ResponseInterface $response, $index) {

                        $gameTable = $this->gameManager->getGameTable();

                        $doms = static::filterHtml($contents, '.type-post');
                        $doms = array_reverse($doms);

                        foreach ($doms as $k => $v)
                        {
                            $html = preg_replace('%<style>[\S\s]*?</style>%im', '', $v);

                            if (!static::isGamePublish($html))
                            {
                                continue;
                            }

                            $result = self::parseItem($html);
                            $id_num = $result['id_num'];
                            unset($result['id_num']);

                            $result['download_links'] = json_encode($result['download_links'], 256);
                            $result['updates_links']  = json_encode($result['updates_links'], 256);
                            $result['website_links']  = json_encode($result['website_links'], 256);

                            $isInserted = $gameTable->tableIns()
                                ->where($gameTable->getNameField(), '=', $result['name'])->find();

                            if (!$isInserted)
                            {
                                $result[$gameTable->getPkField()] = $gameTable->calcPk();

                                $this->gameManager->getGameTable()->tableIns()->insert($result);
                                $this->gameManager->getMysqlClient()
                                    ->logInfo('【O】[' . $id_num . ']数据写入成功: ' . $result['name']);
                            }
                            else
                            {
                                $this->gameManager->getMysqlClient()
                                    ->logInfo('【X】[' . $id_num . ']当前页面已经写入过: ' . $result['name']);
                            }
                        }

                        $this->gameManager->getMysqlClient()->logInfo('');
                    });

                    $ins->setErrorCallback(function(RequestException|ConnectException $e, Downloader $_this, $index) {
                        $this->gameManager->getMysqlClient()->logInfo('出错: ' . $e->getMessage());
                        $this->gameManager->getMysqlClient()->logInfo('');

                    });

                    $ins->addBatchRequest($url, 'get', [
                        "proxy" => $this->proxy,
                    ]);

                    $ins->send();
                }

                $this->gameManager->getMysqlClient()->logInfo('当前月份采集完成: ' . $countPage);
            }
        }

        protected function parseItem(string $html): array
        {
            $commonField = $this->parseItemCommonField($html);
            $detailField = $this->parseItemDetailField($html);

            $result = [//"raw_html" => $html,
            ];

            return array_merge($result, $commonField, $detailField);
        }

        protected function parseItemCommonField(string $html): array
        {
            $crawler = new Crawler($html);

            try
            {
                $name = $crawler->filter('.entry-title a')->first()->innerText();
            }
            catch (\Exception $exception)
            {
                $name = '';
                $this->gameManager->getMysqlClient()->logError('出错: ' . $exception->getMessage());
            }

            try
            {
                $fitgirl_url = $crawler->filter('.entry-title a')->first()->attr('href');
            }
            catch (\Exception $exception)
            {
                $fitgirl_url = '';
                $this->gameManager->getMysqlClient()
                    ->logError('出错: ' . "[$name][fitgirl_url]" . $exception->getMessage());
            }

            try
            {
                $cover_link = $crawler->filter('.entry-content a img')->first()->attr('src');
                $cover_link = strtr($cover_link, [
                    'http:' => 'https:',
                ]);
            }
            catch (\Exception $exception)
            {
                $cover_link = '';
                $this->gameManager->getMysqlClient()
                    ->logError('出错: ' . "[$name][cover_link]" . $exception->getMessage());
            }

            try
            {
                $dateTime = \DateTime::createFromFormat('d/m/Y', $crawler->filter('.entry-date')->first()->text());

                $fitgirl_publish_time = $dateTime->getTimestamp();
            }
            catch (\Exception $exception)
            {
                $fitgirl_publish_time = 0;
                $this->gameManager->getMysqlClient()
                    ->logError('出错: ' . "[$name][$fitgirl_url][fitgirl_publish_time]" . $exception->getMessage());
            }

            try
            {
                $info_url = '';
                preg_match('%https?://[\da-z]+.riotpixels.com/games/([^/]+)%im', $html, $matches);
                if (isset($matches[1]))
                {
                    $info_url = 'https://en.riotpixels.com/games/' . $matches[1];

                    if (isset($this->infoUrlMap[$info_url]))
                    {
                        $info_url = trim($this->infoUrlMap[$info_url], '/\\');
                    }
                }
            }
            catch (\Exception $exception)
            {
                $info_url = '';
                $this->gameManager->getMysqlClient()
                    ->logError('出错: ' . "[$name][$fitgirl_url][info_url]" . $exception->getMessage());
            }

            try
            {
                $id_num = '';
                preg_match('%#339966;">#(\d+)%im', $html, $matches);
                if (isset($matches[1]))
                {
                    $id_num = $matches[1];
                }
            }
            catch (\Exception $exception)
            {
                $info_url = '';
                $this->gameManager->getMysqlClient()
                    ->logError('出错: ' . "[$name][$fitgirl_url][id_num]" . $exception->getMessage());
            }

            try
            {
                $discussion_url = '';
                preg_match('%https://cs\.rin\.ru/forum/viewtopic\.php[^"]+%im', $html, $matches);
                if (isset ($matches[0]))
                {
                    $discussion_url = html_entity_decode($matches[0]);
                }
            }
            catch (\Exception $exception)
            {
                $info_url = '';
                $this->gameManager->getMysqlClient()
                    ->logError('出错: ' . "[$name][$fitgirl_url][discussion_url]" . $exception->getMessage());
            }

            $result = [
                "name"                 => $name ?? '',
                "fitgirl_url"          => $fitgirl_url ?? '',
                "fitgirl_publish_time" => $fitgirl_publish_time ?? '',
                "info_url"             => $info_url ?? '',
                "cover_link"           => $cover_link,
                "id_num"               => (int)$id_num,
                "discussion_url"       => $discussion_url,
            ];

            return $result;
        }

        protected function parseItemDetailField(string $html): array
        {
            $doms = static::filterHtml($html, '.entry-content');
            if (!isset($doms[0]))
            {
                return [];
            }

            $result = [
                "tags"           => "",
                "company"        => "",
                "lang"           => "",
                "original_size"  => "",
                "repack_size"    => "",
                "features"       => "",
                "description"    => "",
                "1337x_url"      => "",
                "download_links" => "",
                "website_links"  => [],
                "updates_links"  => "",
            ];

            $doms = $doms[0];
            $arr  = explode('<h3>', $doms);
            array_shift($arr);

            $download_links = [
                "magnet"      => [],
                "datanodes"   => [],
                "fuckingfast" => [],
                "filecrypt"   => [],
            ];

            $updates_links = [];

            foreach ($arr as $k => $v)
            {
                //文件大小等相关元数据
                if (str_contains($v, '#339966'))
                {
                    $t = preg_split('#<br>|</a>#', $v);
                    foreach ($t as $v1)
                    {
                        // Companies: <strong>Saber Interactive, Focus Home Interactive</strong>
                        // Company: <strong>Saber Interactive, Focus Home Interactive</strong>
                        if (preg_match('#^Compan#iu', trim($v1)))
                        {
                            preg_match('%<strong>([^<]+)</strong>%im', $v1, $matches);
                            if (isset($matches[1]))
                            {
                                $result['company'] = html_entity_decode($matches[1]);
                            }
                        }

                        // Languages: <strong>RUS/ENG/MULTI13</strong>
                        if (preg_match('#^Lang#iu', trim($v1)))
                        {
                            preg_match('%<strong>([^<]+)</strong>%im', $v1, $matches);
                            if (isset($matches[1]))
                            {
                                $result['lang'] = $matches[1];
                            }
                        }

                        // Original Size: <strong>44.1 GB</strong>
                        if (preg_match('#^Original#iu', trim($v1)))
                        {
                            preg_match('%<strong>([^<]+)</strong>%im', $v1, $matches);
                            if (isset($matches[1]))
                            {
                                $result['original_size'] = $matches[1];
                            }
                        }

                        // Repack Size: <strong>30/30.7 GB</strong></p>
                        if (preg_match('#^Repack#iu', trim($v1)))
                        {
                            preg_match('%<strong>([^<]+)</strong>%im', $v1, $matches);
                            if (isset($matches[1]))
                            {
                                $result['repack_size'] = $matches[1];
                            }
                        }
                    }
                }

                if (str_starts_with($v, 'Screenshots'))
                {
                    preg_match('%https?://[\da-z]+.riotpixels.com/games/([^/]+)%im', $v, $matches);
                    if (isset($matches[1]))
                    {
                        $info_url = 'https://en.riotpixels.com/games/' . $matches[1];

                        if (isset($this->infoUrlMap[$info_url]))
                        {
                            $info_url = trim($this->infoUrlMap[$info_url], '/\\');
                        }
                        $result['info_url'] = $info_url;
                    }
                }

                if (str_starts_with($v, 'Download'))
                {
                    preg_match_all('%(?<=href=")magnet:[^"]+%imu', $v, $matches);
                    if (count($matches[0]))
                    {
                        $download_links['magnet'] = array_map('html_entity_decode', $matches[0]);
                    }

                    preg_match_all('%(?<=href=")https://datanodes\.to[^"]+%imu', $v, $matches);
                    if (count($matches[0]))
                    {

                        $links                       = array_map('html_entity_decode', $matches[0]);
                        $download_links['datanodes'] = array_flip(array_flip($links));
                    }

                    preg_match_all('%(?<=href=")https://fuckingfast\.co[^"]+%imu', $v, $matches);
                    if (count($matches[0]))
                    {
                        $links                         = array_map('html_entity_decode', $matches[0]);
                        $download_links['fuckingfast'] = array_flip(array_flip($links));
                    }

                    preg_match_all('%(?<=href=")https://filecrypt\.cc/Container[^"]+%imu', $v, $matches);
                    if (count($matches[0]))
                    {
                        $links                       = array_map('html_entity_decode', $matches[0]);
                        $download_links['filecrypt'] = array_flip(array_flip($links));
                    }

                    preg_match('%(?<=href=")https://1337x\.to[^"]+%imu', $v, $matches);
                    if (isset($matches[0]))
                    {
                        $result['1337x_url'] = $matches[0];
                    }
                }

                if (str_starts_with($v, 'Game Updates'))
                {
                    preg_match_all('%(?<=href=")(https://filecrypt\.cc/Container[^"]+)[^>]+>([^<]+)%imu', $v, $matches, PREG_SET_ORDER);
                    if (count($matches))
                    {
                        foreach ($matches as $v2)
                        {
                            $updates_links[] = [
                                "link" => $v2[1],
                                "name" => $v2[2],
                            ];
                        }
                    }
                    preg_match_all('%(?<=href=")(https://datanodes\.to[^"]+)[^>]+>([^<]+)%imu', $v, $matches, PREG_SET_ORDER);
                    if (count($matches))
                    {
                        foreach ($matches as $v2)
                        {
                            $updates_links[] = [
                                "link" => $v2[1],
                                "name" => $v2[2],
                            ];
                        }
                    }
                    preg_match_all('%(?<=href=")(https://fuckingfast\.co[^"]+)[^>]+>([^<]+)%imu', $v, $matches, PREG_SET_ORDER);
                    if (count($matches))
                    {
                        foreach ($matches as $v2)
                        {
                            $updates_links[] = [
                                "link" => $v2[1],
                                "name" => $v2[2],
                            ];
                        }
                    }
                }

                if (str_starts_with($v, 'Repack Features'))
                {
                    $splitd = preg_split('/(?=<div class="[^>]+su-spoiler-style-fancy[^>]+">)/im', $v, -1, PREG_SPLIT_NO_EMPTY);

                    foreach ($splitd as $v11)
                    {
                        if (str_starts_with($v11, 'Repack Features'))
                        {
                            // Repack Features 下面的ul>li中的内容，数组
                            preg_match_all('%<li>([^<]+)</li>%im', $v11, $matches);

                            $temp = $matches[1];
                            $temp = array_map('trim', $temp);
                            $temp = array_map('html_entity_decode', $temp);

                            $result['features'] = json_encode($temp, 1);
                        }

                        if (str_contains($v11, 'Game Description'))
                        {
                            // Repack Features 的 description
                            $temp = $v11;
                            $doms = static::filterHtml($temp, '.su-spoiler-content');
                            if (!isset($doms[0]))
                            {
                                return [];
                            }

                            $temp = $doms[0];
                            $temp = preg_replace('%(</li>|</p>)%im', "\r\n", $temp);
                            $temp = preg_replace('%(</?[a-z\d]+[^<>]*>)%im', "", $temp);
                            $temp = preg_split("#[\r\n]+#", $temp, -1, \PREG_SPLIT_NO_EMPTY);
                            $temp = array_map('trim', $temp);
                            $temp = array_map('html_entity_decode', $temp);

                            $result['description'] = json_encode($temp, 1);
                        }
                    }
                }
            }

            $result['download_links'] = $download_links;
            $result['updates_links']  = $updates_links;

            return $result;
        }

        protected static function filterHtml($html, $cssSelector): array
        {
            $crawler = new Crawler($html);
            $crawler = $crawler->filter($cssSelector);

            $htmls = [];

            foreach ($crawler as $domElement)
            {
                $htmls[] = $domElement->ownerDocument->saveHTML($domElement);
            }

            return $htmls;
        }

        protected static function isGamePublish(string $html): bool
        {
            return str_contains($html, '#339966;">#');
        }

        /**********************************************************************************/
        // info/screenshot
        /**********************************************************************************/

        public function downloadMainPageMetas(): void
        {
            $gameTable = $this->gameManager->getGameTable();
            $count     = 100;

            $func = function($pages) use ($gameTable) {

                $ins = Downloader::ins();
                $ins->setRetryTimes($this->retryTimes);
                $ins->setEnableCache(true);
                $ins->setCachePath($this->cachePath);
                $ins->baseCacheStrategy();
                $ins->setConcurrency($this->concurrency);
                $ins->setRawHeader($this->headerStr);

                $ins->setSuccessCallback(function(string $contents, Downloader $_this, ResponseInterface $response, $index) use ($pages) {

                    $pageInfo    = $pages[$index];
                    $requestInfo = $_this->getRequestInfoByIndex($index);

                    $gameTable = $this->gameManager->getGameTable();

                    $result = [
                        $gameTable->getCoverLinkFetchStatusField() => static::IMAGE_STATUS_2,
                    ];

                    //头部的网站信息
                    $headerInfo = static::filterHtml($contents, '#articlereleasedata tbody tr');
                    foreach ($headerInfo as $k => $v)
                    {
                        if (preg_match('#<span>Websites?</span>#iu', $v))
                        {
                            preg_match_all('%(?<=href=")https?://[^"]+%imu', $v, $matches);
                            if (isset($matches[0]) && count($matches[0]))
                            {
                                $result[$gameTable->getWebsiteLinksField()] = json_encode($matches[0], 256);
                            }
                        }
                    }

                    //底部的标签部分
                    $headerInfo = static::filterHtml($contents, '#tags_short tbody a');
                    $t          = implode(PHP_EOL, $headerInfo);

                    preg_match_all('%>([^><]+)</a>%imu', $t, $matches);
                    if (isset($matches[1]) && count($matches[1]))
                    {
                        $result[$gameTable->getTagsField()] = implode(',', $matches[1]);
                    }

                    //封面
                    $headerInfo = static::filterHtml($contents, '.cover img');
                    $t          = implode(PHP_EOL, $headerInfo);
                    preg_match('%https?://[^"]+%imu', $t, $matches);
                    if (isset($matches[0]) && ($matches[0]))
                    {
                        $t1 = preg_replace('#\.\d+p\.jpg#', '', $matches[0]);

                        $result[$gameTable->getCoverLinkField()] = strtr($t1, [
                            'http:' => 'https:',
                        ]);
                    }

                    $res = $gameTable->tableIns()
                        ->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])->update($result);

                    if ($res)
                    {
                        $this->gameManager->getMysqlClient()
                            ->logInfo("ID:[{$pageInfo[$gameTable->getPkField()]}] -- : " . '更新成功:' . json_encode($result));
                    }
                    else
                    {
                        $this->gameManager->getMysqlClient()
                            ->logError("ID:[{$pageInfo[$gameTable->getPkField()]}] -- : " . '更新错误');
                    }
                    $this->gameManager->getMysqlClient()->logInfo('');

                });

                $ins->setErrorCallback(function(RequestException $e, Downloader $_this, $index) use ($pages) {
                    $pageInfo    = $pages[$index];
                    $requestInfo = $_this->getRequestInfoByIndex($index);
                    $gameTable   = $this->gameManager->getGameTable();

                    $code = $e->getCode();
                    if (in_array($code, [
                        '403',
                        '404',
                    ]))
                    {
                        $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- 响应【{$code}】[{$requestInfo['url']}]";
                    }
                    else
                    {
                        $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- 响应【{$code}】[{$requestInfo['url']}][{$e->getMessage()}]";
                    }

                    $this->gameManager->getMysqlClient()->logError($msg);
                    $this->gameManager->getMysqlClient()->logInfo('');

                });

                foreach ($pages as $k => $pageInfo)
                {
                    $info_url = $pageInfo[$gameTable->getInfoUrlField()];
                    if ($info_url)
                    {
                        $ins->addBatchRequest($info_url . '/', 'get', [
                            "proxy" => $this->proxy,
                        ]);
                    }
                }

                $ins->send();
            };

            $gameTable->tableIns()->where($gameTable->getCoverLinkFetchStatusField(), '=', static::IMAGE_STATUS_0)
                ->chunk($count, $func, $gameTable->getPkField());
        }

        public function downloadImageMetas(): void
        {
            $gameTable       = $this->gameManager->getGameTable();
            $gameImagesTable = $this->gameManager->getGameImagesTable();
            $count           = 500;

            $func = function($pages) use ($gameTable, $gameImagesTable) {

                foreach ($pages as $k => $pageInfo)
                {
                    $url = $pageInfo[$gameTable->getInfoUrlField()] . '/';

                    $url_screenshots = $url . 'screenshots/';
                    $url_wallpapers  = $url . 'wallpapers/';
                    $url_artworks    = $url . 'artworks/';

                    $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}]";
                    $this->gameManager->getMysqlClient()->logInfo($msg);

                    $data1 = $this->fetchImagesUrl($url_screenshots, static::IMAGE_TYPE_SCREENSHOT, $pageInfo[$gameTable->getPkField()]);
                    $data2 = $this->fetchImagesUrl($url_wallpapers, static::IMAGE_TYPE_WALLPAPERS, $pageInfo[$gameTable->getPkField()]);
                    $data3 = $this->fetchImagesUrl($url_artworks, static::IMAGE_TYPE_ARTWORKS, $pageInfo[$gameTable->getPkField()]);

                    $data = array_merge($data1, $data2, $data3);

                    $this->gameManager->getGameImagesTable()->tableIns()->insertAll($data);

                    $gameTable->tableIns()->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])
                        ->update([
                            $gameTable->getImageFetchStatusField() => static::IMAGE_STATUS_2,
                        ]);

                    $this->gameManager->getMysqlClient()->logInfo('写入完成，共:' . count($data));
                    $this->gameManager->getMysqlClient()->logInfo('');

                }
            };

            $gameTable->tableIns()->where($gameTable->getImageFetchStatusField(), '=', static::IMAGE_STATUS_0)
                ->chunk($count, $func, $gameTable->getPkField());
        }

        public function downloadCoverImages($targetDir = './data/'): void
        {
            $gameTable = $this->gameManager->getGameTable();
            $count     = 500;

            $func = function($pages) use ($gameTable, $targetDir) {
                foreach ($pages as $k => $pageInfo)
                {
                    $origin_url = $pageInfo[$gameTable->getCoverLinkField()];

                    $t   = explode('.', $origin_url);
                    $ext = array_pop($t);

                    $ins = Downloader::ins();
                    $ins->setRetryTimes($this->retryTimes);
                    $ins->setEnableCache(true);
                    $ins->setCachePath($this->cachePath);
                    $ins->baseCacheStrategy();
                    $ins->setConcurrency($this->concurrency);

                    if (str_contains($origin_url, 'riotpixels.net'))
                    {
                        $ins->setRawHeader($this->headerStr);
                        $urls = [
                            $origin_url,
                            $origin_url . '.720p.jpg',
                            $origin_url . '.240p.jpg',
                        ];
                    }
                    else
                    {
                        $ins->setRawHeader(<<<AAA
Connection: keep-alive
Pragma: no-cache
Cache-Control: no-cache
sec-ch-ua-platform: "Windows"
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36
sec-ch-ua: "Chromium";v="136", "Google Chrome";v="136", "Not.A/Brand";v="99"
sec-ch-ua-mobile: ?0
Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8
Sec-Fetch-Site: cross-site
Sec-Fetch-Mode: no-cors
Sec-Fetch-Dest: image
Sec-Fetch-Storage-Access: active
Referer: https://fitgirl-repacks.site/
Accept-Language: zh-CN,zh;q=0.9

AAA
                        );

                        $urls = [
                            $origin_url,
                        ];
                    }

                    $urls_code = [];
                    $is_exists = false;
                    foreach ($urls as $url)
                    {
                        $is_download_success = false;

                        $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- 下载中【{$url}】";
                        $this->gameManager->getMysqlClient()->logInfo($msg);

                        $ins->addBatchRequest($url, 'get', [
                            "proxy" => $this->proxy,
                        ]);

                        $ins->setSuccessCallback(function(string $contents, Downloader $_this, ResponseInterface $response, $index) use (&$is_download_success, $ext, $pageInfo, $gameTable, $targetDir) {
                            $requestInfo = $_this->getRequestInfoByIndex($index);

                            $fileName = hrtime(true) . '.' . $ext;

                            $md5 = md5($fileName);

                            // 2025/04-14/16/400612345107640.jpg
                            $saveName = date('Y/m-d') . DIRECTORY_SEPARATOR . substr($md5, 0, 2) . DIRECTORY_SEPARATOR . $fileName;

                            $filePath = rtrim($targetDir, '/') . '/' . $saveName;
                            is_dir(dirname($filePath)) || mkdir(dirname($filePath), 0777, true);

                            file_put_contents($filePath, $contents);

                            $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- : 写入成功【{$requestInfo['url']}】【 $filePath 】";
                            $this->gameManager->getMysqlClient()->logInfo($msg);

                            $gameTable->tableIns()
                                ->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])->update([
                                    $gameTable->getCoverLinkField() => $saveName,
                                ]);

                            $is_download_success = true;
                            $this->gameManager->getMysqlClient()->logInfo('');
                        });

                        $ins->setErrorCallback(function(RequestException|ConnectException $e, Downloader $_this, $index) use (&$urls_code, $pageInfo, $gameTable) {
                            $requestInfo = $_this->getRequestInfoByIndex($index);

                            $code = $e->getCode();
                            if (in_array($code, [
                                '403',
                                '404',
                            ]))
                            {
                                $urls_code[$requestInfo['url']] = $code;

                                $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- :响应【{$code}】";
                            }
                            else
                            {
                                $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- :请求出错,响应【{$code}】";
                            }

                            $this->gameManager->getMysqlClient()->logError($msg);
                            $this->gameManager->getMysqlClient()->logInfo('');
                        });

                        $ins->send();

                        if ($is_download_success)
                        {
                            $is_exists = true;

                            break;
                        }
                    }

                    $codes = array_values($urls_code);

                    $isAll404 = function($codes) {
                        $result = true;
                        foreach ($codes as $code)
                        {
                            if (!in_array($code, [
                                '403',
                                '404',
                            ]))
                            {
                                $result = false;
                                break;
                            }
                        }

                        return $result;
                    };

                    //有可能的地址都下载后还没有有效图
                    if (!$is_exists && $isAll404($codes))
                    {
                        $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}]: ----全是404【{$pageInfo[$gameTable->getFitgirlUrlField()]}】";
                        $this->gameManager->getMysqlClient()->logInfo($msg);

                        $gameTable->tableIns()
                            ->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])->update([
                                $gameTable->getCoverLinkField() => '-',
                            ]);
                    }

                }
            };

            $gameTable->tableIns()->where($gameTable->getCoverLinkField(), 'like', "http%")
                ->chunk($count, $func, $gameTable->getPkField());

        }

        public function downloadScreenshotImages($targetDir = './data/'): void
        {
            $gameImagesTable = $this->gameManager->getGameImagesTable();
            $count           = 500;

            $func = function($pages) use ($gameImagesTable, $targetDir) {
                foreach ($pages as $k => $pageInfo)
                {
                    $origin_url = $pageInfo[$gameImagesTable->getPathField()];

                    $t   = explode('.', $origin_url);
                    $ext = array_pop($t);

                    $ins = Downloader::ins();
                    $ins->setRetryTimes($this->retryTimes);
                    $ins->setEnableCache(true);
                    $ins->setCachePath($this->cachePath);
                    $ins->baseCacheStrategy();
                    $ins->setConcurrency($this->concurrency);

                    if (str_contains($origin_url, 'riotpixels.net'))
                    {
                        $ins->setRawHeader($this->headerStr);
                        $urls = [
                            $origin_url,
                            $origin_url . '.720p.jpg',
                            $origin_url . '.240p.jpg',
                        ];
                    }
                    else
                    {
                        $ins->setRawHeader(<<<AAA
Connection: keep-alive
Pragma: no-cache
Cache-Control: no-cache
sec-ch-ua-platform: "Windows"
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36
sec-ch-ua: "Chromium";v="136", "Google Chrome";v="136", "Not.A/Brand";v="99"
sec-ch-ua-mobile: ?0
Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8
Sec-Fetch-Site: cross-site
Sec-Fetch-Mode: no-cors
Sec-Fetch-Dest: image
Sec-Fetch-Storage-Access: active
Referer: https://fitgirl-repacks.site/
Accept-Language: zh-CN,zh;q=0.9

AAA
                        );

                        $urls = [
                            $origin_url,
                        ];
                    }

                    $urls_code = [];
                    $is_exists = false;

                    foreach ($urls as $url)
                    {
                        $is_download_success = false;

                        $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- 下载中【{$url}】";
                        $this->gameManager->getMysqlClient()->logInfo($msg);

                        $ins->addBatchRequest($url, 'get', [
                            "proxy" => $this->proxy,
                        ]);

                        $ins->setSuccessCallback(function(string $contents, Downloader $_this, ResponseInterface $response, $index) use (&$is_download_success, $ext, $pageInfo, $gameImagesTable, $targetDir) {
                            $requestInfo = $_this->getRequestInfoByIndex($index);

                            $fileName = hrtime(true) . '.' . $ext;

                            $md5 = md5($fileName);

                            // 2025/04-14/16/400612345107640.jpg
                            $saveName = date('Y/m-d') . DIRECTORY_SEPARATOR . substr($md5, 0, 2) . DIRECTORY_SEPARATOR . $fileName;

                            $filePath = rtrim($targetDir, '/') . '/' . $saveName;
                            is_dir(dirname($filePath)) || mkdir(dirname($filePath), 0777, true);

                            file_put_contents($filePath, $contents);

                            $gameImagesTable->tableIns()
                                ->where($gameImagesTable->getPkField(), '=', $pageInfo[$gameImagesTable->getPkField()])
                                ->update([
                                    $gameImagesTable->getPathField() => $saveName,
                                ]);

                            $is_download_success = true;

                            $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] ---写入成功【{$requestInfo['url']}】【 $filePath 】";
                            $this->gameManager->getMysqlClient()->logInfo($msg);
                            $this->gameManager->getMysqlClient()->logInfo('');
                        });

                        $ins->setErrorCallback(function(RequestException|ConnectException $e, Downloader $_this, $index) use ($pageInfo, $gameImagesTable, &$urls_code) {
                            $requestInfo = $_this->getRequestInfoByIndex($index);

                            $code = $e->getCode();
                            if (in_array($code, [
                                '403',
                                '404',
                            ]))
                            {
                                $urls_code[$requestInfo['url']] = $code;

                                $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- :响应【{$code}】";
                            }
                            else
                            {
                                $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- :请求出错,响应【{$code}】";
                            }

                            $this->gameManager->getMysqlClient()->logError($msg);
                            $this->gameManager->getMysqlClient()->logInfo('');
                        });

                        $ins->send();

                        if ($is_download_success)
                        {
                            $is_exists = true;
                            break;
                        }
                    }

                    $codes = array_values($urls_code);

                    $isAll404 = function($codes) {

                        if (!count($codes))
                        {
                            return false;
                        }

                        $result = true;
                        foreach ($codes as $code)
                        {
                            if (!in_array($code, [
                                '403',
                                '404',
                            ]))
                            {
                                $result = false;
                                break;
                            }
                        }

                        return $result;
                    };

                    //有可能的地址都下载后还没有有效图
                    if (!$is_exists && $isAll404($codes))
                    {
                        $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}]: ----全是404";
                        $this->gameManager->getMysqlClient()->logInfo($msg);

                        $gameImagesTable->tableIns()
                            ->where($gameImagesTable->getPkField(), '=', $pageInfo[$gameImagesTable->getPkField()])
                            ->update([
                                $gameImagesTable->getPathField() => "--$origin_url",
                            ]);
                    }

                }
            };

            $gameImagesTable->tableIns()->where($gameImagesTable->getPathField(), 'like', "http%")
                ->chunk($count, $func, $gameImagesTable->getPkField());

        }

        protected function fetchImagesUrl($url, $imageType, $gameId): array
        {
            $ins = Downloader::ins();
            $ins->setRetryTimes(20);
            $ins->setEnableCache(true);
            $ins->setCachePath($this->cachePath);
            $ins->baseCacheStrategy();
            $ins->setConcurrency($this->concurrency);
            $ins->setRawHeader($this->headerStr);
            $ins->addBatchRequest($url, 'get', [
                "proxy" => $this->proxy,
            ]);

            $data = [];
            $ins->setSuccessCallback(function(string $contents, Downloader $_this, ResponseInterface $response, $index) use (&$data, $imageType, $gameId) {

                $gameTable       = $this->gameManager->getGameTable();
                $gameImagesTable = $this->gameManager->getGameImagesTable();
                $requestInfo     = $_this->getRequestInfoByIndex($index);

                //头部的网站信息
                $images = static::filterHtml($contents, '.gallery-list-more a img');
                $t      = implode(PHP_EOL, $images);
                preg_match_all('%(https?://[^"]+?).\d+p.jpg%imu', $t, $matches, PREG_PATTERN_ORDER);
                if (isset($matches[1]) && count($matches[1]))
                {
                    foreach ($matches[1] as $k => $v)
                    {
                        $v = strtr($v, [
                            'http:' => 'https:',
                        ]);

                        $data[] = [
                            $gameImagesTable->getPkField()      => $gameImagesTable->calcPk(),
                            $gameImagesTable->getPathField()    => $v,
                            $gameImagesTable->getTypeField()    => $imageType,
                            $gameImagesTable->getGameIdField()  => $gameId,
                            $gameImagesTable->getAddTimeField() => time(),
                        ];
                    }
                }

                $msg = '请求成功: ' . $requestInfo['url'] . ',共：' . count($matches[1]);

                $this->gameManager->getMysqlClient()->logInfo($msg);
            });

            $ins->setErrorCallback(function(RequestException|ConnectException $e, Downloader $_this, $index) {
                $requestInfo = $_this->getRequestInfoByIndex($index);

                $code = $e->getCode();
                if ($code == '403')
                {
                    $this->gameManager->getMysqlClient()->logInfo('【403】 需要更新cookie');

                    exit();
                }

                if (in_array($code, [
                    '403',
                    '404',
                ]))
                {
                    $msg = '响应【' . $code . '】';
                }
                else
                {
                    $msg = '请求出错: ' . '响应【' . $code . '】' . ',' . $requestInfo['url'] . ' -- ' . $e->getMessage();
                }

                $this->gameManager->getMysqlClient()->logError($msg);
            });

            $ins->send();

            return $data;
        }


        /**********************************************************************************/
        // to wp
        /**********************************************************************************/

        public function updateToDb(callable $payPostCallback = null, int $minPrice = 30, int $maxPrice = 90): void
        {
            $gameImagesTable = $this->gameManager->getGameImagesTable();
            $gameTable       = $this->gameManager->getGameTable();
            $wpPostTab       = $this->wpManager->getPostsTable();

            $postIds = $gameTable->tableIns()/*
                ->where($gameTable->getPkField(), 'in', [
//                '1097589577146699594',
//                '1097589693282779638',
//                '1100899816667352480',
                  '1100899816667352480',
            ])->page(1, 100)
              */

            ->order($gameTable->getPkField())->column($gameTable->getPkField());

            $wpPosts = $this->getAllWpPost();
            $wpIds   = $wpPosts->column($wpPostTab->getGuidField());

            $arrs = static::compareArrays($postIds, $wpIds);

            /*
             * ------------------------------
             * 待新增
             * ------------------------------
             *
             * **/
            $posts = $gameTable->tableIns()->where($gameTable->getPkField(), 'in', $arrs['toInsertWp'])->select()
                ->toArray();

            $this->wpManager->getMysqlClient()->logInfo('创建文章个数: ' . count($posts));

            foreach ($posts as $k => $post)
            {
                $postId = $post[$gameTable->getPkField()];

                $title = $post[$gameTable->getNameField()];

                $price = call_user_func_array($payPostCallback, [$post]);
                $isPay = $price > 0;

                //正文内容
                $contents = $this->makePostContentByPostInfo($post, $isPay);
                $this->wpManager->getMysqlClient()->logInfo('创建文章: ' . ($k + 1) . '--' . $title);
                $wpPostId = $this->wpManager->addPost($title, $contents, 1, $postId);

                $seo_keyword     = $this->websiteTitle . ',' . $post[$gameTable->getNameField()] . ',' . $post[$gameTable->getTagsField()];
                $seo_description = '';
                $desc            = json_decode($post[$gameTable->getDescriptionField()], true);
                if ($desc)
                {
                    $seo_description = implode(',', $desc);
                }
                $this->wpManager->updatePostSeo($wpPostId, $title . " Download", $seo_keyword, $seo_description);

                if ($isPay)
                {
                    $this->wpManager->makePostPayRead($wpPostId, $price);
                }

                // 添加tag
                $tags         = explode(',', $post[$gameTable->getTagsField()]);
                $tagsToInsert = [];

                foreach ($tags as $tag)
                {
                    if (mb_strlen($tag) > 1)
                    {
                        $tagsToInsert[] = $tag;
                    }
                }

                if (count($tagsToInsert))
                {
                    $tagIds = $this->wpManager->addTags($tagsToInsert);
                    if (count($tagIds))
                    {
                        $this->wpManager->importPostTerm($wpPostId, $tagIds);
                    }
                }
            }

            /*
          * ------------------------------
          * 待更新
          * ------------------------------
          *
          * **/
            $posts = $gameTable->tableIns()->where($gameTable->getPkField(), 'in', $arrs['toUpdateWp'])->select()
                ->toArray();

            $this->wpManager->getMysqlClient()->logInfo('更新文章个数: ' . count($posts));
            foreach ($posts as $k => $post)
            {
                $wpPostId = $wpPostTab->tableIns()->where([
                    [
                        $wpPostTab->getGuidField(),
                        '=',
                        $post[$gameTable->getPkField()],
                    ],
                ])->value($wpPostTab->getPkField());

                $postId = $post[$gameTable->getPkField()];
                $title  = $post[$gameTable->getNameField()];

                $price = call_user_func_array($payPostCallback, [$post]);
                $isPay = $price > 0;

                $contents = $this->makePostContentByPostInfo($post, $isPay);
                $this->wpManager->getMysqlClient()->logInfo('更新文章: ' . ($k + 1) . '--' . $title);
                $this->wpManager->updatePostContentByGuid($postId, $title, $contents);

                $seo_keyword     = $this->websiteTitle . ',' . $post[$gameTable->getNameField()] . ',' . $post[$gameTable->getTagsField()];
                $seo_description = '';
                $desc            = json_decode($post[$gameTable->getDescriptionField()], true);
                if ($desc)
                {
                    $seo_description = implode(',', $desc);
                }

                $this->wpManager->updatePostSeo($wpPostId, $title . " Download", $seo_keyword, $seo_description);

                if ($isPay)
                {
                    $this->wpManager->makePostPayRead($wpPostId, $price);
                }
                else
                {
                    $this->wpManager->makePostPayOff($wpPostId);
                }
            }

            /*
             * ------------------------------
             * 待删除
             * ------------------------------
             *
             * **/

            $this->wpManager->getMysqlClient()->logInfo('删除文章个数: ' . count($arrs['toDeleteWp']));
            $this->wpManager->deletePostByGuid($arrs['toDeleteWp']);

            /*
             * ------------------------------
             * 更新一些信息
             * ------------------------------
             *
             * **/
            $this->wpManager->updateTagsCount();

        }

        protected function getAllWpPost(): \think\model\Collection|\think\Collection
        {
            $wpPostTab = $this->wpManager->getPostsTable();

            return $wpPostTab->tableIns()->where([
                [
                    $wpPostTab->getGuidField(),
                    'regexp',
                    '^[0-9]{18,20}$',
                ],
            ])->order($wpPostTab->getGuidField())->select();
        }

        protected function makePostContentByPostInfo(array $post, bool $isPay = false): string
        {
            $contents        = [];
            $gameTable       = $this->gameManager->getGameTable();
            $gameImagesTable = $this->gameManager->getGameImagesTable();

            /******************************************/
            $coverBackup = '';
            $imagesText  = [];

            $images = $gameImagesTable->tableIns()->where([
                [
                    $gameImagesTable->getGameIdField(),
                    '=',
                    $post[$gameTable->getPkField()],
                ],
            ])->order($gameImagesTable->getPkField(), 'asc')->select();

            $imageGroup                                         = [];
            $imageGroup[static::IMAGE_TYPE_SCREENSHOT]['title'] = 'Screenshots';
            $imageGroup[static::IMAGE_TYPE_WALLPAPERS]['title'] = 'Wallpapers';
            $imageGroup[static::IMAGE_TYPE_ARTWORKS]['title']   = 'Artworks';

            $IMAGE_TYPE_SCREENSHOT_count = 0;
            $IMAGE_TYPE_WALLPAPERS_count = 0;
            $IMAGE_TYPE_ARTWORKS_count   = 0;

            foreach ($images as $k => $v)
            {
                if ($v[$gameImagesTable->getTypeField()] == static::IMAGE_TYPE_WALLPAPERS)
                {
                    if (!$coverBackup)
                    {
                        $coverBackup = $v[$gameImagesTable->getPathField()];
                    }

                    if ($IMAGE_TYPE_WALLPAPERS_count <= $this->imagesMaxCount)
                    {
                        $IMAGE_TYPE_WALLPAPERS_count++;
                        $imageGroup[static::IMAGE_TYPE_WALLPAPERS]['images'][] = ["src" => $this->imageBaseUrl . $v[$gameImagesTable->getPathField()]];
                    }
                }

                if ($v[$gameImagesTable->getTypeField()] == static::IMAGE_TYPE_ARTWORKS)
                {
                    if (!$coverBackup)
                    {
                        $coverBackup = $v[$gameImagesTable->getPathField()];
                    }

                    if ($IMAGE_TYPE_ARTWORKS_count <= $this->imagesMaxCount)
                    {
                        $IMAGE_TYPE_ARTWORKS_count++;
                        $imageGroup[static::IMAGE_TYPE_ARTWORKS]['images'][] = ["src" => $this->imageBaseUrl . $v[$gameImagesTable->getPathField()]];
                    }
                }

                if ($v[$gameImagesTable->getTypeField()] == static::IMAGE_TYPE_SCREENSHOT)
                {
                    if (!$coverBackup)
                    {
                        $coverBackup = $v[$gameImagesTable->getPathField()];
                    }

                    if ($IMAGE_TYPE_SCREENSHOT_count < $this->imagesMaxCount)
                    {
                        $IMAGE_TYPE_SCREENSHOT_count++;
                        $imageGroup[static::IMAGE_TYPE_SCREENSHOT]['images'][] = ["src" => $this->imageBaseUrl . $v[$gameImagesTable->getPathField()]];
                    }
                }
            }

            foreach ($imageGroup as $k => $v)
            {
                if (isset($v['images']))
                {
                    $imagesText[] = [
                        "title"   => $v['title'],
                        "content" => WpTag:: gallery($v['images']),
                    ];
                }
            }
            /******************************************/

            $coverPath = $post[$gameTable->getCoverLinkField()];
            if ($coverPath == '-')
            {
                if ($coverBackup)
                {
                    $coverPath = $coverBackup;
                }
            }

            $cover = [];
            if ($coverPath == '-')
            {
                $cover = WpTag::p('No preview image available.');
            }
            else
            {
                $cover = WpTag::image($this->imageBaseUrl . $coverPath, 210, 0, 'auto', 'cover');
            }

            $leftSide = [
                $cover,
            ];

            $rightSide = [
                WpTag::groupGrid([
                    WpTag::p(Tag::span('Original Size: ') . Tag::strong($post[$gameTable->getOriginalSizeField()])),
                    WpTag::p(Tag::span('Repack Size: ') . Tag::strong($post[$gameTable->getRepackSizeField()])),
                    WpTag::p(Tag::span('Languages: ') . Tag::strong($post[$gameTable->getLangField()])),
                    WpTag::p(Tag::span('Companies: ') . Tag::strong($post[$gameTable->getCompanyField()])),
                ], 1, null),
            ];

            $contents[] = WpTag::columns([
                [
                    "content" => $leftSide,
                    "width"   => "30%",
                ],
                [
                    "content" => $rightSide,
                ],
            ]);

            if (count($imagesText))
            {
                $contents[] = WpTag::zibllTabs($imagesText);
            }

            /******************************************/
            $texts       = [];
            $description = json_decode($post[$gameTable->getDescriptionField()], 1);

            if ($description && count($description))
            {
                $texts[] = [
                    "title"   => 'Game Description',
                    "content" => WpTag::list($description, 'blue'),
                ];
            }

            $features = json_decode($post[$gameTable->getFeaturesField()], 1);
            if ($features && count($features))
            {
                $texts[] = [
                    "title"   => 'Game Features',
                    "content" => WpTag::list($features, 'blue'),
                ];
            }

            if (count($texts))
            {
                $contents[] = WpTag::zibllTabs($texts);
            }

            /******************************************/
            $downloadUrls  = [];
            $downloadLinks = json_decode($post[$gameTable->getDownloadLinksField()], 1);

            $downloadLinksGroup                         = [];
            $downloadLinksGroup['datanodes']['title']   = 'Datanodes';
            $downloadLinksGroup['filecrypt']['title']   = 'Filecrypt';
            $downloadLinksGroup['fuckingfast']['title'] = 'Fuckingfast';
            $downloadLinksGroup['magnet']['title']      = 'Magnet';

            if (count($downloadLinks))
            {
                foreach ($downloadLinks as $k => $urls)
                {
                    if ($k == 'magnet')
                    {
                        if (count($urls))
                        {
                            foreach ($urls as $url)
                            {
                                $downloadLinksGroup['magnet']['links'][] = $url;
                            }
                        }
                    }

                    if ($k == 'fuckingfast')
                    {
                        if (count($urls))
                        {
                            foreach ($urls as $url)
                            {
                                $downloadLinksGroup['fuckingfast']['links'][] = $url;
                            }
                        }
                    }

                    if ($k == 'datanodes')
                    {
                        if (count($urls))
                        {
                            foreach ($urls as $url)
                            {
                                $downloadLinksGroup['datanodes']['links'][] = $url;
                            }
                        }
                    }

                    if ($k == 'filecrypt')
                    {
                        if (count($urls))
                        {
                            foreach ($urls as $url)
                            {
                                $downloadLinksGroup['filecrypt']['links'][] = $url;
                            }
                        }
                    }

                }
            }

            $downloadLinksGroup = array_reverse($downloadLinksGroup);

            foreach ($downloadLinksGroup as $k => $v)
            {
                if (isset($v['links']))
                {
                    $downloadUrls[] = [
                        "title"   => $v['title'],
                        "content" => WpTag::list($v['links'], 'red'),
                    ];
                }
            }

            $contents[] = WpTag::p('Download Mirrors', 'default', '24px');
            $contents[] = WpTag::p('It is recommended to always use Magnet for downloads, and we suggest the following open-source clients: ' . Tag::a('https://github.com/GopeedLab/gopeed/releases', 'Gopeed'), 'red');

            if (count($downloadUrls))
            {
                $downloadArea = WpTag::zibllTabs($downloadUrls);

                if ($isPay)
                {
                    $contents[] = WpTag::hideContent($downloadArea);
                }
                else
                {
                    $contents[] = $downloadArea;
                }
            }
            else
            {
                $contents[] = WpTag::p('Sorry! There are no download resources available', 'red', '28px');
            }

            /******************************************/
            $updatesLinks = json_decode($post[$gameTable->getUpdatesLinksField()], 2);
            if ($updatesLinks && count($updatesLinks))
            {
                $contents[] = WpTag::p('Game Updates', 'default', '24px');
                $links      = [];

                foreach ($updatesLinks as $k => $v)
                {
                    $links[] = Tag::a($v['link'], $v['name']);
                }
                $contents[] = WpTag::listQuote($links, 'red');
            }

            /******************************************/

            $discussionUrl = $post[$gameTable->getDiscussionUrlField()];
            if ($discussionUrl)
            {
                $contents[] = WpTag::p('Discussion & future update', 'default', '24px');
                $contents[] = WpTag::p(Tag::a($discussionUrl, $discussionUrl), 'blue');
            }
            /******************************************/

            $websiteLinks = json_decode($post[$gameTable->getWebsiteLinksField()], 1);
            if ($websiteLinks)
            {
                $websiteLinks_ = [];
                foreach ($websiteLinks as $url)
                {
                    if (!str_contains($url, 'gog.com'))
                    {
                        $websiteLinks_[] = $url;
                    }
                }

                if (count($websiteLinks_))
                {
                    $contents[] = WpTag::p('Website links', 'default', '24px');

                    $links = [];

                    foreach ($websiteLinks as $k => $v)
                    {
                        $links[] = Tag::a($v, $v);
                    }
                    $contents[] = WpTag::list($links, 'red');
                }
            }

            /******************************************/

            return ArticleContent::contentToString($contents);
        }

        protected static function compareArrays($a, $b): array
        {
            // 计算a中有，b中没有的元素
            $onlyInA = array_diff($a, $b);

            // 计算a和b中都有的元素
            $inBoth = array_intersect($a, $b);

            // 计算b中有，a中没有的元素
            $onlyInB = array_diff($b, $a);

            return [
                'toInsertWp' => $onlyInA,
                'toUpdateWp' => $inBoth,
                'toDeleteWp' => $onlyInB,
            ];
        }

        public static function convertToBytes(string $size): float|int
        {
            // 使用正则表达式匹配数值和单位
            if (preg_match('/^([\d.]+)([KMGT])/i', $size, $matches))
            {
                $value = $matches[1];             // 数值部分
                $unit  = strtoupper($matches[2]); // 单位部分，转为大写以便统一处理

                // 根据单位进行转换
                switch ($unit)
                {
                    case 'K':
                        return $value * 1024; // KB -> 字节
                    case 'M':
                        return $value * 1024 * 1024; // MB -> 字节
                    case 'G':
                        return $value * 1024 * 1024 * 1024; // GB -> 字节
                    case 'T':
                        return $value * 1024 * 1024 * 1024 * 1024; // TB -> 字节
                    default:
                        return 0; // 不支持的单位
                }
            }
            elseif (is_numeric($size))
            {
                return $size;
            }
            else
            {
                // 如果不匹配，返回 0 或抛出异常
                return 0;
            }
        }

        /**********************************************************************************/
        // compress
        /**********************************************************************************/

        public function compressCvoerImage($targetDir): void
        {
            $gameTable = $this->gameManager->getGameTable();
            $count     = 500;

            $func = function($pages) use ($gameTable, $targetDir) {
                foreach ($pages as $k => $pageInfo)
                {
                    $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}]: {$pageInfo[$gameTable->getCoverLinkField()]}";
                    $this->gameManager->getMysqlClient()->logInfo($msg);

                    $destPathToSave = $this->compressImage($targetDir, $pageInfo[$gameTable->getCoverLinkField()], true);

                    $res = $gameTable->tableIns()
                        ->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])->update([
                            $gameTable->getCoverLinkField() => $destPathToSave,
                        ]);

                    if ($res)
                    {
                        $this->gameManager->getMysqlClient()
                            ->logInfo("ID:[{$pageInfo[$gameTable->getPkField()]}] -- : 更新成功:" . $destPathToSave);
                    }
                    else
                    {
                        $this->gameManager->getMysqlClient()
                            ->logError("ID:[{$pageInfo[$gameTable->getPkField()]}] -- : 更新错误:" . $destPathToSave);
                    }
                }
            };

            $gameTable->tableIns()->where($gameTable->getCoverLinkField(), 'like', "202%")
                ->chunk($count, $func, $gameTable->getPkField());
        }

        public function compressScreenShotImage($targetDir): void
        {
            $gameImagesTable = $this->gameManager->getGameImagesTable();
            $count           = 500;

            $func = function($pages) use ($gameImagesTable, $targetDir) {
                foreach ($pages as $k => $pageInfo)
                {
                    $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}]: {$pageInfo[$gameImagesTable->getPathField()]}";
                    $this->gameManager->getMysqlClient()->logInfo($msg);

                    $destPathToSave = $this->compressImage($targetDir, $pageInfo[$gameImagesTable->getPathField()], true);

                    if ($destPathToSave)
                    {
                        $res = $gameImagesTable->tableIns()
                            ->where($gameImagesTable->getPkField(), '=', $pageInfo[$gameImagesTable->getPkField()])
                            ->update([
                                $gameImagesTable->getPathField() => $destPathToSave,
                            ]);

                        if ($res)
                        {
                            $this->gameManager->getMysqlClient()
                                ->logInfo("ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- : 更新成功:" . $destPathToSave);
                        }
                        else
                        {
                            $this->gameManager->getMysqlClient()
                                ->logError("ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- : 更新错误:" . $destPathToSave);
                        }
                    }
                    else
                    {
                        $this->gameManager->getMysqlClient()
                            ->logError("ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- : 路径错误:" . $destPathToSave);
                    }
                }
            };

            $gameImagesTable->tableIns()->where($gameImagesTable->getPathField(), 'like', "202%")
                ->chunk($count, $func, $gameImagesTable->getPkField());
        }

        protected function compressImage(string $basePath, string $imageSavepath, bool $deleteOriginOnDone = false): array|bool|string|null
        {
            $originImagePath = rtrim($basePath, '/') . '/' . ltrim($imageSavepath, '/');
            if (!is_file($originImagePath))
            {
                return false;
            }
            if (!is_readable($originImagePath))
            {
                return false;
            }
            if (!is_writeable($originImagePath))
            {
                return false;
            }

            // c/2025/04-19/59/2104627029837.jpg
            $destPathToSave = preg_replace('/^(\d{4})/im', 'c/$1', $imageSavepath);

            $destPath = rtrim($basePath, '/') . '/' . ltrim($destPathToSave, '/');

            is_dir(dirname($destPath)) || mkdir(dirname($destPath), 0777, true);

            try
            {
                $optimizerChain = OptimizerChainFactory::create();
                $optimizerChain->useLogger($this->gameManager->getMysqlClient()->getLogger());
                Image::load($originImagePath)->setOptimizeChain($optimizerChain)->optimize()->save($destPath);
            }
            catch (\Exception $exception)
            {

            }

            $isSuccess = is_file($destPath);

            if (!$isSuccess)
            {
                copy($originImagePath, $destPath);
            }

            if ($deleteOriginOnDone)
            {
                unlink($originImagePath);
            }

            return $destPathToSave;
        }


        /**********************************************************************************/
        // delete error image
        /**********************************************************************************/

        public function deleteErrorCvoerImage($targetDir): void
        {
            $gameTable = $this->gameManager->getGameTable();
            $count     = 500;

            $func = function($pages) use ($gameTable, $targetDir) {
                foreach ($pages as $k => $pageInfo)
                {
                    // /var/game-images/c/2025/04-24/a8/348769785056893.jpg
                    $originImagePath = rtrim($targetDir, '/') . '/' . ltrim($pageInfo[$gameTable->getCoverLinkField()], '/');

                    if (!is_file($originImagePath))
                    {
                        $res = $gameTable->tableIns()
                            ->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])->delete();
                        @unlink($originImagePath);

                        if ($res)
                        {
                            $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- :文件不存在，删除成功:" . $originImagePath;
                        }
                        else
                        {
                            $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- :文件不存在，删除错误:" . $originImagePath;
                        }
                    }
                    elseif (($size = filesize($originImagePath)) < 100)
                    {
                        $res = $gameTable->tableIns()
                            ->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])->delete();
                        @unlink($originImagePath);

                        if ($res)
                        {
                            $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- :文件太小，【{$size}】删除成功:" . $originImagePath;
                        }
                        else
                        {
                            $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- :文件太小，【{$size}】删除错误:" . $originImagePath;
                        }
                    }
                    else
                    {
                        $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- : 文件正常:" . $originImagePath;
                    }

                    $this->gameManager->getMysqlClient()->logInfo($msg);

                }
            };

            $gameTable->tableIns()->where($gameTable->getCoverLinkField(), 'like', "c/%")
                ->chunk($count, $func, $gameTable->getPkField());
        }

        public function deleteErrorScreenShotImage($targetDir): void
        {
            $gameImagesTable = $this->gameManager->getGameImagesTable();
            $count           = 500;

            $func = function($pages) use ($gameImagesTable, $targetDir) {
                foreach ($pages as $k => $pageInfo)
                {
                    // /var/game-images/c/2025/04-24/a8/348769785056893.jpg
                    $originImagePath = rtrim($targetDir, '/') . '/' . ltrim($pageInfo[$gameImagesTable->getPathField()], '/');

                    if (!is_file($originImagePath))
                    {
                        $res = $gameImagesTable->tableIns()
                            ->where($gameImagesTable->getPkField(), '=', $pageInfo[$gameImagesTable->getPkField()])
                            ->delete();

                        @unlink($originImagePath);

                        if ($res)
                        {
                            $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- :文件不存在，删除成功:" . $originImagePath;
                        }
                        else
                        {
                            $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- :文件不存在，删除错误:" . $originImagePath;
                        }
                    }
                    elseif (($size = filesize($originImagePath)) < 100)
                    {
                        $res = $gameImagesTable->tableIns()
                            ->where($gameImagesTable->getPkField(), '=', $pageInfo[$gameImagesTable->getPkField()])
                            ->delete();

                        @unlink($originImagePath);

                        if ($res)
                        {
                            $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- :文件太小，【{$size}】删除成功:" . $originImagePath;
                        }
                        else
                        {
                            $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- :文件太小，【{$size}】删除错误:" . $originImagePath;
                        }
                    }
                    else
                    {
                        $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- : 文件正常:" . $originImagePath;
                    }

                    $this->gameManager->getMysqlClient()->logInfo($msg);
                }
            };

            $gameImagesTable->tableIns()->where($gameImagesTable->getPathField(), 'like', "c/%")
                ->chunk($count, $func, $gameImagesTable->getPkField());
        }
    }