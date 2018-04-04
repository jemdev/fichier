<?php
namespace jemdev\fichier;

/**
 * Classe de gestion de téléchargement de fichiers.
 *
 * Traite les fichiers envoyés par le biais de formulaires et des informations incluses
 * dans la super-globale $_FILE.
 * Copie les fichiers dans le répertoire cible indiqué et crée si nécessaire les
 * répertoires en cascade.
 *
 * @author jemdev <jmolline@jem-dev.com>
 * @version 1.0-dev
 * @license CeCILL 2.1 @see http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.html
 * @since       PHP 5.4.x
 * @todo Traductions dans d'autres langues que le français.
 */
class upload
{
    const APACHE_MIME_TYPES_URL = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
    private $_cheminLangues;
    private $_langue            = 'fr_FR';
    private $_aMessages         = array();
    /**
     * Droits d'accès au fichier
     * @var int
     */
    private $_file_mode         = 0644;
    /**
     * Droits d'accès au répertoire
     * @var int
     */
    private $_dir_mode          = 0755;
    private $_aInfosFichiers    = array();
    /**
     * Liste des extensions de fichier acceptées au téléchargement.
     *
     * Cette liste pourra être différente et modifiée (@see upload::setExtensionsOk() )
     * @var array
     */
    private $_aExtensionsOk     = array('7z', 'csv', 'doc', 'docx', 'gif', 'jpeg', 'jpg', 'odc', 'odb', 'odf', 'odg', 'odi', 'odp', 'ods', 'odt', 'otg', 'otp', 'ots', 'ott', 'pdf', 'png', 'ppt', 'pptx', 'rar', 'rtf', 'txt', 'xls', 'xlsx', 'xml', 'zip');
    /**
     * Liste des types MIME de fichier acceptés au téléchargement.
     *
     * Une liste pré-établie comporte des fichiers image, des fichiers textes et des formats
     * bureautiques divers. Ces types serviront à valider les fichiers téléchargés non sur
     * leur extension qui peut êrte manipulée manuellement par par le type-mime rédini dans
     * les en-tête du fichier lui-même. Si l'extension a été modifiée mais que le type mime
     * fait partie de la liste définie, alors il sera considéré comme acceptable, autrement
     * il sera rejeté.
     *
     * Note :   les types rarx et msox sont des types personnalisés non répertoriés
     *          dans la liste proposée par le fichier Apache (@see upload::APACHE_MIME_TYPES_URL)
     * @var array
     */
    private $_aMimeTypesOk      = array(
        '7z'    => 'application/x-7z-compressed',
        'csv'   => 'text/csv',
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'gif'   => 'image/gif',
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'odc'   => 'application/vnd.oasis.opendocument.chart',
        'odb'   => 'application/vnd.oasis.opendocument.database',
        'odf'   => 'application/vnd.oasis.opendocument.formula',
        'odg'   => 'application/vnd.oasis.opendocument.graphics',
        'odi'   => 'application/vnd.oasis.opendocument.image',
        'odp'   => 'application/vnd.oasis.opendocument.presentation',
        'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt'   => 'application/vnd.oasis.opendocument.text',
        'otg'   => 'application/vnd.oasis.opendocument.graphics-template',
        'otp'   => 'application/vnd.oasis.opendocument.presentation-template',
        'ots'   => 'application/vnd.oasis.opendocument.spreadsheet-template',
        'ott'   => 'application/vnd.oasis.opendocument.text-template',
        'pdf'   => 'application/pdf',
        'png'   => 'image/png',
        'ppt'   => 'application/vnd.ms-powerpoint',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar'   => 'application/x-rar-compressed',
        'rtf'   => 'application/rtf',
        'txt'   => 'text/plain',
        'xls'   => 'application/vnd.ms-excel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml'   => 'application/xml',
        'zip'   => 'application/zip',
        'rarx'  => 'application/x-rar',
        'msox'  => 'application/vnd.ms-office'
    );
    private $_max_file_size;
    private $_repertoireDestination;
    private $_bTypeImage        = false;
    private $_bMultiple         = false;
    /**
     * Chemin absolu vers le fichier source (images seulement)
     * @var    String
     */
    private $_imgSrc     = "";
    /**
     * @var    String
     * @desc   Nom du fichier source
     */
    private $_nomImage   = "";
    /**
     * @var    String
     * @desc   Répertoire source de l'image originale s'il s'agit d'un fichier image
     */
    private $_rep_originale;
    /**
     * @var    String
     * @desc   Répertoire de l'image miniature s'il s'agit d'un fichier image
     */
    private $_rep_vignette;
    /**
     * @var    Integer
     * @desc   Hauteur maximum d'une image en pixels
     */
    private $_hMaxi      = 300;
    /**
     * @var    Integer
     * @desc   Largeur maximum d'une image en pixels
     */
    private $_lMaxi      = 400;
    /**
     * @var    Integer
     * @desc   Hauteur maximum d'une image miniature en pixels
1     */
    private $_hMaxi_v    = 120;

    /**
     * Active ou non le mode débogage afin d'enregistrer ou non les événements.
     * @var bool
     */
    private $_bDebug            = false;
    /**
     * Informations sur le déroulement du processus de traitement
     * @var array
     */
    private $_aLog              = array();

    /**
     * Constructeur.
     * Définir l'instance et ses propriétés de base.
     *
     * @param string $lang          Langue des messages d'erreur.
     */
    public function __construct($lang = 'fr_FR')
    {
        $this->_max_file_size = ini_get('upload_max_filesize');
        $this->_setTypesMimeOk();
        $this->_cheminLangues           = realpath(__DIR__). DIRECTORY_SEPARATOR .'lang'. DIRECTORY_SEPARATOR;
        $this->_setLangue($lang);
    }

    /**
     * Définir la liste des extensions de fichiers autorisés au téléchargement.
     *
     * Attention :
     * Cette méthode devra être utilisée AVANT l'appel de la méthode upload::enregistrerFichier
     *
     * À noter que les fichiers seront testés selon leur type et non selon cette extension
     * afin d'éviter toute tentative d'envoi de fichiers dont l'extension aurait été modifiée
     * manuellement.
     *
     * @param array $aExtensions
     */
    public function setExtensionsOk($aExtensions)
    {
        $this->_aExtensionsOk = $aExtensions;
        return($this);
    }

    /**
     * Définir les droits d'accès aux répertoires qui devront éventuellement être
     * créés (chmod)
     * @param number $mode
     */
    public function setDroitAccesRepertoire($mode = 0755)
    {
        $this->_dir_mode = $mode;
        return($this);
    }

    /**
     * Définir les droits d'accès aux fichiers qui seront enregistrés (chmod)
     * @param number $mode
     */
    public function setDroitAccesFichier($mode = 0644)
    {
        $this->_file_mode = $mode;
        return($this);
    }

    /**
     * Définir les paramètres par défaut pour le traitement d'images.
     *
     * @param  Int       $hMaxi_a   paramètre de hauteur maximum de la grande image qui devra être générée
     * @param  Int       $lMaxi_a   paramètre de largeur maximum de la grande image qui devra être générée
     * @param  Int       $hMaxi_v   paramètre de hauteur maximum de la miniature qui devra être générée
     */
    public function setImageInfo($hMaxi_a = 300, $lMaxi_a = 400, $hMaxi_v = 120)
    {
        $this->_hMaxi           = $hMaxi_a;
        $this->_lMaxi           = $lMaxi_a;
        $this->_hMaxi_v         = $hMaxi_v;
        return($this);
    }

    /**
     * Récupérer les messages d'erreurs enregistrés au fil du processus de traitement.
     */
    public function getErreurs()
    {
        return($this->_aLog);
    }

    /**
     * Enregistrer un fichier téléchargé dans son répertoire de destination.
     *
     * Le paramètre $infosFichier correspond au confenu du tableau $_FILE[nom-du-champ]
     * avec donc les index :
     * - name     => nom du fichier
     * - type     => type mime
     * - tmp_name => chemin où est temporairement stocké le fichier envoyé
     * - error    => Numéro de l'erreur le cas échéant
     * - size     => taille du fichier en octets
     *
     * Si les informations reçues contiennent plusieurs fichiers, la fonction traitera
     * récursivement chaque fichier.
     *
     * @param array  $infosFichier  Informations de téléchargement sur le fichier
     * @param string $cible         Répertoire de destination du fichier.
     */
    public function enregistrerFichier($infosFichier, $cible)
    {
        if(false == $infosFichier)
        {
            return false;
        }
        if(is_array($infosFichier['name']))
        {
            $this->_bMultiple = true;
            foreach($infosFichier['name'] as $k => $f)
            {
                $infos = array(
                    'name'      => $infosFichier['name'][$k],
                    'type'      => $infosFichier['type'][$k],
                    'tmp_name'  => $infosFichier['tmp_name'][$k],
                    'error'     => $infosFichier['error'][$k],
                    'size'      => $infosFichier['size'][$k]
                );
                $this->enregistrerFichier($infos, $cible);
            }
        }
        else
        {
            $this->_aInfosFichiers          = $infosFichier;
            $this->_repertoireDestination   = $cible;
            if($this->_aInfosFichiers['error'] != 0)
            {
                $info = ($this->_aInfosFichiers['error'] == 1) ? $this->_max_file_size : null;
                $info = ($this->_aInfosFichiers['error'] == 2) ? $_POST['MAX_FILE_SIZE'] : null;
                $this->_aLog[] = sprintf($this->_aMessages['err_upload_'. $this->_aInfosFichiers['error']], $info);
                return(false);
            }
            $valide = $this->_validerTypeFichier();
            if(false === $valide)
            {
                $this->_aLog[] = sprintf($this->_aMessages['err_type_ko']);
                return(false);
            }
            if(false === $this->_creerRepertoire($this->_repertoireDestination))
            {
                return(false);
            }
            $fichier = $this->_repertoireDestination . basename($this->_aInfosFichiers['name']);
            if(true == $this->_bTypeImage)
            {
                $this->_imgSrc          = $fichier;
                $this->_nomImage        = $this->_aInfosFichiers['name'];
                $this->_rep_originale   = $cible;
            }
            if(false === move_uploaded_file($this->_aInfosFichiers['tmp_name'], $fichier))
            {
                $this->_aLog[] = sprintf($this->_aMessages['err_move'], $this->_repertoireDestination);
                return(false);
            }
        }
        return true;
    }

    /**
     * Déplacer un fichier vers un autre répertoire.
     * @param string    $fichier                Chemin absolu vers le fichier source
     * @param string    $repertoireDestination  Chemin absolu vers le répertoire de destination.
     * @return boolean
     */
    public function deplacerFichier($fichier, $repertoireDestination)
    {
        if(file_exists($fichier))
        {
            if(false === $this->_creerRepertoire($repertoireDestination))
            {
                return(false);
            }
            $nomFichier = basename($fichier);
            $deplacement = rename($fichier, $repertoireDestination . DIRECTORY_SEPARATOR . $nomFichier);
            if(true !== $deplacement)
            {
                $this->_aLog[] = sprintf($this->_aMessages['err_move'], $repertoireDestination);
                return(false);
            }
        }
        else
        {
            $this->_aLog[] = sprintf($this->_aMessages['err_nofile'], $fichier);
            return(false);
        }
        return(true);
    }

    /**
     * Déterminer le format de l'image originale pour créer une miniature pour la galerie
     * et au besoin retailler l'image originale si elle dépasse les dimensions maximum définies.
     *
     * @param   String  $nom_image              Nom du fichier image original
     * @param   boolean $bMiniature             Définit s'il faut créer une miniature de l'image originale
     * @param   string  $repertoireMiniature    Chemin absolu vers le répertoire de stockage des miniatures
     * @return  Boolean                         Vrai en cas de succès, Faux dans le cas contraire.
     */
    public function creerVignette($bMiniature = false, $repertoireMiniature = null)
    {
        if(true === $this->_bMultiple)
        {
            $this->_aLog[] = sprintf($this->_aMessages['err_imgmult']);
            return(false);
        }
        elseif(true !== $this->_bTypeImage)
        {
            $this->_aLog[] = sprintf($this->_aMessages['']);
            return(false);
        }
        $this->_rep_vignette = $repertoireMiniature;
        /* Création de la vignette pour affichage par exemple de plusieurs miniatures dans une galerie */
        $resultat   = false;
        $info       = pathinfo($this->_imgSrc);
        $extension  = strtolower($info['extension']);
        switch($extension)
        {
            case "jpg":
            case "jpeg":
            case "pjpg":
                $imgSrc = imagecreatefromjpeg($this->_imgSrc);
                break;
            case "png":
            case "x-png":
                $imgSrc = imagecreatefrompng($this->_imgSrc);
                break;
            case "gif":
                $imgSrc = imagecreatefromgif($this->_imgSrc);
                break;
            default:
                $imgSrc = null;
        }
        if(isset($imgSrc))
        {
            /**
             * S'il s'agit d'un type d'image supporté par notre application :
             * Création de l'image miniature puis retaille si nécessaire de l'image normale
             */
            $bMini = true;
            if(true === $bMiniature && !is_null($repertoireMiniature))
            {
                $dir_mini = $this->_creerRepertoire($repertoireMiniature);
                if(false === $dir_mini)
                {
                    return(false);
                }
                $bMini = $this->_creerMiniature($imgSrc, $this->_nomImage);
            }
            if(false !== $bMini)
            {
                $retaille = $this->_retaillerImage($imgSrc, $this->_rep_originale, $this->_nomImage);
            }
            $resultat = true;
        }
        else
        {
            $this->_aLog[] = sprintf($this->_aMessages['err_noimg']);
            $resultat = false;
        }
        return $resultat;
    }

    /**
     * Méthode _miniature().
     *
     * Cette fonction ré-échantillone l'image originale et définit les dimension d'une miniature
     * proportionnée par rapport à une hauteur fixe.
     *
     * @param   String $imgSrc      Chemin absolu vers l'image source de taille originale
     * @param   String $nomImage    Nom du fichier image.
     * @return  string|boolean      Si la création de la miniature s'est correctement déroulée,
     *                              la fonction retourne le nom de l'image, FALSE dans le cas
     *                              contraire.
     */
    private function _creerMiniature($imgSrc, $nomImage)
    {
        /* Quelle taille fait notre image ? */
        $largeurSrc = imagesx($imgSrc);
        $hauteurSrc = imagesy($imgSrc);
        $coef       = $hauteurSrc / $this->_hMaxi_v;
        $lDest      = ceil($largeurSrc / $coef);
        /* Largeur et hauteur des images miniatures */
        $largeur    = $lDest;
        $hauteur    = $this->_hMaxi_v;

        /* Création de l'image */
        $lSrc       = $largeurSrc;
        $hSrc       = $hauteurSrc;
        $miniature  = imagecreatetruecolor($largeur, $hauteur);
        imagealphablending($miniature, false);
        imagesavealpha($miniature, true);
        /* On ré-échantillone l'image initiale pour en créer une copie en miniature */
        imagecopyresampled($miniature, $imgSrc, 0, 0, 0, 0, $largeur, $hauteur, $lSrc, $hSrc);

        /* On enregistre l'image dans le répertoire des miniatures */
        $enreg = imagejpeg($miniature, $this->_rep_vignette . $nomImage);
        return (true === $enreg) ? $nomImage : false;
    }

    /**
     * Cette méthode ré-échantillone l'image originale et définit des dimensions acceptables pour
     * une page web par rapport à une hauteur et une largeur maxi.
     *
     * @param   String  $imgSrc     Chemin absolu vers l'image source de taille originale
     * @param   String  $rep_Dest   Chemin absolu vers le répertoire de destination de l'image finale.
     * @param   String  $nomImage   Nom du fichier image.
     * @return  Boolean             Si la création de la photo s'est correctement déroulée, la fonction retourne TRUE, FALSE dans
     *                              le cas contraire.
     */
    private function _retaillerImage($imgSrc, $rep_Dest, $nomImage)
    {
        /* Quelle taille fait notre image ? */
        $fichier    = getimagesize($this->_imgSrc);
        $largeurSrc = $fichier[0];
        $hauteurSrc = $fichier[1];
        $dimensions = $this->_calculRetaille($largeurSrc, $hauteurSrc);
        /* On arrondit les chiffres en entiers */
        $largeur = ceil($dimensions[0]);
        $hauteur = ceil($dimensions[1]);
        /* Création de l'image */
        $imagenormale = imagecreatetruecolor($largeur, $hauteur);
        imagealphablending($imagenormale, false);
        imagesavealpha($imagenormale, true);
        /* On ré-échantillone l'image originale pour en créer une copie aux nouvelles dimensions */
        imagecopyresampled($imagenormale, $imgSrc, 0, 0, 0, 0, $largeur, $hauteur, $largeurSrc, $hauteurSrc);

        /* On enregistre l'image dans le répertoire des miniatures */
        $enreg = imagejpeg($imagenormale, $rep_Dest . $nomImage);
        return (true == $enreg) ? $nomImage : false;
    }

    /**
     * Méthode de réduction proportionnelle des dimensions de l'image originale à
     * des dimensions acceptable par exemple pour un format d'écran 800/600
     *
     * @param   Int     $lSrc Largeur de l'image originale
     * @param   Int     $hSrc Hauteur de l'image originale
     * @return  Array
     */
    private function _calculRetaille($lSrc, $hSrc)
    {
        $coef_h = 1;
        $coef_l = 1;
        if($lSrc > $this->_lMaxi)
        {
            $largeur  = $this->_lMaxi;
            $coef_l   = $lSrc / $this->_lMaxi;
            $hauteur  = $hSrc / $coef_l;
        }
        elseif($hSrc > $this->_hMaxi)
        {
            $hauteur  = $this->_hMaxi;
            $coef_h   = $hSrc / $this->_hMaxi;
            $largeur  = $lSrc / $coef_h;
        }
        else
        {
            $largeur = $lSrc;
            $hauteur = $hSrc;
        }
        $dimension = array(
            0 => $largeur,
            1 => $hauteur
        );
        return $dimension;
    }

    private function _validerTypeFichier()
    {
        $type = mime_content_type($this->_aInfosFichiers['tmp_name']);
        $masque = '#^image/#';
        if(preg_match($masque, $type))
        {
            $this->_bTypeImage = true;
        }
        $retour = (in_array($type, $this->_aMimeTypesOk)) ? true : false;
        return($retour);
    }

    private function _creerRepertoire($cheminRepertoire)
    {
        if(!file_exists($cheminRepertoire) || !is_dir($cheminRepertoire))
        {
            $creerRepertoire = mkdir($cheminRepertoire, $this->_dir_mode, true);
            if(false === $creerRepertoire)
            {
                $this->_aLog[] = sprintf($this->_aMessages['err_mkdir'], $cheminRepertoire);
                return(false);
            }
        }
        return(true);
    }

    private function _setLangue($lang = 'fr_FR')
    {
        $fichierLangue = (file_exists($this->_cheminLangues . $lang .'.php')) ? $this->_cheminLangues . $lang .'.php' : $this->_cheminLangues .'fr_FR.php';
        include($fichierLangue);
        $this->_aMessages = $aMessages;
    }

    private function _setTypesMimeOk()
    {
        $aExts = (!empty($this->_aExtensionsOk)) ? $this->_aExtensionsOk : null;
        $aMimeTypes = $this->_generateUpToDateMimeArray($aExts);
        foreach($aMimeTypes as $k => $v)
        {
            $this->_aMimeTypesOk[$k] = $v;
        }
    }

    private function _generateUpToDateMimeArray($aTypesOk = null)
    {
        $tm = array();
        $mimetypes = realpath(__DIR__ . DIRECTORY_SEPARATOR .'inc'. DIRECTORY_SEPARATOR .'mime.types');
        $sListe = '';
        if(false != ($f = fopen($mimetypes, 'r')))
        {
            $s = filesize($mimetypes);
            while(!feof($f))
            {
                $sListe .= fread($f, $s);
            }
            fclose($f);
            $aTypes = explode("\n", $sListe);
            foreach($aTypes as $x)
            {
                if(isset($x[0]) && $x[0] !== '#' && preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c = count($out[1])) > 1)
                {
                    for($i = 1; $i < $c; $i++)
                    {
                        if(is_null($aTypesOk) || in_array($out[1][$i], $aTypesOk))
                        {
                            $tm[$out[1][$i]] = $out[1][0];
                        }
                    }
                }
            }
        }
        $aTypes = explode("\n", $sListe);
        foreach($aTypes as $x)
        {
            if(isset($x[0]) && $x[0] !== '#' && preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c = count($out[1])) > 1)
            {
                for($i = 1; $i < $c; $i++)
                {
                    if(is_null($aTypesOk) || in_array($out[1][$i], $aTypesOk))
                    {
                        $tm[$out[1][$i]] = $out[1][0];
                    }
                }
            }
        }
        ksort($tm);
        return($tm);
    }

}

