<?php

    $phantom_script= dirname(__FILE__). '\get-website.js'; 
// echo $phantom_script;
// return;

    $response =  exec ('phantomjs ' . $phantom_script);

    echo  htmlspecialchars($response);
    ?>