<?php

    use Coco\fitDownloader\TgCaptainSdk\TelegramContentMarkdownV2;
    use Coco\fitDownloader\TgCaptainSdk\TelegramTagMarkdownV2;

    require './common.php';

// ============================================================
// 1. TelegramTagMarkdownV2 原子方法演示（全部方法）
// ============================================================

    echo "========== 1. TelegramTagMarkdownV2 原子方法（MarkdownV2） ==========\n\n";

// ---------- 基础转义 ----------
// escape：所有用户可控文本必须先转义，防止破坏 MarkdownV2 语法
// 必须转义的字符：_ * [ ] ( ) ~ ` > # + - = | { } . !
    $rawUserInput = '游戏名 *粗体测试* _斜体_ [链接] (括号) ~删除~ `代码` >引用 #话题 +加 -减 =等 |竖 {花} .点 !感叹';
    $escaped      = TelegramTagMarkdownV2::escape($rawUserInput);
    echo "escape（原始含大量特殊字符）:\n{$escaped}\n\n";

// 可选：保留某些字符不转义（高级用法）
    $keepSome = TelegramTagMarkdownV2::escape('保留星号 * 和点 .', [
        '*',
        '.',
    ]);
    echo "escape（保留 * 和 .）:\n{$keepSome}\n\n";

// ---------- 粗体 / 斜体 / 下划线 / 删除线 / 剧透 ----------
    echo "b (粗体): " . TelegramTagMarkdownV2::b('这是粗体') . "\n";
    echo "i (斜体): " . TelegramTagMarkdownV2::i('这是斜体') . "\n";
    echo "u (下划线): " . TelegramTagMarkdownV2::u('这是下划线') . "\n";
    echo "s (删除线): " . TelegramTagMarkdownV2::s('这是删除线') . "\n";
    echo "spoiler (剧透): " . TelegramTagMarkdownV2::spoiler('这是隐藏内容，点击才显示') . "\n\n";

// ---------- 行内代码 / 代码块 ----------
    echo "code (行内代码): " . TelegramTagMarkdownV2::code('php artisan serve') . "\n";
    echo "code (含反引号和反斜杠): " . TelegramTagMarkdownV2::code('echo `hello`; path\\to\\file') . "\n";

    echo "pre (代码块无语言):\n" . TelegramTagMarkdownV2::pre("<?php\necho 'hello';\n") . "\n";
    echo "pre (代码块带语言):\n" . TelegramTagMarkdownV2::pre("function hello() {\n  return 'world';\n}", 'php') . "\n\n";

// ---------- 链接 ----------
// a：自动 escape 显示文字 + 必要转义 URL
    echo "a (普通链接): " . TelegramTagMarkdownV2::a('https://example.com/game?id=1&name=test', '点击下载') . "\n";
    echo "a (显示文字为空，用 url 本身): " . TelegramTagMarkdownV2::a('https://example.com') . "\n";
    echo "a (URL 含右括号): " . TelegramTagMarkdownV2::a('https://example.com/path(with)', '带括号的链接') . "\n";

// aWithoutEscape：显示文字不转义（url 仍做必要转义）
    echo "aWithoutEscape: " . TelegramTagMarkdownV2::aWithoutEscape('https://example.com/path?x=1&y=2', '原始*显示*文字') . "\n\n";

// ---------- 键值对 ----------
// kv：label 加粗，value 自动 escape（适合纯文本）
    echo "kv: " . TelegramTagMarkdownV2::kv('Original Size', '12.3 GB') . "\n";
    echo "kv (自定义分隔符): " . TelegramTagMarkdownV2::kv('Version', 'v1.1.3.0', ' → ') . "\n";
    echo "kv (value 含特殊字符): " . TelegramTagMarkdownV2::kv('Note', '支持 *粗体* 与 _斜体_') . "\n";

// kvRaw：value 不转义，用于已经生成好的 Markdown 片段
    echo "kvRaw: " . TelegramTagMarkdownV2::kvRaw('Site', TelegramTagMarkdownV2::a('https://fitgirl-repacks.site', 'FitGirl')) . "\n\n";

// ---------- 段落 / 标题 ----------
// p：内容 + 尾部换行，并 escape
    echo "p:\n" . TelegramTagMarkdownV2::p('这是一段普通文字，会自动转义 * _ [ ] 等字符。') . "\n";

// pRaw：不转义
    echo "pRaw:\n" . TelegramTagMarkdownV2::pRaw(TelegramTagMarkdownV2::b('已加粗') . ' 的段落') . "\n";

// title：加粗 + 前后空行
    echo "title:\n" . TelegramTagMarkdownV2::title('游戏更新通知') . "\n";

// titleRaw：不转义，可嵌套其他标签
    echo "titleRaw:\n" . TelegramTagMarkdownV2::titleRaw(TelegramTagMarkdownV2::i('斜体标题') . ' + ' . TelegramTagMarkdownV2::b('粗体')) . "\n";

// ---------- 引用块 ----------
    echo "blockquote:\n" . TelegramTagMarkdownV2::blockquote("这是一段被引用的说明文字。\n第二行也会自动加 >") . "\n\n";
    echo "blockquoteRaw:\n" . TelegramTagMarkdownV2::blockquoteRaw(TelegramTagMarkdownV2::b('重要') . '：请先关闭杀毒软件') . "\n\n";

// 可折叠引用（新客户端支持）
    echo "expandableBlockquote:\n" . TelegramTagMarkdownV2::expandableBlockquote("这是可折叠的长内容\n第二行\n第三行") . "\n\n";

// ---------- 换行 / 空行 ----------
    echo "br: 上一行" . TelegramTagMarkdownV2::br() . "下一行\n";
    echo "line: 上一行" . TelegramTagMarkdownV2::line() . "空一行后的内容\n\n";

// ---------- 列表（自动 escape） ----------
    $plainItems = [
        '支持 2D 与 3D 模式',
        '包含完整 OST',
        '修复了 *崩溃* 问题',
        // * 会被 escape
        '版本 1.2.3',
        // . 会被 escape
    ];
    echo "list (bullet):\n" . TelegramTagMarkdownV2::list($plainItems) . "\n\n";
    echo "list (dash):\n" . TelegramTagMarkdownV2::list($plainItems, 'dash') . "\n\n";
    echo "list (number):\n" . TelegramTagMarkdownV2::list($plainItems, 'number') . "\n\n";
    echo "list (arrow):\n" . TelegramTagMarkdownV2::list($plainItems, 'arrow') . "\n\n";
    echo "list (custom):\n" . TelegramTagMarkdownV2::list($plainItems, 'custom', '★ ') . "\n\n";

// ---------- 列表（不转义，适合放链接等已生成的 Markdown） ----------
    $htmlItems = [   // 变量名沿用，实际是 Markdown 片段
                     TelegramTagMarkdownV2::a('https://example.com/1', '下载链接 1'),
                     TelegramTagMarkdownV2::a('https://example.com/2', '下载链接 2'),
                     TelegramTagMarkdownV2::b('额外说明') . '：请使用最新客户端',
    ];
    echo "listRaw (bullet):\n" . TelegramTagMarkdownV2::listRaw($htmlItems) . "\n\n";
    echo "listRaw (number):\n" . TelegramTagMarkdownV2::listRaw($htmlItems, 'number') . "\n\n";
    echo "listRaw (arrow):\n" . TelegramTagMarkdownV2::listRaw($htmlItems, 'arrow') . "\n\n";

// ---------- 原始 Markdown 片段 ----------
// raw：已经由本类生成好的安全 Markdown，直接拼接时用
    $safeMarkdown = TelegramTagMarkdownV2::b('已安全') . ' 的内容';
    echo "raw: " . TelegramTagMarkdownV2::raw($safeMarkdown) . "\n\n";

// ---------- 话题标签 ----------
// 注意：MarkdownV2 中 # 必须转义，所以最终是 \#2D \#3D
    $tags = [
        '2D',
        '3D',
        'Action Game',
        '  RPG  ',
        '#已有井号',
        '',
        '!!!',
        '中文标签',
        'v1.2.3',
    ];
    echo "tags:\n" . TelegramTagMarkdownV2::tags($tags) . "\n";
    echo "tags (自定义分隔符):\n" . TelegramTagMarkdownV2::tags($tags, ' | ') . "\n";
    echo "tags (自定义前缀):\n" . TelegramTagMarkdownV2::tags($tags, ' ', '＃') . "\n\n";  // 全角＃可少转义

// ---------- 额外增强方法 ----------
    echo "mention: " . TelegramTagMarkdownV2::mention('durov') . "\n";
    echo "emoji (自定义表情): " . TelegramTagMarkdownV2::emoji('5368324170671202286', '👍') . "\n\n";

// ============================================================
// 2. TelegramContent 组装演示
// ============================================================

    echo "========== 2. TelegramContent 组装 ==========\n\n";

// 最简单的用法：把多个字符串按换行拼起来，自动过滤空值
    $simple = TelegramContentMarkdownV2::toString([
        '第一行',
        '',
        // 会被过滤
        null,
        // 会被过滤
        '   ',
        // 纯空白也会被过滤
        '第二行',
        '第三行',
    ]);
    echo "简单拼接:\n{$simple}\n\n";

// 自定义连接符（例如用两个换行）
    $withGlue = TelegramContentMarkdownV2::toString([
        '段落一',
        '段落二',
        '段落三',
    ], "\n\n");
    echo "自定义 glue:\n{$withGlue}\n\n";

// 自定义最大连续换行数（第三个参数）
    $withMaxNl = TelegramContentMarkdownV2::toString([
        "行1\n\n\n\n行2",
        // 内部已有多个换行
        '行3',
    ], "\n", 1);            // 最多允许 1 个连续换行
    echo "压缩连续换行（max=1）:\n{$withMaxNl}\n\n";

// 嵌套数组会自动递归展开
    $nested = TelegramContentMarkdownV2::toString([
        '标题行',
        [
            '嵌套第一行',
            [
                '更深层的内容',
                '',
                null,
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
     * 用 TelegramTagMarkdownV2 生成各个原子片段，再用 TelegramContent 组装
     * 最终得到干净、可直接发给 Telegram 的 MarkdownV2 caption
     */
    $caption = TelegramContentMarkdownV2::toString([

        // 标题
        TelegramTagMarkdownV2::title($gameTitle),

        // 基本信息（kv / kvRaw）
        TelegramTagMarkdownV2::kv('Version', $version),
        TelegramTagMarkdownV2::kv('Original Size', $size),
        TelegramTagMarkdownV2::kv('Repack Size', $repackSize),
        TelegramTagMarkdownV2::kvRaw('Site', TelegramTagMarkdownV2::a($siteUrl, '前往官网')),

        // 空一行
        TelegramTagMarkdownV2::line(),

        // 功能列表
        TelegramTagMarkdownV2::b('Features'),
        TelegramTagMarkdownV2::list($features, 'bullet'),

        // 空一行
        TelegramTagMarkdownV2::br(),

        // 更新日志（用 blockquote 更醒目）
        TelegramTagMarkdownV2::b('Changelog'),
        TelegramTagMarkdownV2::blockquote($changelog),

        // 空一行
        TelegramTagMarkdownV2::br(),

        // 下载链接（listRaw 可放已生成的 Markdown）
        TelegramTagMarkdownV2::b('Download'),
        TelegramTagMarkdownV2::listRaw([
            TelegramTagMarkdownV2::a($siteUrl, '官网页面'),
            TelegramTagMarkdownV2::code($magnet),
            // 磁力链接用 code 方便复制
        ], 'arrow'),

        // 空一行
        TelegramTagMarkdownV2::line(),

        // 话题标签（注意最终会变成 \#Action \#RPG ...）
        TelegramTagMarkdownV2::tags($genres),

        // 结尾提示
        TelegramTagMarkdownV2::i('请关闭杀毒软件后安装，安装完成后可重新开启。'),
    ]);

    echo "最终 caption（可直接用于 sendPhoto / sendMediaGroup 的 caption）:\n";
    echo "----------------------------------------\n";
    echo $caption;
    echo "\n----------------------------------------\n\n";

// ============================================================
// 4. 纯文本消息 Demo（不需要图片时）
// ============================================================

    echo "========== 4. 纯文本消息 Demo ==========\n\n";

    $textMessage = TelegramContentMarkdownV2::toString([
        TelegramTagMarkdownV2::title('系统通知'),
        TelegramTagMarkdownV2::p('今日已成功同步 137 篇文章。'),
        TelegramTagMarkdownV2::kv('成功', '135'),
        TelegramTagMarkdownV2::kv('失败', '2'),
        TelegramTagMarkdownV2::br(),
        TelegramTagMarkdownV2::blockquoteRaw(TelegramTagMarkdownV2::b('注意') . '：失败的 2 条是因为图片 file_id 为空，已自动跳过。'),
        TelegramTagMarkdownV2::br(),
        TelegramTagMarkdownV2::tags([
            '同步完成',
            '系统通知',
        ]),
        TelegramTagMarkdownV2::br(),
        TelegramTagMarkdownV2::spoiler('内部调试信息：耗时 12.3s'),
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
     *     'MarkdownV2'                   // ← 必须用 MarkdownV2
     * );
     *
     * // 发纯文本
     * $tgManager->sendTextMessage(
     *     $textMessage,
     *     $chatId,
     *     $botToken,
     *     'MarkdownV2'
     * );
     */

    echo "========== 6. MarkdownV2 常见陷阱提醒 ==========\n\n";

    echo <<<TIP
1. parse_mode 必须传 'MarkdownV2'（区分大小写）。
2. 所有用户输入、动态文本都要走 escape() 或带自动 escape 的方法（b/i/kv/list/tags 等）。
3. 列表里的 - 和数字后的 . 已在 list()/listRaw() 内部转义，无需手动处理。
4. 链接 URL 中的 ) 和 \\ 会自动转义；显示文字走完整 escape。
5. 话题标签最终会变成 \#tag（# 被转义），这是 MarkdownV2 的正确写法。
6. 代码块/行内代码内部只转义 \\ 和 \`，其它字符保持原样。
7. 不要混用 HTML 标签，MarkdownV2 不支持 <b> 等。
8. 调试时如果 Telegram 报 "can't parse entities"，多半是某处少转义了特殊字符。

Demo 结束。所有方法均已覆盖。
TIP;