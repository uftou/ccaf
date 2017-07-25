<?php

/**
 * Sermon Database class to handle sqlite audio file database.
 */

// include getID3() library (can be in a different directory if full path is specified)
require_once('./getid3/getid3.php');

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
            audiotype TEXT,
            date TEXT,
            author TEXT,
            passage TEXT,
            title TEXT,
            series TEXT,
            tracknumber INTEGER,
            description TEXT,
            downloads INTEGER,
            newfile INTEGER,
            flaggedfile INTEGER,
            flagcomment TEXT );
        ");

    }


    //Destructor
    function __destruct() {
        $pdo = null;
    }

    //Generate list of all files referenced in the db.
    function get_list_of_files() {
        $result = $this->pdo->query('SELECT * FROM files ORDER BY filename ASC');
        return $result;
    }

    //Count all files in the db.
    function count_all_files() {
        $result = $this->pdo->query('SELECT COUNT(*) FROM files');
        return $result->fetchColumn();
    }

    //Calculate total bytes of files in db.
    function count_bytes_all_files() {
        $result = $this->pdo->query('SELECT SUM(size) FROM files');
        return $this->FileSizeConvert($result->fetchColumn());
    }

    //Generate list of all files that are not new or flagged.
    function get_list_of_not_new_files() {
        $result = $this->pdo->query('SELECT * FROM files WHERE newfile = 0 AND flaggedfile = 0 ORDER BY date DESC');
        return $result;
    }

    //Generate list of all new files references in the db.
    function get_list_of_new_files() {
        $result = $this->pdo->query('SELECT * FROM files WHERE newfile = 1 ORDER BY filename ASC');
        return $result;
    }

    //Generate list of all flagged files in the db.
    function get_list_of_flagged_files() {
        $result = $this->pdo->query('SELECT * FROM files WHERE flaggedfile = 1 ORDER BY filename ASC');
        return $result;
    }

    //Get number of different series in the db.
    function count_series() {
        $result = $this->pdo->query('SELECT COUNT(DISTINCT series) FROM files');
        return $result->fetchColumn();
    }


    //Scan drive for new files (aka files that are not already in the db)
    //This method will insert any new files found into the db.
    //It will also perform checks on the db and file states.
    function scan_files( ){

        //ID3 class inclusion
        global $getID3;

        // List of all the audio files found in the directory and sub-directories
        $audioFileArray = array();

        // List of filenames already handled during the scan. To detect dupe filenames.
        $alreadyhandledFileArray = array();

        // Make list of all files in db to remove non existent files
        $DBaudioFileArray = array();
        foreach ( $this->get_list_of_files()->fetchAll() as $row ) {
            $DBaudioFileArray[$row['filename']] = $row['md5_sign'];
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
                $audioFileArray[md5_file($fileinfo)] = $fileinfo;

                //Warning: files with same md5key in different folders will not be inserted into the db.
                //print md5_file($fileinfo) . "<br />\n";
            }
        }

        foreach ($audioFileArray as $md5key => $fileinfo) {

            $filename = $fileinfo->getFilename();

            // For each file found on disk remove entry from list from DB
            // if any left at the end, they are files that no longer are present on the drive.
            unset($DBaudioFileArray[$filename]);

            // Analyze file and store returned data in $fid3
            $fid3 = $getID3->analyze($fileinfo->getPathname());
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

            //decode filename date if named according to our naming scheme
            $date = $this->decode_filename($filename);

            // Build file url based on server path
            $filepath = realpath($fileinfo->getRealPath());
            $base_url  = preg_replace("!^{$doc_root}!", '', $filepath); # ex: '' or '/mywebsite'
            $full_url  = "$protocol://{$domain}{$disp_port}{$base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc.

            //print $filename . " - " . $full_url . "<br />\n";

            // check file not in db
            if( !$this->is_file_in_db( $filename ) ) {
                //insert audiofile in db for first time
                $this->insert_into_db( $filename, $md5key, $full_url, $fileinfo->getSize(), $filelength,
                                        $filetitle, $fileauthor, $audiotype, $passage, $series, $tracknumber, $date );

                $alreadyhandledFileArray[$filename] = $md5key;

            // file in db
            } else {

                //is file flagged? if so, clear for new investigation.
                if( $this->is_file_flagged( $filename ) ) {
                    $this->clear_file_flag( $filename );
                }

                if( isset($alreadyhandledFileArray[$filename]) ){
                    $this->flag_file( $filename, "There are 1 or more files with the same filename. Please change files names so they are distinct. Dupe: " . $full_url);
                } else {

                    //is md5 valid?
                    $valid_md5 = $this->is_file_md5_correct_in_db( $filename, $md5key );

                    //is filepath valid?
                    $valid_filepath = $this->is_filepath_correct_in_db( $filename, $full_url );

                    if( $valid_md5 AND !$valid_filepath ) {
                        $this->update_filepath( $filename, $full_url );
                    } elseif( !$valid_md5 AND $valid_filepath ) {
                        $this->flag_file( $filename, "File seems to have changed or been corrupted. Please investigate." );
                    } elseif( !$valid_md5 AND !$valid_filepath ) {
                        $this->flag_file( $filename, "File changed or corrupted and a new folder. Please investigate." );
                    }
                }

                $alreadyhandledFileArray[$filename] = $md5key;
            }
        }


        // If there are files from the database that weren't found in the audio folder,
        // flag them and add a comment stating that the file can no longer be found.
        if( count($DBaudioFileArray) ){
            foreach($DBaudioFileArray as $fn => $md5er){
                $this->flag_file($fn, "File not found on drive. It needs to be removed from the DB.");
            }
        }

        return true;
    }

    //Look for duplicate files in the db.
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

    //Delete duplicate files from the db.
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
    function insert_into_db( $fn, $md5s, $u, $fs, $fl, $ft, $fa, $fg, $fp, $fser, $tr, $fd ) {

        // Prepare INSERT statement for SQLite DB
        $insert = "INSERT INTO files (filename, md5_sign, url, newfile, flaggedfile, size, length, title, author, audiotype, passage, series, tracknumber, date)
                    VALUES (:filename, :md5_sign, :url, :newfile, :flaggedfile, :filesize, :filelength, :title, :author, :audiotype, :passage, :series, :tracknumber, :date)";
        $stmt = $this->pdo->prepare($insert);

        // Bind parameters
        $stmt->bindParam(':filename', $fn);
        $stmt->bindParam(':md5_sign', $md5s);
        $stmt->bindParam(':url', $u);
        $stmt->bindValue(':newfile', 1);
        $stmt->bindValue(':flaggedfile', 0);
        $stmt->bindParam(':filesize', $fs);
        $stmt->bindParam(':filelength', $fl);
        $stmt->bindParam(':title', $ft);
        $stmt->bindParam(':author', $fa);
        $stmt->bindParam(':audiotype', $fg);
        $stmt->bindParam(':passage', $fp);
        $stmt->bindParam(':series', $fser);
        $stmt->bindParam(':tracknumber', $tr);
        $stmt->bindParam(':date', $fd);

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

    //Check if file md5 sum is the same as the one in db.
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

    //Check if the file path is the same as the one in DB
    //We suppose that filenames are unique in the DB
    function is_filepath_correct_in_db( $fn, $fp ) {
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

        if( $fp === $result['url'] ) {
            return true;
        } else {
            return false;
        }
    }

    //Remove audio file from database
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

    //Function to retrieve one file info
    //We suppose that filenames are unique in the DB
    function getAudioFileInfo( $fn ) {
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
    function update_filepath( $fn, $fu ){
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

    //Function to update file info
    function update_file_info( $fn, $ft, $fser, $fg, $fa, $fd, $fp, $fdes, $tr ){

        // Prepare UPDATE statement to update file information
        $update = "UPDATE files SET title = :title,
            series = :series,
            tracknumber = :tracknumber,
            audiotype = :audiotype,
            author = :author,
            date = :date,
            passage = :passage,
            description = :description,
            newfile = 0 WHERE
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

        // Execute statement
        $stmt->execute();

        return;
    }

    //Function to flag a file
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

    //Function to check if a file is flagged
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

    //Function to clear the flag on a file and the flag comment
    function clear_file_flag( $fn ){
        // Prepare UPDATE statement to clear the flag
        $update = "UPDATE files SET flaggedfile = 0, flagcomment = ''
            WHERE filename = :filename";

        $stmt = $this->pdo->prepare($update);

        $stmt->bindParam(':filename', $fn);

        $stmt->execute();

        return;
    }

    //Function that generates json file used on the calvarychapelnice site
    function generate_json_file() {
        // Open new file to write
        $json_file = new SplFileObject("../CCNiceAudioFiles.json", "w");
        $json_file->fwrite("[\n");

        // Retrieve all non new entries from the database
        $result = $this->get_list_of_not_new_files();

        // For each file generate json entry to be used for presentation
        foreach( $result as $i ){

            unset($i['newfile']);
            unset($i['flaggedfile']);
            unset($i['flagcomment']);
            unset($i['md5_sign']);

            $json_file->fwrite(json_encode($i) . ",\n");
        }

        $json_file->fseek(-2, SEEK_CUR);

        $json_file->fwrite("\n]");
        $json_file = null;

        return;
    }

    //Function that generates a rss feed file / xml format
    function update_rss_feed() {

    }

    //Utility Functions

    //Decode filename date - file starts with yyyymmdd string.
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

    //Convert file size from bytes to human readable string
    function FileSizeConvert($bytes)
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
