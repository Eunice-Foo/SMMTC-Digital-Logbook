<?php
// Check if mod_rewrite is enabled
if(in_array('mod_rewrite', apache_get_modules())) {
    echo "mod_rewrite is enabled";
} else {
    echo "mod_rewrite is not enabled";
}
?>