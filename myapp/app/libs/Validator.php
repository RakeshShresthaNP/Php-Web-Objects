<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

final class Validator
{

    private array $errors = [];

    private ?PDO $db = null;

    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    public function __construct(private array &$data, private array $customMessages = [], private string $secretKey = 'bank-level-secret-key-123')
    {
        $this->db = db();
    }

    public static function make(array &$data, array $rules, array $messages = []): self
    {
        $instance = new self($data, $messages);
        $instance->validate($rules);
        return $instance;
    }

    private function validate(array $ruleMap): void
    {
        foreach ($ruleMap as $field => $rules) {
            $rulesArray = explode('|', $rules);
            if (! isset($this->data[$field]))
                $this->data[$field] = null;

            if (in_array('sometimes', $rulesArray) && ($this->data[$field] === null || $this->data[$field] === '')) {
                continue;
            }

            $this->applyRulesToField($field, $this->data[$field], $rulesArray);
        }
    }

    private function applyRulesToField(string $field, mixed &$value, array $rules): void
    {
        foreach ($rules as $rule) {
            [
                $ruleName,
                $param
            ] = str_contains($rule, ':') ? explode(':', $rule) : [
                $rule,
                null
            ];

            if (is_string($value))
                $value = trim(strip_tags($value));

            $isValid = match ($ruleName) {
                'numeric' => ctype_digit(strval($value)),
                'digits' => ctype_digit(strval($value)) && strlen(strval($value)) === (int) $param,
                'int_only' => filter_var($value, FILTER_VALIDATE_INT) !== false,
                'required' => ! empty($value) || $value === "0" || $value === 0,
                'email' => (bool) ($value = strtolower(filter_var($value, FILTER_VALIDATE_EMAIL))),
                'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
                'uuid' => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $value),
                'password' => preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', (string) $value),
                'alpha' => ctype_alpha(str_replace(' ', '', strip_tags((string) $value))),
                'alpha_space' => preg_match('/^[a-zA-Z\s]+$/', strip_tags((string) $value)),
                'alpha_secure' => preg_match('/^[a-zA-Z0-9@._-]+$/', strip_tags((string) $value)),
                'min' => is_numeric($value) ? (float) $value >= (float) $param : strlen((string) $value) >= (int) $param,
                'max' => is_numeric($value) ? (float) $value <= (float) $param : strlen((string) $value) <= (int) $param,
                'matches' => (string) $value === (string) ($this->data[$param] ?? ''),
                'json' => (json_decode((string) $value) !== null),
                'html' => $this->validateAndCleanHTML($value, $param),
                'iban' => $this->validateIBAN((string) $value),
                'card' => $this->validateAndCleanCard($value),
                'amount' => is_numeric($value) && bccomp((string) $value, '0', 2) > 0,
                'min_amt' => bccomp((string) $value, (string) $param, 2) >= 0,
                'max_amt' => bccomp((string) $value, (string) $param, 2) <= 0,
                'currency' => in_array(strtoupper((string) $value), [
                    'USD',
                    'EUR',
                    'GBP',
                    'AED'
                ]) && (bool) ($value = strtoupper($value)),
                'balance' => $this->checkUserBalance($value, $param),
                'velocity' => $this->checkVelocity($param),
                'phone' => (function () use (&$value, $param) {
                    $clean = preg_replace('/[^\d]/', '', (string) $value);
                    if (strlen($clean) < 7 || strlen($clean) > 15 || preg_match('/^(\d)\1{9,}$/', $clean))
                        return false;
                    $country = strtoupper($param ?? 'DEFAULT');
                    $config = HelperGeo::$phonePatterns[$country] ?? HelperGeo::$phonePatterns['DEFAULT'];
                    $isValid = (bool) preg_match($config['regex'], $clean);
                    if ($isValid && ! empty($config['prefix']))
                        $value = '+' . $config['prefix'] . $clean;
                    return $isValid;
                })(),
                'zip' => (function () use ($value, $param) {
                    $val = trim((string) $value);
                    $country = strtoupper($param ?? 'DEFAULT');
                    if (in_array($country, HelperGeo::NO_ZIP_COUNTRIES) && empty($val))
                        return true;
                    $regex = HelperGeo::$postalPatterns[$country] ?? HelperGeo::$postalPatterns['DEFAULT'];
                    return (bool) preg_match($regex, $val);
                })(),
                'unique' => $this->dbLookup($field, $value, $param, true),
                'exists' => $this->dbLookup($field, $value, $param, false),
                'date_format' => (function () use ($value, $param) {
                    $d = DateTime::createFromFormat($param, (string) $value);
                    return $d && $d->format($param) === $value;
                })(),
                default => true
            };

            if (! $isValid) {
                $this->addError($field, $ruleName, $param);
                break;
            }
        }
    }

    private function validateAndCleanHTML(mixed &$value, ?string $customTags): bool
    {
        if (empty($value))
            return true;
        $tags = $customTags ? explode(',', $customTags) : [
            'a',
            'em',
            'strong',
            'cite',
            'code',
            'ul',
            'ol',
            'li',
            'dl',
            'dt',
            'dd',
            'table',
            'tr',
            'td',
            'br',
            'b',
            'i',
            'p'
        ];
        $formattedTags = '<' . implode('><', $tags) . '>';
        $clean = strip_tags((string) $value, $formattedTags);
        $clean = preg_replace('/on\w+="[^"]*"/i', '', $clean);
        $clean = preg_replace('/href="javascript:[^"]*"/i', 'href="#"', $clean);
        $value = $clean;
        return true;
    }

    private function validateIBAN(string $iban): bool
    {
        $iban = str_replace(' ', '', strtoupper($iban));
        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban))
            return false;
        $lookup = "A10B11C12D13E14F15G16H17I18J19K20L21M22N23O24P25Q26R27S28T29U30V31W32X33Y34Z35";
        $checkStr = substr($iban, 4) . substr($iban, 0, 4);
        $numericIban = "";
        foreach (str_split($checkStr) as $char) {
            $pos = strpos($lookup, $char);
            $numericIban .= ($pos !== false) ? substr($lookup, $pos + 1, 2) : $char;
        }
        return (bcmod($numericIban, '97') === '1');
    }

    private function validateAndCleanCard(mixed &$number): bool
    {
        $number = preg_replace('/\D/', '', (string) $number);
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;
        for ($i = 0; $i < $numDigits; $i ++) {
            $digit = (int) $number[$i];
            if ($i % 2 == $parity) {
                $digit *= 2;
                if ($digit > 9)
                    $digit -= 9;
            }
            $sum += $digit;
        }
        return ($sum > 0 && $sum % 10 == 0);
    }

    private function dbLookup(string $field, mixed $value, string $param, bool $isUniqueCheck): bool
    {
        if (! $this->db)
            return true;
        $p = explode(',', $param);
        $table = $p[0];
        $sql = "SELECT 1 FROM `$table` WHERE `$field` = ?";
        $args = [
            $value
        ];
        if (isset($p[1], $p[2]) && $p[1] !== '' && $p[2] !== '') {
            $sql .= " AND `{$p[1]}` != ?";
            $args[] = $p[2];
        }
        if (isset($p[3], $p[4])) {
            $sql .= " AND `{$p[3]}` = ?";
            $args[] = $p[4];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        $found = (bool) $stmt->fetch();
        return $isUniqueCheck ? ! $found : $found;
    }

    private function checkUserBalance(mixed $amount, string $userIdKey): bool
    {
        if (! $this->db)
            return true;
        $userId = $this->data[$userIdKey] ?? null;
        $stmt = $this->db->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt->execute([
            $userId
        ]);
        $current = (string) ($stmt->fetchColumn() ?: '0.00');
        return bccomp($current, (string) $amount, 2) >= 0;
    }

    private function checkVelocity(string $limit): bool
    {
        if (! $this->db)
            return true;
        $userId = $this->data['user_id'] ?? 0;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE wallet_id = (SELECT id FROM wallets WHERE user_id = ?) AND created_at > NOW() - INTERVAL 1 MINUTE");
        $stmt->execute([
            $userId
        ]);
        return $stmt->fetchColumn() < (int) $limit;
    }

    public function validateFile(string $field, ?array $file, string $rules): bool
    {
        if (! $file || ! isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field][] = _t("file_upload_failed");
            return false;
        }

        $ruleArray = explode('|', $rules);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $actualMime = $finfo->file($file['tmp_name']);

        if (! in_array($actualMime, self::ALLOWED_MIMES)) {
            $this->errors[$field][] = _t("file_type_not_allowed");
            return false;
        }

        foreach ($ruleArray as $rule) {
            [
                $type,
                $param
            ] = str_contains($rule, ':') ? explode(':', $rule) : [
                $rule,
                null
            ];
            if ($type === 'max_size') {
                if ($file['size'] > ((int) $param * 1024 * 1024)) {
                    $this->errors[$field][] = str_replace(':param', (string) $param, _t("file_too_large"));
                    return false;
                }
            }
        }
        return true;
    }

    private function addError($field, $rule, $param): void
    {
        $msg = $this->customMessages["$field.$rule"] ?? $this->customMessages[$rule] ?? _t($rule) ?? "Field $field invalid ($rule).";

        $this->errors[$field][] = str_replace(':param', (string) ($param ?? ''), $msg);
    }

    public function fails(): bool
    {
        return ! empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
