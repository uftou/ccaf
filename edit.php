<?php 
include 'AudioFileDB.class.php';
$afdb = new AudioFileDB();
$filename = htmlspecialchars($_GET["filename"]);

// TODO: change to $_POST method for the form submission

if( isset($_GET["update"]) AND $_GET["update"] === "yes" ){

    // Is file published
    $pub = 0;
    if( isset($_GET["publishedfile"]) ){
        $pub = 1;
    }

    // Is there a translation
    $trans = 0;
    if( isset($_GET["translated"]) ){
        $trans = 1;
    }

    $afdb->update_file_info( $filename, $_GET["title"], $_GET["series"], $_GET["audiotype"], $_GET["author"],
        $_GET["date"], $_GET["passage"], $_GET["description"], $_GET["tracknumber"],
        $trans, $_GET["translator"], $pub );
}

$fileinfo = $afdb->get_file_info($filename);

//print_r($fileinfo);

$temp_md5 = $fileinfo['md5_sign'];

// check if file has been changed by looking at md5 signs
if( $temp_md5 && !$afdb->is_file_md5_correct_in_db($filename,md5_file($fileinfo['filepath'])) ){
    $afdb->complete_new_file_info($filename);
    $fileinfo = $afdb->get_file_info($filename);
    print 'md5 sign is different. file was updated...';
}

// if there's no md5_sign and it's a new file,
// then update the database info from id3 tags, etc...
if( !$temp_md5 && $fileinfo['newfile'] ) {
    $afdb->complete_new_file_info($filename);
    $fileinfo = $afdb->get_file_info($filename);
    //print 'md5 sign was null: ' . $temp_md5 . '<br>';
}


?>

<?php include 'header.php'; ?>

<br><br>

<div class="panel panel-default">
    <div class="panel-body">

        <?php if( isset($_GET["update"]) AND $_GET["update"] === "yes" ){ ?>
            <div class="row">
                <div class="col-sm-12">
                    <br>
                    <div class="alert alert-success text-center" role="alert">Le fichier a été mis à jour correctement!<br>
                        <a href="manage.php">Retourner à la page de gestion</a></p>
                    </div>
                </div>
            </div>
        <?php } ?>
        
        <div class="row">
            <!--<div class="col-sm-10 col-sm-offset-2">
                <h3>Éditer les détails du fichier</h3>
            </div>-->
        </div>

        <form class="form-horizontal" role="form" action="" method="get">
            <div class="form-group">
                <label class="col-sm-2 control-label">Fichier</label>
                <div class="col-sm-10">
                    <p class="form-control-static"><?php print $filename; ?></p>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Url</label>
                <div class="col-sm-10">
                    <p class="form-control-static"><?php print $fileinfo['url']; ?></p>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Durée</label>
                <div class="col-sm-1">
                    <p class="form-control-static"><?php print $fileinfo['length']; ?></p>
                </div>
                <label class="col-sm-2 control-label">Taille du Fichier</label>
                <div class="col-sm-1">
                    <p class="form-control-static"><?php print $afdb->convert_file_size($fileinfo['size']); ?></p>
                </div>
                <label class="col-sm-2 control-label">Signature MD5</label>
                <div class="col-sm-4">
                    <p class="form-control-static"><?php print $fileinfo['md5_sign']; ?></p>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Téléchargements</label>
                <div class="col-sm-1">
                    <p class="form-control-static"><?php print $fileinfo['downloads']; ?></p>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Titre</label>
                <div class="col-sm-10">
                <input type="text" class="form-control" name="title" placeholder="Titre" value="<?php print $fileinfo['title']; ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Serie</label>
                <div class="col-sm-10">
                <input type="text" class="form-control" name="series" placeholder="Text input" value="<?php print $fileinfo['series']; ?>">
                    <span class="help-block">Par exemple: Ephésiens 2006, Luc 2014 ou Esther 2013</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Numéro d'Episode</label>
                <div class="col-sm-10">
                <input type="text" class="form-control" name="tracknumber" placeholder="Text input" value="<?php print $fileinfo['tracknumber']; ?>">
                    <span class="help-block">Numéro dans une série.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Catégorie</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="audiotype" placeholder="Text input" value="<?php print $fileinfo['audiotype']; ?>">
                    <span class="help-block">Par exemple: Enseignement, Louange, Autre</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Auteur</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="author" placeholder="Text input" value="<?php print $fileinfo['author']; ?>">
                    <span class="help-block">Par exemple: Pierre Petrignani, Pierre Menegoli, etc...</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Traducteur</label>
                <div class="col-sm-10">
                    <div class="checkbox">
                        <label><input type="checkbox" name="translated" <?php if( $fileinfo['translated'] == 1){ print "checked"; } ?>>Oui / Non</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label"></label>
                <div class="col-sm-10">
                <input type="text" class="form-control" name="translator" placeholder="Text input" value="<?php print $fileinfo['translator']; ?>">
                    <span class="help-block">Par exemple: Nancy Petrignani, James Loewen etc...</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Date</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control datepicker" name="date" placeholder="Text input" value="<?php print $fileinfo['date']; ?>">
                    <span class="help-block">Format: aaaa-mm-jj</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Passage</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="passage" placeholder="Text input" value="<?php print $fileinfo['passage']; ?>">
                    <span class="help-block">Par exemple: Matthieu 12.12-45, 1 Corinthiens 13.1-20, etc...</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Description</label>
                <div class="col-sm-10">
                <textarea class="form-control" name="description" rows="3"><?php print $fileinfo['description']; ?></textarea>
                     <span class="help-block">Description du fichier audio, du message, de la chanson, etc...</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Publier</label>
                <div class="col-sm-10">
                    <div class="checkbox">
                    <label><input type="checkbox" name="publishedfile" <?php if( $fileinfo['publishedfile'] == 1){ print "checked"; } ?>>Oui / Non</label>
                    </div>
                </div>
            </div>

            <input type="hidden" name="filename" value="<?php print $filename; ?>">
            <input type="hidden" name="update" value="yes">

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary">Appliquer</button>
                    <a href="manage.php"><button type="button" class="btn btn-default">Retourner à la page de gestion</button></a>
                </div>
            </div>
        </form>

    </div>
</div>

<?php include 'footer.php'; ?>
