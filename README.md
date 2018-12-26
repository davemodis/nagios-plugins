# check_redis NRPE plugin

## Usage

`check_redis [-h host] [-p port] [-a auth] [-C connected_clients | -M used_memory] [-w warning] [-c critical]`

   -h                  redis host  
   -p                  redis port, eg: 6379  
   -C                  Number of clients connected to Redis  
   -M                  Memory used by the Redis server  
   -w WARNING          Warning value or 0  
   -c CRITICAL         Critical value or 0  
   -H                  Display this screen  
