<?php

    use Coco\fitDownloader\TgCaptainSdk\TelegramContentHTML;
    use Coco\fitDownloader\TgCaptainSdk\TelegramTagHTML;

    require './common.php';

    /**
     * 使用方式：
     * parse_mode 必须设为 HTML（因为 TelegramTag 生成的是 HTML 标签）
     */

// ============================================================
// 1. TelegramTag 原子方法演示（全部方法）
// ============================================================

    echo "========== 1. TelegramTag 原子方法 ==========\n\n";

// ---------- 基础转义 ----------
// escape：所有用户可控文本必须先转义，防止 HTML 注入
    $rawUserInput = '游戏名 <script>alert(1)</script> & "特殊字符"';
    $escaped      = TelegramTagHTML::escape($rawUserInput);
    echo "escape:\n{$escaped}\n\n";

// ---------- 粗体 / 斜体 / 下划线 / 删除线 / 剧透 ----------
    echo "b (粗体): " . TelegramTagHTML::b('这是粗体') . "\n";
    echo "i (斜体): " . TelegramTagHTML::i('这是斜体') . "\n";
    echo "u (下划线): " . TelegramTagHTML::u('这是下划线') . "\n";
    echo "s (删除线): " . TelegramTagHTML::s('这是删除线') . "\n";
    echo "spoiler (剧透): " . TelegramTagHTML::spoiler('这是隐藏内容，点击才显示') . "\n\n";

// ---------- 行内代码 / 代码块 ----------
    echo "code (行内代码): " . TelegramTagHTML::code('php artisan serve') . "\n";
    echo "pre (代码块无语言):\n" . TelegramTagHTML::pre("<?php\necho 'hello';\n") . "\n";
    echo "pre (代码块带语言):\n" . TelegramTagHTML::pre("function hello() {\n  return 'world';\n}", 'php') . "\n\n";

// ---------- 链接 ----------
// a：自动 escape url 和显示文字
    echo "a (普通链接): " . TelegramTagHTML::a('https://example.com/game?id=1&name=test', '点击下载') . "\n";
    echo "a (显示文字为空，用 url 本身): " . TelegramTagHTML::a('https://example.com') . "\n";

// aWithoutEscape：当你已经自己处理过转义，或需要保留特殊字符时使用（慎用）
    echo "aWithoutEscape: " . TelegramTagHTML::aWithoutEscape('https://example.com/path?x=1&y=2', '原始链接') . "\n\n";

// ---------- 键值对 ----------
// kv：label 加粗，value 自动 escape（适合纯文本）
    echo "kv: " . TelegramTagHTML::kv('Original Size', '12.3 GB') . "\n";
    echo "kv (自定义分隔符): " . TelegramTagHTML::kv('Version', 'v1.1.3.0', ' → ') . "\n";

// kvRaw：value 不转义，用于已经生成好的 HTML 片段
    echo "kvRaw: " . TelegramTagHTML::kvRaw('Site', TelegramTagHTML::a('https://fitgirl-repacks.site', 'FitGirl')) . "\n\n";

// ---------- 段落 / 标题 ----------
// p：内容 + 尾部换行，并 escape
    echo "p:\n" . TelegramTagHTML::p('这是一段普通文字，会自动转义。') . "\n";

// pRaw：不转义
    echo "pRaw:\n" . TelegramTagHTML::pRaw(TelegramTagHTML::b('已加粗') . ' 的段落') . "\n";

// title：加粗 + 前后空行
    echo "title:\n" . TelegramTagHTML::title('游戏更新通知') . "\n";

// titleRaw：不转义，可嵌套其他标签
    echo "titleRaw:\n" . TelegramTagHTML::titleRaw(TelegramTagHTML::i('斜体标题') . ' + ' . TelegramTagHTML::b('粗体')) . "\n";

// ---------- 引用块 ----------
    echo "blockquote:\n" . TelegramTagHTML::blockquote('这是一段被引用的说明文字。') . "\n";
    echo "blockquoteRaw:\n" . TelegramTagHTML::blockquoteRaw(TelegramTagHTML::b('重要') . '：请先关闭杀毒软件') . "\n\n";

// ---------- 换行 / 空行 ----------
    echo "br: 上一行" . TelegramTagHTML::br() . "下一行\n";
    echo "line: 上一行" . TelegramTagHTML::line() . "空一行后的内容\n\n";

// ---------- 列表（自动 escape） ----------
    $plainItems = [
        '支持 2D 与 3D 模式',
        '包含完整 OST',
        '修复了 <崩溃> 问题',
        // 会被 escape
    ];
    echo "list (bullet):\n" . TelegramTagHTML::list($plainItems) . "\n\n";
    echo "list (dash):\n" . TelegramTagHTML::list($plainItems, 'dash') . "\n\n";
    echo "list (number):\n" . TelegramTagHTML::list($plainItems, 'number') . "\n\n";
    echo "list (arrow):\n" . TelegramTagHTML::list($plainItems, 'arrow') . "\n\n";
    echo "list (custom):\n" . TelegramTagHTML::list($plainItems, 'custom', '★ ') . "\n\n";

// ---------- 列表（不转义，适合放链接等 HTML） ----------
    $htmlItems = [
        TelegramTagHTML::a('https://example.com/1', '下载链接 1'),
        TelegramTagHTML::a('https://example.com/2', '下载链接 2'),
        TelegramTagHTML::b('额外说明') . '：请使用最新客户端',
    ];
    echo "listRaw (bullet):\n" . TelegramTagHTML::listRaw($htmlItems) . "\n\n";
    echo "listRaw (number):\n" . TelegramTagHTML::listRaw($htmlItems, 'number') . "\n\n";

// ---------- 原始 HTML 片段 ----------
// raw：已经由本类生成好的安全 HTML，直接拼接时用
    $safeHtml = TelegramTagHTML::b('已安全') . ' 的内容';
    echo "raw: " . TelegramTagHTML::raw($safeHtml) . "\n\n";

// ---------- 话题标签 ----------
    $tags = [
        '2D',
        '3D',
        'Action Game',
        '  RPG  ',
        '#已有井号',
        '',
        '!!!',
        '中文标签',
    ];
    echo "tags:\n" . TelegramTagHTML::tags($tags) . "\n";
    echo "tags (自定义分隔符):\n" . TelegramTagHTML::tags($tags, ' | ') . "\n\n";

// ============================================================
// 2. TelegramContent 组装演示
// ============================================================

    echo "========== 2. TelegramContent 组装 ==========\n\n";

// 最简单的用法：把多个字符串按换行拼起来，自动过滤空值
    $simple = TelegramContentHTML::toString([
        '第一行',
        '',
        // 会被过滤
        null,
        // 会被过滤
        '第二行',
        '第三行',
    ]);
    echo "简单拼接:\n{$simple}\n\n";

// 自定义连接符（例如用两个换行）
    $withGlue = TelegramContentHTML::toString([
        '段落一',
        '段落二',
        '段落三',
    ], "\n\n");
    echo "自定义 glue:\n{$withGlue}\n\n";

// 嵌套数组会自动递归展开
    $nested = TelegramContentHTML::toString([
        '标题行',
        [
            '嵌套第一行',
            [
                '更深层的内容',
                '',
            ],
            '嵌套最后一行',
        ],
        '结尾',
    ]);
    echo "嵌套数组:\n{$nested}\n\n";

// ============================================================
// 3. 真实业务场景：游戏更新通知 caption
// ============================================================

    echo "========== 3. 真实业务场景 Demo ==========\n\n";

    /**
     * 模拟一次游戏更新的数据
     */
    $gameTitle  = 'Supporter Edition – v1.1.3.0 (MS Store) + Bonus OST';
    $version    = 'v1.1.3.0';
    $size       = '12.3 GB';
    $repackSize = '4.8 GB';
    $siteUrl    = 'https://fitgirl-repacks.site/supporter-edition';
    $magnet     = 'magnet:?xt=urn:btih:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $genres     = [
        'Action',
        'RPG',
        '2D',
        'Open World',
    ];
    $features   = [
        '完整支持者版内容',
        '附赠完整 OST',
        '修复启动崩溃',
        '支持中文界面',
    ];
    $changelog  = "1. 修复了部分机型黑屏问题\n2. 优化了内存占用\n3. 新增成就系统";

    /**
     * 用 TelegramTag 生成各个原子片段，再用 TelegramContent 组装
     * 最终得到干净、可直接发给 Telegram 的 HTML caption
     */
    $caption = TelegramContentHTML::toString([

        // 标题
        TelegramTagHTML::title($gameTitle),

        // 基本信息（kv / kvRaw）
        TelegramTagHTML::kv('Version', $version),
        TelegramTagHTML::kv('Original Size', $size),
        TelegramTagHTML::kv('Repack Size', $repackSize),
        TelegramTagHTML::kvRaw('Site', TelegramTagHTML::a($siteUrl, '前往官网')),

        // 空一行
        TelegramTagHTML::line(),

        // 功能列表
        TelegramTagHTML::b('Features'),
        TelegramTagHTML::list($features, 'bullet'),

        // 空一行
        TelegramTagHTML::br(),

        // 更新日志（用 blockquote 更醒目）
        TelegramTagHTML::b('Changelog'),
        TelegramTagHTML::blockquote($changelog),

        // 空一行
        TelegramTagHTML::br(),

        // 下载链接（listRaw 可放 HTML）
        TelegramTagHTML::b('Download'),
        TelegramTagHTML::listRaw([
            TelegramTagHTML::a($siteUrl, '官网页面'),
            TelegramTagHTML::code($magnet),
            // 磁力链接用 code 方便复制
        ], 'arrow'),

        // 空一行
        TelegramTagHTML::line(),

        // 话题标签
        TelegramTagHTML::tags($genres),

        // 结尾提示
        TelegramTagHTML::i('请关闭杀毒软件后安装，安装完成后可重新开启。'),
    ]);

    echo "最终 caption（可直接用于 sendPhoto / sendMediaGroup 的 caption）:\n";
    echo "----------------------------------------\n";
    echo $caption;
    echo "\n----------------------------------------\n\n";

// ============================================================
// 4. 纯文本消息 Demo（不需要图片时）
// ============================================================

    echo "========== 4. 纯文本消息 Demo ==========\n\n";

    $textMessage = TelegramContentHTML::toString([
        TelegramTagHTML::title('系统通知'),
        TelegramTagHTML::p('今日已成功同步 137 篇文章。'),
        TelegramTagHTML::kv('成功', '135'),
        TelegramTagHTML::kv('失败', '2'),
        TelegramTagHTML::br(),
        TelegramTagHTML::blockquoteRaw(TelegramTagHTML::b('注意') . '：失败的 2 条是因为图片 file_id 为空，已自动跳过。'),
        TelegramTagHTML::br(),
        TelegramTagHTML::tags([
            '同步完成',
            '系统通知',
        ]),
    ]);

    echo "纯文本消息:\n";
    echo "----------------------------------------\n";
    echo $textMessage;
    echo "\n----------------------------------------\n\n";

// ============================================================
// 5. 如何在 TgManager 里实际调用（示意）
// ============================================================

    /*
     * 假设你已经有 TgManager 实例 $tgManager
     *
     * // 发带图片的消息（caption 用上面组装好的 $caption）
     * $tgManager->sendImageMessage(
     *     $imagePathsOrFileIds,          // 图片数组
     *     $chatId,
     *     $botToken,
     *     $caption,                      // 这里传入
     *     'HTML'                         // 必须用 HTML，因为 TelegramTag 生成的是 HTML
     * );
     *
     * // 发纯文本
     * $tgManager->sendTextMessage(
     *     $textMessage,
     *     $chatId,
     *     $botToken,
     *     'HTML'
     * );
     */

    echo "Demo 结束。所有方法均已覆盖。\n";