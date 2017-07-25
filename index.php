<?php
include 'AudioFileDB.class.php';
$afdb = new AudioFileDB();

if( $afdb->are_there_duplicates_in_db() ){
    $afdb->delete_duplicates_from_db();
}

if( isset($_GET["action"]) ){
    switch( $_GET["action"] ) {
        case "fdelete":
            $afdb->remove_file_from_db( $_GET["filename"] );
            break;
    }
}

$new_files = $afdb->get_list_of_new_files()->fetchAll();
$number_new_files = count($new_files);

$flagged_files = $afdb->get_list_of_flagged_files()->fetchAll();
$number_flagged_files = count($flagged_files);

$other_files = $afdb->get_list_of_not_new_files()->fetchAll();
$number_other_files = count($other_files);
?>

<?php include 'header.php'; ?>

    <div class="text-center">
        <strong>Informations: </strong>
        <?php print $afdb->count_all_files(); ?> Fichiers au Total -
        <?php print $afdb->count_bytes_all_files(); ?> -
        <?php print $afdb->count_series(); ?> series
    </div>

    <!-- New file list -->
    <?php if( $number_new_files != 0 ){ ?>
    <div class="">
        <div class="bg-success">
            <h3 class=""><?php print $number_new_files; ?> Nouveaux fichiers</h3>
            <p>Ce sont des fichiers qui ne sont pas encore accessibles sur le site publique.
            Mais qui sont présents sur le serveur FTP.</p>
        </div>

        <!-- Table -->
        <table class="table table-bordered table-striped table-condensed">
            <?php
                foreach($new_files as $row){
                    print "<tr><td class=\"col-md-1 text-center\">" .
                        "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                        "<button type=\"button\" class=\"btn btn-primary btn-xs\">Ajouter</button>" .
                        "</a></td><td class=\"printeddate\">" .
                        $row['date'] . "</td><td>" .
                        $row['title'] . "</td><td>" .
                        $row['series'] . "</td><td>" .
                        $row['author'] . "</td><td>" .
                        $row['passage'] . "</td><td>" .
                        "<a href=\"" . $row['url'] . "\">" . "Ecouter" . "</a>" .
                        "</td></tr>\n";
                }
            ?>
        </table>
    </div>
    <?php } ?>
    <!-- End of new file list -->

    <!-- Flagged file list -->
    <?php if( $number_flagged_files != 0 ){ ?>
    <div class="">
        <div class="bg-warning">
            <h3 class=""><?php print $number_flagged_files; ?> Fichiers à problem</h3>
            <p>Ce sont les fichiers qui presentent des incoherences: doubles, fichier non présent, etc...<br />
            Il faut résoudre ces problèmes par l'accès ftp.</p>
        </div>

        <!-- Table -->
        <table class="table table-bordered table-striped table-condensed">
            <?php
                foreach($flagged_files as $row){
                    print "<tr><td class=\"col-md-1 text-center\">\n" .
                        "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                        "<button type=\"button\" class=\"btn btn-primary btn-xs\">Editer</button>" .
                        "</a></td>\n<td>" .
                        "<a href=\"" . $row['url'] . "\">" . $row['filename'] . "</a></td><td>" .
                        $row['flagcomment'] .
                        "-> <a href=\"index.php?action=fdelete&filename=" . $row['filename'] . "\">" .
                        "<button type=\"button\" class=\"btn btn-danger btn-xs\">Supprimer</button>".
                        "</a>\n</td></tr>\n";
                }
            ?>
        </table>
    </div>
    <?php } ?>
    <!-- End of flagged file list -->

    <!-- General list of files. aka files that are not new and not flagged. -->
    <?php if( $number_other_files != 0 ){ ?>
    <div class="">
        <div class="bg-info">
            <h3 class=""><?php print $number_other_files; ?> Fichiers</h3>
            <p>Ce sont les fichiers qui sont déjà entrés dans la base.</p>
        </div>

        <!-- Table -->
        <table class="table table-bordered table-striped table-condensed">
            <?php
                 foreach($other_files as $row){
                    print "<tr><td class=\"col-md-1 text-center\">" .
                        "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                        "<button type=\"button\" class=\"btn btn-primary btn-xs\">Editer</button>" .
                        "</a></td><td class=\"printeddate\">" .
                        $row['date'] . "</td><td>" .
                        $row['title'] . "</td><td>" .
                        $row['series'] . "</td><td>" .
                        $row['author'] . "</td><td>" .
                        $row['passage'] . "</td><td>" .
                        "<a href=\"" . $row['url'] . "\">" . "Ecouter" . "</a>" .
                        "</td></tr>\n";
                }
            ?>
        </table>
    </div>
    <?php } ?>
    <!-- End of general file list. -->

    <div class="bg-info col-sm-12">
        &nbsp;
    </div>

<?php include 'footer.php'; ?>
