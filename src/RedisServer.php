<?php


class RedisServer
{
    /**
     * @var Redis
     */
    protected $_redis = null;

    /**
     * @var array
     */
    protected $_options = [];

    /**
     * @var int
     */
    protected $_redisRef = 0;
    protected $_dbStack = [];

    /**
     * RedisServer constructor.
     * @param null $options
     */
    function __construct($options)
    {
        $this->_options = $options;
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->release(false);
    }

    /**
     *    连接到缓存服务器
     */
    function connect()
    {
        if (empty($this->_options)) {
            return;
        }
        $this->_redis = new Redis();

        $this->_redis->pconnect($this->_options['host'], $this->_options['port']);

        if(isset($this->_options['auth']) && !empty($this->_options['auth'])) {
            $this->_redis->auth($this->_options['auth']);
        }

        $this->_redisRef = 0;
    }

    /**
     * @param $db
     * @return Redis
     */
    public function &getRedis($db = -1)
    {
        if (empty($this->_redis)) {
            $this->connect();
        }
        $db = ($db >= 0)?$db:$this->getDB();
        $this->_dbStack[] = $db;
        $this->_redisRef = count($this->_dbStack);
        if ($db < 0 || $db > 255)
            $db = 0;

        $this->_redis->select($db);

        return $this->_redis;
    }

    /**
     * 在使用以后必须释放
     * @param $soft
     * @return bool
     */
    public function release($soft)
    {
        if (empty($this->_redis))
            return true;

        if ($soft) {
            array_pop($this->_dbStack);
            $this->_redisRef = count($this->_dbStack);
            if ($this->_redisRef > 0) {
                $db = $this->_dbStack[$this->_redisRef - 1];
                $this->_redis->select($db);
            }
        } elseif (!$soft) {
            $this->_redis->close();
            $this->_redis = null;
            return true;
        }

        return false;
    }

    //----------------------------------------------------------------------

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        if (is_array($value) || is_object($value))
            $value = json_encode($value);

        $redis = $this->getRedis();

        if (empty($ttl)) {
            $result = $redis->set($key, $value);
        } else {
            $result = $redis->setex($key, $ttl, $value);
        }

        $this->release(true);

        return $result;
    }

    /**
     * 自增缓存
     * @access public
     * @param string $key 缓存变量名
     * @param int $ttl 时间
     * @return mixed
     */
    public function incr($key, $ttl = null)
    {
        $redis = $this->getRedis();
        $result = $redis->incrBy($key, 1);
        if (!empty($ttl)) {
            $redis->expire($key, $ttl);
        }

        $this->release(true);
        return $result;
    }

    /**
     *    获取缓存
     * @param     string $key
     * @return    mixed
     */
    public function &get($key)
    {
        $redis = $this->getRedis();
        $data = $redis->get($key);

        $this->release(true);

        return $data;
    }

    /**
     * @param int $db
     * @return void
     */
    public function clear($db = -1)
    {
        $redis = $this->getRedis($db);
        $redis->flushDB();
        $this->release(true);
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        $redis = $this->getRedis();
        $redis->del($key);

        $this->release(true);
    }

    /**
     * @param $key
     * @param $score
     * @param $value
     */
    public function zAdd($key, $score, $value)
    {
        $redis = $this->getRedis();
        $redis->zAdd($key, $score, $value);

        $this->release(true);
    }

    /**
     * @param $key
     * @return bool|int
     */
    public function exists($key)
    {
        $redis = $this->getRedis();
        $exists = $redis->exists($key);

        $this->release(true);

        return $exists;
    }

    /**
     * @param $key
     * @param $value
     */
    public function zRem($key, $value)
    {
        $redis = $this->getRedis();
        $redis->zRem($key, $value);

        $this->release(true);
    }

    /**
     * @param $key
     * @param $value
     * @return bool|float
     */
    public function zScore($key, $value)
    {
        $redis = $this->getRedis();
        $score = $redis->zScore($key, $value);

        $this->release(true);

        return $score;
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @return array
     */
    public function zRange($key, $start, $end)
    {
        $redis = $this->getRedis();
        $list = $redis->zRange($key, $start, $end);

        $this->release(true);

        return $list;
    }

    /**
     * @return int
     */
    private function getDB()
    {
        return 0;
    }
}