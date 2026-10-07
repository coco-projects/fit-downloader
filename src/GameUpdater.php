<?php

    namespace Coco\fitDownloader;

    use Coco\fitDownloader\TgCaptainSdk\TelegramContentHTML;
    use Coco\fitDownloader\TgCaptainSdk\TelegramTagHTML;
    use Coco\wp\ArticleContent;
    use Coco\wp\Manager;
    use Coco\wp\Tag;
    use Coco\wp\WpTag;

    class GameUpdater
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
        protected string $mysqlDbName   = '';

        protected string $redisHost     = '127.0.0.1';
        protected string $redisPassword = '';
        protected int    $redisPort     = 6379;
        protected int    $redisDbIndex  = 6;

        protected string $cachePath   = '../downloadCache';
        protected string $processPath = '../process/';
        protected int    $retryTimes  = 18;
        protected int    $concurrency = 5;

        // 复制过来的头删除这个
        // Accept-Encoding: gzip, deflate, br, zstd
        protected string $headerStr  = '';
        protected array  $infoUrlMap = [];

        /***********************************/

        public Manager           $wpManager;
        public GameSourceManager $gameSourceManager;
        public TgManager         $tgManager;

        protected string $redisLogName;
        protected bool   $redisLogEnable = false;
        protected string $websiteTitle   = 'Games';
        protected string $imageBaseUrl   = '';
        public string    $imagePath      = 'data';
        protected int    $imagesMaxCount = 15;
        protected string $lang           = 'cn';
        protected string $mainSite;

        public string $postTgBotToken;
        public int    $postTgChatId;

        public int    $postTgSleepMin      = 5;
        public int    $postTgSleepMax      = 12;
        public int    $backupImageSleepMin = 5;
        public int    $backupImageSleepMax = 12;
        public string $backupImageBotToken;
        public array  $backupImageChatId   = [];

        protected array $langMap = [
            "screenshots"                     => [
                "en" => "Screenshots",
                "cn" => "游戏截图",
            ],
            "wallpapers"                      => [
                "en" => "Wallpapers",
                "cn" => "壁纸",
            ],
            "artworks"                        => [
                "en" => "Artworks",
                "cn" => "插画",
            ],
            "no_preview_image_available"      => [
                "en" => "No preview image available.",
                "cn" => "暂无图片",
            ],
            "original_size"                   => [
                "en" => 'Original Size',
                "cn" => "初始大小",
            ],
            "repack_size"                     => [
                "en" => 'Repack Size',
                "cn" => "打包大小",
            ],
            "languages"                       => [
                "en" => 'Languages',
                "cn" => "内置语言",
            ],
            "companies"                       => [
                "en" => 'Companies',
                "cn" => "开发公司",
            ],
            "game_description"                => [
                "en" => 'Game Description',
                "cn" => "游戏介绍",
            ],
            "game_features"                   => [
                "en" => 'Game Features',
                "cn" => "游戏特点",
            ],
            "magnet"                          => [
                "en" => 'magnet',
                "cn" => "磁力链接",
            ],
            "download_mirrors"                => [
                "en" => 'Download Mirrors',
                "cn" => "下载链接",
            ],
            "recommended_client"              => [
                "en" => 'It is recommended to always use Magnet for downloads, and we suggest the following open-source clients: ',
                "cn" => "建议始终优先使用磁力链接下载，推荐使用开源下载工具: ",
            ],
            "no_download_resources_available" => [
                "en" => 'Sorry! There are no download resources available!',
                "cn" => "抱歉，当前游戏暂无下载资源！",
            ],
            "only_magnet"                     => [
                "en" => 'Please go to the main website and search for the name of the current game; the page provides more download channels.',
                "cn" => "请前往主站搜索当前游戏名，页面上有更多下载渠道提供",
            ],
            "manual_download"                 => [
                "en" => 'Tip: Please manually copy the magnet link below and use a magnet client to download. If the link below fails to work, please go to the main website and search for the name of the current game, as the page provides more available download channels.',
                "cn" => "提示：手动复制下方磁力链接，使用磁力客户端下载，如果下方链接无法下载，请前往主站搜索当前游戏名，页面上有更多下载渠道提供",
            ],
            "more_game"                       => [
                "en" => 'For more free standalone game downloads, please head to our official main site.',
                "cn" => "更多免费单机游戏下载，请访问主站",
            ],
            'game_updates'                    => [
                "en" => 'Game Updates',
                "cn" => "游戏更新",
            ],
            'website_links'                   => [
                "en" => 'Website links',
                "cn" => "相关链接",
            ],
            'discussion_future_update'        => [
                "en" => 'discussion && update',
                "cn" => "论坛与更新",
            ],
            'precautions'                     => [
                "en" => 'Precautions and recommended settings',
                "cn" => "注意事项和推荐设置",
            ],
            'site_url'                        => [
                "en" => "website link",
                "cn" => '游戏主站',
            ],
            'game_name'                       => [
                "en" => 'game',
                "cn" => "游戏名称",
            ],
        ];

        protected array $noteMap = [
            "magnet_download"        => [
                "en" => "Always recommend using magnet links to download games for faster and more stable speeds. Recommend pure, open-source, ad-free download software (such as Gopeed or BitComet).",
                "cn" => "始终建议使用磁力链接下载游戏，速度更快更稳定。推荐使用纯净、开源、无广告的下载软件（如 Gopeed 或 BitComet）。",
            ],
            "hardware_check"         => [
                "en" => "Before running the game, evaluate the game's system hardware requirements. If your computer only has 2G of RAM, you basically cannot expect to run large next-gen games smoothly.",
                "cn" => "运行游戏之前先评估游戏对系统硬件的要求。如果你的电脑只有 2G 内存，基本不能指望能流畅运行太大的次世代游戏。",
            ],
            "virtual_memory"         => [
                "en" => "Extracting high-compression packages consumes a lot of memory. You must enable virtual memory and manually set it to 1 to 1.5 times the physical memory.",
                "cn" => "解压高压包极其消耗内存。必须打开虚拟内存，必须将其手动设置为物理内存的 1 到 1.5 倍。",
            ],
            "virtual_memory_example" => [
                "en" => "Example: If the computer has 8G RAM, suggest increasing to 12GB - 16GB; if 16G RAM, set to 24GB (both initial and maximum values fill in 24576 MB).",
                "cn" => "示例：如果电脑是 8G 内存，建议加大到 12GB - 16GB；如果是 16G 内存，建议设为 24GB（初始值和最大值均填 24576 MB）。",
            ],
            "disk_space"             => [
                "en" => "When extracting the game, the hard drive needs 2-3 times the free space of the game installation package. For example, if the package is 10G, the target drive needs at least 20-30G of free space.",
                "cn" => "解压游戏时，硬盘需要有 2-3 倍于游戏安装包的可用空间。例如游戏安装包为 10G，那么目标盘至少需要留有 20-30G 的可用空间。",
            ],
            "ntfs_format"            => [
                "en" => "The hard disk partition format must be NTFS. The outdated FAT32 format cannot write single files larger than 4GB, which will cause the extraction to freeze.",
                "cn" => "硬盘分区格式必须是 NTFS，过时的 FAT32 格式无法写入大于 4GB 的单文件，会导致解压卡死。",
            ],
            "disable_antivirus"      => [
                "en" => "During game installation, temporarily disable Windows Defender or third-party antivirus software, or add the “game download directory” and “target installation directory” to the antivirus exclusion list.",
                "cn" => "在安装游戏期间，暂时关闭 Windows Defender 或第三方杀毒软件，或者将“游戏下载目录”和“准备安装的目标目录”整体加入杀毒软件的排除项（Exclusion）。",
            ],
            "limit_ram_usage"        => [
                "en" => "If the computer has less than 4G RAM, or high-end computers frequently report extraction errors, be sure to check “Limit installer to 2 GB of RAM usage” on the first interface during installation. This slightly limits extraction speed but greatly improves stability and solves 90% of memory overflow errors.",
                "cn" => "如果电脑内存低于 4G，或者高配电脑频繁解压报错，安装时务必勾选第一个界面上的“Limit installer to 2 GB of RAM usage”。这会稍微限制解压速度，但能极大提高稳定性，解决 90% 的内存溢出报错。",
            ],
            "run_as_admin"           => [
                "en" => "When running setup.exe, always right-click the icon and select “Run as administrator” to ensure the installer has full system read/write permissions.",
                "cn" => "运行 setup.exe 时，始终右键这个图标，选择“以管理员身份运行”，确保安装程序拥有完整的系统读写权限。",
            ],
            "path_restriction"       => [
                "en" => "The absolute path of the game installation cannot contain any Chinese characters, special symbols, or extra spaces.",
                "cn" => "游戏安装的绝对路径中不能包含任何中文字符、特殊符号或多余的空格。",
            ],
            "path_correct"           => [
                "en" => "Correct example: “D:\\Games\\Steam”",
                "cn" => "正确示例：“D:\\Games\\Steam”",
            ],
            "path_wrong"             => [
                "en" => "Wrong example: “D:\\游戏\\赛博朋克 2077”",
                "cn" => "错误示例：“D:\\游戏\\赛博朋克 2077”",
            ],
            "no_other_tasks"         => [
                "en" => "During the extraction process, the CPU and memory are pushed to the limit. At this time, strictly prohibit playing other large games, background rendering, or opening many browser tabs, otherwise it can easily cause resource contention leading to instant errors (such as Unarc.dll) or even a blue screen.",
                "cn" => "解压过程中，CPU 和内存会被逼向极限。此时严禁玩其他大作、挂机渲染或开大量浏览器网页，否则极易导致资源争抢而瞬间报错（如 Unarc.dll），甚至电脑直接蓝屏。",
            ],
            "install_runtimes"       => [
                "en" => "At the checkbox interface at the end of installation, be sure to check the installation of DirectX and Visual C++ Redistributable (runtime library suite). Many players successfully extract but cannot open the game (popping up 0xc000007b or missing various .dll files) because of missing these basic runtimes.",
                "cn" => "在安装结束时的勾选界面，务必勾选安装 DirectX 和 Visual C++ Redistributable（运行库全家桶）。很多玩家解压成功却打不开游戏（弹出 0xc000007b 或缺少各种 .dll），就是因为缺少这些基础运行库。",
            ],
            "defender_quarantine"    => [
                "en" => "If the installation completes smoothly but double-clicking the game icon has no response, 99% of the time it is because Windows Defender silently quarantined or deleted the “crack patch” (such as steam_api64.dll) in the game directory right after extraction. Go to the “Protection history” in Windows Security and select “Restore and allow” for that file.",
                "cn" => "如果安装顺利完成，但双击游戏图标没有任何反应，99% 是因为 Windows Defender 在解压结束的一瞬间，默默把游戏目录下的“破解补丁”（如 steam_api64.dll）给隔离或删除了。请前往 Windows 安全中心的“保护历史记录”中选择“还原并允许”该文件。",
            ],
            "no_download_dll"        => [
                "en" => "After encountering an Unarc.dll error, never download this DLL file alone from the internet and place it in the system drive. The game compression mechanism uses highly customized dedicated libraries; generic DLLs downloaded online are useless and can easily infect the system.",
                "cn" => "遇到 Unarc.dll 报错后，千万不要在网上单独下载这个 DLL 文件放进系统盘。游戏压缩机制使用的是高度定制的专用库，网上下载的通用 DLL 根本无济于事，还极易导致系统中毒。",
            ],
            "reinstall_system"       => [
                "en" => "If you have strictly followed all the above configurations and requirements and still stubbornly get an Unarc.dll error during installation, you basically can only consider reinstalling a pure version of the system.",
                "cn" => "如果你严格执行了以上所有的配置和要求，安装时依然顽固弹出 Unarc.dll 错误，基本只能考虑重装纯净版系统。",
            ],
            "change_machine"         => [
                "en" => "If it still does not work, then consider installing on another machine.",
                "cn" => "如果还不行，则应该考虑更换其他机器安装。",
            ],
        ];

        const IMAGE_STATUS_0 = 0;
        const IMAGE_STATUS_1 = 1;
        const IMAGE_STATUS_2 = 2;

        const IMAGE_TYPE_SCREENSHOT = 0;
        const IMAGE_TYPE_WALLPAPERS = 1;
        const IMAGE_TYPE_ARTWORKS   = 2;

        /**********************************************************************************/

        public function __construct(array $config = [])
        {
            foreach ($config as $k => $v)
            {
                if (property_exists($this, $k))
                {
                    $this->$k = $v;
                }
            }

            $this->redisLogName = $this->mysqlDbName . ':';

            $this->initGameSourceManager();
            $this->initWpManager();
            $this->initTgManager();
        }

        private function langEcho(string $key, array $data = []): string
        {
            // 先校验翻译键是否存在，避免报错
            if (!isset($this->langMap[$key][$this->lang]))
            {
                return '__wrong__';
            }

            $result = sprintf($this->langMap[$key][$this->lang], ...$data);

            return $result !== false ? $result : '__wrong__';
        }

        private function getNoteList(): array
        {
            $notes = [];
            foreach ($this->noteMap as $key => $v)
            {
                $notes[] = $this->noteMap[$key][$this->lang] ?? '__wrong__';
            }

            return $notes;
        }

        public function setLangEn(): static
        {
            $this->lang = 'en';

            return $this;
        }

        public function setLangCn(): static
        {
            $this->lang = 'cn';

            return $this;
        }

        public function initWpManager(): void
        {
            $this->wpManager = new Manager($this->redisLogName);
            $this->wpManager->setRedisConfig($this->redisHost, $this->redisPassword, $this->redisPort, $this->redisDbIndex);
            $this->wpManager->setMysqlConfig($this->mysqlDbName, $this->mysqlHost, $this->mysqlUsername, $this->mysqlPassword, $this->mysqlPort);
            $this->wpManager->setEnableRedisLog($this->redisLogEnable);
            $this->wpManager->setEnableEchoLog($this->debug);
            $this->wpManager->initServer();
            $this->wpManager->initTableStruct();
        }

        public function initGameSourceManager(): void
        {
            $this->gameSourceManager = new GameSourceManager();
            $this->gameSourceManager->setRedisConfig($this->redisHost, $this->redisPassword, $this->redisPort, $this->redisDbIndex);
            $this->gameSourceManager->setMysqlConfig($this->mysqlDbName, $this->mysqlHost, $this->mysqlUsername, $this->mysqlPassword, $this->mysqlPort);
            $this->gameSourceManager->setDebug($this->debug);
            $this->gameSourceManager->setLogNamespace($this->redisLogName);
            $this->gameSourceManager->setEnableRedisLog($this->redisLogEnable);
            $this->gameSourceManager->setEnableEchoLog($this->debug);
            $this->gameSourceManager->setHeaderStr($this->headerStr);
            $this->gameSourceManager->setInfoUrlMap($this->infoUrlMap);
            $this->gameSourceManager->setProxy($this->proxy);
            $this->gameSourceManager->setCachePath($this->cachePath);
            $this->gameSourceManager->setRetryTimes($this->retryTimes);
            $this->gameSourceManager->setConcurrency($this->concurrency);
            $this->gameSourceManager->initServer();
            $this->gameSourceManager->initTableStruct();
        }

        public function initTgManager(): void
        {
            $this->tgManager = new TgManager($this);
            $this->tgManager->setProxy($this->proxy);

        }

        /**********************************************************************************/
        // to wp
        /**********************************************************************************/

        public function updateToWpPost(callable $payPostCallback = null, int $typeId = 1, bool $insertOnly = false): void
        {
            $gameImagesTable = $this->gameSourceManager->getGameImagesTable();
            $gameTable       = $this->gameSourceManager->getGameTable();
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

                $price = call_user_func_array($payPostCallback, [
                    $post,
                    $this,
                ]);
                $isPay = $price > 0;

                //正文内容
                $contents = $this->makePostContentByPostInfo($post, $isPay);
                $this->wpManager->getMysqlClient()->logInfo('创建文章: ' . ($k + 1) . '--' . $title);
                $wpPostId = $this->wpManager->addPost($title, $contents, $typeId, $postId);

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

            if (!$insertOnly)
            {
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
            }

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
            $gameTable       = $this->gameSourceManager->getGameTable();
            $gameImagesTable = $this->gameSourceManager->getGameImagesTable();

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
            $imageGroup[static::IMAGE_TYPE_SCREENSHOT]['title'] = $this->langEcho('screenshots');
            $imageGroup[static::IMAGE_TYPE_WALLPAPERS]['title'] = $this->langEcho('wallpapers');
            $imageGroup[static::IMAGE_TYPE_ARTWORKS]['title']   = $this->langEcho('artworks');

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
                $cover = WpTag::p($this->langEcho('no_preview_image_available'));
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
                    WpTag::p(Tag::span($this->langEcho('original_size')) . ': ' . Tag::strong($post[$gameTable->getOriginalSizeField()])),
                    WpTag::p(Tag::span($this->langEcho('repack_size')) . ': ' . Tag::strong($post[$gameTable->getRepackSizeField()])),
                    WpTag::p(Tag::span($this->langEcho('languages')) . ': ' . Tag::strong($post[$gameTable->getLangField()])),
                    WpTag::p(Tag::span($this->langEcho('companies')) . ': ' . Tag::strong($post[$gameTable->getCompanyField()])),
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
                    "title"   => $this->langEcho('game_description'),
                    "content" => WpTag::list($description, 'blue'),
                ];
            }

            $features = json_decode($post[$gameTable->getFeaturesField()], 1);
            if ($features && count($features))
            {
                $texts[] = [
                    "title"   => $this->langEcho('game_features'),
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
            $downloadLinksGroup['magnet']['title']      = $this->langEcho('magnet');

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
            $textLen            = 80;

            /*
                        //下载链接：无按钮，纯链接
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
            */

            /*
                        //下载链接：按钮风格
                        foreach ($downloadLinksGroup as $k => $v)
                        {
                            if (isset($v['links']))
                            {
                                $linkBtns = [];

                                foreach ($v['links'] as $k1 => $link)
                                {
                                    $linkBtns[] = [
                                        "link"     => $link,
                                        "text"     => substr($link,0,80),
                                        "btnColor" => "orange",
                                    ];
                                }

                                $downloadUrls[] = [
                                    "title"   => $v['title'],
                                    //                        "content" => WpTag::list($v['links'], 'red'),
                                    "content" => WpTag:: buttons($linkBtns),
                                ];
                            }
                        }
            */

            /*
                        //下载链接：link风格
                        foreach ($downloadLinksGroup as $k => $v)
                        {
                            if (isset($v['links']))
                            {
                                $linkBtns = [];

                                foreach ($v['links'] as $k1 => $link)
                                {
                                    $linkBtns[] = WpTag::p([Tag::a($link, substr($link, 0, 80))]);
                                }

                                $downloadUrls[] = [
                                    "title"   => $v['title'],
                                    "content" => WpTag::list($linkBtns, 'red'),
                                ];
                            }
                        }

            */

            //下载链接：link风格
            foreach ($downloadLinksGroup as $k => $v)
            {
                if (isset($v['links']))
                {
                    $linkBtns = [];

                    foreach ($v['links'] as $k1 => $link)
                    {
                        $text       = strlen($link) > $textLen ? substr($link, 0, $textLen) . '...' : $link;
                        $linkBtns[] = Tag::a($link, $text, 'red');
                    }

                    $downloadUrls[] = [
                        "title"   => $v['title'],
                        //"content" => WpTag::listQuote($linkBtns, 'red'),
                        "content" => WpTag::list($linkBtns, 'red'),
                    ];
                }
            }

            $contents[] = WpTag::p($this->langEcho('download_mirrors'), 'default', '24px');
            $contents[] = WpTag::p($this->langEcho('recommended_client') . Tag::a('https://github.com/GopeedLab/gopeed/releases', 'Gopeed'), 'red');

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
                $contents[] = WpTag::p($this->langEcho('no_download_resources_available'), 'red', '28px');
            }

            /******************************************/
            $updatesLinks = json_decode($post[$gameTable->getUpdatesLinksField()], 2);
            if ($updatesLinks && count($updatesLinks))
            {
                $contents[] = WpTag::p($this->langEcho('game_updates'), 'default', '24px');
                $links      = [];

                foreach ($updatesLinks as $k => $v)
                {
                    $links[] = Tag::a($v['link'], $v['name']);
                }
//                $contents[] = WpTag::listQuote($links, 'red');
                $contents[] = WpTag::list($links, 'red');
            }

            /******************************************/

            $discussionUrl = $post[$gameTable->getDiscussionUrlField()];
            if ($discussionUrl)
            {
                $contents[] = WpTag::p($this->langEcho('discussion_future_update'), 'default', '24px');
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
                    $contents[] = WpTag::p($this->langEcho('website_links'), 'default', '24px');

                    $links = [];

                    foreach ($websiteLinks as $k => $v)
                    {
                        $links[] = Tag::a($v, $v);
                    }
                    $contents[] = WpTag::list($links, 'red');
                }
            }

            /******************************************/
            //注意事项
            $notes = $this->getNoteList();

            $contents[] = WpTag::zibllTabs([
                [
                    "title"   => $this->langEcho('precautions'),
                    "content" => WpTag::list($notes, 'blue'),
                ],
            ]);

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


        /**********************************************************************************/
        // to tg
        /**********************************************************************************/

        public function sendToTgMessage()
        {
            $gameImagesTable = $this->gameSourceManager->getGameImagesTable();
            $gameTable       = $this->gameSourceManager->getGameTable();
            $processFile     = rtrim($this->processPath, '\/\\') . DIRECTORY_SEPARATOR . $this->postTgChatId . '.txt';

            is_dir(dirname($processFile)) or mkdir(dirname($processFile), 0755, true);

            $processPostId = 0;
            if (is_file($processFile))
            {
                $processPostId = (int)file_get_contents($processFile);
            }

            $where = [];

            if ($processPostId > 0)
            {
                $where = [
                    [
                        $gameTable->getPkField(),
                        '>',
                        $processPostId,
                    ],
                ];

                $this->gameSourceManager->getMysqlClient()->logInfo('进度文件：' . $processPostId);
            }

            $postIds = $gameTable->tableIns()/*
                ->where($gameTable->getPkField(), 'in', [
                '1287912366851228215',
                '1287912366918340561',
                '1287912366943504034',
                '1287912366972862744',
            ])->page(1, 100)*/

            ->where($where)->order($gameTable->getPkField())->column($gameTable->getPkField());

            $posts = $gameTable->tableIns()->where($gameTable->getPkField(), 'in', $postIds)->select()->toArray();

            $this->wpManager->getMysqlClient()->logInfo('创建文章个数: ' . count($posts));

            foreach ($posts as $k => $post)
            {
                $postId = $post[$gameTable->getPkField()];
                $title  = $post[$gameTable->getNameField()];

                //正文内容
                $contents = $this->makeTgMessageContentByPostInfo($post);
                $this->gameSourceManager->getMysqlClient()->logInfo('创建文章: ' . ($k + 1) . '--' . $title);

                $fileIds = $this->tgManager->sendImageMessage($contents['images'], $this->postTgChatId, $this->postTgBotToken, $contents['html'], 'html');

                $this->gameSourceManager->getMysqlClient()->logInfo('等 1 S');
                sleep(1);

                $messageId = $this->tgManager->sendTextMessage($contents['download'], $this->postTgChatId, $this->postTgBotToken, 'html');

                if ($messageId)
                {
                    $this->gameSourceManager->getMysqlClient()->logInfo('两条信息发成功，写入进度文件：' . $postId);
                    file_put_contents($processFile, $postId);
                }

                $t = rand($this->postTgSleepMin, $this->postTgSleepMax);
                $this->gameSourceManager->getMysqlClient()->logInfo('等 ' . $t . ' S');

                sleep($t);
            }

        }

        public function backupCvoerImage()
        {
            $this->tgManager->backupCvoerImage();
        }

        public function backupScreenShotImage()
        {
            $this->tgManager->backupScreenShotImage();
        }

        protected function makeTgMessageContentByPostInfo(array $post): array
        {
            $gameTable       = $this->gameSourceManager->getGameTable();
            $gameImagesTable = $this->gameSourceManager->getGameImagesTable();

            /******************************************/
            while (true)
            {
                //剧照
                //有图片的
                $gameInfo = $gameTable->tableIns()->where([
                    [
                        $gameTable->getPkField(),
                        '=',
                        $post[$gameTable->getPkField()],
                    ],
                ])->find();

                $tgFileId = '';
                if (str_starts_with($post[$gameTable->getCoverLinkField()], 'c/'))
                {
                    $tgFileId = $gameInfo[$gameTable->getTgFileIdField()];
                    if (!$tgFileId)
                    {
                        $this->gameSourceManager->getMysqlClient()
                            ->logInfo('剧照没上传：' . $post[$gameTable->getPkField()]);

                        $t = 10;
                        $this->gameSourceManager->getMysqlClient()->logInfo('等 ' . $t . ' S');
                        sleep($t);
                        continue;
                    }
                }

                //截图
                $images = $gameImagesTable->tableIns()->where([
                    [
                        $gameImagesTable->getGameIdField(),
                        '=',
                        $post[$gameTable->getPkField()],
                    ],
                ])->order($gameImagesTable->getPkField(), 'asc')->limit(6)->column($gameTable->getTgFileIdField());

                if (in_array('', $images))
                {
                    $this->gameSourceManager->getMysqlClient()
                        ->logInfo('截图没上传完：' . $post[$gameTable->getPkField()]);

                    $t = 10;
                    $this->gameSourceManager->getMysqlClient()->logInfo('等 ' . $t . ' S');
                    sleep($t);

                    continue;
                }

                break;
            }

            //如果有剧照就放到第一张图9
            if ($tgFileId)
            {
                array_unshift($images, $tgFileId);
            }

            //下载链接
            $downloadLinks = json_decode($post[$gameTable->getDownloadLinksField()], 1);

            //-------------------------------------------------
            $descriptionParts   = [];
            $descriptionParts[] = TelegramTagHTML::title('[' . $post[$gameTable->getPkField()] . '] ');
            $descriptionParts[] = TelegramTagHTML::kv($this->langEcho('game_name'), $post[$gameTable->getNameField()]);
            $descriptionParts[] = TelegramTagHTML::kv($this->langEcho('original_size'), $post[$gameTable->getOriginalSizeField()]);
            $descriptionParts[] = TelegramTagHTML::kv($this->langEcho('repack_size'), $post[$gameTable->getRepackSizeField()]);
            $descriptionParts[] = TelegramTagHTML::kv($this->langEcho('languages'), $post[$gameTable->getLangField()]);
            $descriptionParts[] = TelegramTagHTML::kv($this->langEcho('companies'), $post[$gameTable->getCompanyField()]);
            $descriptionParts[] = TelegramTagHTML::line();
            $descriptionParts[] = TelegramTagHTML::title($this->langEcho('more_game'));
            $descriptionParts[] = TelegramTagHTML::kvRaw($this->langEcho('site_url'), TelegramTagHTML::a($this->mainSite, $this->mainSite));
            $descriptionParts[] = TelegramTagHTML::line();
            $descriptionParts[] = TelegramTagHTML::tags(explode(',', $post[$gameTable->getTagsField()]));

            //-------------------------------------------------
            $downloadParts   = [];
            $downloadParts[] = TelegramTagHTML::title('[' . $post[$gameTable->getPkField()] . '] ');

            $downloadParts[] = TelegramTagHTML::line();
            $downloadParts[] = TelegramTagHTML::kv($this->langEcho('game_name'), $post[$gameTable->getNameField()]);
            $downloadParts[] = TelegramTagHTML::br();

            $downloadLink = '';
            if (count($downloadLinks))
            {
                foreach ($downloadLinks as $k => $urls)
                {
                    if ($k == 'magnet')
                    {
                        if (count($urls))
                        {
                            $downloadLink = $urls[0];
                        }
                    }
                }
            }

            if ($downloadLink)
            {
                $downloadParts[] = TelegramTagHTML::b($this->langEcho('manual_download'));
                $downloadParts[] = TelegramTagHTML::title($this->langEcho('download_mirrors') . ':');
                $downloadParts[] = TelegramTagHTML::blockquote(static::limitMagnetTrackers($downloadLink,5));
            }
            else
            {
                $downloadParts[] = TelegramTagHTML::b($this->langEcho('only_magnet'));
            }

            //-------------------------------------------------
            $result['html']     = TelegramContentHTML::toString($descriptionParts);
            $result['download'] = TelegramContentHTML::toString($downloadParts);
            $result['images']   = $images;

            return $result;
        }


        /**
         * 截断 magnet 链接中的 tracker，只保留前 N 个
         *
         * @param string $magnet      原始 magnet 链接（支持带 &amp; 的 HTML 实体形式）
         * @param int    $maxTrackers 最多保留几个 tracker，默认 5
         * @return string             处理后的 magnet 链接
         */
        protected static function limitMagnetTrackers(string $magnet, int $maxTrackers = 5): string
        {
            // 先把 HTML 实体还原成真正的 &
            $magnet = html_entity_decode($magnet, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // 不是 magnet 链接直接返回
            if (stripos($magnet, 'magnet:?') !== 0) {
                return $magnet;
            }

            // 去掉 "magnet:?" 前缀，按 & 拆分参数
            $query = substr($magnet, 8);
            $parts = explode('&', $query);

            $result        = [];
            $trackerCount  = 0;

            foreach ($parts as $part) {
                // 空参数跳过
                if ($part === '') {
                    continue;
                }

                // 判断是否是 tracker（tr= 或 tr%3D 都兼容）
                if (stripos($part, 'tr=') === 0 || stripos($part, 'tr%3D') === 0) {
                    if ($trackerCount < $maxTrackers) {
                        $result[] = $part;
                        $trackerCount++;
                    }
                    // 超过数量的 tracker 直接丢弃
                    continue;
                }

                // 非 tracker 参数全部保留（xt、dn、xl 等）
                $result[] = $part;
            }

            return 'magnet:?' . implode('&', $result);
        }

        /**********************************************************************************/
        //common
        /**********************************************************************************/

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


    }