-- À exécuter une seule fois sur MySQL/MariaDB après avoir configuré restaurant_db.
ALTER TABLE paiement
    ADD COLUMN reference_transaction VARCHAR(100) NULL AFTER statut;

CREATE INDEX idx_paiement_reference_transaction
    ON paiement(reference_transaction);
