<?php

    namespace Coco\fitDownloader\tables;

    use Coco\tableManager\TableAbstract;

    class Game extends TableAbstract
    {
        public string $comment = '数据采集表';

        public array $fieldsSqlMap = [
            "name"                 => "`__FIELD__NAME__` VARCHAR (255) COLLATE utf8mb4_unicode_ci COMMENT '名',",
            "cover_link"           => "`__FIELD__NAME__` VARCHAR (255) COLLATE utf8mb4_unicode_ci COMMENT '封面路径，下载后就是本地path',",
            "info_url"             => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'https://zh.riotpixels.com/games/super-monkey-ball-banana-rumble 地址中的游戏名',",
            "fitgirl_url"          => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'https://fitgirl-repacks.site/rising-sun-iron-aces/ fitgirl地址',",
            "fitgirl_publish_time" => "`__FIELD__NAME__` INT (10) UNSIGNED NOT NULL DEFAULT '0',",
            "tags"                 => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签',",
            "company"              => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公司',",
            "lang"                 => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Languages',",
            "original_size"        => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Original Size',",
            "repack_size"          => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Repack Size',",
            "raw_html"             => "`__FIELD__NAME__` LONGTEXT COLLATE utf8mb4_unicode_ci COMMENT 'Description',",
            "features"             => "`__FIELD__NAME__` LONGTEXT COLLATE utf8mb4_unicode_ci COMMENT 'Features 一行一个',",
            "description"          => "`__FIELD__NAME__` LONGTEXT COLLATE utf8mb4_unicode_ci COMMENT 'Description',",
            "discussion_url"       => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'https://cs.rin.ru/forum/viewtopic.php?f=10&t=146381 讨论地址',",
            "1337x_url"            => "`__FIELD__NAME__` CHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'https://1337x.to/torrent/6084690/Red-Dead-Redemption-2-Ultimate-Edition-Build-1491-50-UE-Unlocker-MULTi13-FitGirl-Repack/ 下载地址',",

            "download_links" => "`__FIELD__NAME__` LONGTEXT COLLATE utf8mb4_unicode_ci COMMENT '下载地址 json',",
            "updates_links"  => "`__FIELD__NAME__` LONGTEXT COLLATE utf8mb4_unicode_ci COMMENT '更新地址 json',",
            "website_links"  => "`__FIELD__NAME__` LONGTEXT COLLATE utf8mb4_unicode_ci COMMENT '官网地址这些 json',",

            "cover_link_fetch_status" => "`__FIELD__NAME__` TINYINT (11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '封面图，0:未下载图片，1:下载中，2:下载完成',",
            "image_fetch_status"      => "`__FIELD__NAME__` TINYINT (11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '截图，0:未下载图片，1:下载中，2:下载完成',",

            "add_time" => "`__FIELD__NAME__` INT (10) UNSIGNED NOT NULL DEFAULT '0',",
        ];

        protected array $indexSentence = [
            "name" => "KEY `__INDEX__NAME___index` ( __FIELD__NAME__ ),",
        ];

        public function setNameField(string $value): static
        {
            $this->setFeildName('name', $value);

            return $this;
        }

        public function getNameField(): string
        {
            return $this->getFieldName('name');
        }

        public function setCoverLinkField(string $value): static
        {
            $this->setFeildName('cover_link', $value);

            return $this;
        }

        public function getCoverLinkField(): string
        {
            return $this->getFieldName('cover_link');
        }

        public function setInfoUrlField(string $value): static
        {
            $this->setFeildName('info_url', $value);

            return $this;
        }

        public function getInfoUrlField(): string
        {
            return $this->getFieldName('info_url');
        }

        public function setFitgirlUrlField(string $value): static
        {
            $this->setFeildName('fitgirl_url', $value);

            return $this;
        }

        public function getFitgirlUrlField(): string
        {
            return $this->getFieldName('fitgirl_url');
        }

        public function setFitgirlPublishTimeField(string $value): static
        {
            $this->setFeildName('fitgirl_publish_time', $value);

            return $this;
        }

        public function getFitgirlPublishTimeField(): string
        {
            return $this->getFieldName('fitgirl_publish_time');
        }

        public function setTagsField(string $value): static
        {
            $this->setFeildName('tags', $value);

            return $this;
        }

        public function getTagsField(): string
        {
            return $this->getFieldName('tags');
        }

        public function setCompanyField(string $value): static
        {
            $this->setFeildName('company', $value);

            return $this;
        }

        public function getCompanyField(): string
        {
            return $this->getFieldName('company');
        }

        public function setLangField(string $value): static
        {
            $this->setFeildName('lang', $value);

            return $this;
        }

        public function getLangField(): string
        {
            return $this->getFieldName('lang');
        }

        public function setOriginalSizeField(string $value): static
        {
            $this->setFeildName('original_size', $value);

            return $this;
        }

        public function getOriginalSizeField(): string
        {
            return $this->getFieldName('original_size');
        }

        public function setRepackSizeField(string $value): static
        {
            $this->setFeildName('repack_size', $value);

            return $this;
        }

        public function getRepackSizeField(): string
        {
            return $this->getFieldName('repack_size');
        }

        public function setRawHtmlField(string $value): static
        {
            $this->setFeildName('raw_html', $value);

            return $this;
        }

        public function getRawHtmlField(): string
        {
            return $this->getFieldName('raw_html');
        }

        public function setFeaturesField(string $value): static
        {
            $this->setFeildName('features', $value);

            return $this;
        }

        public function getFeaturesField(): string
        {
            return $this->getFieldName('features');
        }

        public function setDescriptionField(string $value): static
        {
            $this->setFeildName('description', $value);

            return $this;
        }

        public function getDescriptionField(): string
        {
            return $this->getFieldName('description');
        }

        public function setDiscussionUrlField(string $value): static
        {
            $this->setFeildName('discussion_url', $value);

            return $this;
        }

        public function getDiscussionUrlField(): string
        {
            return $this->getFieldName('discussion_url');
        }

        public function set1337xUrlField(string $value): static
        {
            $this->setFeildName('1337x_url', $value);

            return $this;
        }

        public function get1337xUrlField(): string
        {
            return $this->getFieldName('1337x_url');
        }

        public function setDownloadLinksField(string $value): static
        {
            $this->setFeildName('download_links', $value);

            return $this;
        }

        public function getDownloadLinksField(): string
        {
            return $this->getFieldName('download_links');
        }

        public function setUpdatesLinksField(string $value): static
        {
            $this->setFeildName('updates_links', $value);

            return $this;
        }

        public function getUpdatesLinksField(): string
        {
            return $this->getFieldName('updates_links');
        }

        public function setWebsiteLinksField(string $value): static
        {
            $this->setFeildName('website_links', $value);

            return $this;
        }

        public function getWebsiteLinksField(): string
        {
            return $this->getFieldName('website_links');
        }

        public function setCoverLinkFetchStatusField(string $value): static
        {
            $this->setFeildName('cover_link_fetch_status', $value);

            return $this;
        }

        public function getCoverLinkFetchStatusField(): string
        {
            return $this->getFieldName('cover_link_fetch_status');
        }

        public function setImageFetchStatusField(string $value): static
        {
            $this->setFeildName('image_fetch_status', $value);

            return $this;
        }

        public function getImageFetchStatusField(): string
        {
            return $this->getFieldName('image_fetch_status');
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