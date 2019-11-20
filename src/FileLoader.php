<?php


class FileLoader
{
    const FILE_CACHE = 'file:cache';     //set 缓存列表
    const FILE_CACHE_CLEAR = 'file:cache:clear';     //string 缓存清理状态

    /**
     * @var RedisServer
     */
    protected $redisServer;

    /**
     * 存储路径配置，本地和shm路径
     * @var
     */
    protected $option = [
        'localpath' => ROOT_PATH,
        'shmpath' => '/dev/shm/fileloader/',
    ];

    /**
     * FileLoader constructor.
     * @param $redisServer
     * @param array $option
     */
    function __construct($redisServer, $option = [])
    {
        $this->redisServer = $redisServer;
        if (!empty($option)) {
            $this->option = array_merge($this->option, $option);
        }
    }

    /**
     * 析构函数
     */
    function __destruct()
    {}

    //------------------------------------------------------------------------------------------------------------------

    /**
     * @param $key
     * @return mixed
     */
    private function getOption($key)
    {
        return $this->option[$key];
    }

    /**
     * @param $fullpath
     * @return string
     */
    public function get($uri)
    {
        $parse = parse_url($uri);
        if (isset($parse['scheme'])) {

            //生成本地路径
            $savepath = $this->getOption('localpath') . 'download/' . $parse['host'] . '/' . md5($parse['path']);
            if (!file_exists($savepath)) {
                $curl = new curl($uri);
                $dir = dirname($savepath);
                if(!is_dir($dir)){
                    mkdir($dir, 0744, true);
                }
                $curl->download($savepath, true, '');
            }

            $fullpath = $savepath;
        }

        if ($this->redisServer->exists(self::FILE_CACHE_CLEAR)) {
            //在缓存清理状态，暂时禁用缓存
            return $fullpath;
        }

        //获取相对路径
        $path = str_replace(ROOT_PATH, '', $fullpath);
        $this->redisServer->zAdd(self::FILE_CACHE, time(), $path);

        $shmpath = $this->getOption('shmpath');
        if (!file_exists($shmpath . $path)) {
            $dir = dirname($shmpath . $path);
            if(!is_dir($dir)){
                mkdir($dir, 0744, true);
            }
            copy(ROOT_PATH . $path, $shmpath . $path);
        }
        return $shmpath . $path;
    }

    //------------------------------------------

    /**
     * 计划任务，需要定时执行，删除过期文件
     */
    public function clear()
    {
        if ($this->redisServer->incr(self::FILE_CACHE_CLEAR) == 1) {
            //默认5天
            $keepTime = defined('FILE_CACHE_LIMIT')?FILE_CACHE_LIMIT:5;//文件存储5天
            $gcTime = time() - $keepTime * 86400;
            $arr = $this->redisServer->zRange(self::FILE_CACHE, 0, $gcTime);

            foreach ($arr as $file) {
                $score = $this->redisServer->zScore(self::FILE_CACHE, $file);
                if ($score < $gcTime) {

                    $this->redisServer->zRem(self::FILE_CACHE, $file);
                    if (file_exists('/dev/shm' . $file)) {
                        unlink('/dev/shm' . $file);
                    }
                }
            }

            $this->redisServer->delete(self::FILE_CACHE_CLEAR);
        }
    }
}