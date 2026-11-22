<?php
class Paiement extends Model
{
    public function enregistrerTransaction(int $idCommande, string $transId): void
    {
        $st = $this->db->prepare(
            'UPDATE paiement SET reference_transaction = ? WHERE id_commande = ?'
        );
        $st->execute([$transId, $idCommande]);
    }

    public function parTransaction(string $transId): ?array
    {
        $st = $this->db->prepare(
            'SELECT p.*, c.id_client
             FROM paiement p
             JOIN commande c ON c.id_commande = p.id_commande
             WHERE p.reference_transaction = ?'
        );
        $st->execute([$transId]);
        return $st->fetch() ?: null;
    }

    public function mettreAJour(int $idCommande, string $statut): void
    {
        $st = $this->db->prepare('UPDATE paiement SET statut = ? WHERE id_commande = ?');
        $st->execute([$statut, $idCommande]);
    }
}
