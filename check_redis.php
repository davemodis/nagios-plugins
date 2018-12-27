#!/usr/bin/env php
<?php

#
# Check redis status
#
# Usage: check_redis [-h host] [-p port] [-a auth] [-C connected_clients | -M used_memory] [-w warning] [-c critical]
#   -h                  redis host
#   -p                  redis port, eg: 6379
#   -C                  Number of clients connected to Redis
#   -M                  Memory used by the Redis server
#   -w WARNING       	Warning value 
#   -c CRITICAL     	Critical value 
#   -H 	       			Display this screen
#
# (c) 2018, Dave Modis <dumbashable@gmail.com>
# https://github.com/davemodis/nagios-plugins
#

$host = '127.0.0.1';
$port = 6379;
$auth = false;
$warning = 0;
$critical = 0;
$mode = 0;

for($i = 1; $i < count($argv); $i++)
{
	switch ($argv[$i]) {
		case '-h':
			$host = $argv[$i+1];
			break;
		case '-p':
			$port = (int)$argv[$i+1];
			break;
		case '-a':
			$auth = $argv[$i+1];
			break;
		case '-w':
			$warning = (float)$argv[$i+1];
			break;
		case '-c':
			$critical = (float)$argv[$i+1];
			break;
		case '-C':
			if(!$mode)
				$mode = 1;
			break;
		case '-M':
			if(!$mode)
				$mode = 2;
		// case '-L':
		// 	if(!$mode)
		// 		$mode = 3;
		// 	break;
			break;
		case '-H':
			die('Usage: check_redis [-h host] [-p port] [-C connected_clients | -L latency | -M used_memory] [-w warning] [-c critical]
   -h                  redis host
   -p                  redis port, eg: 6379
   -C                  Number of clients connected to Redis
   -M                  Memory used by the Redis server
   -w WARNING          Warning value or 0 
   -c CRITICAL         Critical value or 0
   -H 	               Display this screen
');
//   -L                  Average time it takes Redis to respond to a query
			break;
	}
}

if(!$mode)
{
	echo("UNKNOWN - Mode is not set\n");
	exit(3);
}

if( $warning > $critical )
{
	echo("UNKNOWN - warning ($warning) can't be greater than critical ($critical)\n");
	exit(3);
}


$redis = new Redis();
$status = @$redis->connect($host, $port);

if( !$status )
{
	echo("CRITICAL - could not connect to redis $host:$port\n");
	exit(2);
}

if( $auth )
{
	$status = $redis->auth($auth);

	if( !$status )
	{
		echo("CRITICAL - auth fail\n");
		exit(2);
	}
}


switch ($mode) {
	case 1:
		$info = 'Clients';
		break;
	case 2:
		$info = 'Memory';
		break;
}


try{
	$status = $redis->rawCommand('INFO', $info);
}
catch(RedisException $e)
{
	echo("CRITICAL - ".$e->getMessage()."\n");
	exit(2);
}

	

$status = explode("\n", $status);
array_shift($status);

$gigabite = 1073741824;

foreach ($status as $s) {
	$s = explode(':', $s);

	if($mode === 1)
	{
		$s[1] = trim($s[1]);
		if( $critical && $s[0] === 'connected_clients' && $critical < (int)$s[1] )
		{
			echo("CRITICAL - connected_clients {$s[1]} greater then {$critical}\n");
			exit(2);
		}

		if( $warning && $s[0] === 'connected_clients' && $warning < (int)$s[1] )
		{
			echo("WARNING - connected_clients {$s[1]} greater then {$warning}\n");
			exit(1);
		}

		if( $s[0] === 'connected_clients' )
		{
			echo("OK - connected_clients {$s[1]}\n");
			exit(0);
		}
	}

	if($mode === 2)
	{
		if( $critical && $s[0] === 'used_memory' && $critical * $gigabite < (int)$s[1])
		{
			echo("CRITICAL - used_memory ".number_format(((int)$s[1]/$gigabite),2)."G greater then {$critical}G\n");
			exit(2);
		}

		if( $warning && $s[0] === 'used_memory' && $warning * $gigabite < (int)$s[1])
		{
			echo("WARNING - used_memory ".number_format(((int)$s[1]/$gigabite),2)."G greater then {$warning}G\n");
			exit(1);
		}

		if( $s[0] === 'used_memory_human' )
		{
			echo("OK - used_memory {$s[1]}\n");
			exit(0);
		}
	}
}

echo("UNKNOWN - Mode is not set\n");
exit(3);
