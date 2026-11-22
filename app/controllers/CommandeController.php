<?php
class CommandeController extends Controller
{
    // /commande : récapitulatif + formulaire
    public function index(): void
    {
        $this->exigerConnexion('commande');
        $this->exigerPanier();

        $this->afficherFormulaire([], [
            'mode'     => 'livraison',
            'adresse'  => '',
            'quartier' => '',
            'rue'      => '',
            'repere'   => '',
            'paiement' => 'mobile_money',
            'code'     => '',
        ]);
    }

    // /commande/valider : enregistre la commande (POST)
    public function valider(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('commande');
        }
        $this->exigerConnexion('commande');
        $detail   = $this->exigerPanier();
        $idClient = (int) clientConnecte()['id_client'];

        $old = [
            'mode'     => $_POST['mode_recuperation'] ?? 'livraison',
            'adresse'  => $_POST['adresse'] ?? '',
            'quartier' => trim($_POST['quartier'] ?? ''),
            'rue'      => trim($_POST['rue'] ?? ''),
            'repere'   => trim($_POST['repere'] ?? ''),
            'paiement' => $_POST['mode_paiement'] ?? 'mobile_money',
            'code'     => strtoupper(trim($_POST['code_promo'] ?? '')),
        ];
        $erreurs = [];

        if (!csrfValide()) {
            $erreurs[] = 'Session expirée, veuillez réessayer.';
        }
        if (!in_array($old['mode'], ['livraison', 'a_emporter'], true)) {
            $erreurs[] = 'Mode de récupération invalide.';
        }
        if (!in_array($old['paiement'], ['mobile_money', 'especes'], true)) {
            $erreurs[] = 'Mode de paiement invalide.';
        }

        // Stock : on additionne les quantités d'un même produit (options différentes)
        $parProduit = [];
        foreach ($detail['lignes'] as $l) {
            $id = (int) $l['produit']['id_produit'];
            if (!isset($parProduit[$id])) {
                $parProduit[$id] = ['qte' => 0, 'p' => $l['produit']];
            }
            $parProduit[$id]['qte'] += $l['quantite'];
        }
        foreach ($parProduit as $x) {
            if ($x['p']['stock'] !== null && $x['qte'] > (int) $x['p']['stock']) {
                $erreurs[] = 'Stock insuffisant pour « ' . $x['p']['nom_produit']
                           . ' » (il en reste ' . (int) $x['p']['stock'] . ').';
            }
        }

        // Adresse (seulement pour une livraison)
        $idAdresse       = null;
        $nouvelleAdresse = false;
        if ($old['mode'] === 'livraison') {
            if (ctype_digit((string) $old['adresse'])) {
                $adresse = $this->model('Adresse')->find((int) $old['adresse'], $idClient);
                if ($adresse) {
                    $idAdresse = (int) $adresse['id_adresse'];
                } else {
                    $erreurs[] = 'Adresse introuvable.';
                }
            } else {
                if ($old['quartier'] === '') {
                    $erreurs[] = 'Indiquez le quartier de livraison.';
                } else {
                    $nouvelleAdresse = true;
                }
            }
        }

        // Code promo (facultatif)
        $promo = null;
        if ($old['code'] !== '') {
            $promo = $this->model('Promotion')->findValide($old['code']);
            if (!$promo) {
                $erreurs[] = 'Code promo invalide ou expiré.';
            }
        }

        if ($erreurs) {
            $this->afficherFormulaire($erreurs, $old);
            return;
        }

        // Calcul du total avec les prix de la base
        $sousTotal = $detail['total'];
        $remise    = $promo ? round($sousTotal * (float) $promo['pourcentage'] / 100) : 0;
        $frais     = $old['mode'] === 'livraison' ? FRAIS_LIVRAISON : 0;
        $total     = $sousTotal - $remise + $frais;

        $lignes = [];
        foreach ($detail['lignes'] as $l) {
            $lignes[] = [
                'id_produit'       => (int) $l['produit']['id_produit'],
                'options_choisies' => $l['options'] ? implode(', ', array_column($l['options'], 'libelle')) : null,
                'quantite'         => $l['quantite'],
                'prix_unitaire'    => $l['unitaire'],
            ];
        }

        try {
            if ($nouvelleAdresse) {
                $idAdresse = $this->model('Adresse')->creer($idClient, $old['quartier'], $old['rue'], $old['repere']);
            }

            $idCommande = $this->model('Commande')->creer([
                'mode'       => $old['mode'],
                'total'      => $total,
                'id_client'  => $idClient,
                'id_promo'   => $promo['id_promo'] ?? null,
                'paiement'   => $old['paiement'],
                'frais'      => $frais,
                'id_adresse' => $idAdresse,
            ], $lignes);
        } catch (Throwable $e) {
            error_log('Commande : ' . $e->getMessage());
            $this->afficherFormulaire(['Une erreur est survenue, veuillez réessayer.'], $old);
            return;
        }

        if ($old['paiement'] === 'mobile_money') {
            try {
                if (!Fapshi::configOk()) {
                    throw new RuntimeException('Les identifiants Fapshi ne sont pas configurés.');
                }

                $client = clientConnecte();
                $reponse = Fapshi::initiate([
                    'amount'      => (int) round($total),
                    'email'       => $client['email'] ?? null,
                    'redirectUrl' => url('paiement/callback'),
                    'userId'      => (string) $idClient,
                    'externalId'  => (string) $idCommande,
                    'message'     => 'Commande Kamer Taste n° ' . $idCommande,
                ]);

                $transId = trim((string) ($reponse['transId'] ?? ''));
                $link = trim((string) ($reponse['link'] ?? ''));
                if ($transId === '' || $link === '') {
                    throw new RuntimeException('Fapshi n’a pas retourné de lien de paiement.');
                }

                $this->model('Paiement')->enregistrerTransaction($idCommande, $transId);
                Panier::vider();
                header('Location: ' . $link);
                exit;
            } catch (Throwable $e) {
                error_log('Fapshi initiation : ' . $e->getMessage());
                $this->model('Paiement')->mettreAJour($idCommande, 'echoue');
                $this->afficherFormulaire([
                    'Le paiement Mobile Money n’a pas pu être initié. Vérifiez la configuration Fapshi ou choisissez le paiement en espèces.',
                ], $old);
                return;
            }
        }

        Panier::vider();
        $this->redirect('commande/confirmation/' . $idCommande);
    }

    // /commande/confirmation/12 (juste après la commande)
    public function confirmation(string $id = '0'): void
    {
        $this->afficherCommande((int) $id, false);
    }

    // /commande/voir/12 (depuis l'historique)
    public function voir(string $id = '0'): void
    {
        $this->afficherCommande((int) $id, true);
    }

    // /commande/historique
    public function historique(): void
    {
        $this->exigerConnexion('commande/historique');

        $this->view('commande/historique', [
            'titre'     => 'Mes commandes',
            'commandes' => $this->model('Commande')->byClient((int) clientConnecte()['id_client']),
        ]);
    }

    // ----- Méthodes privées (non accessibles par l'URL) -----

    private function exigerConnexion(string $apres = 'commande'): void
    {
        if (!estConnecte()) {
            $_SESSION['apres_connexion'] = $apres;
            $_SESSION['flash'] = 'Connectez-vous ou créez un compte pour continuer.';
            $this->redirect('client/connexion');
        }
    }

    private function exigerPanier(): array
    {
        $detail = Panier::detail();
        if (!$detail['lignes']) {
            $_SESSION['flash'] = 'Votre panier est vide.';
            $this->redirect('panier');
        }
        return $detail;
    }

    private function afficherFormulaire(array $erreurs, array $old): void
    {
        $detail = Panier::detail();

        $this->view('commande/index', [
            'titre'    => 'Passer la commande',
            'lignes'   => $detail['lignes'],
            'total'    => $detail['total'],
            'adresses' => $this->model('Adresse')->byClient((int) clientConnecte()['id_client']),
            'erreurs'  => $erreurs,
            'old'      => $old,
        ]);
    }

    private function afficherCommande(int $id, bool $historique): void
    {
        $this->exigerConnexion($historique ? 'commande/voir/' . $id : 'commande/historique');

        $idClient = (int) clientConnecte()['id_client'];
        $modele   = $this->model('Commande');
        $commande = $modele->find($id, $idClient);   // seulement ses propres commandes

        if (!$commande) {
            $this->notFound();
            return;
        }

        $this->view('commande/confirmation', [
            'titre'      => $historique ? 'Commande n°' . $id : 'Commande confirmée',
            'historique' => $historique,
            'commande'   => $commande,
            'lignes'     => $modele->lignes($id),
            'paiement'   => $modele->paiement($id),
            'livraison'  => $modele->livraison($id),
        ]);
    }
}
