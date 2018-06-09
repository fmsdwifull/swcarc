<?php
	class Server
	{
		public $buffer;
		public $serv;
		public $fd;
		public $redis;
		public $pdo;
		
		//接受数据
		function onReceive($serv, $fd, $from_id, $data)
		{
			//在这里可以读取到EventCallback对象上的属性和方法
			$this->buffer[$fd] = $data;
			$this->doRedis($fd,$data);
			$this->doMysql($data);
			$this->fd = $fd;
			echo $data."\n";
		}
		
		
  	

		//启动定时器，用来发送指定
		function onWorkerStart($serv, $worker_id)
		{			
			if ($serv->worker_id == 0) 
			{
 				//定时器方式
				$serv->tick(3000, function (){
					if($this->redis->hget('cmd','flag')==1)
					{
						echo "请对".$this->redis->hget('cmd','ccode')."执行命令：".$this->redis->hget('cmd','token')."\n";
						//找到对应fd
						$this->serv->send($this->redis->hget('base',$this->redis->hget('cmd','ccode')),$this->redis->hget('cmd','token'));
						
						//记载命令记录
						$cmdrecord = array();
						$this->redis->hset('identifycode',$identifycode);
						$cmdrecord['ccode'] = $this->redis->hget('cmd','ccode');
						$cmdrecord['token'] = $this->redis->hget('cmd','token');
						//$cmdrecord['from'] =    
						//$cmdrecord['result'] =  
						$cmdrecord['time'] = date("YmsHis",time());

						//生成临时字符串，用于执行结果识别,按照时间最近原则，结果查询后写入后销毁
						$identifycode = date("YmdHis",time());
						$this->redis->hset('cmd','identifycode',$identifycode);
						
						
						//清空命令标志
						$this->redis->hset('cmd','flag',0);
						$this->redis->hset('cmd','ccode',null);
						$this->redis->hset('cmd','token',null);
						
					}
				}); 
				
				//基于redis的订阅发布方式
				//$this->redis->subscribe(array('chx'), array($this, 'callback'));   

			}
		}
		
		function callback($instance,$channelName,$message)
		{  
			var_dump($instance);
			echo $message;  
		} 				
 		
		
		//redis写操作
		function doRedis($fd,$data)
		{
			$clientArray = explode(",",$data);
			//实例化redis
          //连接
		  if($clientArray[2]==81)
		  {
			  $this->redis->hset('base', $clientArray[1],$fd);
		  }
		  
		    //$this->redis->set('cat', "xxxxxxxxxxxxxxxxxxxxx");
		    //var_dump($this->redis->hgetall('base')); 
		    // $this->redis->get('cat');
			//var_dump($clientArray);
			//echo "doredis----".$fd."\n";
		}
		
		//mysql处理
		function doMysql($data)
		{
			$clientArray = explode(",",$data);
			if($clientArray[2]==24)
			{
				//$stmt = $this->pdo->prepare("insert into user(name,gender,age)values(:name,:gender,:age)");
				//$stmt->execute(array(
				//	":name" => "test",
				//	":gender" => 1,
				//	":age" => 22
				//));	
			}
		}

		function run()
		{
			//redis链接，不是复杂查询的，放在redis里面，实时动态的
			$this->redis = new Redis();
			$this->redis->connect('127.0.0.1', 6379);
			
			//mysql数据库
			$db = array(
				'dsn' => 'mysql:host=localhost;dbname=test;port=3306;charset=utf8',
				'host' => 'localhost',
				'port' => '3306',
				'dbname' => 'test',
				'username' => 'root',
				'password' => 'mysql',
				'charset' => 'utf8',
			);
			$options = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //默认是PDO::ERRMODE_SILENT, 0, (忽略错误模式)
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 默认是PDO::FETCH_BOTH, 4
			);

			try{
				$this->pdo = new PDO($db['dsn'], $db['username'], $db['password'], $options);
			}catch(PDOException $e){
				die('数据库连接失败:' . $e->getMessage());
			}
			
			$serv = new swoole_server("10.20.65.205",7878);
			//$serv->set(array(
			//	'worker_num' => 3,
			//	'daemonize' => false,
			//	'backlog' => 128,
			//));
			$this->serv  = $serv;
			$serv->on('receive', array($this, 'onReceive'));
			$serv->on('workerstart', array($this, 'onWorkerStart')) ;			
			$serv->start();
		}
	}
	//echo "---------------";
	//ini_set('default_socket_timeout', -1); 
	$server= new Server;
	$server->run();
?>