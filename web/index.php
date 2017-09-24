<?php
    
    use \Workerman\Protocols\Http;
    
    if( $_SERVER['REQUEST_URI'] == "/" ){
        Http::header("location: /".sha1(microtime().uniqid()));
    }else{
        
        $id = substr(substr($_SERVER['REQUEST_URI'], 1), 0);
        
        global $App;
        
        //echo $id;
        
        $source = $App->config->complie->temp . $id . ".cpp";
        
        if(is_file($source)){
            $code = json_encode(["code"=>htmlspecialchars(file_get_contents($source))]);
        }else{
            $code = '{"code" : "#include &lt;iostream&gt;\n\nusing namespace std;\n\nint main(){\n    \n    cout &lt;&lt; \"Hello World!\" &lt;&lt; endl;\n    \n    return 0;\n    \n}"}';
        }
        
        include(__DIR__. "/template.html");
        
    }