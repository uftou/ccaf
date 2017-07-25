<?php
include 'AudioFileDB.class.php';

$afdb = new AudioFileDB();

if( $afdb->are_there_duplicates_in_db() ){
    print "There are duplicates! <br />";
    $afdb->delete_duplicates_from_db();
}
$afdb->scan_files( );

?>

<?php include 'header.php'; ?>

        <h1>List of Audio Files</h1>
        <p>There are a total of <?php print $afdb->count_all_files(); ?> files in the DB.</p>

        <h2>New Files</h2>
        <table class="table table-striped table-condensed">
            <?php
                foreach($afdb->get_list_of_new_files() as $row){
                    print "<tr><td>\n" .
                        "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                        "<button type=\"button\" class=\"btn btn-primary btn-xs\">Edit</button>" .
                        "</a></td>\n<td>" .
                        "<a href=\"" . $row['url'] . "\">" . $row['filename'] . "</a></td></tr>\n";
                }
            ?>
        </table>

<?php include 'footer.php'; ?>
