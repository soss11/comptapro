<?php
/**
 * Classe Validator - Validation des données
 */

class Validator {

    private $errors = [];
    private $data = [];

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Valider un champ requis
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field] = $message ?? "Le champ {$field} est requis.";
        }
        return $this;
    }

    /**
     * Valider une adresse email
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !Security::validateEmail($this->data[$field])) {
            $this->errors[$field] = $message ?? "L'adresse email n'est pas valide.";
        }
        return $this;
    }

    /**
     * Valider une longueur minimale
     */
    public function min($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "Le champ {$field} doit contenir au moins {$length} caractères.";
        }
        return $this;
    }

    /**
     * Valider une longueur maximale
     */
    public function max($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "Le champ {$field} ne doit pas dépasser {$length} caractères.";
        }
        return $this;
    }

    /**
     * Valider que deux champs correspondent
     */
    public function match($field, $matchField, $message = null) {
        if (isset($this->data[$field]) && isset($this->data[$matchField]) && $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = $message ?? "Les champs ne correspondent pas.";
        }
        return $this;
    }

    /**
     * Valider un numéro de téléphone
     */
    public function phone($field, $message = null) {
        if (isset($this->data[$field]) && !Security::validatePhone($this->data[$field])) {
            $this->errors[$field] = $message ?? "Le numéro de téléphone n'est pas valide.";
        }
        return $this;
    }

    /**
     * Valider un format de date
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "La date n'est pas au bon format.";
            }
        }
        return $this;
    }

    /**
     * Valider qu'une valeur est unique en base
     */
    public function unique($field, $table, $column = null, $excludeId = null, $message = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }

        $column = $column ?? $field;
        $db = Database::getInstance();

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $params = [$this->data[$field]];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $db->fetch($sql, $params);

        if ($result && $result['count'] > 0) {
            $this->errors[$field] = $message ?? "Cette valeur existe déjà.";
        }

        return $this;
    }

    /**
     * Vérifier si la validation a réussi
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Vérifier si la validation a échoué
     */
    public function fails() {
        return !$this->passes();
    }

    /**
     * Récupérer les erreurs
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Récupérer la première erreur
     */
    public function firstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
