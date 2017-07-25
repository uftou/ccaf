<?php


?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-sm-12">
        <br>
    </div>
</div>

<div class="row">

    <div class="col-sm-6 col-sm-offset-3">
        <h3>Gestion</h3>
        <p>Vous pouvez gérer les fichiers qui sont dans la base données. Vous pourrez, par exemple,
        editer les informations concernant un fichier, gérer les fichiers qui posent problème dans la base,
        voir quels fichiers n'ont pas été publiés, etc...</p>
        <p><a href="manage.php"><button type="button" class="btn btn-primary">Gérer la base</button></a></p>
    </div>

    <div class="col-sm-6 col-sm-offset-3">
        <h3>Nouveaux</h3>
        <p>En cliquant ici, l'application cherchera tous les nouveaux fichiers dans le dossier audio du serveur ftp
        pour les ajouter à la base de fichiers. Vous pourrez ensuite les éditer avant de les publier.</p>
        <p><a href="scan.php"><button type="button" class="btn btn-primary">Rechercher de nouveaux fichiers</button></a></p>
    </div>

    <div class="col-sm-6 col-sm-offset-3">
        <h3>Publier</h3>
        <p>En cliquant ici, vous pouvez vérifier que tous les changements, toutes les mises-à-jour et toutes les suppressions
        sont bien publiés sur le site web de l'église.</p>
        <p><a href="publish.php"><button type="button" class="btn btn-primary">Publier tous les changements</button></a></p>
    </div>
</div>

<?php include 'footer.php'; ?>
