<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\WebServer;
use \Workerman\Protocols\Websocket;

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';

$recv_worker = new Worker('Websocket://0.0.0.0:8080');
$recv_worker->onWorkerStart = function($recv_worker)
{
    $send_worker = new Worker('Websocket://0.0.0.0:8008');
    $send_worker->onMessage = function($connection, $data)
    {
    };
    $recv_worker->sendWorker = $send_worker;
    $send_worker->listen();
};
$objRedis = new Redis();
$objRedis->connect('127.0.0.1');
$objRedis->set('cloudcamera-test', 0);
$recv_worker->onMessage = function($connection, $data)use($recv_worker, $objRedis)
{
    if (!is_object($data)){
        //尝试获取文件类型
        $bin     = substr($data, 0, 2);
        $strinfo = unpack('C2chars', $bin);
        $typeCode = intval($strinfo['chars1'] . $strinfo['chars2']);
        switch ($typeCode){
            case 255216:
                //jpeg图片类型
                $picId = $objRedis->incr('cloudcamera-test');
                file_put_contents('/var/www/html/CloudCamera/temp/test_' . str_pad($picId, 10, '0', STR_PAD_LEFT) . '.jpeg', $data);
                break;
            case 8273:
                //wav音频类型
                $audioId = $objRedis->incr('cloudcamera-audio-test');
                file_put_contents('/var/www/html/CloudCamera/temp/audio_test_' . str_pad($audioId, 10, '0', STR_PAD_LEFT) . '.wav', $data);
                break;
        }
    }

//    file_put_contents('/var/www/html/CloudCamera/test.txt', print_r($connection, true), FILE_APPEND);
//    file_put_contents('/var/www/html/CloudCamera/test.txt', print_r($data, true), FILE_APPEND);
//    foreach($recv_worker->sendWorker->connections as $send_connection)
//    {
        //$send_connection->websocketType = "\x82";
//        $send_connection->send($data);
//    }
};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
