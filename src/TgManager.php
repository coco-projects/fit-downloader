<?php

    namespace Coco\fitDownloader;

    use Coco\fitDownloader\tables\Game;
    use Coco\fitDownloader\tables\GameImages;
    use Telegram\Bot\Api;
    use Telegram\Bot\FileUpload\InputFile;
    use Telegram\Bot\HttpClients\GuzzleHttpClient;
    use GuzzleHttp\Client as GuzzleClient;
    use function Amp\File\isFile;

    class TgManager
    {
        // http://127.0.0.1:1080
        protected string       $proxy      = '';
        protected static array $bots       = [];
        protected bool         $debug      = true;
        protected ?string      $baseBotUrl = 'https://api.telegram.org/bot';

        // 抽象出的 HttpClient 属性，用于复用连接
        protected ?GuzzleClient     $rawGuzzleClient   = null;
        protected ?GuzzleHttpClient $httpClientHandler = null;

        public function __construct(public GameUpdater $gameUpdater)
        {
            // 初始化高效的底层连接客户端
            $this->initHttpClient();
        }

        /**
         * 动态更新代理（如果中途需要更换代理IP）
         */
        public function setProxy(string $proxy): void
        {
            $this->proxy = $proxy;
            $this->initHttpClient(); // 重新实例化以应用新代理
        }

        public function backupCvoerImage(): void
        {
            $gameTable   = $this->gameUpdater->gameSourceManager->getGameTable();
            $count       = 5;
            $chatIdIndex = 0;

            $func = function($pages) use ($gameTable, &$chatIdIndex) {

                $currentChatId = $this->gameUpdater->backupImageChatId[$chatIdIndex];

                $this->gameUpdater->gameSourceManager->getMysqlClient()
                    ->logInfo('当前发送群组id: ' . $chatIdIndex . ' -> ' . $currentChatId);

                $imagePaths = [];

                if (!count($pages))
                {
                    $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('没有需要处理的图片');

                    return false;
                }

                foreach ($pages as $k => $pageInfo)
                {
                    $msg = "ID:[{$pageInfo[$gameTable->getPkField()]}]: {$pageInfo[$gameTable->getCoverLinkField()]}";
                    $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo($msg);

                    $imagePath = implode('', [
                        rtrim($this->gameUpdater->imagePath, '\\/'),
                        DIRECTORY_SEPARATOR,
                        $pageInfo[$gameTable->getCoverLinkField()],
                    ]);

                    //只有是文件才添加上传
                    if (is_file($imagePath))
                    {
                        $imagePaths[] = $imagePath;
                    }
                    else
                    {
                        $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('文件不存在: ' . $imagePath);
                    }
                }

                if (!count($imagePaths))
                {
                    $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('当前批次没有可上传的文件 ');

                    return false;
                }

                $fileIds = $this->sendImageMessage($imagePaths, $currentChatId, $this->gameUpdater->backupImageBotToken, '封面：' . date("Y-m-d H:i:s", time()));

                //发成功一次就换一个chat，不停循环
                $chatIdIndex++;
                if ($chatIdIndex >= count($this->gameUpdater->backupImageChatId))
                {
                    $chatIdIndex = 0;
                }

                //fileId更新到数据库
                foreach ($fileIds as $path => $fileId)
                {
                    $res = $gameTable->tableIns()->where([
                        [
                            $gameTable->getCoverLinkField(),
                            '=',
                            strtr($path, [$this->gameUpdater->imagePath . DIRECTORY_SEPARATOR => ""]),
                        ],
                    ])->update([
                        $gameTable->getTgFileIdField()   => $fileId,
                        $gameTable->getTgBotTokenField() => $this->gameUpdater->backupImageBotToken,
                    ]);

                    if ($res)
                    {
                        $this->gameUpdater->gameSourceManager->getMysqlClient()
                            ->logInfo("更新成功:" . $path . ' => ' . $fileId);
                    }
                    else
                    {
                        $this->gameUpdater->gameSourceManager->getMysqlClient()
                            ->logError("更新错误:" . $path . ' => ' . $fileId);
                    }
                }

                $t = rand($this->gameUpdater->backupImageSleepMin, $this->gameUpdater->backupImageSleepMax);
                $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('整个批次等 ' . $t . ' S');

                sleep($t);
            };

            //获取有本地图片，但是没有token的记录，说明没上传过
            $gameTable->tableIns()->where([
                [
                    $gameTable->getCoverLinkField(),
                    'like',
                    "c/202%",
                ],
                [
                    $gameTable->getTgBotTokenField(),
                    '=',
                    "",
                ],
            ])->chunk($count, $func, $gameTable->getPkField());

            $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('处理结束');
        }

        public function backupScreenShotImage(): void
        {
            $gameImagesTable = $this->gameUpdater->gameSourceManager->getGameImagesTable();
            $count           = 5;
            $chatIdIndex     = 0;

            $func = function($pages) use ($gameImagesTable, &$chatIdIndex) {

                $currentChatId = $this->gameUpdater->backupImageChatId[$chatIdIndex];

                $this->gameUpdater->gameSourceManager->getMysqlClient()
                    ->logInfo('当前发送群组id: ' . $chatIdIndex . ' -> ' . $currentChatId);

                $imagePaths = [];

                if (!count($pages))
                {
                    $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('没有需要处理的图片');

                    return false;
                }

                foreach ($pages as $k => $pageInfo)
                {
                    $msg = "ID:[{$pageInfo[$gameImagesTable->getPkField()]}]: {$pageInfo[$gameImagesTable->getPathField()]}";
                    $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo($msg);

                    $imagePath = implode('', [
                        rtrim($this->gameUpdater->imagePath, '\\/'),
                        DIRECTORY_SEPARATOR,
                        $pageInfo[$gameImagesTable->getPathField()],
                    ]);

                    //只有是文件才添加上传
                    if (is_file($imagePath))
                    {
                        $imagePaths[] = $imagePath;
                    }
                    else
                    {
                        $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('文件不存在: ' . $imagePath);
                    }
                }

                if (!count($imagePaths))
                {
                    $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('当前批次没有可上传的文件 ');

                    return false;
                }

                $fileIds = $this->sendImageMessage($imagePaths, $currentChatId, $this->gameUpdater->backupImageBotToken, '截图：' . date("Y-m-d H:i:s", time()));

                //发成功一次就换一个chat，不停循环
                $chatIdIndex++;
                if ($chatIdIndex >= count($this->gameUpdater->backupImageChatId))
                {
                    $chatIdIndex = 0;
                }

                //fileId更新到数据库
                foreach ($fileIds as $path => $fileId)
                {
                    $res = $gameImagesTable->tableIns()->where([
                        [
                            $gameImagesTable->getPathField(),
                            '=',
                            strtr($path, [$this->gameUpdater->imagePath . DIRECTORY_SEPARATOR => ""]),
                        ],
                    ])->update([
                        $gameImagesTable->getTgFileIdField()   => $fileId,
                        $gameImagesTable->getTgBotTokenField() => $this->gameUpdater->backupImageBotToken,
                    ]);

                    if ($res)
                    {
                        $this->gameUpdater->gameSourceManager->getMysqlClient()
                            ->logInfo("更新成功:" . $path . ' => ' . $fileId);
                    }
                    else
                    {
                        $this->gameUpdater->gameSourceManager->getMysqlClient()
                            ->logError("更新错误:" . $path . ' => ' . $fileId);
                    }
                }

                $t = rand($this->gameUpdater->backupImageSleepMin, $this->gameUpdater->backupImageSleepMax);
                $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('整个批次等 ' . $t . ' S');

                sleep($t);
            };

            //获取有本地图片，但是没有token的记录，说明没上传过
            $gameImagesTable->tableIns()->where([
                [
                    $gameImagesTable->getPathField(),
                    'like',
                    "c/202%",
                ],
                [
                    $gameImagesTable->getTgBotTokenField(),
                    '=',
                    "",
                ],
            ])->chunk($count, $func, $gameImagesTable->getPkField());

            $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('处理结束');
        }

        /**
         * 核心方法：发送图片（支持单图/多图自动切片/富文本/限流自动重试/FileId兼容）
         *
         * 改动要点：
         * 1. 无论多少张，统一先按 10 张一组切片
         * 2. 循环切片：1 张走单图逻辑，多张走多图逻辑（消除重复代码）
         * 3. caption 只挂在第一组第一张
         * 4. 日志、异常、extraParams、path 重复 key 全部统一处理
         * 5. 返回 [path => file_id] 映射，顺序与传入 $images 一致
         *
         * @param array       $images      图片路径、URL或fileId数组
         * @param string|int  $chatId      目标会话ID
         * @param string      $botToken    机器人Token
         * @param string|null $caption     富文本内容（只会出现在第一组第一张）
         * @param string      $parseMode   文本解析模式 MarkdownV2/HTML
         * @param array       $extraParams 额外透传参数（多图时仅支持标量）
         * @param int         $maxRetries  最大限流重试次数
         *
         * @return array  返回 [原路径 => file_id] 的映射数组（顺序与传入 $images 一致）
         */
        public function sendImageMessage(array $images, $chatId, string $botToken, string $caption = null, string $parseMode = 'HTML', array $extraParams = [], int $maxRetries = 3): array
        {
            if (empty($images))
            {
                throw new \InvalidArgumentException('图片路径数组不能为空');
            }

            $telegram    = $this->getBotManager($botToken);
            $resultMap   = [];
            $pathCounter = []; // 全局去重计数，保证 key 稳定不覆盖

            // 统一切片：无论多少张都先按 10 张一组
            $imageChunks = array_chunk($images, 10);

            foreach ($imageChunks as $chunkIndex => $chunkImages)
            {
                $isFirstChunk = ($chunkIndex === 0);

                if (count($chunkImages) === 1)
                {
                    // ---------- 单图 ----------
                    $imagePath = reset($chunkImages);
                    $fileId    = $this->sendSinglePhoto($telegram, $chatId, $imagePath, $isFirstChunk ? $caption : null,   // 只有第一组才挂 caption
                        $isFirstChunk ? $parseMode : null, $extraParams, $maxRetries);
                    $this->addToResultMap($resultMap, $pathCounter, $imagePath, $fileId);
                }
                else
                {
                    // ---------- 多图 ----------
                    $chunkMap = $this->sendMediaGroupChunk($telegram, $chatId, $chunkImages, $isFirstChunk,                      // 只有第一组才允许挂 caption
                        $caption, $parseMode, $extraParams, $maxRetries);
                    // 合并本批结果（保持全局 pathCounter 一致）
                    foreach ($chunkMap as $path => $fileId)
                    {
                        $this->addToResultMap($resultMap, $pathCounter, $path, $fileId);
                    }
                }

                // 多组之间稍微延迟，降低限流风险
                if (count($imageChunks) > 1 && $chunkIndex < count($imageChunks) - 1)
                {
                    $t = 6;
                    $this->gameUpdater->gameSourceManager->getMysqlClient()->logInfo('整个批次等 ' . $t . ' S');

                    sleep($t);
                }
            }

            return $resultMap;
        }

        /**
         * 发送纯文本消息（支持富文本/限流自动重试）
         *
         * @param string      $text        文本内容
         * @param string|int  $chatId      目标会话ID
         * @param string      $botToken    机器人Token
         * @param string|null $parseMode   文本解析模式 MarkdownV2/HTML（传 null 表示不解析）
         * @param array       $extraParams 额外透传参数
         * @param int         $maxRetries  最大限流重试次数
         *
         * @return int|null  成功返回 message_id，失败返回 null
         */
        public function sendTextMessage(string $text, $chatId, string $botToken, ?string $parseMode = 'HTML', array $extraParams = [], int $maxRetries = 3): ?int
        {
            if ($text === '')
            {
                return null;
            }

            $telegram = $this->getBotManager($botToken);

            $params = array_merge([
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => $parseMode,
            ], $extraParams);

            // 过滤 null，避免 API 收到空值
            $params = array_filter($params, fn($v) => !is_null($v));

            $this->log('sendMessage data: ' . json_encode($params, JSON_UNESCAPED_UNICODE), 'info');

            $response = $this->executeWithRetry(function() use ($telegram, $params) {
                return $telegram->sendMessage($params);
            }, $maxRetries);

            try
            {
                if ($response instanceof \Telegram\Bot\Objects\Message)
                {
                    $this->log('sendTextMessage 成功，ID: ' . $response->getMessageId(), 'info');

                    return $response->getMessageId();
                }
                else
                {
                    $this->log('sendTextMessage 返回异常: ' . json_encode($response, JSON_UNESCAPED_UNICODE), 'error');
                }
            }
            catch (\Exception $e)
            {
                $this->log('sendTextMessage 解析异常: ' . $e->getMessage(), 'error');
            }

            return null;
        }

        /**
         * 发送单张图片（本地文件 / file_id / URL 均支持）
         * caption 是否挂载由调用方决定（通常只给第一组第一张）
         *
         * @return string|null 成功返回 file_id，失败返回 null
         */
        protected function sendSinglePhoto(Api $telegram, $chatId, $imagePath, ?string $caption, ?string $parseMode, array $extraParams, int $maxRetries): ?string
        {
            $photoValue = (is_string($imagePath) && is_file($imagePath)) ? InputFile::create($imagePath) : $imagePath;

            $params = array_merge([
                'chat_id'    => $chatId,
                'photo'      => $photoValue,
                'caption'    => $caption,
                'parse_mode' => $parseMode,
            ], $extraParams);

            // 过滤 null，避免 API 收到空值
            $params = array_filter($params, fn($v) => !is_null($v));

            $this->log('sendPhoto data: ' . json_encode($params, JSON_UNESCAPED_UNICODE), 'info');

            $response = $this->executeWithRetry(function() use ($telegram, $params) {
                return $telegram->sendPhoto($params);
            }, $maxRetries);

            if ($response && method_exists($response, 'getPhoto'))
            {
                $photos = $response->getPhoto();
                if (!empty($photos))
                {
                    return end($photos)->getFileId();
                }
            }

            return null;
        }

        /**
         * 发送一组多图（最多 10 张）
         * - 本地文件在重试闭包内重新 fopen，避免 resource closed
         * - caption / parse_mode 只挂在本批第一张（由 $attachCaption 控制）
         * - extraParams 只接受标量，非标量直接跳过并打日志，防止强制 (string) 出错
         *
         * @return array  [原始路径 => file_id] 本批映射
         */
        protected function sendMediaGroupChunk(Api $telegram, $chatId, array $chunkImages, bool $attachCaption, ?string $caption, ?string $parseMode, array $extraParams, int $maxRetries): array
        {
            $chunkResult = [];
            $pathCounter = []; // 本批内部去重用

            $response = $this->executeWithRetry(function() use (
                $telegram, $chatId, $chunkImages, $attachCaption, $caption, $parseMode, $extraParams
            ) {
                $mediaGroup    = [];
                $multipartData = [
                    [
                        'name'     => 'chat_id',
                        'contents' => (string)$chatId,
                    ],
                ];

                foreach ($chunkImages as $index => $imagePath)
                {
                    $type = is_string($imagePath) && is_file($imagePath) ? 'LOCAL_FILE' : 'FILE_ID_OR_URL';
                    $this->log(sprintf('media[%d] type=%s value=%s is_file=%s', $index, $type, is_string($imagePath) ? (strlen($imagePath) > 80 ? substr($imagePath, 0, 80) . '...' : $imagePath) : gettype($imagePath), is_string($imagePath) ? (is_file($imagePath) ? 'yes' : 'no') : 'n/a'), 'info');

                    if (is_string($imagePath) && is_file($imagePath))
                    {
                        $attachmentName = 'pic_attach_' . $index;
                        $mediaItem      = [
                            'type'  => 'photo',
                            'media' => 'attach://' . $attachmentName,
                        ];
                        // 关键：每次重试都重新打开文件句柄
                        $multipartData[] = [
                            'name'     => $attachmentName,
                            'contents' => fopen($imagePath, 'r'),
                            'filename' => basename($imagePath),
                        ];
                    }
                    else
                    {
                        // file_id 或 URL
                        $mediaItem = [
                            'type'  => 'photo',
                            'media' => $imagePath,
                        ];
                    }

                    // 只有调用方允许时，才在本批第一张挂 caption
                    if ($attachCaption && $index === 0)
                    {
                        if (!is_null($caption))
                        {
                            $mediaItem['caption'] = $caption;
                        }
                        if (!is_null($parseMode))
                        {
                            $mediaItem['parse_mode'] = $parseMode;
                        }
                    }

                    $mediaGroup[] = $mediaItem;
                }

                $multipartData[] = [
                    'name'     => 'media',
                    'contents' => json_encode($mediaGroup),
                ];

                // extraParams 安全处理：只接受标量，避免强制转 string 出问题
                foreach ($extraParams as $paramKey => $paramValue)
                {
                    if (is_null($paramValue))
                    {
                        continue;
                    }
                    if (!is_scalar($paramValue))
                    {
                        $this->log("extraParams 跳过非标量参数: {$paramKey}", 'error');
                        continue;
                    }
                    $multipartData[] = [
                        'name'     => (string)$paramKey,
                        'contents' => (string)$paramValue,
                    ];
                }

                $this->log('sendMediaGroup data: ' . json_encode($multipartData, JSON_UNESCAPED_UNICODE), 'info');

                return $telegram->post('sendMediaGroup', $multipartData, true);
            }, $maxRetries);

            // 统一解析 + 异常处理（与单图路径行为一致：解析失败只记日志，不中断整体）
            try
            {
                if ($response instanceof \Telegram\Bot\TelegramResponse)
                {
                    $decoded = $response->getDecodedBody();
                    $this->log('sendMediaGroup 成功', 'info');
                    $messages = $decoded['result'] ?? [];

                    // 调试用（保留原逻辑，受 $this->debug 控制更安全，这里先按原样）
                    if ($this->debug)
                    {
                        file_put_contents('./messages.json', json_encode($messages, JSON_UNESCAPED_UNICODE));
                    }

                    foreach ($messages as $i => $message)
                    {
                        if (empty($message['photo']) || !is_array($message['photo']))
                        {
                            continue;
                        }
                        $lastPhoto = end($message['photo']);
                        $fileId    = $lastPhoto['file_id'] ?? null;
                        if ($fileId && isset($chunkImages[$i]))
                        {
                            $this->addToResultMap($chunkResult, $pathCounter, $chunkImages[$i], $fileId);
                        }
                    }
                }
                else
                {
                    $this->log('sendMediaGroup 返回异常: ' . json_encode($response, JSON_UNESCAPED_UNICODE), 'error');
                }
            }
            catch (\Exception $e)
            {
                $this->log('sendMediaGroup 解析异常: ' . $e->getMessage(), 'error');
            }

            return $chunkResult;
        }

        /**
         * 向结果 map 写入 file_id
         * - 正常情况用原始 path 做 key，保证调用方能反查
         * - 若同一 path 出现多次，自动追加 #2、#3... 防止覆盖，同时保持可读性
         * - 返回值顺序与传入 $images 顺序一致（通过调用时机保证）
         */
        protected function addToResultMap(array &$resultMap, array &$pathCounter, $path, ?string $fileId): void
        {
            if ($fileId === null)
            {
                return;
            }

            $key = (string)$path;
            if (isset($resultMap[$key]))
            {
                $pathCounter[$key] = ($pathCounter[$key] ?? 1) + 1;
                $key               = $key . '#' . $pathCounter[$key];
            }

            $resultMap[$key] = $fileId;
        }

        /**
         * 初始化并缓存 Guzzle 客户端，实现连接复用 (Keep-Alive)
         */
        protected function initHttpClient(): void
        {
            $guzzleOptions = [
                'timeout'         => 30.0,
                'connect_timeout' => 10.0,
            ];

            if (!empty($this->proxy))
            {
                $guzzleOptions['proxy'] = $this->proxy;
            }

            // 全局复用这一个 Guzzle 实例
            $this->rawGuzzleClient   = new GuzzleClient($guzzleOptions);
            $this->httpClientHandler = new GuzzleHttpClient($this->rawGuzzleClient);
        }

        /**
         * 智能高发群发送信闭包执行器（核心防封防限流逻辑）
         */
        protected function executeWithRetry(callable $action, int $maxRetries)
        {
            $retryCount = 0;

            while ($retryCount <= $maxRetries)
            {
                try
                {
                    return $action();
                }
                catch (\Exception $e)
                {
                    $errorMessage = $e->getMessage();

                    // 检测是否触发了 Telegram 的 429 Too Many Requests 限流
                    // 错误文本通常包含: "Too Many Requests: retry after X" 或者 状态码 429
                    if ($this->unlikely_contains_429($e, $errorMessage, $retryAfter))
                    {
                        $retryCount++;
                        if ($retryCount > $maxRetries)
                        {
                            throw $e; // 超过最大重试次数，抛出异常
                        }

                        // 动态等待 Telegram 要求的时间，通常加 1 秒作为安全缓冲
                        $sleepTime = $retryAfter ? (int)$retryAfter + 1 : 3;
                        sleep($sleepTime);
                        continue; // 重新进入循环尝试发送
                    }

                    // 如果是其他网络抖动导致的 cURL error（如 timeout、7、56 等），也可以选择短暂重试
                    if (str_contains($errorMessage, 'cURL error') && $retryCount < $maxRetries)
                    {
                        $retryCount++;
                        sleep(2);
                        continue;
                    }

                    // 属于逻辑错误、Token不对或ChatId找不到等致命错误，直接抛出不重试
                    throw $e;
                }
            }

            return null;
        }

        /**
         * 辅助方法：解析异常中的 429 限流等待时间
         */
        protected function unlikely_contains_429(\Exception $e, string $message, &$retryAfter): bool
        {
            if (method_exists($e, 'getCode') && $e->getCode() === 429)
            {
                // 尝试通过正则获取具体需要等待的秒数
                if (preg_match('/retry after (\d+)/i', $message, $matches))
                {
                    $retryAfter = (int)$matches[1];
                }

                return true;
            }

            if (preg_match('/Too Many Requests/i', $message))
            {
                if (preg_match('/retry after (\d+)/i', $message, $matches))
                {
                    $retryAfter = (int)$matches[1];
                }

                return true;
            }

            return false;
        }

        protected function getBotManager(string $botToken): Api
        {
            if (!isset(static::$bots[$botToken]))
            {
                static::$bots[$botToken] = new Api($botToken, false, $this->httpClientHandler, $this->baseBotUrl);
            }

            return static::$bots[$botToken];
        }

        /**
         * 统一日志入口，避免到处写长链式调用
         */
        protected function log(string $message, string $level = 'info'): void
        {
            $logAction = 'log' . ucfirst($level);
            $client    = $this->gameUpdater->gameSourceManager->getMysqlClient();

            $client->$logAction($message);
        }
    }
