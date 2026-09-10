<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\ReferenceTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ReferenceController extends AbstractActionController
{
    public function __construct(
        private ReferenceTable $referenceTable
    ) {
    }

    public function indexAction()
    {
        return new ViewModel([
            'pays'      => $this->referenceTable->getPays(),
            'villes'    => $this->referenceTable->getVilles(),
            'sites'     => $this->referenceTable->getSites(),
            'centrales' => $this->referenceTable->getCentrales(),
            'familles'  => $this->referenceTable->getFamilles(),
        ]);
    }

    /**
     * Ajout complet en une fois (Pays -> Ville -> Site -> Centrale)
     */
    public function ajouterAction()
    {
        $request = $this->getRequest();
        $erreurs = [];

        $nomPays = '';
        $nomVille = '';
        $nomSite = '';
        $adresse = '';
        $latitude = '';
        $longitude = '';
        $typeCentrale = '';

        if ($request->isPost()) {

            $nomPays = trim((string) $request->getPost('nom_pays', ''));
            $nomVille = trim((string) $request->getPost('nom_ville', ''));
            $nomSite = trim((string) $request->getPost('nom_site', ''));
            $adresse = trim((string) $request->getPost('adresse', ''));
            $latitude = trim((string) $request->getPost('latitude', ''));
            $longitude = trim((string) $request->getPost('longitude', ''));
            $typeCentrale = trim((string) $request->getPost('type_centrale', ''));

            if ($nomPays === '') $erreurs[] = 'Le nom du pays est obligatoire.';
            if ($nomVille === '') $erreurs[] = 'Le nom de la ville est obligatoire.';
            if ($nomSite === '') $erreurs[] = 'Le nom du site est obligatoire.';
            if ($typeCentrale === '') $erreurs[] = 'Le type de centrale est obligatoire.';
            if ($latitude !== '' && !is_numeric($latitude)) $erreurs[] = 'La latitude doit être un nombre.';
            if ($longitude !== '' && !is_numeric($longitude)) $erreurs[] = 'La longitude doit être un nombre.';

            if (empty($erreurs)) {
                try {
                    $this->referenceTable->creerInfrastructure(
                        $nomPays,
                        $nomVille,
                        $nomSite,
                        $adresse !== '' ? $adresse : null,
                        $latitude !== '' ? (float) $latitude : null,
                        $longitude !== '' ? (float) $longitude : null,
                        $typeCentrale
                    );

                    return $this->redirect()->toRoute('parametre');

                } catch (\Throwable $e) {
                    error_log('[ReferenceController::ajouterAction] ' . $e->getMessage());
                    $erreurs[] = 'Une erreur est survenue lors de la création.';
                }
            }
        }

        return new ViewModel([
            'erreurs'      => $erreurs,
            'nomPays'      => $nomPays,
            'nomVille'     => $nomVille,
            'nomSite'      => $nomSite,
            'adresse'      => $adresse,
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'typeCentrale' => $typeCentrale,
        ]);
    }

    /**
     * Ajout individuel — Pays
     */
    public function paysAjouterAction()
    {
        /** @var \Laminas\Http\PhpEnvironment\Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $nom = trim((string) $request->getPost('nom_pays', ''));
            if ($nom !== '') {
                try {
                    $this->referenceTable->ajouterPays($nom);
                } catch (\Throwable $e) {
                    error_log('[ReferenceController::paysAjouterAction] ' . $e->getMessage());
                }
            }
        }

        return $this->redirect()->toRoute('parametre');
    }

    /**
     * Ajout individuel — Ville
     */
    public function villeAjouterAction()
    {
        /** @var \Laminas\Http\PhpEnvironment\Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $nom = trim((string) $request->getPost('nom_ville', ''));
            $idPays = (int) $request->getPost('id_pays', 0);
            if ($nom !== '' && $idPays > 0) {
                try {
                    $this->referenceTable->ajouterVille($nom, $idPays);
                } catch (\Throwable $e) {
                    error_log('[ReferenceController::villeAjouterAction] ' . $e->getMessage());
                }
            }
        }

        return $this->redirect()->toRoute('parametre');
    }

    /**
     * Ajout individuel — Site
     */
    public function siteAjouterAction()
    {
        /** @var \Laminas\Http\PhpEnvironment\Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $nom = trim((string) $request->getPost('nom_site', ''));
            $adresse = trim((string) $request->getPost('adresse', ''));
            $latitude = trim((string) $request->getPost('latitude', ''));
            $longitude = trim((string) $request->getPost('longitude', ''));
            $idVille = (int) $request->getPost('id_ville', 0);

            if ($nom !== '' && $idVille > 0) {
                try {
                    $this->referenceTable->ajouterSite(
                        $nom,
                        $adresse !== '' ? $adresse : null,
                        $latitude !== '' && is_numeric($latitude) ? (float) $latitude : null,
                        $longitude !== '' && is_numeric($longitude) ? (float) $longitude : null,
                        $idVille
                    );
                } catch (\Throwable $e) {
                    error_log('[ReferenceController::siteAjouterAction] ' . $e->getMessage());
                }
            }
        }

        return $this->redirect()->toRoute('parametre');
    }

    /**
     * Ajout individuel — Centrale
     */
    public function centraleAjouterAction()
    {
        /** @var \Laminas\Http\PhpEnvironment\Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $type = trim((string) $request->getPost('type_centrale', ''));
            $idSite = (int) $request->getPost('id_site', 0);

            if ($type !== '' && $idSite > 0) {
                try {
                    $this->referenceTable->ajouterCentrale($type, $idSite);
                } catch (\Throwable $e) {
                    error_log('[ReferenceController::centraleAjouterAction] ' . $e->getMessage());
                }
            }
        }

        return $this->redirect()->toRoute('parametre');
    }

    /**
     * Ajout individuel — Famille
     */
    public function familleAjouterAction()
    {
        /** @var \Laminas\Http\PhpEnvironment\Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $nom = trim((string) $request->getPost('nom_famille', ''));
            if ($nom !== '') {
                try {
                    $this->referenceTable->ajouterFamille($nom);
                } catch (\Throwable $e) {
                    error_log('[ReferenceController::familleAjouterAction] ' . $e->getMessage());
                }
            }
        }

        return $this->redirect()->toRoute('parametre');
    }

    /**
     * Historique d'une centrale
     */
    public function centraleHistoriqueAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        $centrale = $this->referenceTable->getCentraleById($id);

        if (!$centrale) {
            return $this->notFoundAction();
        }

        $items = $this->referenceTable->getEquipementsAvecHistoriqueParCentrale($id);

        return new ViewModel([
            'centrale' => $centrale,
            'items'    => $items,
        ]);
    }
}