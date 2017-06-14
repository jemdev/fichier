# jemdev\fichier Téléchargement de fichiers

- Auteur:       Jean Molliné
- Licence:      [CeCILL V2][]
- Pré-Requis: 
  - PHP >= 5.4
- Contact :     [Message][]
- GitHub :      [github.com/jemdev/fichier][]
- Packagist :   [packagist.org/packages/jemdev/fichier][]
****
# Avertissement
Attention, cette classe est livrée sans garantie. Développée rapidement pour répondre à un besoin interne à cause d'anomalies trop longue à corriger sur une librairie utilisée jusqu'alors, elle répond d'abord à mon besoin. Toute proposition d'amélioration sera prise en considération si elle conserve la simplicité d'utilisation.
****
# Installation
Avec Composer, ajouter ce qui suit dans la partie require de votre composer.json:

```json
{  
    "jemdev/fichier": "dev-master"  
}  
```
****
# Présentation
Cette classe permet de gérer l'envoi de fichiers par l'utilisation d'un formulaire. Il peut s'agir de fichiers textes, bureautique ou des images.

Des méthodes additionnelles permettent de retailler des images voire de créer des miniatures en réduisant proportionnellement les dimensions de l'image originale.
# Utilisation
Chaque fichier devra être traité individuellement au cas où certains paramètres seraient propres à chaque fichier, par exemple leur destination.
À partir d'une instance de jemdev\fichier\upload, on définit d'abord les paramètres si nécessaire. Notez que les méthodes de réglage des paramètres peuvent être chainées.
### Utilisation de base
Sans paramétrage particulier, l'utilisation est on ne peut plus simple :

```php
/* Création de l'instance */
$oUpload = new jemdev\fichier\upload();
/* Enregistrement du fichier vers le répertoire de stockage */
$sauvegarde = $oUpload->enregistrerFichier($_FILES['fichier'], $home .'temp'. DS);
/* Vérification des erreurs et récupération des messages s'il y a lieu */
if(false === $sauvegarde)
{
    $erreur = '<pre>'. print_r($oUpload->getErreurs(), true) .'</pre>'. PHP_EOL;
}
```

Si plusieurs fichiers sont sélectionnés via un champ de type *file* avec l'attribut *multiple*, on pourra se retrouver alors avec le contenu de *$_FILES* ressemblant à ceci:

```
Array(
    [fichier] => Array(
        [name] => Array(
            [0] => sites_publics.rar
            [1] => stats_jemweb.info.txt
            [2] => ZSST-20130331-140024.7z
        )
        [type] => Array(
            [0] => application/octet-stream
            [1] => text/plain
            [2] => application/octet-stream
        )
        [tmp_name] => Array(
            [0] => C:\Windows\Temp\phpD031.tmp
            [1] => C:\Windows\Temp\phpD042.tmp
            [2] => C:\Windows\Temp\phpD053.tmp
        )
        [error] => Array(
            [0] => 0
            [1] => 0
            [2] => 0
        )
        [size] => Array(
            [0] => 140763
            [1] => 29446
            [2] => 2025976
        )
    )
)
```
Chacun des fichiers sera traité dans une boucle automatique et stocké au même endroit. Si on veut effectuer un traitement spécifique pour chacun en variant les paramètres de l'instance, il faudra effectuer la boucle en amont.
Chaque tour de la boucle traitera donc un tableau semblable à celui-ci: 

```
Array(
    [name]     => sites_publics.rar
    [type]     => application/octet-stream
    [tmp_name] => C:\Windows\Temp\phpD031.tmp
    [error]    => 0
    [size]     => 140763
)
```
On peut donc traiter le tableau en amont en préparant un tableau par fichier de la manière suivante :

```php
$oUpload = new jemdev\fichier\upload();
foreach($_FILES['fichier']['name'] as $k => $fichier)
{
    $infos = array(
        'name'      => $_FILES['fichier']['name'][$k],
        'type'      => $_FILES['fichier']['type'][$k],
        'tmp_name'  => $_FILES['fichier']['tmp_name'][$k],
        'error'     => $_FILES['fichier']['error'][$k],
        'size'      => $_FILES['fichier']['size'][$k]
    );
    /* On trie le lieu de stockage en fonction du type */
    $aType = explode('/', $_FILES['fichier']['type'][$k]);
    $repertoireCible = $home .'temp'. DS . $aType[0] . DS;
    $sauvegarde = $oUpload->enregistrerFichier($infos, $repertoireCible);
    if(false === $sauvegarde)
    {
        /* En cas d'erreur, on récupère le message et on arrête le traitement desfichiers */
        $erreur = '<pre>'. print_r($oUpload->getErreurs(), true) .'</pre>'. PHP_EOL;
        break;
    }
}
```
Notez que si le répertoire de destination n'existe pas, il sera créé. Par défaut, les droits d'accès à ce répertoire seront définis à 0755 (*chmod*), ceux des fichiers à 0644.
On peut modifier ce mode avant l'enregistrement. Par exemple si on veut que le fichier soit défini à 0777 :

```php
$oUpload = new jemdev\fichier\upload();
$oUpload->setDroitAccesFichier(0777);
//... la suite du code de traitement ne change pas.
```

# Manipulation d'images
Les fichiers envoyés peuvent être des images et peuvent, si nécessaire, être redimensionnés. On peut également générer une miniature indépendamment du fichier original dans un répertoire distinct.
### Note :
Les méthodes de traitement d'images ne seront pas accessible lors d'envoi de fichiers multiples.

On peut au préalable indiquer les dimensions maximales de l'image normale ainsi que la hauteur maximale de la vignette. Le cas échéant, les retailles de l'image originale seront proportionnelles.

Le même principe que pour tout fichier est mis en œuvre pour l'enregistrement de l'image : on peut y rajouter la création d'une miniature en indiquant un répertoire de stockage distinct, par exemple un sous-répertoire dans celui contenant l'image normale.

Pour un fichier simple, on peut ainsi créer une miniature en indiquant le chemin vers le répertoire approprié pour les miniatures, suivez les commentaires dans l'exemple de code ci-dessous).

```php
// Définition du répertoire de destination de l'image normale.
$repImgs  = $home .'temp'. DS .'image'. DS;
// Définition du répertoire des miniatures.
$repMinis = $repImgs .'minis'. DS;
// On définit les dimensions maximum pour les images (hauteur, largeur, et hauteur miniature)
$oUpload->setImageInfo(350, 600, 100);
// On enregistre le fichier
$enreg = $oUpload->enregistrerFichier($_FILES['image'], $repImgs);
if(false !== $enreg)
{
    // Redimensionnement éventuelle de l'image si ses dimensions originales dépassent les
    // maximums définis ci-dessus, la création de la miniature sera alors automatique
    // si on précise le premier paramètre à TRUE et qu'on indique le répertoire de
    // destination.
    $oUpload->creerVignette(true, $repMinis);
}
else
{
    // En cas d'erreur, on affiche les messages stockés.
    echo('<pre class="vardumpdebug">'. PHP_EOL);
    var_dump($oUpload->getErreurs());
    echo("</pre>". PHP_EOL);
}
```

# En résumé
Ce système est très basique pour une utilisation quotidienne. N'hésitez pas à proposer des améliorations si toutefois elles ne compliquent pas son utilisation.

[CeCILL V2]: http://www.cecill.info/licences/Licence_CeCILL_V2-fr.html "Texte de la licence CeCILL V2"
[Message]: http://jem-dev.com/a-propos/contacter-jemdev/ "Contacter Jean Molliné via son site"
[github.com/jemdev/fichier]: https://github.com/jemdev/fichier "Page GitHub de cette classe"
[packagist.org/packages/jemdev/fichier]: https://packagist.org/packages/jemdev/fichier "Page Packagist de cette classe"
