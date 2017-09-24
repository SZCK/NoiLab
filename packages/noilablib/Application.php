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
    
    class Application {
        
        public $config;
        
        public function _config($name){
            
            $this->config = json_decode(file_get_contents($name));
            
        }
        
    }