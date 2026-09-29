<?php

    namespace Coco\fitDownloader\tables;

    use Coco\tableManager\TableAbstract;

    class GameImages extends TableAbstract
    {
        public string $comment = '游戏图片相关';

        public array $fieldsSqlMap = [
            "game_id"      => "`__FIELD__NAME__` BIGINT (10) UNSIGNED NOT NULL COMMENT '游戏表id',",
            "path"         => "`__FIELD__NAME__` VARCHAR (255) COLLATE utf8mb4_unicode_ci COMMENT '图片路径',",
            "tg_file_id"   => "`__FIELD__NAME__` char(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '' COMMENT '机器人将图片上传到群组后返回的 file_id',",
            "tg_bot_token" => "`__FIELD__NAME__` char(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '' COMMENT '上传的机器人的token',",
            "type"         => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '0:screenshot，1:wallpapers，2:artworks',",
            "add_time"     => "`__FIELD__NAME__` INT (10) UNSIGNED NOT NULL DEFAULT '0',",
        ];

        protected array $indexSentence = [
            "game_id" => "KEY `__INDEX__NAME___index` ( __FIELD__NAME__ ),",
            "type"    => "KEY `__INDEX__NAME___index` ( __FIELD__NAME__ ),",
        ];

        public function setGameIdField(string $value): static
        {
            $this->setFeildName('game_id', $value);

            return $this;
        }

        public function getGameIdField(): string
        {
            return $this->getFieldName('game_id');
        }

        public function setPathField(string $value): static
        {
            $this->setFeildName('path', $value);

            return $this;
        }

        public function getPathField(): string
        {
            return $this->getFieldName('path');
        }

        public function setTgFileIdField(string $value): static
        {
            $this->setFeildName('tg_file_id', $value);

            return $this;
        }

        public function getTgFileIdField(): string
        {
            return $this->getFieldName('tg_file_id');
        }

        public function setTgBotTokenField(string $value): static
        {
            $this->setFeildName('tg_bot_token', $value);

            return $this;
        }

        public function getTgBotTokenField(): string
        {
            return $this->getFieldName('tg_bot_token');
        }

        public function setTypeField(string $value): static
        {
            $this->setFeildName('type', $value);

            return $this;
        }

        public function getTypeField(): string
        {
            return $this->getFieldName('type');
        }

        public function setAddTimeField(string $value): static
        {
            $this->setFeildName('add_time', $value);

            return $this;
        }

        public function getAddTimeField(): string
        {
            return $this->getFieldName('add_time');
        }
    }