<?php
include 'AudioFileDB.class.php';
$afdb = new AudioFileDB();

//if( $afdb->are_there_duplicates_in_db() ){
//    $afdb->delete_duplicates_from_db();
//}

if( isset($_GET["action"]) ){
    switch( $_GET["action"] ) {
        case "fdelete":
            $afdb->remove_file_from_db( $_GET["filename"] );
            break;
    }
}

$new_files = $afdb->get_list_of_new_files()->fetchAll();
$number_new_files = $afdb->count_new_files();

$published_files = $afdb->get_list_of_published_files()->fetchAll();
$number_published_files = $afdb->count_published_files();

$non_published_files = $afdb->get_list_of_non_published_files()->fetchAll();
$number_non_published_files = $afdb->count_non_published_files();

$flagged_files = $afdb->get_list_of_flagged_files()->fetchAll();
$number_flagged_files = $afdb->count_flagged_files();


?>

<?php include 'header.php'; ?>

    <div class="row">
        <div class="col-sm-12 text-center">
            <br>
            <strong>Informations: </strong>
            <?php print $afdb->count_all_files(); ?> Fichiers au Total -
            <?php print $afdb->count_bytes_all_files(); ?> -
            <?php print $afdb->count_series(); ?> series
            <br>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <br>
            <!-- Nav Tabs -->
            <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#new" aria-controls="new" role="tab" data-toggle="tab"><?php print $number_new_files; ?> Nouveaux Fichiers</a></li>
                <li role="presentation"><a href="#published" aria-controls="published" role="tab" data-toggle="tab"><?php print $number_published_files; ?> Fichiers Publiés</a></li>
                <li role="presentation"><a href="#nonpublished" aria-controls="nonpublished" role="tab" data-toggle="tab"><?php print $number_non_published_files; ?> Fichiers Non-Publiés</a></li>
                <li role="presentation"><a href="#flagged" aria-controls="flagged" role="tab" data-toggle="tab"><?php print $number_flagged_files; ?> Fichiers à Problèmes</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">

<!-- New -->    <div role="tabpanel" class="tab-pane active" id="new">  <!-- New file list tab -->
                    <div class="">
                        <div class="col-sm-12">
                            <h3 class=""><?php print $number_new_files; ?> Nouveaux fichiers</h3>
                            <p>Ce sont des fichiers qui ne sont pas encore accessibles sur le site publique. Mais qui sont présents sur le serveur FTP.</p>
                        </div>

                        <?php if( $number_new_files != 0 ){ ?>

                        <!-- Table -->
                        <table class="table table-bordered table-striped table-condensed">
                        <?php foreach($new_files as $row){
                            print "<tr><td class=\"col-md-1 text-center\">" .
                                "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                                "<button type=\"button\" class=\"btn btn-primary btn-xs\">Ajouter</button>" .
                                "</a></td><td class=\"printeddate\">" .
                                $row['date'] . "</td><td>" .
                                $row['filename'] . "</td><td>" .
                                $afdb->convert_file_size($row['size']) . "</td><td>" .
                                "<a href=\"" . $row['url'] . "\">" . "Ecouter" . "</a>" .
                                "</td></tr>\n";
                        } ?>
                        </table>
                        <?php } ?>
                    </div>
                </div>

<!-- Pub -->    <div role="tabpanel" class="tab-pane" id="published"> <!-- Published file list tab -->
                    <div class="">
                        <div class="col-sm-12">
                            <h3 class=""><?php print $number_published_files; ?> Fichiers</h3>
                            <p>Ce sont les fichiers qui sont déjà entrés dans la base.</p>
                        </div>

                        <?php if( $number_published_files != 0 ){ ?>

                            <!-- Table -->
                            <table class="table table-bordered table-striped table-condensed">
                            <?php foreach($published_files as $row){
                                print "<tr><td class=\"col-md-1 text-center\">" .
                                    "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                                    "<button type=\"button\" class=\"btn btn-primary btn-xs\">Editer</button>" .
                                    "</a></td><td class=\"printeddate\">" .
                                    $row['date'] . "</td><td>" .
                                    $row['filename'] . "</td><td>" .
                                    $row['passage'] . "</td><td>" .
                                    $row['author'] . "</td><td>" .
                                    $row['series'] . "</td><td>" .
                                    "<a href=\"" . $row['url'] . "\">" . "Ecouter" . "</a>" .
                                    "</td></tr>\n";
                            } ?>
                            </table>
                        <?php } ?>
                    </div>
                </div>

<!-- NonPub --> <div role="tabpanel" class="tab-pane" id="nonpublished"> <!-- Non-Published file list tab -->
                    <div class="">
                        <div class="col-sm-12">
                            <h3 class=""><?php print $number_non_published_files; ?> Fichiers</h3>
                            <p>Ce sont les fichiers qui sont déjà entrés dans la base.</p>
                        </div>

                        <?php if( $number_non_published_files != 0 ){ ?>

                            <!-- Table -->
                            <table class="table table-bordered table-striped table-condensed">
                            <?php foreach($non_published_files as $row){
                                print "<tr><td class=\"col-md-1 text-center\">" .
                                    "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                                    "<button type=\"button\" class=\"btn btn-primary btn-xs\">Editer</button>" .
                                    "</a></td><td class=\"printeddate\">" .
                                    $row['date'] . "</td><td>" .
                                    $row['filename'] . "</td><td>" .
                                    $row['passage'] . "</td><td>" .
                                    $row['author'] . "</td><td>" .
                                    $row['series'] . "</td><td>" .
                                    "<a href=\"" . $row['url'] . "\">" . "Ecouter" . "</a>" .
                                    "</td></tr>\n";
                            } ?>
                            </table>
                        <?php } ?>
                    </div>
                </div>

<!-- Flag -->   <div role="tabpanel" class="tab-pane" id="flagged"> <!-- Flagged file list -->
                    <div class="">
                        <div class="col-sm-12">
                            <h3 class=""><?php print $number_flagged_files; ?> Fichiers à problem</h3>
                            <p>Ce sont les fichiers qui presentent des incoherences: doubles, fichier non présent, etc...<br />
                            Il faut résoudre ces problèmes par l'accès ftp.</p>
                        </div>

                        <?php if( $number_flagged_files != 0 ){ ?>
                            <!-- Table -->
                            <table class="table table-bordered table-striped table-condensed">
                            <?php foreach($flagged_files as $row){
                                print "<tr><td class=\"col-md-1 text-center\">\n" .
                                    "<a href=\"edit.php?filename=" . $row['filename'] . "\">" .
                                    "<button type=\"button\" class=\"btn btn-primary btn-xs\">Editer</button>" .
                                    "</a></td>\n<td>" .
                                    "<a href=\"" . $row['url'] . "\">" . $row['filename'] . "</a></td><td>" .
                                    $row['flagcomment'] .
                                    "-> <a href=\"manage.php?action=fdelete&filename=" . $row['filename'] . "\">" .
                                    "<button type=\"button\" class=\"btn btn-danger btn-xs\">Supprimer</button>".
                                    "</a>\n</td></tr>\n";
                            } ?>
                            </table>
                        <?php } ?>
                    </div>
                </div>


            </div> <!-- End of tabbed content -->
        </div> <!-- End of row-col -->
    </div> <!-- End of row -->


<?php include 'footer.php'; ?>
