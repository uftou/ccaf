<?php

/**
 * Sermon Database class to handle sqlite audio file database.
 */

// include getID3() library (can be in a different directory if full path is specified)
require_once('./assets/getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3();
$getID3->setOption(array('encoding'=>'UTF-8'));

class AudioFileDB {

    private $pdo = null;

    private $filedirectorypath = "../audio";

    //Constructor
    function __construct() {

        try{
            $this->pdo = new PDO('sqlite:'.dirname(__FILE__).'/AudioFiles_CCNice.sqlite3');
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(Exception $e) {
            echo "Can't reach the SQLite DB : ".$e->getMessage();
            die();
        }

        $this->pdo->query("CREATE TABLE IF NOT EXISTS files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL UNIQUE,
            md5_sign INTEGER,
            length TEXT,
            size INTEGER,
            url TEXT NOT NULL,
            filepath TEXT NOT NULL,
            audiotype TEXT,
            date TEXT,
            author TEXT,
            passage TEXT,
            title TEXT,
            series TEXT,
            tracknumber INTEGER,
            description TEXT,
            translator TEXT,
            translated INTEGER,
            downloads INTEGER,
            newfile INTEGER,
            publishedfile INTEGER,
            flaggedfile INTEGER,
            flagcomment TEXT );
        ");

    }


    //Destructor
    function __destruct() {
        $pdo = null;
    }

// All Files

    // Generate list of all files referenced in the db.
    function get_list_of_files() {
        $result = $this->pdo->query('SELECT * FROM files ORDER BY filename ASC');
        return $result;
    }

    // Count all files in the db.
    function count_all_files() {
        $result = $this->pdo->query('SELECT COUNT(*) FROM files');
        return $result->fetchColumn();
    }

    // Calculate total bytes of files in db.
    function count_bytes_all_files() {
        $result = $this->pdo->query('SELECT SUM(size) FROM files');
        return $this->convert_file_size($result->fetchColumn());
    }

    // Generate list of all files that are not new or flagged.
    function get_list_of_not_new_files() {
        $result = $this->pdo->query('SELECT * FROM files WHERE newfile = 0 AND flaggedfile = 0 ORDER BY date DESC');
        return $result;
    }

// Series

    // Get number of different series in the db.
    function count_series() {
        $result = $this->pdo->query('SELECT COUNT(DISTINCT series) FROM files');
        return $result->fetchColumn();
    }


// New Files

    // Generate list of all new files references in the db.
    function get_list_of_new_files() {
        $result = $this->pdo->query('SELECT * FROM files WHERE newfile = 1 ORDER BY date DESC');
        return $result;
    }

    // Count new files in the db.
    function count_new_files() {
        $result = $this->pdo->query('SELECT COUNT(*) FROM files WHERE newfile = 1');
        $temp = $result->fetchColumn();
        if( !$temp ){ $temp = 0; }
        return $temp;
    }


// Published Files

    // Generate list of all published files in the db.
    function get_list_of_published_files() {
        $result = $this->pdo->query('SELECT * FROM files WHERE publishedfile = 1 ORDER BY date DESC');
        return $result;
    }

    // Count published files in the db.
    function count_published_files() {
        $result = $this->pdo->query('SELECT COUNT(*) FROM files WHERE publishedfile = 1');
        $temp = $result->fetchColumn();
        if( !$temp ){ $temp = 0; }
        return $temp;
    }

// Non Published Files

    // Generate list of all non published files in the db.
    function get_list_of_non_published_files() {
        $result = $this->pdo->query('SELECT * FROM files WHERE publishedfile = 0 ORDER BY date DESC');
        return $result;
    }

    // Count non published files in the db.
    function count_non_published_files() {
        $result = $this->pdo->query('SELECT COUNT(*) FROM files WHERE publishedfile = 0');
        $temp = $result->fetchColumn();
        if( !$temp ){ $temp = 0; }
        return $temp;
    }

// Flagged Files

    // Generate list of all flagged files in the db.
    function get_list_of_flagged_files() {
        $result = $this->pdo->query('SELECT * FROM files WHERE flaggedfile = 1 ORDER BY date DESC');
        return $result;
    }


    // Count flagged files in the db.
    function count_flagged_files() {
        $result = $this->pdo->query('SELECT COUNT(*) FROM files WHERE flaggedfile = 1');
        $temp = $result->fetchColumn();
        if( !$temp ){ $temp = 0; }
        return $temp;
    }



//Scan drive for new files (aka files that are not already in the db)
//This method will insert any new files found into the db.
//It will also perform checks on the db and file states.
    function scan_files( ){

        // List of all the audio files found in the directory and sub-directories
        $audioFileArray = array();

        // List of filenames already handled during the scan. To detect dupe filenames.
        $alreadyhandledFileArray = array();

        // Make list of all files in db to remove non existent files
        $DBaudioFileArray = array();
        foreach ( $this->get_list_of_files()->fetchAll() as $row ) {
            $DBaudioFileArray[] = $row['filename'];
        }

        // Prepare variables for file urls
        //$base_dir  = dirname(dirname(realpath($this->filedirectorypath))); // Absolute path to your installation, ex: /var/www/mywebsite
        $doc_root  = preg_replace("!{$_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
        $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
        $port      = $_SERVER['SERVER_PORT'];
        $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
        $domain    = $_SERVER['SERVER_NAME'];
        // variables for file urls

        // Create recursive dir iterator which skips dot folders
        $dir = new RecursiveDirectoryIterator( $this->filedirectorypath , FilesystemIterator::SKIP_DOTS);

        // Flatten the recursive iterator, consider only files, no directories
        $it  = new RecursiveIteratorIterator($dir);

        // Find all the mp3 files
        foreach ($it as $fileinfo) {
            if ($fileinfo->isFile() && !strcmp($fileinfo->getExtension(), "mp3")) {
                $audioFileArray[] = $fileinfo;

                //Warning: files with same md5key in different folders will not be inserted into the db.
                //print md5_file($fileinfo) . "<br />\n";
            }
        }

        foreach ($audioFileArray as $key => $fileinfo) {

            $filename = $fileinfo->getFilename();

            //For each file found on disk remove entry from list from DB
            // if any left at the end, they are files that no longer are present on the drive.
            //print "unsetting: " . $filename . "<br>";
            unset($DBaudioFileArray[array_search($filename,$DBaudioFileArray,true)]);

             // check file not in db
            if( !$this->is_file_in_db( $filename ) ) {

                //decode filename date if named according to our naming scheme
                $date = $this->decode_filename($filename);

                // Build file url based on server path
                $filepath = realpath($fileinfo->getRealPath());
                $base_url  = preg_replace("!^{$doc_root}!", '', $filepath); # ex: '' or '/mywebsite'
                $full_url  = "$protocol://{$domain}{$disp_port}{$base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc.

                //print $filename . " - " . $full_url . "<br />\n";

                //insert audiofile in db for first time
                $this->insert_new_file_into_db( $filename, $full_url, $fileinfo->getRealPath(), $fileinfo->getSize(), $date );

                $alreadyhandledFileArray[$key] = $filename;

            } else {
                // if file in alreadyhandled array, then duplicate named files have been found
                // how to check for duplicate file names?
                // if we're here then the name has already ben added to the db (but maybe during a previous run)
                //we still need to look for the file in the alreadyhandledFileArray
                if( in_array($filename, $alreadyhandledFileArray) ){
                    // flag file
                    $this->flag_file($filename, "Il y a 2 ou plusieurs fichiers audio avec le même nom dans le dossier audio du serveur ftp. Veuillez changer les noms ou supprimer les doublons.");
                }
            }


        }

        // If there are files from the database that weren't found in the audio folder,
        // flag them and add a comment stating that the file can no longer be found.
        if( count($DBaudioFileArray) ){
            //print_r($DBaudioFileArray);
            foreach($DBaudioFileArray as $key => $fn){
                $this->flag_file($fn, "Le fichier en question n'est plus présent dans le dossier audio du serveur ftp. Il faut le supprimer de la base de données.");
                $this->set_file_not_new($fn);
            }
        }

        return true;
    }

// Look for duplicate files in the db.
// TODO: remove: it is useless since filenames are unique in db
    function are_there_duplicates_in_db() {
        $result = $this->pdo->query('SELECT filename,md5_sign,count(*) FROM files
            GROUP BY filename, md5_sign
            HAVING COUNT(*) > 1');
        $results = count($result->fetchAll());

        if( $results ) {
            return true;
        } else {
            return false;
        }
    }

// Delete duplicate files from the db.
// TODO: remove: same as previous function
    function delete_duplicates_from_db() {
        //TODO: possibly log or flag the file duplication.
        //it could be 2 physical files in different folders!
        //Somehow signal that case.

        $result = $this->pdo->query('DELETE FROM files WHERE rowid NOT IN (
            SELECT MIN(rowid) FROM files GROUP BY filename, md5_sign)');
        $results = $result->rowCount();

        return true;
    }

//Insert new audio file into the db.
    function insert_new_file_into_db( $fn, $u, $fpath, $fs, $fd ) {

        // Prepare INSERT statement for SQLite DB
        $insert = "INSERT INTO files (filename, url, filepath, newfile, flaggedfile, publishedfile, size, date, md5_sign)
                    VALUES (:filename, :url, :filepath, :newfile, :flaggedfile, :publishedfile, :filesize, :date, :md5)";
        $stmt = $this->pdo->prepare($insert);

        // TODO: handle default cases if the variables are not set

        // Bind parameters
        $stmt->bindParam(':filename', $fn);
        $stmt->bindParam(':url', $u);
        $stmt->bindParam(':filepath', $fpath);
        $stmt->bindValue(':newfile', 1);
        $stmt->bindValue(':flaggedfile', 0);
        $stmt->bindValue(':publishedfile', 0);
        $stmt->bindParam(':filesize', $fs);
        $stmt->bindParam(':date', $fd);
        $stmt->bindValue(':md5', NULL);

        $stmt->execute();
    }

//Check if a file is already in the db.
    function is_file_in_db( $fn ) {
        // Prepare SELECT statement to search for the file.
        $select = "SELECT * FROM files WHERE
            filename = :filename";
        $stmt = $this->pdo->prepare($select);

        // Bind parameters
        $stmt->bindParam(':filename', $fn);

        // Execute statement
        $stmt->execute();

        // Count results from SELECT
        $rows = count($stmt->fetchAll());

        if( $rows ) {
            return true;
        } else {
            return false;
        }
    }

// Check if file md5 sum is the same as the one in db.
// This could signal a modified or corrupted file
// TODO: It would probably be best to update the data in the db with id3 after this check
    function is_file_md5_correct_in_db( $fn, $md5s ) {
        // Prepare SELECT statement to search for the file.
        $select = "SELECT * FROM files WHERE
                    filename = :filename AND
                    md5_sign = :md5_sign";
        $stmt = $this->pdo->prepare($select);

        // Bind parameters
        $stmt->bindParam(':filename', $fn);
        $stmt->bindparam(':md5_sign', $md5s);

        // Execute statement
        $stmt->execute();

        // Count results from SELECT
        $rows = count($stmt->fetchAll());

        if( $rows == 1 ) {
            return true;
        } else {
            return false;
        }
    }

// Check if the file url is the same as the one in DB
// We suppose that filenames are unique in the DB
    function is_file_url_correct_in_db( $fn, $furl ) {
        // Prepare SELECT statement to retrieve the file
        $select = "SELECT * FROM files WHERE
                    filename = :filename";
        $stmt = $this->pdo->prepare($select);

        // Bind parameters
        $stmt->bindParam(':filename', $fn);

        // Execute statement
        $stmt->execute();

        // Count results from SELECT
        $result = $stmt->fetch();

        if( $furl === $result['url'] ) {
            return true;
        } else {
            return false;
        }
    }

// Remove audio file from database
// If it is still on the ftp server, it will be found as a new file later.
    function remove_file_from_db( $fn ) {
        // Prepare DELETE statement for selected file
        $delete = "DELETE FROM files WHERE filename = :filename";
        $stmt = $this->pdo->prepare($delete);

        //Bind parameters
        $stmt->bindParam(':filename', $fn);

        //Execute statement
        $stmt->execute();

        return;
    }

// Function to retrieve one file info
// We suppose that filenames are unique in the DB
    function get_file_info( $fn ) {
       // Prepare SELECT statement to search for the file.
        $select = "SELECT * FROM files WHERE
            filename = :filename";
        $stmt = $this->pdo->prepare($select);

        // Bind parameters
        $stmt->bindParam(':filename', $fn);

        // Execute statement
        $stmt->execute();

        // Count results from SELECT
        $row = $stmt->fetch();

        return $row;
    }

//Function to update file url
    function update_file_url( $fn, $fu ){
        // Prepare UPDATE statement to update file url
        $update = "UPDATE files SET url = :fileurl WHERE
            filename = :filename";
        $stmt = $this->pdo->prepare($update);

        // Bind parameters
        $stmt->bindParam(':filename', $fn);
        $stmt->bindParam(':fileurl', $fu);

        // Execute statement
        $stmt->execute();

        return;
    }

// Function to update file info
// This is used by the edit.php page to add user-entered information into the db
    function update_file_info( $fn, $ft, $fser, $fg, $fa, $fd, $fp, $fdes, $tr, $transld, $translr, $pub ){

        // Prepare UPDATE statement to update file information
        $update = "UPDATE files SET title = :title,
            series = :series,
            tracknumber = :tracknumber,
            audiotype = :audiotype,
            author = :author,
            date = :date,
            passage = :passage,
            description = :description,
            newfile = 0,
            translated = :translated,
            translator = :translator,
            publishedfile = :published
            WHERE
            filename = :filename";

        $stmt = $this->pdo->prepare($update);

        // Bind parameters
        $stmt->bindParam(':title', $ft);
        $stmt->bindParam(':series', $fser);
        $stmt->bindParam(':tracknumber', $tr);
        $stmt->bindParam(':audiotype', $fg);
        $stmt->bindParam(':author', $fa);
        $stmt->bindParam(':date', $fd);
        $stmt->bindParam(':passage', $fp);
        $stmt->bindParam(':description', $fdes);
        $stmt->bindParam(':filename', $fn);
        $stmt->bindParam(':translated', $transld);
        $stmt->bindParam(':translator', $translr);
        $stmt->bindParam(':published', $pub);

        // Execute statement
        $stmt->execute();

        return;
    }

// Function to analyse new file
// Extracts id3 tag data and calculates md5 sum for a file
// 8 variables are populated here
// This function is called when editing a new file, if the md5_sign is null on edit call
    function complete_new_file_info( $fn ){

        // Retrieve file path
        $filepath = $this->get_file_info($fn)['filepath'];

        // Calculate MD5 sum
        $file_md5 = md5_file($filepath);
        //print $file_md5 . "<br><br>\n";

        // Extract id3 tag data
        global $getID3;
        $fid3 = $getID3->analyze($filepath);
        //print_r($fid3['tags']);
        //print "<br /><br />";

        $filelength = "";
        if( isset($fid3['playtime_string']) ) {
            $filelength = $fid3['playtime_string'];
        }
        $filetitle = "";
        if( isset($fid3['tags']['id3v2']['title'][0]) ) {
            $filetitle = $fid3['tags']['id3v2']['title'][0];
        }
        $fileauthor = "";
        if( isset($fid3['tags']['id3v2']['artist'][0]) ) {
            $fileauthor = $fid3['tags']['id3v2']['artist'][0];
        }
        $audiotype = "";
        if( isset($fid3['tags']['id3v2']['genre'][0]) ) {
            $audiotype = $fid3['tags']['id3v2']['genre'][0];
        }
        $passage = "";
        if( isset($fid3['tags']['id3v2']['comment'][0]) ) {
            $passage = $fid3['tags']['id3v2']['comment'][0];
        }
        $series = "";
        if( isset($fid3['tags']['id3v2']['album'][0]) ) {
            $series = $fid3['tags']['id3v2']['album'][0];
        }
        $tracknumber = 0;
        if( isset($fid3['tags']['id3v2']['track_number'][0]) ) {
            $tracknumber = $fid3['tags']['id3v2']['track_number'][0];
        }
        //print $fileauthor . " - " . $series . " - " . $tracknumber . " - " . $filetitle . " - " . $filelength . " - " . $audiotype . " - " . $passage . "<br />\n";

        $fid3 = null;

        // Prepare UPDATE statement to update file information in DB
        $update = "UPDATE files SET md5_sign = :md5,
            length = :length,
            title = :title,
            series = :series,
            tracknumber = :tracknumber,
            audiotype = :audiotype,
            author = :author,
            passage = :passage,
            newfile = 0 WHERE
            filename = :filename";

        $stmt = $this->pdo->prepare($update);

        // Bind parameters
        $stmt->bindParam(':md5', $file_md5);
        $stmt->bindParam(':length',$filelength);
        $stmt->bindParam(':title', $filetitle);
        $stmt->bindParam(':series', $series);
        $stmt->bindParam(':tracknumber', $tracknumber);
        $stmt->bindParam(':audiotype', $audiotype);
        $stmt->bindParam(':author', $fileauthor);
        $stmt->bindParam(':passage', $passage);
        $stmt->bindParam(':filename', $fn);

        // Execute statement
        $stmt->execute();

        return;
    }

// Function to flag a file
// Takes a filename and a comment
    function flag_file( $fn, $com ){
        // Prepare UPDATE statement to update file info
        $update = "UPDATE files SET flaggedfile = :flag,
            flagcomment = :flagcomment
            WHERE filename = :filename";

        $stmt = $this->pdo->prepare($update);

        // Bind parameters
        $stmt->bindParam(':filename', $fn);
        $stmt->bindValue(':flag', 1);
        $stmt->bindParam(':flagcomment', $com);

        // Execute statement
        $stmt->execute();

        return;
    }

// Function to check if a file is flagged
// Takes a filename and returns true/false
    function is_file_flagged( $fn ){
        // Prepare SELECT statement to retrieve the file
        $select = "SELECT * FROM files WHERE
                    filename = :filename";
        $stmt = $this->pdo->prepare($select);

        // Bind parameters
        $stmt->bindParam(':filename', $fn);

        // Execute statement
        $stmt->execute();

        // Count results from SELECT
        $result = $stmt->fetch();

        if( $result['flaggedfile'] != 0 ) {
            return true;
        } else {
            return false;
        }
    }

// Function to clear the flag on a file and the flag comment
// Takes a filename and returns nothing
    function clear_file_flag( $fn ){
        // Prepare UPDATE statement to clear the flag
        $update = "UPDATE files SET flaggedfile = 0, flagcomment = ''
            WHERE filename = :filename";

        $stmt = $this->pdo->prepare($update);

        $stmt->bindParam(':filename', $fn);

        $stmt->execute();

        return;
    }

// Function to set file as not new
    function set_file_not_new( $fn ){
        //Prepare UPDATE statement to clear newfile flag
        $update = "UPDATE files SET newfile = 0 WHERE filename = :filename";

        $stmt = $this->pdo->prepare($update);

        $stmt->bindparam(':filename', $fn);

        $stmt->execute();

        return;
    }


// Function that generates json file used on the calvarychapelnice site
    function generate_json_file() {
        // Open new file to write
        $json_file = new SplFileObject("../CCNiceAudioFiles.json", "w");
        $json_file->fwrite("[\n");

        // Retrieve all non new entries from the database
        $result = $this->get_list_of_published_files();

        // For each file generate json entry to be used for presentation
        foreach( $result as $i ){

            unset($i['newfile']);
            unset($i['flaggedfile']);
            unset($i['flagcomment']);
            unset($i['md5_sign']);
            unset($i['publishedfile']);
            unset($i['translated']);
            unset($i['downloads']);
            unset($i['filepath']);

            $readable_size = $this->convert_file_size($i['size']);
            $i['size'] = $readable_size;

            $json_file->fwrite(json_encode($i) . ",\n");
        }

        $json_file->fseek(-2, SEEK_CUR);

        $json_file->fwrite("\n]");
        $json_file = null;

        return;
    }

// Function that generates json file of 5 most recent files
    function generate_recent_json_file() {
        // Open new file to write
        $json_file = new SplFileObject("../CCNice5RecentAudioFiles.json", "w");
        $json_file->fwrite("[\n");

        // Retrieve all non new entries from the database
        $result = $this->pdo->query('SELECT * FROM files WHERE publishedfile = 1 ORDER BY date DESC LIMIT 5');

        // For each file generate json entry to be used for presentation
        foreach( $result as $i ){

            unset($i['newfile']);
            unset($i['flaggedfile']);
            unset($i['flagcomment']);
            unset($i['md5_sign']);
            unset($i['publishedfile']);
            unset($i['translated']);
            unset($i['downloads']);
            unset($i['filepath']);

            $readable_size = $this->convert_file_size($i['size']);
            $i['size'] = $readable_size;

            $json_file->fwrite(json_encode($i) . ",\n");
        }

        $json_file->fseek(-2, SEEK_CUR);

        $json_file->fwrite("\n]");
        $json_file = null;

        return;
    }

// Function that generates a rss feed file / xml format
    function generate_rss_podcast_feed() {
        //Open new file to write
        $rss_feed_file = new SplFileObject("../ccnice_rss.xml","w");

        // Retrieve the 100 most recent entries
        $result = $this->pdo->query('SELECT * FROM files WHERE publishedfile = 1 ORDER BY date DESC LIMIT 100');

        // Write the head of the file
        $feed_head = ''; //TODO

        // Build item list
        
        // Build feed tail
        $feed_tail = '</channel></rss>';

        // Concatenate the 3 parts and then write to the file



        $rss_feed_file->fwrite($rss_feed);
        $rss_feed_file = null;

        return;
    }

    function generate_itunes_rss_podcast_feed() {

    }

    function generate_json_podcast_feed() {

    }


// Utility Functions

// Decode filename date - file starts with yyyymmdd string.
    function decode_filename($fn) {
        $match = array();
        $pattern = "/^\d{4}\d{2}\d{2}/";
        preg_match( $pattern, $fn, $match );

        if( count($match) == 1 ) {
            $year = substr($match[0], 0, 4);
            $month = substr($match[0], 4, 2);
            $day = substr($match[0], 6, 2);

            if( checkdate( $month, $day, $year ) )
                return  $year . "-" . $month . "-" . $day;
        }
        return "";
    }

// Convert file size from bytes to human readable string
    function convert_file_size($bytes)
    {
        $result = 0;

        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );

    foreach($arBytes as $arItem)
    {
        if($bytes >= $arItem["VALUE"])
        {
            $result = $bytes / $arItem["VALUE"];
            $result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
            break;
        }
    }
    return $result;
    }


}
