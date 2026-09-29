<?php

    namespace Coco\fitDownloader\TgCaptainSdk;

    /**
     * Telegram MarkdownV2 原子标签工具
     * parse_mode = MarkdownV2 时使用
     *
     * 官方支持实体：
     * *bold*  _italic_  __underline__  ~strikethrough~  ||spoiler||
     * `code`  ```pre```  [text](url)  > blockquote
     *
     * 转义规则（MarkdownV2 强制）：
     * 必须对普通文本中的 _ * [ ] ( ) ~ ` > # + - = | { } . ! 进行转义
     *
     * 与 HTML 版调用方式 1:1 对齐，方法名、参数完全一致。
     * 额外增强：
     * - escape 支持可选「保留某些字符不转义」
     * - pre 支持多行代码块更稳妥的换行处理
     * - tags 支持自定义前缀字符（默认 #）
     * - 新增 helper：mention、emojiCode、expandableBlockquote（兼容新客户端）
     */
    class TelegramTagMarkdownV2
    {
        /**
         * MarkdownV2 必须转义的特殊字符
         * 参考官方文档：https://core.telegram.org/bots/api#markdownv2-style
         */
        protected const SPECIAL_CHARS = [
            '_',
            '*',
            '[',
            ']',
            '(',
            ')',
            '~',
            '`',
            '>',
            '#',
            '+',
            '-',
            '=',
            '|',
            '{',
            '}',
            '.',
            '!',
        ];

        /**
         * 转义 Telegram MarkdownV2 特殊字符
         * 必须对所有用户可控文本调用
         *
         * @param string|null $text
         * @param array       $keep 可选：这些字符不转义（高级用法）
         */
        public static function escape(?string $text, array $keep = []): string
        {
            if ($text === null || $text === '')
            {
                return '';
            }

            $chars = self::SPECIAL_CHARS;
            if (!empty($keep))
            {
                $chars = array_diff($chars, $keep);
            }

            // 先转义反斜杠本身，再转义其他特殊字符
            $text = str_replace('\\', '\\\\', $text);

            foreach ($chars as $char)
            {
                $text = str_replace($char, '\\' . $char, $text);
            }

            return $text;
        }

        /**
         * 键值对一行：标签加粗（value 会 escape，适合纯文本）
         * 例：Original Size: 12.3 GB
         */
        public static function kv(string $label, string $value, string $separator = ': '): string
        {
            return self::b($label) . self::escape($separator) . self::escape($value);
        }

        /**
         * 键值对一行（value 不转义，用于已经由本类生成的 Markdown 片段）
         * 例：TelegramTag::kvRaw('Site', TelegramTag::a($url, $url))
         */
        public static function kvRaw(string $label, string $value, string $separator = ': '): string
        {
            return self::b($label) . self::escape($separator) . $value;
        }

        /**
         * 段落：内容 + 尾部换行（会 escape）
         */
        public static function p(string $text): string
        {
            return self::escape($text) . "\n";
        }

        /**
         * 段落（不转义）
         */
        public static function pRaw(string $text): string
        {
            return $text . "\n";
        }

        /**
         * 标题感：加粗 + 前后空行（会 escape）
         */
        public static function title(string $text): string
        {
            return "\n" . self::b($text) . "\n";
        }

        /**
         * 标题感（不转义，用于嵌套其他标签）
         */
        public static function titleRaw(string $text): string
        {
            return "\n" . '*' . $text . '*' . "\n";
        }

        /**
         * 引用块（会 escape）
         * MarkdownV2 中每行前加 >
         */
        public static function blockquote(string $text): string
        {
            $escaped = self::escape($text);
            // 把内部换行也加上 >
            $lines = explode("\n", $escaped);
            $lines = array_map(static fn($line) => '>' . $line, $lines);

            return implode("\n", $lines);
        }

        /**
         * 引用块（不转义）
         */
        public static function blockquoteRaw(string $text): string
        {
            $lines = explode("\n", $text);
            $lines = array_map(static fn($line) => '>' . $line, $lines);

            return implode("\n", $lines);
        }

        /**
         * 可折叠引用块（Telegram 新客户端支持，旧客户端降级为普通引用）
         * 语法：||> 内容 ||
         */
        public static function expandableBlockquote(string $text): string
        {
            return '||' . self::blockquote($text) . '||';
        }

        public static function expandableBlockquoteRaw(string $text): string
        {
            return '||' . self::blockquoteRaw($text) . '||';
        }

        /** 粗体 */
        public static function b(string $text): string
        {
            return '*' . self::escape($text) . '*';
        }

        /** 斜体 */
        public static function i(string $text): string
        {
            return '_' . self::escape($text) . '_';
        }

        /** 下划线 */
        public static function u(string $text): string
        {
            return '__' . self::escape($text) . '__';
        }

        /** 删除线 */
        public static function s(string $text): string
        {
            return '~' . self::escape($text) . '~';
        }

        /** 剧透 / 折叠 */
        public static function spoiler(string $text): string
        {
            return '||' . self::escape($text) . '||';
        }

        /** 行内代码 */
        public static function code(string $text): string
        {
            // 行内代码内只需转义 ` 和 \
            $escaped = str_replace([
                '\\',
                '`',
            ], [
                '\\\\',
                '\\`',
            ], $text);

            return '`' . $escaped . '`';
        }

        /**
         * 预格式化代码块
         *
         * @param string      $text
         * @param string|null $language 可选语言标识（部分客户端会高亮）
         */
        public static function pre(string $text, ?string $language = null): string
        {
            // 代码块内只需转义 \ 和 `
            $escaped = str_replace([
                '\\',
                '`',
            ], [
                '\\\\',
                '\\`',
            ], $text);

            if ($language !== null && $language !== '')
            {
                // 语言标识本身也要简单清理，避免破坏语法
                $lang = preg_replace('/[^\w\-+]/', '', $language);

                return '```' . $lang . "\n" . $escaped . "\n```";
            }

            return "```\n" . $escaped . "\n```";
        }

        /**
         * 链接
         *
         * @param string      $url
         * @param string|null $text 显示文字，为空则用 url 本身
         */
        public static function a(string $url, ?string $text = null): string
        {
            $display = ($text === null || $text === '') ? $url : $text;

            // 显示文字需要完整 escape
            $displayEscaped = self::escape($display);

            // URL 中需要转义的只有 ) 和 \
            $urlEscaped = str_replace([
                '\\',
                ')',
            ], [
                '\\\\',
                '\\)',
            ], $url);

            return '[' . $displayEscaped . '](' . $urlEscaped . ')';
        }

        /**
         * 链接（不转义显示文字，url 仍做必要转义）
         * 当你已经自己处理过显示文字时使用
         */
        public static function aWithoutEscape(string $url, ?string $text = null): string
        {
            $display    = ($text === null || $text === '') ? $url : $text;
            $urlEscaped = str_replace([
                '\\',
                ')',
            ], [
                '\\\\',
                '\\)',
            ], $url);

            return '[' . $display . '](' . $urlEscaped . ')';
        }

        /**
         * 用户提及（@username 或 文字链到用户）
         * 例：mention('durov') → [@durov](tg://user?id=...) 或直接 @durov
         * 这里提供最常用的 @username 形式（已 escape）
         */
        public static function mention(string $username): string
        {
            $username = ltrim($username, '@');

            return self::escape('@' . $username);
        }

        /**
         * 自定义 emoji（Telegram Premium）
         * 语法：![👍](tg://emoji?id=5368324170671202286)
         */
        public static function emoji(string $emojiId, string $fallback = '🔑'): string
        {
            $fallbackEscaped = self::escape($fallback);

            return '![' . $fallbackEscaped . '](tg://emoji?id=' . $emojiId . ')';
        }

        /** 换行 */
        public static function br(): string
        {
            return "\n";
        }

        /** 空行（两个换行） */
        public static function line(): string
        {
            return "\n\n";
        }

        /**
         * 列表
         *
         * @param array       $items  字符串数组（会自动 escape）
         * @param string      $style  bullet | dash | number | arrow | custom
         * @param string|null $prefix style=custom 时使用的前缀，例如 '★ '
         */
        public static function list(array $items, string $style = 'bullet', ?string $prefix = null): string
        {
            if (empty($items))
            {
                return '';
            }

            $lines = [];
            $index = 1;

            foreach ($items as $item)
            {
                $item    = is_string($item) ? $item : (string)$item;
                $escaped = self::escape($item);

                switch ($style)
                {
                    case 'dash':
                        $lines[] = '\\- ' . $escaped;          // MarkdownV2 中 - 需要转义
                        break;
                    case 'number':
                        $lines[] = $index . '\\. ' . $escaped; // 数字后的 . 也需要转义
                        $index++;
                        break;
                    case 'arrow':
                        $lines[] = '→ ' . $escaped;
                        break;
                    case 'custom':
                        $p       = $prefix ?? '• ';
                        $lines[] = self::escape($p) . $escaped;
                        break;
                    case 'bullet':
                    default:
                        $lines[] = '• ' . $escaped;
                        break;
                }
            }

            return implode("\n", $lines);
        }

        /**
         * 列表（不转义，用于已经由本类生成好的 Markdown 片段）
         * 例如：item 里直接放 TelegramTag::a(...) 的结果
         *
         * @param array       $items  已安全的 Markdown 字符串数组
         * @param string      $style  bullet | dash | number | arrow | custom
         * @param string|null $prefix style=custom 时的前缀
         */
        public static function listRaw(array $items, string $style = 'bullet', ?string $prefix = null): string
        {
            if (empty($items))
            {
                return '';
            }

            $lines = [];
            $index = 1;

            foreach ($items as $item)
            {
                $item = is_string($item) ? $item : (string)$item;

                switch ($style)
                {
                    case 'dash':
                        $lines[] = '\\- ' . $item;
                        break;
                    case 'number':
                        $lines[] = $index . '\\. ' . $item;
                        $index++;
                        break;
                    case 'arrow':
                        $lines[] = '→ ' . $item;
                        break;
                    case 'custom':
                        $p = $prefix ?? '• ';
                        // 前缀本身如果含特殊字符需要调用方自己处理，这里直接拼接
                        $lines[] = $p . $item;
                        break;
                    case 'bullet':
                    default:
                        $lines[] = '• ' . $item;
                        break;
                }
            }

            return implode("\n", $lines);
        }

        /**
         * 原始 Markdown 片段（已确保安全时使用，不再二次 escape）
         * 用于拼接已经由本类生成的标签
         */
        public static function raw(string $markdown): string
        {
            return $markdown;
        }

        /**
         * 生成 Telegram 话题标签
         * 例：['2D', '3D', 'action'] → #2D #3D #action
         *
         * 规则：
         * - 自动加 #
         * - 去掉首尾空白
         * - 空字符串 / 纯符号的项会被过滤
         * - 标签内空格替换为下划线（Telegram 话题不支持空格）
         * - 只保留字母、数字、下划线（其余字符剔除）
         * - # 本身在 MarkdownV2 中需要转义，所以最终输出 \#tag
         *
         * @param array  $tags   标签名数组，如 ['2D', '3D', 'action']
         * @param string $glue   标签之间的分隔符，默认空格
         * @param string $prefix 前缀字符，默认 #
         */
        public static function tags(array $tags, string $glue = ' ', string $prefix = '#'): string
        {
            $result = [];

            foreach ($tags as $tag)
            {
                $tag = trim((string)$tag);
                if ($tag === '')
                {
                    continue;
                }

                // 去掉已有的 #
                $tag = ltrim($tag, '#');

                // 空格 → 下划线，只保留安全字符
                $tag = preg_replace('/\s+/', '_', $tag);
                $tag = preg_replace('/[^\w]/u', '', $tag); // \w = 字母数字下划线，u 支持中文等

                if ($tag === '')
                {
                    continue;
                }

                // MarkdownV2 中 # 必须转义
                $result[] = self::escape($prefix) . $tag;
            }

            return implode($glue, $result);
        }
    }
