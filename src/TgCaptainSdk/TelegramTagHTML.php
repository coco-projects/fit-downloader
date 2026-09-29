<?php

    namespace Coco\fitDownloader\TgCaptainSdk;

    class TelegramTagHTML
    {
        /**
         * 转义 Telegram HTML 特殊字符
         * 必须对所有用户可控文本调用
         */
        public static function escape(?string $text): string
        {
            if ($text === null || $text === '')
            {
                return '';
            }

            return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
         * 键值对一行（value 不转义，用于已经由本类生成的 HTML 片段）
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
            return "\n" . '<b>' . $text . '</b>' . "\n";
        }

        /** 引用块（会 escape） */
        public static function blockquote(string $text): string
        {
            return '<blockquote>' . self::escape($text) . '</blockquote>';
        }

        /** 引用块（不转义） */
        public static function blockquoteRaw(string $text): string
        {
            return '<blockquote>' . $text . '</blockquote>';
        }

        /** 粗体 */
        public static function b(string $text): string
        {
            return '<b>' . self::escape($text) . '</b>';
        }

        /** 斜体 */
        public static function i(string $text): string
        {
            return '<i>' . self::escape($text) . '</i>';
        }

        /** 下划线 */
        public static function u(string $text): string
        {
            return '<u>' . self::escape($text) . '</u>';
        }

        /** 删除线 */
        public static function s(string $text): string
        {
            return '<s>' . self::escape($text) . '</s>';
        }

        /** 剧透 / 折叠 */
        public static function spoiler(string $text): string
        {
            return '<tg-spoiler>' . self::escape($text) . '</tg-spoiler>';
        }

        /** 行内代码 */
        public static function code(string $text): string
        {
            return '<code>' . self::escape($text) . '</code>';
        }

        /**
         * 预格式化代码块
         *
         * @param string      $text
         * @param string|null $language 可选语言标识（部分客户端会高亮）
         */
        public static function pre(string $text, ?string $language = null): string
        {
            if ($language !== null && $language !== '')
            {
                return '<pre><code class="language-' . self::escape($language) . '">' . self::escape($text) . '</code></pre>';
            }

            return '<pre>' . self::escape($text) . '</pre>';
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

            return '<a href="' . self::escape($url) . '">' . self::escape($display) . '</a>';
        }

        public static function aWithoutEscape(string $url, ?string $text = null): string
        {
            $display = ($text === null || $text === '') ? $url : $text;

            return '<a href="' . $url . '">' . $display . '</a>';
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
                        $lines[] = '- ' . $escaped;
                        break;
                    case 'number':
                        $lines[] = $index . '. ' . $escaped;
                        $index++;
                        break;
                    case 'arrow':
                        $lines[] = '→ ' . $escaped;
                        break;
                    case 'custom':
                        $p       = $prefix ?? '• ';
                        $lines[] = $p . $escaped;
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
         * 原始 HTML 片段（已确保安全时使用，不再二次 escape）
         * 用于拼接已经由本类生成的标签
         */
        public static function raw(string $html): string
        {
            return $html;
        }

        /**
         * 列表（不转义，用于已经由本类生成好的 HTML 片段）
         * 例如：item 里直接放 TelegramTag::a(...) 的结果
         *
         * @param array       $items  已安全的 HTML 字符串数组
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
                        $lines[] = '- ' . $item;
                        break;
                    case 'number':
                        $lines[] = $index . '. ' . $item;
                        $index++;
                        break;
                    case 'arrow':
                        $lines[] = '→ ' . $item;
                        break;
                    case 'custom':
                        $p       = $prefix ?? '• ';
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
         * 生成 Telegram 话题标签
         * 例：['2D', '3D', 'action'] → #2D #3D #action
         *
         * 规则：
         * - 自动加 #
         * - 去掉首尾空白
         * - 空字符串 / 纯符号的项会被过滤
         * - 标签内空格替换为下划线（Telegram 话题不支持空格）
         * - 只保留字母、数字、下划线（其余字符剔除）
         *
         * @param array  $tags 标签名数组，如 ['2D', '3D', 'action']
         * @param string $glue 标签之间的分隔符，默认空格
         */
        public static function tags(array $tags, string $glue = ' '): string
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

                $result[] = '#' . $tag;
            }

            return implode($glue, $result);
        }
    }
