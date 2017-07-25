<?php 
include 'AudioFileDB.class.php';
$afdb = new AudioFileDB();
$filename = htmlspecialchars($_GET["filename"]);

if( isset($_GET["update"]) AND $_GET["update"] === "yes" ){
    $afdb->update_file_info( $filename, $_GET["title"], $_GET["series"], $_GET["audiotype"], $_GET["author"],
                                $_GET["date"], $_GET["passage"], $_GET["description"], $_GET["tracknumber"] );
}

$fileinfo = $afdb->getAudioFileInfo($filename);

?>

<?php include 'header.php'; ?>

<div class="panel panel-default">
    <!--<div class="panel-heading">
        <h3 class="panel-title col-md-offset-2">Ajouter ou Modifier le fichier:  <?php print $filename; ?></h3>
    </div>-->

    <div class="panel-body">
        <form class="form-horizontal" role="form" action="#success" method="get">
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
                    <p class="form-control-static"><?php print $afdb->FileSizeConvert($fileinfo['size']); ?></p>
                </div>
                <label class="col-sm-2 control-label">Signature MD5</label>
                <div class="col-sm-4">
                    <p class="form-control-static"><?php print $fileinfo['md5_sign']; ?></p>
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

            <input type="hidden" name="filename" value="<?php print $filename; ?>">
            <input type="hidden" name="update" value="yes">

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-2">
                    <button type="submit" class="btn btn-default">Appliquer</button>
                </div>
            </div>
                <?php if( isset($_GET["update"]) AND $_GET["update"] === "yes" ){ ?>
                <div class="form-group" id="success">
                    <p class="bg-success">Le fichier a été mis à jour correctement!</p>
                    <div class="col-sm-offset-2 col-sm-10">
                        <a href="index.php"><button type="button" class="btn btn-default">Retour à l'Accueil</button></a>
                    </div>
                </div>
                <?php } ?>
        </form>

    </div>
</div>

<?php include 'footer.php'; ?>
