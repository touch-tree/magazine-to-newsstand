<?php
declare(strict_types=1);
//#########################################################
set_include_path(implode(PATH_SEPARATOR, 
array(get_include_path(),__DIR__)));
spl_autoload_register();
sys::init();
//###################################################################
?>