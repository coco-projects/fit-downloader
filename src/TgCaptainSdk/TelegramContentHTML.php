<?php

    namespace Coco\fitDownloader\TgCaptainSdk;

    class TelegramContentHTML
    {
        const TYPE = 'HTML';

        /**
         * 把内容数组拼成最终字符串
         *
         * 规则：
         * - 数组元素可以是 string 或 array（递归展开）
         * - 自动过滤 null / 空字符串
         * - 片段之间默认用单个换行连接（避免过多空行）
         * - 最终去掉首尾空白
         *
         * @param array  $parts
         * @param string $glue 片段之间的连接符，默认 "\n"
         */
        public static function toString(array $parts, string $glue = "\n"): string
        {
            $flat = [];
            self::flatten($parts, $flat);

            // 去掉纯空白片段
            $flat = array_values(array_filter($flat, static function($v) {
                return $v !== null && $v !== '';
            }));

            $result = implode($glue, $flat);

            // 压缩连续 3 个以上换行为 2 个，避免大片空白
            $result = preg_replace("/\n{3,}/", "\n\n", $result);

            return trim($result);
        }

        /**
         * 递归展开嵌套数组
         */
        protected static function flatten(array $parts, array &$out): void
        {
            foreach ($parts as $part)
            {
                if (is_array($part))
                {
                    self::flatten($part, $out);
                }
                elseif ($part !== null)
                {
                    $out[] = (string)$part;
                }
            }
        }
    }