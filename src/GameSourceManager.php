<?php

    namespace Coco\fitDownloader;

    use Coco\fitDownloader\tables\Game;
    use Coco\fitDownloader\tables\GameImages;
    use Coco\simplePageDownloader\Downloader;
    use Coco\tableManager\TableRegistry;
    use DI\Container;
    use GuzzleHttp\Exception\ConnectException;
    use GuzzleHttp\Exception\RequestException;
    use Psr\Http\Message\ResponseInterface;
    use Spatie\Image\Image;
    use Spatie\ImageOptimizer\OptimizerChainFactory;
    use Symfony\Component\DomCrawler\Crawler;

    class GameSourceManager
    {
        /***********************************/
        protected string $proxy          = '';
        protected bool   $debug          = true;
        protected bool   $enableRedisLog = false;
        protected bool   $enableEchoLog  = false;

        protected string $mysqlHost     = '127.0.0.1';
        protected string $mysqlUsername = 'root';
        protected string $mysqlPassword = 'root';
        protected int    $mysqlPort     = 3306;
        protected string $mysqlDbName;

        protected string $redisHost     = '127.0.0.1';
        protected string $redisPassword = '';
        protected int    $redisPort     = 6379;
        protected int    $redisDbIndex  = 6;

        protected string $cachePath   = '../downloadCache';
        protected int    $retryTimes  = 8;
        protected int    $concurrency = 5;

        // 复制过来的头删除这个
        // Accept-Encoding: gzip, deflate, br, zstd
        protected string $headerStr  = '';
        protected array  $infoUrlMap = [];

        /***********************************/

        protected ?Container $container    = null;
        protected array      $tables       = [];
        protected ?string    $logNamespace = 'GameSource';

        /**********************************************************************************/

        public function __construct()
        {
            $this->container = new Container();
        }

        public function setEnableEchoLog(bool $enableEchoLog): static
        {
            $this->enableEchoLog = $enableEchoLog;

            return $this;
        }

        public function setEnableRedisLog(bool $enableRedisLog): static
        {
            $this->enableRedisLog = $enableRedisLog;

            return $this;
        }

        public function setProxy(string $proxy): static
        {
            $this->proxy = $proxy;

            return $this;
        }

        public function setDebug(bool $debug): static
        {
            $this->debug = $debug;

            return $this;
        }

        public function setLogNamespace(?string $logNamespace): static
        {
            $this->logNamespace = $logNamespace;

            return $this;
        }

        public function setHeaderStr(string $headerStr): static
        {
            $this->headerStr = $headerStr;

            return $this;
        }

        public function setInfoUrlMap(array $infoUrlMap): static
        {
            $this->infoUrlMap = $infoUrlMap;

            return $this;
        }

        public function setCachePath(string $cachePath): static
        {
            $this->cachePath = $cachePath;

            return $this;
        }

        public function setRetryTimes(int $retryTimes): static
        {
            $this->retryTimes = $retryTimes;

            return $this;
        }

        public function setConcurrency(int $concurrency): static
        {
            $this->concurrency = $concurrency;

            return $this;
        }

        public function initServer(): static
        {
            ini_set('memory_limit', '512M');

            Downloader::initClientConfig([
                'timeout' => 30.0,
                'version' => 2.0,
                // 确保 HTTP/2 开启
                'verify'  => true,
                // ⚡ 极其重要：必须设为 true！否则 HTTP/2 无法在 HTTPS 下正常协商
                'debug'   => false,
                'proxy'   => $this->proxy,

                'headers' => [
                    'Expect'          => '',
                    // 禁用 Expect 100-continue 握手
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    // ⚡ 极其重要：告诉服务器允许压缩传输，网页体积会瞬间缩小 80%！
                ],

                'curl' => [
                    CURLOPT_IPRESOLVE     => CURL_IPRESOLVE_V4,
                    // 强制只解析 IPv4
                    CURLOPT_FORBID_REUSE  => false,
                    // 保持持久连接连接池
                    CURLOPT_FRESH_CONNECT => false,
                    CURLOPT_TCP_NODELAY   => 1,
                    // 降低 TCP 包延迟
                ],
            ]);

            Downloader::initLogger('download_log', $this->debug, $this->enableRedisLog);

            Downloader::setRedis(redisHost: $this->redisHost, redisPort: $this->redisPort, password: $this->redisPassword, db: $this->redisDbIndex);

            $this->logNamespace .= ':games:log:';

            $this->initMysql();
            $this->initRedis();

            return $this;
        }

        public function initTableStruct(string $tablePrefix = ''): static
        {
            $this->initGameTable($tablePrefix . 'game', function(Game $table) {
                $registry = $table->getTableRegistry();

                $table->setPkField('id');
                $table->setIsPkAutoInc(false);
                $table->setPkValueCallable($registry::snowflakePKCallback());
            });

            $this->initGameImagesTable($tablePrefix . 'game_images', function(GameImages $table) {
                $registry = $table->getTableRegistry();

                $table->setPkField('id');
                $table->setIsPkAutoInc(false);
                $table->setPkValueCallable($registry::snowflakePKCallback());
            });

            return $this;
        }

        private function initRedis(): static
        {
            $this->container->set('redisClient', function(Container $container) {

                /**
                 * @var \Redis $redis
                 */
                $redis = (new \Redis());
                $redis->connect($this->redisHost, $this->redisPort);
                $this->redisPassword && $redis->auth($this->redisPassword);
                $redis->select($this->redisDbIndex);

                return $redis;
            });

            return $this;
        }

        /*
        *
        * ------------------------------------------------------
        *
        * */

        public function setMysqlConfig($db, $host = '127.0.0.1', $username = 'root', $password = 'root', $port = 3306): static
        {
            $this->mysqlHost     = $host;
            $this->mysqlPassword = $password;
            $this->mysqlUsername = $username;
            $this->mysqlPort     = $port;
            $this->mysqlDbName   = $db;

            return $this;
        }

        public function setRedisConfig(string $host = '127.0.0.1', string $password = '', int $port = 6379, int $db = 9): static
        {
            $this->redisHost     = $host;
            $this->redisPassword = $password;
            $this->redisPort     = $port;
            $this->redisDbIndex  = $db;

            return $this;
        }

        public function getContainer(): Container
        {
            return $this->container;
        }

        public function getMysqlClient(): TableRegistry
        {
            return $this->container->get('mysqlClient');
        }

        public function getRedisClient(): \Redis
        {
            return $this->container->get('redisClient');
        }

        protected function initMysql(): static
        {
            $this->container->set('mysqlClient', function(Container $container) {

                $registry = new TableRegistry($this->mysqlDbName, $this->mysqlHost, $this->mysqlUsername, $this->mysqlPassword, $this->mysqlPort,);

                $logName = 'mysql';
                $registry->setStandardLogger($logName);

                if ($this->enableRedisLog)
                {
                    $registry->addRedisHandler(redisHost: $this->redisHost, redisPort: $this->redisPort, password: $this->redisPassword, db: $this->redisDbIndex, logName: $this->logNamespace . $logName, callback: TableRegistry::getStandardFormatter());
                }

                if ($this->enableEchoLog)
                {
                    $registry->addStdoutHandler(TableRegistry::getStandardFormatter());
                }

                return $registry;

            });

            return $this;
        }

        /*
         *
         * ------------------------------------------------------
         *
         * */
        public function createAllTable($forceCreateTable = false): void
        {
            $this->getMysqlClient()->createAllTable($forceCreateTable);
        }

        public function dropAllTable(): void
        {
            $this->getMysqlClient()->dropAllTable();
        }

        public function truncateAllTable(): void
        {
            $this->getMysqlClient()->truncateAllTable();
        }

        /*
         *
         * ------------------------------------------------------
         *
         * */
        public function initGameTable(string $name, callable $callback): static
        {
            $this->tables['Game'] = $name;

            $table = new Game($name);

            $this->getMysqlClient()->addTable($table, $callback);

            return $this;
        }

        public function getGameTable(): Game
        {
            return $this->getMysqlClient()->getTable($this->tables['Game']);
        }

        public function initGameImagesTable(string $name, callable $callback): static
        {
            $this->tables['GameImages'] = $name;

            $table = new GameImages($name);

            $this->getMysqlClient()->addTable($table, $callback);

            return $this;
        }

        public function getGameImagesTable(): GameImages
        {
            return $this->getMysqlClient()->getTable($this->tables['GameImages']);
        }

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

                $this->getMysqlClient()->logInfo('第一页: ' . $archive_url_apge1);
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

                $this->getMysqlClient()->logInfo('总页数: ' . $countPage);

                $pages = [$archive_url_apge1];
                for ($i = 2; $i <= $countPage; $i++)
                {
                    $pages[] = $archive_url_apge1 . "page/{$i}/";
                }
                $pages = array_reverse($pages);

                foreach ($pages as $k => $url)
                {
                    $ins->setSuccessCallback(function(string $contents, Downloader $_this, ResponseInterface $response, $index) {

                        $gameTable = $this->getGameTable();

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

                                $this->getGameTable()->tableIns()->insert($result);
                                $this->getMysqlClient()
                                    ->logInfo('【O】[' . $id_num . ']数据写入成功: ' . $result['name']);
                            }
                            else
                            {
                                $this->getMysqlClient()
                                    ->logInfo('【X】[' . $id_num . ']当前页面已经写入过: ' . $result['name']);
                            }
                        }

                        $this->getMysqlClient()->logInfo('');
                    });

                    $ins->setErrorCallback(function(RequestException|ConnectException $e, Downloader $_this, $index) {
                        $this->getMysqlClient()->logInfo('出错: ' . $e->getMessage());
                        $this->getMysqlClient()->logInfo('');

                    });

                    $ins->addBatchRequest($url, 'get', [
                        "proxy" => $this->proxy,
                    ]);

                    $ins->send();
                }

                $this->getMysqlClient()->logInfo('当前月份采集完成: ' . $countPage);
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
                $this->getMysqlClient()->logError('出错: ' . $exception->getMessage());
            }

            try
            {
                $fitgirl_url = $crawler->filter('.entry-title a')->first()->attr('href');
            }
            catch (\Exception $exception)
            {
                $fitgirl_url = '';
                $this->getMysqlClient()->logError('出错: ' . "[$name][fitgirl_url]" . $exception->getMessage());
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
                $this->getMysqlClient()->logError('出错: ' . "[$name][cover_link]" . $exception->getMessage());
            }

            try
            {
                $dateTime = \DateTime::createFromFormat('d/m/Y', $crawler->filter('.entry-date')->first()->text());

                $fitgirl_publish_time = $dateTime->getTimestamp();
            }
            catch (\Exception $exception)
            {
                $fitgirl_publish_time = 0;
                $this->getMysqlClient()
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
                $this->getMysqlClient()
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
                $this->getMysqlClient()
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
                $this->getMysqlClient()
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
            $gameTable = $this->getGameTable();
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

                    $gameTable = $this->getGameTable();

                    $result = [
                        $gameTable->getCoverLinkFetchStatusField() => GameUpdater::IMAGE_STATUS_2,
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
                        $this->getMysqlClient()
                            ->logInfo("ID:[{$pageInfo[$gameTable->getPkField()]}] -- : " . '更新成功:' . json_encode($result));
                    }
                    else
                    {
                        $this->getMysqlClient()
                            ->logError("ID:[{$pageInfo[$gameTable->getPkField()]}] -- : " . '更新错误');
                    }
                    $this->getMysqlClient()->logInfo('');

                });

                $ins->setErrorCallback(function(RequestException $e, Downloader $_this, $index) use ($pages) {
                    $pageInfo    = $pages[$index];
                    $requestInfo = $_this->getRequestInfoByIndex($index);
                    $gameTable   = $this->getGameTable();

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

                    $this->getMysqlClient()->logError($msg);
                    $this->getMysqlClient()->logInfo('');

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

            $gameTable->tableIns()->where($gameTable->getCoverLinkFetchStatusField(), '=', GameUpdater::IMAGE_STATUS_0)
                ->chunk($count, $func, $gameTable->getPkField());
        }

        public function downloadImageMetas(): void
        {
            $gameTable       = $this->getGameTable();
            $gameImagesTable = $this->getGameImagesTable();
            $count           = 500;

            $func = function($pages) use ($gameTable, $gameImagesTable) {

                foreach ($pages as $k => $pageInfo)
                {
                    $url = $pageInfo[$gameTable->getInfoUrlField()] . '/';

                    $url_screenshots = $url . 'screenshots/';
                    $url_wallpapers  = $url . 'wallpapers/';
                    $url_artworks    = $url . 'artworks/';

                    $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}]";
                    $this->getMysqlClient()->logInfo($msg);

                    $data1 = $this->fetchImagesUrl($url_screenshots, GameUpdater::IMAGE_TYPE_SCREENSHOT, $pageInfo[$gameTable->getPkField()]);
                    $data2 = $this->fetchImagesUrl($url_wallpapers, GameUpdater::IMAGE_TYPE_WALLPAPERS, $pageInfo[$gameTable->getPkField()]);
                    $data3 = $this->fetchImagesUrl($url_artworks, GameUpdater::IMAGE_TYPE_ARTWORKS, $pageInfo[$gameTable->getPkField()]);

                    $data = array_merge($data1, $data2, $data3);

                    $this->getGameImagesTable()->tableIns()->insertAll($data);

                    $gameTable->tableIns()->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])
                        ->update([
                            $gameTable->getImageFetchStatusField() => GameUpdater::IMAGE_STATUS_2,
                        ]);

                    $this->getMysqlClient()->logInfo('写入完成，共:' . count($data));
                    $this->getMysqlClient()->logInfo('');

                }
            };

            $gameTable->tableIns()->where($gameTable->getImageFetchStatusField(), '=', GameUpdater::IMAGE_STATUS_0)
                ->chunk($count, $func, $gameTable->getPkField());
        }

        public function downloadCoverImages($targetDir = './data/'): void
        {
            $gameTable = $this->getGameTable();
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
                        $this->getMysqlClient()->logInfo($msg);

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

                            $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}] -- : 写入成功: 【{$requestInfo['url']}】【 $filePath 】";
                            $this->getMysqlClient()->logInfo($msg);

                            $gameTable->tableIns()
                                ->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])->update([
                                    $gameTable->getCoverLinkField() => $saveName,
                                ]);

                            $is_download_success = true;
                            $this->getMysqlClient()->logInfo('');
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

                            $this->getMysqlClient()->logError($msg);
                            $this->getMysqlClient()->logInfo('');
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
                        $this->getMysqlClient()->logInfo($msg);

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
            $gameImagesTable = $this->getGameImagesTable();
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
                        $this->getMysqlClient()->logInfo($msg);

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

                            $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}] : 写入成功: 【{$requestInfo['url']}】【 $filePath 】";
                            $this->getMysqlClient()->logInfo($msg);
                            $this->getMysqlClient()->logInfo('');
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

                            $this->getMysqlClient()->logError($msg);
                            $this->getMysqlClient()->logInfo('');
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
                        $this->getMysqlClient()->logInfo($msg);

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

                $gameTable       = $this->getGameTable();
                $gameImagesTable = $this->getGameImagesTable();
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

                $this->getMysqlClient()->logInfo($msg);
            });

            $ins->setErrorCallback(function(RequestException|ConnectException $e, Downloader $_this, $index) {
                $requestInfo = $_this->getRequestInfoByIndex($index);

                $code = $e->getCode();
                if ($code == '403')
                {
                    $this->getMysqlClient()->logInfo('【403】 需要更新cookie');
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

                $this->getMysqlClient()->logError($msg);
            });

            $ins->send();

            return $data;
        }

        /**********************************************************************************/
        // compress
        /**********************************************************************************/

        public function compressCvoerImage($targetDir): void
        {
            $gameTable = $this->getGameTable();
            $count     = 500;

            $func = function($pages) use ($gameTable, $targetDir) {
                foreach ($pages as $k => $pageInfo)
                {
                    $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}]: {$pageInfo[$gameTable->getCoverLinkField()]}";
                    $this->getMysqlClient()->logInfo($msg);

                    $destPathToSave = $this->compressImage($targetDir, $pageInfo[$gameTable->getCoverLinkField()], true);

                    $res = $gameTable->tableIns()
                        ->where($gameTable->getPkField(), '=', $pageInfo[$gameTable->getPkField()])->update([
                            $gameTable->getCoverLinkField() => $destPathToSave,
                        ]);

                    if ($res)
                    {
                        $this->getMysqlClient()
                            ->logInfo("ID:[{$pageInfo[$gameTable->getPkField()]}] -- : 更新成功:" . $destPathToSave);
                    }
                    else
                    {
                        $this->getMysqlClient()
                            ->logError("ID:[{$pageInfo[$gameTable->getPkField()]}] -- : 更新错误:" . $destPathToSave);
                    }
                }
            };

            $gameTable->tableIns()->where($gameTable->getCoverLinkField(), 'like', "202%")
                ->chunk($count, $func, $gameTable->getPkField());
        }

        public function compressScreenShotImage($targetDir): void
        {
            $gameImagesTable = $this->getGameImagesTable();
            $count           = 500;

            $func = function($pages) use ($gameImagesTable, $targetDir) {
                foreach ($pages as $k => $pageInfo)
                {
                    $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}]: {$pageInfo[$gameImagesTable->getPathField()]}";
                    $this->getMysqlClient()->logInfo($msg);

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
                            $this->getMysqlClient()
                                ->logInfo("ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- : 更新成功:" . $destPathToSave);
                        }
                        else
                        {
                            $this->getMysqlClient()
                                ->logError("ID:[{$pageInfo[$gameImagesTable->getPkField()]}] -- : 更新错误:" . $destPathToSave);
                        }
                    }
                    else
                    {
                        $this->getMysqlClient()
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
                $optimizerChain->useLogger($this->getMysqlClient()->getLogger());
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
            $gameTable = $this->getGameTable();
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

                    $this->getMysqlClient()->logInfo($msg);

                }
            };

            $gameTable->tableIns()->where($gameTable->getCoverLinkField(), 'like', "c/%")
                ->chunk($count, $func, $gameTable->getPkField());
        }

        public function deleteErrorScreenShotImage($targetDir): void
        {
            $gameImagesTable = $this->getGameImagesTable();
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

                    $this->getMysqlClient()->logInfo($msg);
                }
            };

            $gameImagesTable->tableIns()->where($gameImagesTable->getPathField(), 'like', "c/%")
                ->chunk($count, $func, $gameImagesTable->getPkField());
        }

    }