<?php
include 'AudioFileDB.class.php';

$afdb = new AudioFileDB();

// timing start
$start_time = microtime(true);

// launch filescan
$afdb->scan_files();

// timing end
$end_time = microtime(true);
$search_time = $end_time - $start_time;

// count files
$new_file_count = $afdb->count_new_files();
$published_file_count = $afdb->count_published_files();
$non_published_file_count = $afdb->count_non_published_files();

?>

<?php include 'header.php'; ?>

    <div class="row">
        <div class="col-sm-6 col-sm-offset-3">
            <br>
            <strong>Informations: </strong>
            <p>Le scan du dossier des fichiers audio a pris <?php print round($search_time, 4); ?> secondes.</p>
            <p>Résultats:</p>
            <p>- <?php print $afdb->count_all_files(); ?> fichiers en tout dans la base de données.</p>
            <p>- <?php print $published_file_count; ?> fichiers publiés.</p>
            <p>- <?php print $non_published_file_count; ?> fichiers non-publiés.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-sm-offset-3">

            <h2><?php print $new_file_count; ?> Nouveaux Fichiers</h2>
            <p><?php print $new_file_count; ?> nouveaux fichiers ont été trouvés dans le fichier "audio" du serveur ftp et ajoutés à la base de données.</p>
            <p>Rendez-vous sur la page de gestion pour éditer les informations concernant les fichiers et pour les publier sur le site web.</p>
            <br>
            <p><a href="manage.php"><button type="button" class="btn btn-primary">Gérer la base de données</button></a></p>
        </div>
    </div>

<?php include 'footer.php'; ?>
