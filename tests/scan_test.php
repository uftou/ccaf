<?php

/**
 * Scanner class to handle finding new files on the server
 * It puts them in a temp_file table in the sqlite audio file database
 * We are handling mp3 files only here.
 */

class AudioFileScanner {

    private $pdo = null;

    private $filedirectorypath = "../audio/";

    //Constructor
    function __construct() {

        try{
            $this->pdo = new PDO('sqlite:'.dirname(__FILE__).'/AudioFiles_CCNice.sqlite3');
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(Exception $e) {
            echo "Can't reach the SQLite DB : " . $e->getMessage();
            die();
        }

        $this->pdo->query("CREATE TABLE IF NOT EXISTS temp_files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL UNIQUE,
            url TEXT NOT NULL );
        ");

    }

    //Destructor
    function __destruct() {
        $pdo = null;
    }

    //Generate list of all files referenced in db.
    function get_list_of_files() {
        $result = $this->pdo->query('SELECT * FROM temp_files ORDER BY filename ASC');
        return $result;
    }

    //Count all files in the db.
    function count_all_files() {
        $result = $this->pdo->query('SELECT COUNT(*) FROM temp_files');
        return $result->fetchColumn();
    }

    //Insert audio file into db table
    function insert_into_db( $fn, $u ) {

        //TODO: Check if file already in DB

        //Prepare INSERT statement for SQLite DB
        $insert = "INSERT INTO temp_files (filename, url)
                    VALUES (:filename, :url)";
        $stmt = $this->pdo->prepare($insert);

        //Bind parameters
        $stmt->bindParam(':filename', $fn);
        $stmt->bindParam(':url', $u);

        $stmt->execute();

    }

    function find_and_add_files( ){

        $audioFileArray = array();

        if( $this->count_all_files() != 0 ){
            $this->pdo->query("DELETE FROM temp_files");
        }

        // Create recursive dir iterator which skips dot folders
        $dir = new RecursiveDirectoryIterator( $this->filedirectorypath , FilesystemIterator::SKIP_DOTS);

        // Flatten the recursive iterator, consider only files, no directories
        $it  = new RecursiveIteratorIterator($dir);

        // Scan list of files into array
        foreach ($it as $fileinfo) {
            if ($fileinfo->isFile() && !strcmp($fileinfo->getExtension(), "mp3")) {
                $audioFileArray[] = $fileinfo;
            }
        }

        $doc_root  = preg_replace("!{$_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
        $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
        $port      = $_SERVER['SERVER_PORT'];
        $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
        $domain    = $_SERVER['SERVER_NAME'];

        foreach ( $audioFileArray as $fileinfo ){

            // Build URL
            $filepath = realpath($fileinfo->getRealPath());
            $base_url  = preg_replace("!^{$doc_root}!", '', $filepath); # ex: '' or '/mywebsite'
            $full_url  = "$protocol://{$domain}{$disp_port}{$base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc. 

            $this->insert_into_db( $fileinfo->getFilename(), $full_url );
        }

    }

    function pop_file( ){
        //get file from db
        $result = $this->pdo->query('SELECT * FROM temp_files')->fetchAll();
        print_r($result);
        //delete file from db

        //retrieve splfileinfo object

    }

    function clear_db( ){
        $result = $this->pdo->query('DROP TABLE temp_files');
    }
    

}



$TFDB = new AudioFileScanner();
$TFDB->find_and_add_files();
echo "find_and_add_files<br><br>";
$TFDB->pop_file();
echo "file pop<br><br>";
$TFDB->clear_db();
echo "drop table<br>";

// Test function
/*
function scan_files( ){

    $audioFileArray = array();
    $filedirectorypath = "../audio";
    date_default_timezone_set('UTC');

    $doc_root  = preg_replace("!{$_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
    $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
    $port      = $_SERVER['SERVER_PORT'];
    $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
    $domain    = $_SERVER['SERVER_NAME'];

    // Create recursive dir iterator which skips dot folders
    $dir = new RecursiveDirectoryIterator( $filedirectorypath , FilesystemIterator::SKIP_DOTS);

    // Flatten the recursive iterator, consider only files, no directories
    $it  = new RecursiveIteratorIterator($dir);

    // get start time
    $start_time = time();

    // scan list of files into array (we could just directly inject files in temporary table in db)

    $number_files = 0;

    foreach ($it as $fileinfo) {
        if ($fileinfo->isFile() && !strcmp($fileinfo->getExtension(), "mp3")) {
            //print $fileinfo->getFilename() . "-" . md5_file($fileinfo) . " /";
            $number_files++;
            $audioFileArray[] = $fileinfo;
        }
    }

    // get finish time
    $end_time = time();

    // print elapsed time
    $elapsed_time = $end_time - $start_time;
    print "Elapsed time in seconds: " . $elapsed_time . "\n\n";

    // print number of files found
    print "Files found: " . $number_files . "\n\n";

    // print list of files
    foreach ($audioFileArray as $md5key => $fileinfo) {
        print $fileinfo->getFilename() . " - " . date(DATE_ATOM,$fileinfo->getMTime()) . "\n";
    }
}
 */

?>
