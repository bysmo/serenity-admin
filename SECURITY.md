# 🔐 Serenity Admin — Architecture de Sécurité & d'Immuabilité des Données

> **Version :** Laravel 12 / PHP 8.2  
> **Mise à jour :** Avril 2026

---

## Table des Matières

1. [Vue d'Ensemble](#1-vue-densemble)
2. [Couche 1 — Chiffrement Systématique des Montants](#2-couche-1--chiffrement-systématique-des-montants)
3. [Couche 2 — Checksum par Ligne (Signature HMAC-SHA256)](#3-couche-2--checksum-par-ligne-signature-hmac-sha256)
4. [Couche 3 — Ledger Merkle & Hash Chain (Immuabilité)](#4-couche-3--ledger-merkle--hash-chain-immuabilité)
5. [Couche 4 — Journal d'Audit CRUD (Traçabilité Humaine)](#5-couche-4--journal-daudit-crud-traçabilité-humaine)
6. [Couche 5 — PIN Membre (Sécurité Mobile)](#6-couche-5--pin-membre-sécurité-mobile)
7. [Tables Sous Surveillance](#7-tables-sous-surveillance)
8. [Algorithme de Vérification d'Intégrité (audit:checksums)](#8-algorithme-de-vérification-dintégrité-auditchecksums)
9. [Planification Automatique (Cron)](#9-planification-automatique-cron)
10. [Interfaces Administratives](#10-interfaces-administratives)
11. [Initialisation et Réinitialisation](#11-initialisation-et-réinitialisation)
12. [Clé Maîtresse & Gouvernance](#12-clé-maîtresse--gouvernance)

---

## 1. Vue d'Ensemble

Le système de sécurité de Serenity Admin est structuré en **5 couches défensives indépendantes** et complémentaires, formant une défense en profondeur (_Defense in Depth_).

```
┌──────────────────────────────────────────────────────────────────────┐
│                     DÉFENSE EN PROFONDEUR                            │
│                                                                      │
│  ① Chiffrement AES-256  →  Montants illisibles en BD                │
│  ② Checksum HMAC-SHA256 →  Signature sur chaque ligne               │
│  ③ Hash Chain Merkle    →  Immuabilité de l'historique              │
│  ④ AuditLog CRUD        →  Traçabilité humaine (Qui/Quoi/Quand)    │
│  ⑤ PIN Membre           →  Second facteur mobile (actions critiq.)  │
│                                                                      │
│  ↓ Orchestrateur : audit:checksums (toutes les 10 min)              │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 2. Couche 1 — Chiffrement Systématique des Montants

### Objectif
Rendre **tous les montants financiers illisibles** en base de données, même pour un administrateur système ou un attaquant ayant accès direct au serveur MySQL.

### Implémentation : `App\Casts\EncryptedDecimal`

```php
// Lecture : déchiffrement transparent → float
public function get(...): float {
    return (float) Crypt::decrypt($value); // Laravel AES-256-CBC
}

// Écriture : chiffrement avant persist
public function set(...): mixed {
    return Crypt::encrypt((float) $value);
}
```

**Algorithme utilisé :** AES-256-CBC via `Crypt::encrypt()` de Laravel, dérivé de la clé `APP_KEY`.

### Colonnes chiffrées

| Table                   | Colonnes chiffrées                                           |
|-------------------------|--------------------------------------------------------------|
| `paiements`             | `montant`                                                    |
| `remboursements`        | `montant`                                                    |
| `nano_credits`          | `montant`, `montant_restant_du`                              |
| `nano_credit_echeances` | `montant`, `penalite_cumulee`                                |
| `nano_credit_versements`| `montant`                                                    |
| `nano_credit_garants`   | `solde_preleve`                                              |
| `epargne_souscriptions` | `montant_par_versement`, `solde_courant`                     |
| `epargne_versements`    | `montant`                                                    |
| `epargne_echeances`     | `montant`                                                    |
| `epargne_plans`         | `montant_min`, `montant_max`, `taux_remuneration`            |
| `cotisations`           | `montant`                                                    |
| `membres`               | `garant_solde`                                               |

### Comportement de Sécurité
- Un attaquant ayant un dump SQL voit : `eyJpdiI6IkRYVlV4...` (chaîne AES-256 base64).
- Sans la `APP_KEY`, les données sont **irrécupérables**.
- En cas d'erreur de déchiffrement, le Cast retourne `0.0` (valeur neutre, sans plantage).

---

## 3. Couche 2 — Checksum par Ligne (Signature HMAC-SHA256)

### Objectif
Détecter toute **modification directe SQL** sur les colonnes d'un enregistrement, même si l'attaquant a accès au serveur de base de données.

### Principe

À chaque création ou modification d'un enregistrement via l'application, une **empreinte cryptographique** est calculée sur l'ensemble de ses colonnes métiers et stockée dans la colonne `checksum`.

### Trait `App\Traits\HasChecksum` — méthode `calculateChecksum()`

```php
public function calculateChecksum(): string
{
    // 1. Exclure les colonnes techniques
    $attributes = array_diff_key($this->attributes,
        array_flip(['id', 'checksum', 'created_at', 'updated_at', 'deleted_at'])
    );

    // 2. Normaliser les types pour éviter les divergences int/string entre
    //    la mémoire PHP et les valeurs PDO récupérées depuis la BD
    $normalized = [];
    foreach ($attributes as $key => $value) {
        $normalized[$key] = is_null($value) ? null
            : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);
    }

    // 3. Trier les clés (ordre alphabétique garanti)
    ksort($normalized);

    // 4. Signer avec HMAC-SHA256 + APP_KEY
    return hash_hmac('sha256', json_encode($normalized), config('app.key'));
}
```

**Déclencheur Eloquent :** L'événement `saving` recalcule le checksum **avant tout INSERT ou UPDATE**.

```php
static::saving(function ($model) {
    $model->checksum = $model->calculateChecksum();
});
```

### Vérification

```php
public function verifyChecksum(): bool {
    return hash_equals($this->checksum, $this->calculateChecksum());
    // hash_equals() résiste aux timing attacks (pas de ==)
}
```

---

## 4. Couche 3 — Ledger Merkle & Hash Chain (Immuabilité)

### Objectif
Empêcher la **réécriture silencieuse de l'historique** : même si un attaquant modifie, insère ou supprime des lignes directement en SQL, la rupture de la chaîne de hachage sera détectée lors du prochain scan.

### Architecture : Table `system_merkle_ledgers`

```sql
id              BIGINT AUTO_INCREMENT  -- Numéro séquentiel du maillon
table_name      VARCHAR(100)           -- Table concernée
record_id       BIGINT                 -- ID de l'enregistrement
action          ENUM('created','updated','deleted')
record_checksum VARCHAR(64)            -- Checksum du record à ce moment
previous_hash   VARCHAR(64)           -- Hash du maillon précédent
hash_chain      VARCHAR(64)           -- Hash de ce maillon (chaîné)
created_at      TIMESTAMP
```

> ⚠️ Cette table est **Append-Only** : aucun enregistrement n'est jamais modifié ou supprimé par l'application.

### Algorithme de Chaînage

```
hash_chain[N] = HMAC-SHA256(
    table_name | record_id | action | record_checksum | hash_chain[N-1],
    APP_KEY
)
```

Chaque maillon intègre le hash du maillon précédent. Toute modification rétroactive d'un maillon invalide tous les suivants → **détection certaine**.

### Concurrence & Atomicité

La méthode `appendToMerkleLedger()` s'exécute dans une **transaction SQL avec verrou exclusif** :

```php
DB::transaction(function () use (...) {
    // Verrou FOR UPDATE sur le dernier enregistrement
    // → Empêche deux forks simultanés de la chaîne
    $lastLedger = SystemMerkleLedger::lockForUpdate()->orderBy('id', 'desc')->first();
    $previousHash = $lastLedger?->hash_chain;
    ...
});
```

### Détection des Attaques

| Type d'attaque                    | Signal détecté                                      |
|-----------------------------------|-----------------------------------------------------|
| Modification SQL d'une colonne    | Checksum statique invalide (Couche 2)               |
| Suppression SQL d'une ligne       | "Évaporation" : ID attendu Ledger, absent en BD     |
| Insertion SQL directe             | "Fantôme" : ID présent en BD, absent du Ledger      |
| Manipulation SQL du Ledger        | Rupture de la Hash Chain (hash_chain incohérent)    |
| Modification du Ledger + recalcul | Détecté si la clé `APP_KEY` n'est pas compromise     |

---

## 5. Couche 4 — Journal d'Audit CRUD (Traçabilité Humaine)

### Objectif
Savoir précisément **qui a modifié quoi, quand, depuis quelle adresse IP**, avec le différentiel exact des colonnes (Avant / Après).

### Table `audit_logs`

| Colonne      | Type    | Contenu                                               |
|--------------|---------|-------------------------------------------------------|
| `user_id`    | INT     | ID de l'admin connecté (null = Système/CLI)           |
| `action`     | ENUM    | `created`, `updated`, `deleted`                       |
| `model`      | VARCHAR | Classe PHP complète (`App\Models\NanoCreditPalier`)   |
| `model_id`   | INT     | ID de l'enregistrement impacté                        |
| `old_values` | JSON    | État des colonnes AVANT modification                  |
| `new_values` | JSON    | État des colonnes APRÈS modification                  |
| `ip_address` | VARCHAR | IP de l'acteur                                        |
| `user_agent` | VARCHAR | Navigateur/Client API                                 |
| `description`| TEXT    | Message de synthèse automatique                       |

### Delta Intelligent

Pour un `update`, seules les colonnes **réellement modifiées** sont capturées, en excluant les champs techniques :

```php
$ignoredKeys = ['checksum', 'created_at', 'updated_at', 'deleted_at'];
$oldValues = array_diff_key(
    array_intersect_key($this->getOriginal(), $this->getChanges()),
    array_flip($ignoredKeys)
);
$newValues = array_diff_key($this->getChanges(), array_flip($ignoredKeys));

// Si le seul changement est le checksum (recalcul automatique)
// → Aucune entrée d'audit créée (anti-spam)
if (empty($newValues)) return;
```

### Identification de l'Acteur

```php
$userId = auth()->id()          // Admin web connecté
       ?? (auth('membre')->check()
            ? auth('membre')->id() // Membre API (Sanctum)
            : null);               // null = Système (Cron, CLI Artisan)
```

---

## 6. Couche 5 — PIN Membre (Sécurité Mobile)

### Objectif
Protéger les **opérations financières critiques** (retrait, virement, demande de crédit) avec un second facteur d'authentification sur l'application mobile Serenity.

### Stockage & Vérification

```php
// Le PIN est hashé (bcrypt) — jamais stocké en clair
public function setPin(string $pin): void {
    $this->update([
        'code_pin'            => Hash::make($pin),       // bcrypt
        'code_pin_created_at' => now(),
        'pin_attempts'        => 0,
        'pin_locked_until'    => null,
    ]);
}

public function verifyPin(string $pin): bool {
    if ($this->isPinLocked()) return false;           // Compte verrouillé ?
    $valid = Hash::check($pin, $this->code_pin);      // Vérification bcrypt

    if (!$valid) {
        $attempts = ($this->pin_attempts ?? 0) + 1;
        if ($attempts >= self::PIN_MAX_ATTEMPTS) {    // 5 échecs max
            $this->update([
                'pin_locked_until' => now()->addMinutes(30), // 30 min lockout
                'pin_attempts'     => 0,
            ]);
        } else {
            $this->update(['pin_attempts' => $attempts]);
        }
    } else {
        $this->update(['pin_attempts' => 0, 'pin_locked_until' => null]);
    }
    return $valid;
}
```

### Modes de Protection

| Mode        | Description                                         |
|-------------|-----------------------------------------------------|
| `each_time` | PIN requis à **chaque opération critique**          |
| `session`   | PIN requis une fois, valide pendant **5 minutes**   |

### Anti-Bruteforce

- **5 tentatives** maximum avant verrouillage automatique
- **30 minutes** de blocage après le 5ème échec
- Compteur réinitialisé après succès

---

## 7. Tables Sous Surveillance

### 📊 Tables Financières Transactionnelles

| Table                    | Modèle              | Criticité  |
|--------------------------|---------------------|------------|
| `paiements`              | `Paiement`          | 🔴 Critique |
| `remboursements`         | `Remboursement`     | 🔴 Critique |
| `nano_credits`           | `NanoCredit`        | 🔴 Critique |
| `nano_credit_echeances`  | `NanoCreditEcheance`| 🔴 Critique |
| `nano_credit_versements` | `NanoCreditVersement`| 🔴 Critique|
| `nano_credit_garants`    | `NanoCreditGarant`  | 🔴 Critique |
| `epargne_souscriptions`  | `EpargneSouscription`| 🔴 Critique|
| `epargne_versements`     | `EpargneVersement`  | 🔴 Critique |
| `epargne_echeances`      | `EpargneEcheance`   | 🔴 Critique |

### ⚙️ Tables de Configuration & Paramétrage

| Table                | Modèle            | Impact en cas d'altération               |
|----------------------|-------------------|------------------------------------------|
| `cotisations`        | `Cotisation`      | Modification des règles de cagnotte      |
| `epargne_plans`      | `EpargnePlan`     | Modification des taux de tontine         |
| `payment_methods`    | `PaymentMethod`   | Redirection des fonds de paiement        |
| `app_settings`       | `AppSetting`      | Altération des paramètres globaux        |
| `nano_credit_paliers`| `NanoCreditPalier`| Modification des seuils de crédit        |
| `parrainage_configs` | `ParrainageConfig`| Altération des règles de commission      |

### 👤 Table Membres

| Table     | Modèle   | Champs protégés                              |
|-----------|----------|----------------------------------------------|
| `membres` | `Membre` | Statut, solde garant, restrictions crédit    |

---

## 8. Algorithme de Vérification d'Intégrité (`audit:checksums`)

Commande : `php artisan audit:checksums`  
Fréquence : **toutes les 10 minutes** (planifiée) + manuellement via l'interface.

### 🔁 PASSE 1 — Validation de la Chaîne Merkle

```
POUR CHAQUE maillon N du Ledger (ordre chronologique) :

    payload_calc = table_name | record_id | action | record_checksum | hash_chain[N-1]
    hash_calc    = HMAC-SHA256(payload_calc, APP_KEY)

    SI hash_calc ≠ hash_chain_stocké[N] :
        → ALERTE CRITIQUE : "Chaîne Merkle corrompue à l'ID {N}"
        → Log dans security.log (level: alert)
        → Arrêt immédiat de l'audit (état compromis)

    Mise à jour de la liste "alive" par table :
        action == 'created' ou 'updated' → alive[table][id] = true
        action == 'deleted'              → supprimer de alive[table][id]
```

### 🔍 PASSE 2 — Vérification des Checksums Statiques

```
POUR CHAQUE table surveillée :
    POUR CHAQUE enregistrement en base (chunks de 500) :

        checksum_recalculé = HMAC-SHA256(
            JSON(colonnes_normalisées_triées),
            APP_KEY
        )

        SI checksum_stocké ≠ checksum_recalculé :

            Récupérer le dernier AuditLog pour cet enregistrement

            SI AuditLog trouvé et new_values disponible :
                Comparer colonne par colonne pour isoler les champs altérés
                Identifier l'user_id responsable (si traçable)
                origin = "Bypass Applicatif" ou "Modification par User ID: X"
            SINON :
                origin = "Manipulation SQL pure (aucune trace applicative)"

            → ALERTE : "Intégrité compromise dans {table} (ID: {X})"
            → Log dans security.log
```

### 👻 PASSE 3 — Détection Fantômes & Évaporations

```
expected_ids = IDs "alive" selon le Ledger Merkle (résultat Passe 1)
actual_ids   = IDs actuellement présents dans la table BD

Fantômes = actual_ids MINUS expected_ids
    → Enregistrement présent en BD mais JAMAIS tracé dans le Ledger
    → Signe d'une INSERT SQL directe non autorisée
    → ALERTE : "Fantôme détecté dans {table} (ID: {X})"

Évaporations = expected_ids MINUS actual_ids
    → Enregistrement attendu selon le Ledger mais absent de la BD
    → Signe d'une DELETE SQL directe non autorisée
    → ALERTE : "Évaporation détectée dans {table} (ID: {X})"
```

### 📤 Résultats du Scan

Après les 3 passes, le résultat est :
1. **Sauvegardé en base** dans `audit_checksum_logs` (historique complet)
2. **Mis en cache** (`audit_checksums_status`) pour l'affichage en temps réel du gadget de dashboard
3. **Journalisé** dans `storage/logs/security.log`

---

## 9. Planification Automatique (Cron)

Configurée dans `routes/console.php` via le Scheduler Laravel 12.

| Tâche                              | Fréquence    | Description                              |
|------------------------------------|--------------|------------------------------------------|
| `audit:checksums`                  | ⏱ 10 min    | **Scan d'intégrité complet (3 passes)**  |
| `audit:reconcile`                  | ⏱ 5 min     | Réconciliation des soldes caisses        |
| `audit:merkle --period=1`          | ⏱ 60 min    | Calcul de la racine Merkle               |
| `nano-credits:check-paliers`       | 🕕 06h00    | Upgrade/Downgrade des paliers            |
| `nano-credits:appliquer-penalites` | 🕡 06h30    | Calcul des pénalités de retard           |
| `nano-credits:prelever-garants`    | 🕖 07h00    | Prélèvement automatique des garants      |
| `app:check-overdue-payments`       | 🕘 09h00    | Notifications paiements en retard        |
| `app:check-low-balances`           | 🕘 09h00    | Alertes soldes caisses faibles           |
| `parrainage:activer-commissions`   | ⏱ 60 min    | Activation des commissions validées      |

### Configuration Crontab Serveur

```bash
# Une seule ligne cron suffit, Laravel dispatche toutes les tâches
* * * * * cd /var/www/serenity && php artisan schedule:run >> /dev/null 2>&1
```

---

## 10. Interfaces Administratives

### 🔐 Menu "Sécurité & Intégrité" (Sidebar Admin)

| Page                  | Route                           | Description                              |
|-----------------------|---------------------------------|------------------------------------------|
| Historique des scans  | `GET /logs/security`            | Tous les rapports (valides/KO)           |
| Détail d'un scan      | `GET /logs/security/{id}`       | Rapport forensique complet               |
| Chaîne Merkle         | `GET /audit/integrity/ledger`   | Visualisation paginée de tous les maillons|
| Modifications Traçées | `GET /audit/integrity/changes`  | Journal CRUD avec diff Avant/Après       |
| Lancer un scan        | `POST /logs/security/scan`      | Déclenchement manuel immédiat            |

**Indicateur automatique :** Si le dernier scan a détecté des anomalies, le menu affiche un **badge rouge pulsé** et l'icône du cadenas change de couleur.

### 🛠 Actions de Remédiation

Depuis le détail d'un scan, 3 actions sont disponibles :

| Action   | Effet                                                                    |
|----------|--------------------------------------------------------------------------|
| `restore`| Restaure les valeurs du dernier état applicatif connu (via AuditLog)     |
| `accept` | Accepte l'état actuel comme légitime — recalcule le checksum             |
| `suspend`| Suspend le compte du membre rattaché à l'enregistrement corrompu         |

---

## 11. Initialisation et Réinitialisation

### Première installation

```bash
php artisan migrate           # Créer toutes les tables (incluant system_merkle_ledgers)
php artisan db:seed           # Générer les données initiales avec checksums
php artisan audit:initialize  # Établir la baseline cryptographique (point 0 de la chaîne)
```

### Après un `migrate:fresh --seed`

```bash
php artisan migrate:fresh --seed
php artisan audit:initialize
```

La commande `audit:initialize` :
1. **Tronque** la table `system_merkle_ledgers` (reset de la chaîne)
2. **Parcourt** toutes les tables surveillées (chunk par chunk)
3. **Calcule** le checksum de chaque enregistrement via `calculateChecksum()`
4. **Insère** chaque enregistrement dans le Ledger avec son hash chaîné complet

> ⚠️ Cette commande doit être lancée après toute restauration de backup ou réinitialisation de données.

---

## 12. Clé Maîtresse & Gouvernance

### `APP_KEY` — Clé Unique de Confiance

**Toute la chaîne de sécurité repose sur la `APP_KEY`** définie dans `.env` :

```env
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX==
```

Elle est utilisée pour :
- ① Chiffrement AES-256-CBC de tous les montants
- ② HMAC-SHA256 de chaque checksum de ligne
- ③ HMAC-SHA256 de chaque maillon de la Hash Chain Merkle

### ⚠️ Avertissement Critique

> **Toute modification de la `APP_KEY` invalide instantanément :**
> - Tous les montants chiffrés en base (illisibles / erreur de déchiffrement)
> - Tous les checksums existants (100% des records marqués corrompus lors du scan)
> - Toute la chaîne Merkle (rupture dès le premier maillon vérifié)
>
> **En cas de rotation de clé nécessaire :** re-chiffrer les montants, recalculer les checksums et relancer `php artisan audit:initialize`.

### Bonnes Pratiques de Gouvernance

- [ ] Ne **jamais versionner** le fichier `.env` dans Git (`.gitignore`)
- [ ] Stocker la `APP_KEY` dans un gestionnaire de secrets sécurisé (Vault, AWS Secrets Manager)
- [ ] Restreindre l'accès SSH et MySQL aux seuls administrateurs techniques autorisés
- [ ] Consulter régulièrement `storage/logs/security.log` pour les alertes
- [ ] Vérifier le menu "Sécurité & Intégrité" après chaque déploiement en production
- [ ] Archiver les `audit_checksum_logs` et `system_merkle_ledgers` à long terme (évidence forensique)

---

*Document généré à partir du code source réel de l'application Serenity Admin.*
