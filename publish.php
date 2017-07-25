<?php
include 'AudioFileDB.class.php';
$afdb = new AudioFileDB();

$afdb->generate_json_file();

$afdb->generate_recent_json_file();

?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-sm-6 col-sm-offset-3">
        <br>
        <div class="alert alert-success text-center" role="alert">Les 2 fichiers ont été correctement générés!</div>
    </div>

    <div class="col-sm-6 col-sm-offset-3">
        <p>De quels fichiers parle-t-on?<p>
        <h3>1 - Json de tous les fichiers publiés</h3>
        <p>Un fichier json de tous les fichiers publiés a été écrit. C'est ce fichier
        qui est utilisé par la page audio de ccnice.fr pour afficher la liste de tous
        les fichiers audio disponibles.</p>
    </div>
    <div class="col-sm-6 col-sm-offset-3">
        <h3>2 - Json des 5 fichiers les plus récents</h3>
        <p>Un fichier json des 5 fichiers audio les plus récents a été écrit. C'est le
        fichier qui est utilisé pour afficher les derniers enseignements sur la page
        principale de ccnice.fr</p>
    </div>
    <div class="col-sm-6 col-sm-offset-3">
        <p>Ce troisième point reste à coder:</p>
        <h3>3 - Fichier RSS</h3>
        <p>Un fichier RSS a été écrit. Il contient les liens vers tous les fichiers publiés.
        C'est un format de fichier utilisé pour les podcasts, itunes, etc...</p>
    </div>
</div>

<?php include 'footer.php'; ?>
