<?php
include 'AudioFileDB.class.php';
$afdb = new AudioFileDB();
$filelist = $afdb->get_list_of_files();
?>

<?php include 'header.php'; ?>

        <h1>List of Audio Files</h1>
        <p>There are a total of <?php print $afdb->count_all_files(); ?> files in the DB.</p>

        <h2>All Files</h2>
        <table class="table table-striped table-condensed">
            <?php
                foreach($filelist as $row){
                    print "<tr><td>" .
                        "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                        "<button type=\"button\" class=\"btn btn-primary btn-xs\">Edit</button>" .
                        "</a></td><td>" .
                        $row['filename'] . "</td></tr>\n";
                }
            ?>
        </table>
        <hr />

<?php include 'footer.php'; ?>
