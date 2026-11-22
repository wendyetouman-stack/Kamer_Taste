<?php
class PaiementController extends Controller
{
    public function callback(): void
    {
        $transId = trim((string) ($_GET['transId'] ?? ''));
        if ($transId === '') {
            $this->redirect('commande/historique');
        }

        try {
            $status = Fapshi::paymentStatus($transId);
            $paiement = $this->model('Paiement')->parTransaction($transId);
            $statut = Fapshi::normalizeStatus((string) ($status['status'] ?? ''));

            if ($paiement && $statut !== null) {
                $this->model('Paiement')->mettreAJour((int) $paiement['id_commande'], $statut);
                $this->redirect('commande/confirmation/' . (int) $paiement['id_commande']);
            }
        } catch (Throwable $e) {
            error_log('Fapshi callback : ' . $e->getMessage());
        }

        $this->redirect('commande/historique');
    }

    public function webhook(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $secret = '';
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'x-wh-secret') {
                $secret = (string) $value;
                break;
            }
        }

        if (FAPSHI_WEBHOOK_SECRET === '' || !hash_equals(FAPSHI_WEBHOOK_SECRET, $secret)) {
            http_response_code(401);
            echo 'Unauthorized';
            return;
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo 'Invalid JSON';
            return;
        }

        $transId = (string) ($payload['transId'] ?? $payload['trans_id'] ?? '');
        $status = (string) ($payload['status'] ?? '');
        $statut = Fapshi::normalizeStatus($status);

        if ($transId !== '' && $statut !== null) {
            $paiement = $this->model('Paiement')->parTransaction($transId);
            if ($paiement) {
                $this->model('Paiement')->mettreAJour((int) $paiement['id_commande'], $statut);
            }
        }

        http_response_code(200);
        echo 'OK';
    }
}
