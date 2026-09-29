<?php

    namespace Coco\fitDownloader\TgCaptainSdk;

    class TelegramContentMarkdownV2
    {
        const TYPE = 'MarkdownV2';

        /**
         * 把内容数组拼成最终字符串
         *
         * 规则：
         * - 数组元素可以是 string 或 array（递归展开）
         * - 自动过滤 null / 空字符串
         * - 片段之间默认用单个换行连接（避免过多空行）
         * - 最终去掉首尾空白
         * - 连续 3 个以上换行压缩为 2 个（可自定义）
         *
         * @param array  $parts
         * @param string $glue             片段之间的连接符，默认 "\n"
         * @param int    $maxConsecutiveNl 允许的最大连续换行数，超过则压缩（默认 2）
         */
        public static function toString(array $parts, string $glue = "\n", int $maxConsecutiveNl = 2): string
        {
            $flat = [];
            self::flatten($parts, $flat);

            // 去掉纯空白片段（只含空格/制表符的也去掉）
            $flat = array_values(array_filter($flat, static function($v) {
                return $v !== null && trim((string)$v) !== '';
            }));

            $result = implode($glue, $flat);

            // 压缩连续换行
            if ($maxConsecutiveNl >= 1)
            {
                $pattern     = '/\n{' . ($maxConsecutiveNl + 1) . ',}/';
                $replacement = str_repeat("\n", $maxConsecutiveNl);
                $result      = preg_replace($pattern, $replacement, $result);
            }

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