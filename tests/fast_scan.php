<?php

if ( !array_key_exists("count", $_GET) ) {
    $i = 0;
    $_GET["count"] = 0;
} else {
    $i = $_GET["count"] + 1;
}

// scan for files quickly and fill temp_files table in db
// loop on page updating files table 10 at a time until temp_files is empty

while ( $i <= 1000 ) {
    md5("help me figure this one out");
    if ( $i % 10 == 0 ) {
        header('refresh:0; url=' . $_SERVER['PHP_SELF'] . '?count=' . $i );
        print "<p>$i</p>\n";
        exit;
    }
    $i++;
}

if($i>1000)
    header("refresh: ");

?>
