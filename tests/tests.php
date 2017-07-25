<?php
include 'AudioFileDB.class.php';
//include 'AudioFile.class.php';

$testDB = new AudioFileDB();

$audioFileArray = array();

//Function that scans for new audio files.
//Fills an array with file references and urls.

function make_list_of_files(&$list) {

    // Create recursive dir iterator which skips dot folders
    $dir = new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS);

    // Flatten the recursive iterator, consider only files, no directories
    $it  = new RecursiveIteratorIterator($dir);

    // Find all the mp3 files
    foreach ($it as $fileinfo) {
        if ($fileinfo->isFile() && !strcmp($fileinfo->getExtension(), "mp3")) {
            $list[md5_file($fileinfo)] = $fileinfo;
        }
    }

    /* alternate method not currently used.
    $it->rewind();
    $Regex = new RegexIterator($it, '/^.+\.mp3$/i', RecursiveRegexIterator::GET_MATCH);
    foreach ($Regex as $regar) {
        print $regar[0] ."\n";
    }*/

    return;
}

make_list_of_files($audioFileArray);

//Function that compares each file to see if it is referenced in the DB
//Here we make an AudioFile object. If not referenced in the DB we insert it.
//We should do other checks if the file and md5 match but other details need to be updated.
//path, url, maybe mp3 tag information. (i need to find a good mp3 tag inspector)

foreach ($audioFileArray as $md5key => $fileinfo) {
    //print $md5key . " - " . $fileinfo . " - " . $fileinfo->getSize() . "\n";
    //WAIT: maybe use info to get audiofile object

    $filename = $fileinfo->getFilename();
    $filepath = $fileinfo->getPath();

    //print "Working on " . $filepath . " - " . $filename . " -\n";

    // check file not in db
    if( !$testDB->is_file_in_db( $filename, $md5key ) ) {
        //insert audiofile in db for first time
        $testDB->insert_into_db( $filename, $md5key, $filepath );
    
    // file in db
    } else {
        //is md5 correct?
        $testDB->is_file_md5_correct_in_db( $filename, $md5key );
                //if no, flag for inspection

        //yes,
        //$testDB->is_filepath_correct_in_db( $filename, $filepath );
                    //check path and that mp3 info is correct, need for update?
                    //what to check?
    }
}

//$testDB->delete_duplicates_from_db();
//$testDB->are_there_duplicates_in_db();
//$testDB->get_list_of_files();

//$testDB->count_all_files();

$testDB->scan_files( );

//Function to check if all the files listed in the db are present in the file system
//Gets list of all files referenced in the db and checks if each is in list of files made previously.
//We could make a list initially and remove each one as the check against the files.
//Any remaining files would be files no longer present!


?>
