# jemdev\fichier T�l�chargement de fichiers

- Auteur:       Jean Mollin�
- Licence:      [CeCILL V2][]
- Pr�-Requis: 
  - PHP >= 5.4
- Contact :     [Message][]
- GitHub :      [github.com/jemdev/fichier][]
- Packagist :   [packagist.org/packages/jemdev/fichier][]
****
# Avertissement
Attention, cette classe est livr�e sans garantie. D�velopp�e rapidement pour r�pondre � un besoin interne � cause d'anomalies trop longue � corriger sur une librairie utilis�e jusqu'alors, elle r�pond d'abord � mon besoin. Toute proposition d'am�lioration sera prise en consid�ration si elle conserve la simplicit� d'utilisation.
****
# Installation
Avec Composer, ajouter ce qui suit dans la partie require de votre composer.json:

```json
{  
    "jemdev/fichier": "dev-master"  
}  
```
****
# Pr�sentation
Cette classe permet de g�rer l'envoi de fichiers par l'utilisation d'un formulaire. Il peut s'agir de fichiers textes, bureautique ou des images.

Des m�thodes additionnelles permettent de retailler des images voire de cr�er des miniatures en r�duisant proportionnellement les dimensions de l'image originale.
# Utilisation
Chaque fichier devra �tre trait� individuellement au cas o� certains param�tres seraient propres � chaque fichier, par exemple leur destination.
� partir d'une instance de jemdev\fichier\upload, on d�finit d'abord les param�tres si n�cessaire. Notez que les m�thodes de r�glage des param�tres peuvent �tre chain�es.
### Utilisation de base
Sans param�trage particulier, l'utilisation est on ne peut plus simple :

```php
/* Cr�ation de l'instance */
$oUpload = new jemdev\fichier\upload();
/* Enregistrement du fichier vers le r�pertoire de stockage */
$sauvegarde = $oUpload->enregistrerFichier($_FILES['fichier'], $home .'temp'. DS);
/* V�rification des erreurs et r�cup�ration des messages s'il y a lieu */
if(false === $sauvegarde)
{
    $erreur = '<pre>'. print_r($oUpload->getErreurs(), true) .'</pre>'. PHP_EOL;
}
```

Si plusieurs fichiers sont s�lectionn�s via un champ de type *file* avec l'attribut *multiple*, on pourra se retrouver alors avec le contenu de *$_FILES* ressemblant � ceci:

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
Chacun des fichiers sera trait� dans une boucle automatique et stock� au m�me endroit. Si on veut effectuer un traitement sp�cifique pour chacun en variant les param�tres de l'instance, il faudra effectuer la boucle en amont.
Chaque tour de la boucle traitera donc un tableau semblable � celui-ci: 

```
Array(
    [name]     => sites_publics.rar
    [type]     => application/octet-stream
    [tmp_name] => C:\Windows\Temp\phpD031.tmp
    [error]    => 0
    [size]     => 140763
)
```
On peut donc traiter le tableau en amont en pr�parant un tableau par fichier de la mani�re suivante :

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
        /* En cas d'erreur, on r�cup�re le message et on arr�te le traitement desfichiers */
        $erreur = '<pre>'. print_r($oUpload->getErreurs(), true) .'</pre>'. PHP_EOL;
        break;
    }
}
```
Notez que si le r�pertoire de destination n'existe pas, il sera cr��. Par d�faut, les droits d'acc�s � ce r�pertoire seront d�finis � 0755 (*chmod*), ceux des fichiers � 0644.
On peut modifier ce mode avant l'enregistrement. Par exemple si on veut que le fichier soit d�fini � 0777 :

```php
$oUpload = new jemdev\fichier\upload();
$oUpload->setDroitAccesFichier(0777);
//... la suite du code de traitement ne change pas.
```

# Manipulation d'images
Les fichiers envoy�s peuvent �tre des images et peuvent, si n�cessaire, �tre redimensionn�s. On peut �galement g�n�rer une miniature ind�pendamment du fichier original dans un r�pertoire distinct.
### Note :
Les m�thodes de traitement d'images ne seront pas accessible lors d'envoi de fichiers multiples.

On peut au pr�alable indiquer les dimensions maximales de l'image normale ainsi que la hauteur maximale de la vignette. Le cas �ch�ant, les retailles de l'image originale seront proportionnelles.

Le m�me principe que pour tout fichier est mis en �uvre pour l'enregistrement de l'image : on peut y rajouter la cr�ation d'une miniature en indiquant un r�pertoire de stockage distinct, par exemple un sous-r�pertoire dans celui contenant l'image normale.

Pour un fichier simple, on peut ainsi cr�er une miniature en indiquant le chemin vers le r�pertoire appropri� pour les miniatures, suivez les commentaires dans l'exemple de code ci-dessous).

```php
// D�finition du r�pertoire de destination de l'image normale.
$repImgs  = $home .'temp'. DS .'image'. DS;
// D�finition du r�pertoire des miniatures.
$repMinis = $repImgs .'minis'. DS;
// On d�finit les dimensions maximum pour les images (hauteur, largeur, et hauteur miniature)
$oUpload->setImageInfo(350, 600, 100);
// On enregistre le fichier
$enreg = $oUpload->enregistrerFichier($_FILES['image'], $repImgs);
if(false !== $enreg)
{
    // Redimensionnement �ventuelle de l'image si ses dimensions originales d�passent les
    // maximums d�finis ci-dessus, la cr�ation de la miniature sera alors automatique
    // si on pr�cise le premier param�tre � TRUE et qu'on indique le r�pertoire de
    // destination.
    $oUpload->creerVignette(true, $repMinis);
}
else
{
    // En cas d'erreur, on affiche les messages stock�s.
    echo('<pre class="vardumpdebug">'. PHP_EOL);
    var_dump($oUpload->getErreurs());
    echo("</pre>". PHP_EOL);
}
```

# En r�sum�
Ce syst�me est tr�s basique pour une utilisation quotidienne. N'h�sitez pas � proposer des am�liorations si toutefois elles ne compliquent pas son utilisation.

[CeCILL V2]: http://www.cecill.info/licences/Licence_CeCILL_V2-fr.html "Texte de la licence CeCILL V2"
[Message]: http://jem-dev.com/a-propos/contacter-jemdev/ "Contacter Jean Mollin� via son site"
[github.com/jemdev/fichier]: https://github.com/jemdev/fichier "Page GitHub de cette classe"
[packagist.org/packages/jemdev/fichier]: https://packagist.org/packages/jemdev/fichier "Page Packagist de cette classe"
