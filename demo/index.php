<?php
define('ROOT_PATH', dirname(__FILE__).'/../');
define('DS', DIRECTORY_SEPARATOR);

class World {

    public static function Run()
    {
        var_dump(ROOT_PATH);
        $world = new World();
        echo 'fjsdklafsdklj';
        $world->progress();
    }

    //----------------------------------

    /**
     * @var Redis
     */
    private $redis = null;

    /**
     * @var FileLoader
     */
    private $fileLoader = null;

    private $pathArr = [
        DS . 'demo' . DS,
        DS . 'src' . DS,
    ];

    /**
     * World constructor.
     */
    public function __construct()
    {
        spl_autoload_register([$this, 'autoloader']);
        $this->initRedis();
        $this->initFileLoader();
    }

    //----------------------------------

    private function initRedis()
    {
        if (empty($this->redis)) {
            $this->redis = new RedisServer([
                'host' => '127.0.0.1',
                'port' => '6379',
                'auth' => '',
            ]);
        }
    }

    private function initFileLoader()
    {
        if (empty($this->fileLoader)) {
            $this->fileLoader = new FileLoader($this->redis, []);
        }
    }

    public function autoloader($class)
    {
        $arr = explode('\\', $class);
        $classfile = end($arr);

        foreach ($this->pathArr as $path) {
            if(empty($path))
                continue;
            $path = ROOT_PATH . $path . $classfile . '.php';
            if (file_exists($path)) {
                /** @noinspection PhpIncludeInspection */
                include_once($path);
                return true;
            }
        }

        return false;
    }

    public function progress()
    {
        $path = $this->fileLoader->get('http://imgcdn.yuexing.com/static/data/files/mall/ad/logo/43941566369330.jpg');
        var_dump($path);
    }
}

World::Run();