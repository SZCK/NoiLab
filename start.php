<?php
    /**
     * ++++++++++++++++++++++++++
     * # This file is a part of #
     * # NoiLab. Licensed under #
     * # MIT, use it under the  #
     * # license.               #
     * ++++++++++++++++++++++++++
     * 
     * @author xtl<xtl@xtlsoft.top>
     * @license MIT
     * @package xtlsoft/noilab
     * 
     */
    
    namespace NoiLab;
    
    require_once("vendor/autoload.php");
    
    use \Workerman\Worker;
    use \Workerman\WebServer;
    use \Workerman\Connection\TcpConnection;
    
    $App = new \NoiLab\Application();
    $App->_config(__DIR__. "/config.json");
    
    $web = new WebServer("http://" . $App->config->server->web);
    $web->addRoot("*", __DIR__. "/web/");
    $web->name = "WebServer";
    
    $sock = new Worker("websocket://" . $App->config->server->socket);
    $sock->name = "WebSocket";
    $sock->count = 3;
    
    $sock->onMessage = function($conn, $msg) use ($App){
        
        if(substr($msg, 0, 4) == "_ID_"){
            //$conn->send(substr($msg, 4));
            $conn->id = substr($msg, 5);
            $conn->stdin = false;
            return;
        }
        
        if($msg == "_COMPLIE_"){
            $out = $App->config->complie->temp . $conn->id . ".o";
            $in = $App->config->complie->temp . $conn->id . ".cpp";
            $cmd = $App->config->complie->path . " $in -o $out";
            
            $descriptorspec = array(
                0 => array('pty'),
                1 => array('pty'),
                2 => array('pty')
            );
            
            $env = array_merge(
                array('COLUMNS'=>78, 'LINES'=> 18), $_SERVER
            );
            
            $proc = @proc_open($cmd, $descriptorspec, $pipes, null, $env);
            stream_set_blocking($pipes[0], 0);
            
            $conn->send("+ Complie.Start\r\n");
            
            $stdout = new TcpConnection($pipes[1]);
            $stdout->onMessage = function($c, $m) use ($conn){
                
                $conn->send($m);
                
            };
            
            $stdin = new TcpConnection($pipes[2]);
            $stdin->onMessage = function($c, $m) use ($conn){
                
                $conn->send($m);
                
            };
            
            $stdout->onClose = function ($c) use ($conn, $proc, $stdin, $descriptorspec, $env, $out){
                
                $stdin->close();
                proc_terminate($proc);
                $stat = proc_get_status($proc)['exitcode'];
                if($stat == 0){
                    $conn->send("OK!\r\n+ Complie.End\r\n");
                    $proc = @proc_open($out, $descriptorspec, $pipes, null, $env);
                    $start = microtime();
                    stream_set_blocking($pipes[0], 0);
                    
                    $conn->send("+ Running.Start\r\n\r\n");
                    
                    $stdout = new TcpConnection($pipes[1]);
                    $stdout->onMessage = function($c, $m) use ($conn){
                        
                        $conn->send($m);
                        
                    };
                    
                    $stdout->onClose = function($c) use ($conn, $proc, $stdin, $start){
                        
                        $stdin->close();
                        proc_terminate($proc);
                        $stat = proc_get_status($proc)['exitcode'];
                        $time = microtime()- $start;
                        $conn->send("\r\n+ Running.End with return $stat in $time s.\r\n\r\n");
                        
                    };
                    
                    $conn->stdin = $pipes[0];
                    
                    $stdin = new TcpConnection($pipes[2]);
                    $stdin->onMessage = function($c, $m) use ($conn){
                        
                        $conn->send($m);
                    
                    };
                    
                }else{
                    $conn->send("+ Complie.Error\r\n");
                }
                
            };
            
            return;
            
        }
        
        if($conn->stdin){
            
            @fwrite($conn->stdin, $msg);
            
        }
        
    };
    
    Worker::runAll();
    