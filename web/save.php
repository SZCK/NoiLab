<?php
    
    global $App;
    
    $id = $_POST['id'];
    $value = $_POST['value'];
    
    $source = $App->config->complie->temp . $id . ".cpp";
    
    file_put_contents($source, $value);
    
    echo "保存成功！";