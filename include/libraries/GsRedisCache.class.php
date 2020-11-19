<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * ECSHOP SESSION
 */
class GsRedisCache {
    
    /**
     * 
     * @var Redis
     */
    private $redis = null;
    private $retries = 5;
    private $_socket = false;
    private $password;
    private $hostname = "localhost";
    private $unixSocket;
    public $connectionTimeout = null;
    public $socketClientFlags = STREAM_CLIENT_CONNECT;
    public $database = 0;
    public $dataTimeout = null;
    public $port = 6379;
    
    function __construct() {
        if(defined("REDIS_HOST"))$this->hostname = REDIS_HOST;
        if(defined("REDIS_PASS"))$this->password = REDIS_PASS;
        if(defined("REDIS_PORT"))$this->port = REDIS_PORT;
       /*  $this->redis = new Redis();
        $this->redis->connect("127.0.0.1",6379); */
    }
    /**
     * @inheritdoc
     */
    public function getValue($key)
    {
        return $this->executeCommand('GET', [$key]);
    }
    
    public function delValue($key){
        return $this->executeCommand("DEL",[$key]);
    }
    
    
    /**
     * @inheritdoc
     */
    public function setValue($key, $value, $expire)
    {

        if ($expire == 0) {

            return (bool) $this->executeCommand('SET', [$key, $value]);
        } else {
            $expire = (int) ($expire * 1000);
          
            return (bool) $this->executeCommand('SET', [$key, $value, 'PX', $expire]);
        }
    }
     /**
     * @inheritdoc
     */
    public function increase($key, $num)
    {
        
         return (bool) $this->executeCommand('incrby', [$key, $num]);
        
    }
    
    public function executeCommand($name, $params = [])
    {
        $this->open();   
        $params = array_merge(explode(' ', $name), $params);
        $command = '*' . count($params) . "\r\n";
        foreach ($params as $arg) {
            $command .= '$' . mb_strlen($arg, '8bit') . "\r\n" . $arg . "\r\n";
        }
        
        if ($this->retries > 0) {
            $tries = $this->retries;
            while ($tries-- > 0) {
                try {
                    return $this->sendCommandInternal($command, $params);
                } catch (Exception $e) {
                    // backup retries, fail on commands that fail inside here
                    $retries = $this->retries;
                    $this->retries = 0;
                    $this->close();
                    $this->open();
                    $this->retries = $retries;
                }
            }
        }
        return $this->sendCommandInternal($command, $params);
    }
    
    /**
     * Sends RAW command string to the server.
     * @throws Exception on connection error.
     */
    private function sendCommandInternal($command, $params)
    {
        $written = @fwrite($this->_socket, $command);
        if ($written === false) {
            throw new Exception("Failed to write to socket.\nRedis command was: " . $command);
        }
        if ($written !== ($len = mb_strlen($command, '8bit'))) {
            throw new Exception("Failed to write to socket. $written of $len bytes written.\nRedis command was: " . $command);
        }
        return $this->parseResponse(implode(' ', $params));
    }
    
    /**
     * @param string $command
     * @return mixed
     * @throws Exception on error
     */
    private function parseResponse($command)
    {
        if (($line = fgets($this->_socket)) === false) {
            throw new Exception("Failed to read from socket.\nRedis command was: " . $command);
        }
        $type = $line[0];
        $line = mb_substr($line, 1, -2, '8bit');
        switch ($type) {
            case '+': // Status reply
                if ($line === 'OK' || $line === 'PONG') {
                    return true;
                } else {
                    return $line;
                }
            case '-': // Error reply
                throw new Exception("Redis error: " . $line . "\nRedis command was: " . $command);
            case ':': // Integer reply
                // no cast to int as it is in the range of a signed 64 bit integer
                return $line;
            case '$': // Bulk replies
                if ($line == '-1') {
                    return null;
                }
                $length = (int)$line + 2;
                $data = '';
                while ($length > 0) {
                    if (($block = fread($this->_socket, $length)) === false) {
                        throw new Exception("Failed to read from socket.\nRedis command was: " . $command);
                    }
                    $data .= $block;
                    $length -= mb_strlen($block, '8bit');
                }
                
                return mb_substr($data, 0, -2, '8bit');
            case '*': // Multi-bulk replies
                $count = (int) $line;
                $data = [];
                for ($i = 0; $i < $count; $i++) {
                    $data[] = $this->parseResponse($command);
                }
                
                return $data;
            default:
                throw new Exception('Received illegal data from redis: ' . $line . "\nRedis command was: " . $command);
        }
    }
    
    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->_socket !== false) {
            return;
        }
        $connection = ($this->unixSocket ?: $this->hostname . ':' . $this->port) . ', database=' . $this->database;
        $this->_socket = @stream_socket_client(
            $this->unixSocket ? 'unix://' . $this->unixSocket : 'tcp://' . $this->hostname . ':' . $this->port,
            $errorNumber,
            $errorDescription,
            $this->connectionTimeout ? $this->connectionTimeout : ini_get('default_socket_timeout'),
            $this->socketClientFlags
            );
        if ($this->_socket) {
            if ($this->dataTimeout !== null) {
                stream_set_timeout($this->_socket, $timeout = (int) $this->dataTimeout, (int) (($this->dataTimeout - $timeout) * 1000000));
            }
            if ($this->password !== null) {
                $this->executeCommand('AUTH', [$this->password]);
            }
            if ($this->database !== null) {
                $this->executeCommand('SELECT', [$this->database]);
            }
            //$this->initConnection();
        } else {
            $message = YII_DEBUG ? "Failed to open redis DB connection ($connection): $errorNumber - $errorDescription" : 'Failed to open DB connection.';
            throw new Exception($message);
        }
    }
    public function close()
    {
        if ($this->_socket !== false) {
            try {
                $this->executeCommand('QUIT');
            } catch (Exception $e) {
                // ignore errors when quitting a closed connection
            }
            fclose($this->_socket);
            $this->_socket = false;
        }
    }
}

?>