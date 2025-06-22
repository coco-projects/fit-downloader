<?php

    namespace Coco\fitDownloader;

    use Coco\fitDownloader\tables\Game;
    use Coco\fitDownloader\tables\GameImages;
    use Coco\tableManager\TableRegistry;
    use DI\Container;

    class GameManager
    {
        protected ?Container $container = null;
        protected array      $tables    = [];

        protected bool $enableRedisLog = false;
        protected bool $enableEchoLog  = false;

        protected ?string $logNamespace;

        protected string $redisHost     = '127.0.0.1';
        protected string $redisPassword = '';
        protected int    $redisPort     = 6379;
        protected int    $redisDb       = 14;

        protected string $mysqlDb;
        protected string $mysqlHost     = '127.0.0.1';
        protected string $mysqlUsername = 'root';
        protected string $mysqlPassword = 'root';
        protected int    $mysqlPort     = 3306;

        public function __construct(protected string $redisNamespace, ?Container $container = null)
        {
            if (!is_null($container))
            {
                $this->container = $container;
            }
            else
            {
                $this->container = new Container();
            }

            $this->redisNamespace .= 'games';
            $this->logNamespace   = $this->redisNamespace . ':log:';
        }

        public function initServer(): static
        {
            $this->initMysql();
            $this->initRedis();

            return $this;
        }

        public function setEnableEchoLog(bool $enableEchoLog): static
        {
            $this->enableEchoLog = $enableEchoLog;

            return $this;
        }

        public function setEnableRedisLog(bool $enableRedisLog): static
        {
            $this->enableRedisLog = $enableRedisLog;

            return $this;
        }

        public function initTableStruct(string $tablePrefix = ''): void
        {
            $this->initGameTable($tablePrefix . 'game', function(Game $table) {
                $registry = $table->getTableRegistry();

                $table->setPkField('id');
                $table->setIsPkAutoInc(false);
                $table->setPkValueCallable($registry::snowflakePKCallback());
            });

            $this->initGameImagesTable($tablePrefix . 'game_images', function(GameImages $table) {
                $registry = $table->getTableRegistry();

                $table->setPkField('id');
                $table->setIsPkAutoInc(false);
                $table->setPkValueCallable($registry::snowflakePKCallback());
            });

        }

        /*
        *
        * ------------------------------------------------------
        *
        * */

        public function setMysqlConfig($db, $host = '127.0.0.1', $username = 'root', $password = 'root', $port = 3306): static
        {
            $this->mysqlHost     = $host;
            $this->mysqlPassword = $password;
            $this->mysqlUsername = $username;
            $this->mysqlPort     = $port;
            $this->mysqlDb       = $db;

            return $this;
        }

        public function setRedisConfig(string $host = '127.0.0.1', string $password = '', int $port = 6379, int $db = 9): static
        {
            $this->redisHost     = $host;
            $this->redisPassword = $password;
            $this->redisPort     = $port;
            $this->redisDb       = $db;

            return $this;
        }

        public function getContainer(): Container
        {
            return $this->container;
        }

        public function getMysqlClient(): TableRegistry
        {
            return $this->container->get('mysqlClient');
        }

        public function getRedisClient(): \Redis
        {
            return $this->container->get('redisClient');
        }

        protected function initMysql(): static
        {
            $this->container->set('mysqlClient', function(Container $container) {

                $registry = new TableRegistry($this->mysqlDb, $this->mysqlHost, $this->mysqlUsername, $this->mysqlPassword, $this->mysqlPort,);

                $logName = 'game-log';
                $registry->setStandardLogger($logName);

                if ($this->enableRedisLog)
                {
                    $registry->addRedisHandler(redisHost: $this->redisHost, redisPort: $this->redisPort, password: $this->redisPassword, db: $this->redisDb, logName: $this->logNamespace . $logName, callback: TableRegistry::getStandardFormatter());
                }

                if ($this->enableEchoLog)
                {
                    $registry->addStdoutHandler(TableRegistry::getStandardFormatter());
                }

                return $registry;

            });

            return $this;
        }

        /*
         *
         * ------------------------------------------------------
         *
         * */
        public function createAllTable($forceCreateTable = false): void
        {
            $this->getMysqlClient()->createAllTable($forceCreateTable);
        }

        public function dropAllTable(): void
        {
            $this->getMysqlClient()->dropAllTable();
        }

        public function truncateAllTable(): void
        {
            $this->getMysqlClient()->truncateAllTable();
        }

        /*
         *
         * ------------------------------------------------------
         *
         * */
        public function initGameTable(string $name, callable $callback): static
        {
            $this->tables['Game'] = $name;

            $table = new Game($name);

            $this->getMysqlClient()->addTable($table, $callback);

            return $this;
        }

        public function getGameTable(): Game
        {
            return $this->getMysqlClient()->getTable($this->tables['Game']);
        }

        public function initGameImagesTable(string $name, callable $callback): static
        {
            $this->tables['GameImages'] = $name;

            $table = new GameImages($name);

            $this->getMysqlClient()->addTable($table, $callback);

            return $this;
        }

        public function getGameImagesTable(): GameImages
        {
            return $this->getMysqlClient()->getTable($this->tables['GameImages']);
        }


        private function initRedis(): static
        {
            $this->container->set('redisClient', function(Container $container) {

                /**
                 * @var \Redis $redis
                 */
                $redis = (new \Redis());
                $redis->connect($this->redisHost, $this->redisPort);
                $this->redisPassword && $redis->auth($this->redisPassword);
                $redis->select($this->redisDb);

                return $redis;
            });

            return $this;
        }

    }