<?php
class Panier
{
    public static function all(): array
    {
        return $_SESSION['panier'] ?? [];
    }

    public static function ajouter(int $idProduit, array $options, int $quantite): void
    {
        sort($options);
        $cle = $idProduit . '-' . implode('.', $options);

        if (isset($_SESSION['panier'][$cle])) {
            $_SESSION['panier'][$cle]['quantite'] = min(20, $_SESSION['panier'][$cle]['quantite'] + $quantite);
        } else {
            $_SESSION['panier'][$cle] = [
                'id_produit' => $idProduit,
                'options'    => $options,
                'quantite'   => $quantite,
            ];
        }
    }

    public static function modifier(string $cle, int $quantite): void
    {
        if (!isset($_SESSION['panier'][$cle])) {
            return;
        }
        if ($quantite < 1) {
            self::supprimer($cle);
            return;
        }
        $_SESSION['panier'][$cle]['quantite'] = min(20, $quantite);
    }

    public static function supprimer(string $cle): void
    {
        unset($_SESSION['panier'][$cle]);
    }

    public static function vider(): void
    {
        unset($_SESSION['panier']);
    }

    // Nombre total d'articles (pour le badge de la barre de navigation)
    public static function compter(): int
    {
        return array_sum(array_column(self::all(), 'quantite'));
    }

    // Lignes détaillées + total, calculés avec les prix de la base
    public static function detail(): array
    {
        $produits = new Produit();
        $lignes   = [];
        $total    = 0.0;

        foreach (self::all() as $cle => $item) {
            $p = $produits->find($item['id_produit']);

            // Produit supprimé ou devenu indisponible : on le retire
            if (!$p || !$p['disponible']) {
                self::supprimer($cle);
                continue;
            }

            $options  = $produits->optionsByIds($item['id_produit'], $item['options']);
            $unitaire = (float) $p['prix'];
            foreach ($options as $o) {
                $unitaire += (float) $o['prix_supplement'];
            }
            $sousTotal = $unitaire * $item['quantite'];

            $lignes[] = [
                'cle'       => $cle,
                'produit'   => $p,
                'options'   => $options,
                'quantite'  => $item['quantite'],
                'unitaire'  => $unitaire,
                'sous_total'=> $sousTotal,
            ];
            $total += $sousTotal;
        }

        return ['lignes' => $lignes, 'total' => $total];
    }
}