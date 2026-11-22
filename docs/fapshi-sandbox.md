# Paiement Fapshi en sandbox

## Contrainte HTTPS

Le paiement utilise le domaine sandbox `https://sandbox.fapshi.com`, mais c'est surtout pour le **callback** et le **webhook** que l'application doit exposer une URL publique HTTPS.

- `http://127.0.0.1:8000` peut être utilisé pour afficher et tester localement l'application.
- `http://localhost:8000` reste réservé au navigateur de la machine locale.
- Fapshi ne peut pas appeler directement `localhost` ou `127.0.0.1` pour envoyer une notification serveur.
- Une URL publique commençant obligatoirement par `https://` est requise pour le webhook :

```text
https://VOTRE-DOMAINE-TEMPORAIRE/paiement/webhook
```

- L'URL de retour doit également être HTTPS lorsqu'elle est utilisée depuis Internet :

```text
https://VOTRE-DOMAINE-TEMPORAIRE/paiement/callback
```

L'application lit l'adresse publique depuis `APP_BASE_URL` pour construire ces URL. Ne configure jamais une adresse locale dans `APP_BASE_URL` si Fapshi doit rappeler votre serveur.

## Tunnel HTTPS local

Pour des tests en sandbox depuis une machine locale, démarrez d'abord le serveur PHP :

```bash
cd /mnt/data/Kamer_Taste
php -S 127.0.0.1:8000 -t public public/index.php
```

Puis créez un tunnel HTTPS temporaire avec Cloudflare Tunnel :

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

L'outil affichera une URL du type :

```text
https://exemple-tunnel.trycloudflare.com
```

Dans le terminal qui lance PHP, définissez cette URL avant le démarrage du serveur :

```bash
export APP_BASE_URL="https://exemple-tunnel.trycloudflare.com"
export FAPSHI_API_BASE="https://sandbox.fapshi.com"
export FAPSHI_API_USER="VOTRE_API_USER_SANDBOX"
export FAPSHI_API_KEY="VOTRE_API_KEY_SANDBOX"
export FAPSHI_WEBHOOK_SECRET="VOTRE_SECRET_WEBHOOK_SANDBOX"

php -S 127.0.0.1:8000 -t public public/index.php
```

Configurez ensuite dans Fapshi :

```text
Webhook : https://exemple-tunnel.trycloudflare.com/paiement/webhook
Callback : https://exemple-tunnel.trycloudflare.com/paiement/callback
```

La redirection du navigateur utilisera `redirectUrl`, tandis que l'API Fapshi pourra utiliser le webhook pour confirmer ou annuler le paiement côté serveur.

## Sécurité

- Gardez les identifiants Fapshi uniquement dans les variables d'environnement.
- Ne mettez jamais `FAPSHI_API_USER`, `FAPSHI_API_KEY` ou `FAPSHI_WEBHOOK_SECRET` dans JavaScript, SQL, les vues ou Git.
- Vérifiez toujours le statut du paiement auprès de Fapshi après le retour.
- Vérifiez aussi le secret du webhook avec `X-Wh-Secret` côté serveur.
- Utilisez uniquement `https://sandbox.fapshi.com` pendant les tests.
- Ne passez à une URL de production Fapshi qu'après avoir obtenu les identifiants de production et activé HTTPS sur un domaine réel.

## Variables requises

| Variable | Exemple | Utilisation |
|---|---|---|
| `APP_BASE_URL` | `https://exemple-tunnel.trycloudflare.com` | Adresse publique HTTPS utilisée pour les liens |
| `FAPSHI_API_BASE` | `https://sandbox.fapshi.com` | Endpoint API sandbox |
| `FAPSHI_API_USER` | Identifiant sandbox | Authentification API |
| `FAPSHI_API_KEY` | Clé sandbox | Authentification API |
| `FAPSHI_WEBHOOK_SECRET` | Secret webhook sandbox | Validation du webhook |

## Procédure de test

1. Lancer PHP sur `127.0.0.1:8000`.
2. Créer un tunnel HTTPS vers ce port.
3. Exporter les variables de sandbox.
4. Configurer l'adresse HTTPS du webhook dans Fapshi.
5. Créer une commande test.
6. Choisir Mobile Money.
7. Utiliser uniquement les moyens de test fournis par le sandbox Fapshi.
8. Vérifier le retour sur `/paiement/callback`.
9. Vérifier que le webhook met à jour la commande dans `/paiement/webhook`.
