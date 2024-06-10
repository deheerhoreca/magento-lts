<?php

// PHP info
ob_start();
phpinfo();
$php_info = ob_get_contents();
ob_get_clean();
echo $php_info;

// Additional info about realpath cache
$realpath_cache_size      = realpath_cache_size();
$ini_realpath_cache_size  = ini_get("realpath_cache_size");
$ini_realpath_cache_ttl   = ini_get("realpath_cache_ttl");
?>
<div><h4>Realpath Cache</h4>
  <table>
    <tr><td>Actual realpath_cache_size:</td><td><?=$realpath_cache_size?> bytes</td></tr>
    <tr><td>INI realpath_cache_size:</td><td><?=$ini_realpath_cache_size?></td></tr>
    <tr><td>INI realpath_cache_ttl:</td><td><?=$ini_realpath_cache_ttl?> seconds</td></tr>
  </table>
</div>

<pre><?php
// var_dump(realpath_cache_get());
?></pre>
