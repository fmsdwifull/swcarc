# swcarc
基于swoole的车基控制，外部命令采用定时器轮询redis执行。发布订阅会发生接收不到的情况，故而采用轮询。