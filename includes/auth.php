<?php
/* auth.php – registration / login helpers
 * -------------------------------------- */
require_once 'config.php';

/* ---------- core filters ---------- */
function clean(string $v): string    { return trim($v); }          // DB input
function html_safe(string $v): string{ return htmlspecialchars($v, ENT_QUOTES); }

function valid_email(string $e): bool
{
    return filter_var($e, FILTER_VALIDATE_EMAIL);
}

function strong_pwd(string $p)
{
    /* ≥8 chars, ≥1 digit, ≥1 letter, ≥1 non-alnum (NO whitespace) */
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $p);
}

function username_from_email(string $e): string
{
    return strtok($e, '@');
}

/* ---------- duplicate checks ---------- */
function email_available(PDO $db, string $email): bool
{
    $sql  = 'SELECT 1 FROM Users WHERE Email = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetchColumn() === false;
}

function username_unique(PDO $db, string $username): bool
{
    /* Rare edge-case: two different domains share the same local part   *
     * We only warn – the UNIQUE(email) constraint still protects rows.  */
    $sql  = 'SELECT COUNT(*) FROM Users
             WHERE SUBSTRING_INDEX(Email,"@",1) = ?';
    $stmt = $db->prepare($sql);
    $stmt->execute([$username]);
    return $stmt->fetchColumn() < 2;
}

/* ---------- registration ---------- */
function registerUser(string $fn, string $ln, string $email, string $pwd): array
{
    $fn    = clean($fn);
    $ln    = clean($ln);
    $email = strtolower(clean($email));
    $pwd   = clean($pwd);

    if (!valid_email($email))
        return ['success'=>false,'error'=>'Invalid e-mail format'];

    if (!strong_pwd($pwd))
        return ['success'=>false,'error'=>
            'Weak password - min. 8 chars incl. digit, letter & symbol'];

    $db = get_pdo_connection();

    /* ---- duplicate e-mail / username tests ---- */
    if (!email_available($db, $email))
        return ['success'=>false,'error'=>'E-mail already in use'];

    $uname = username_from_email($email);
    if (!username_unique($db, $uname))
        return ['success'=>false,'error'=>'Username clash - choose another e-mail'];

    /* ---- insert ---- */
    $hash = password_hash($pwd, PASSWORD_DEFAULT);   // Argon2id if PHP ≥ 8.2

    try {
        $stmt = $db->prepare('CALL InsertUser(?,?,?,?)');
        $stmt->execute([$email, $hash, $fn, $ln]);
        $stmt->closeCursor();
        return ['success'=>true];
    } catch (PDOException $e) {
        /* fall-back guard against race-condition duplicates */
        if ($e->getCode() === '23000')
            return ['success'=>false,'error'=>'E-mail already in use'];
        return ['success'=>false,'error'=>'Registration failed'];
    }
}

/* ---------- login ---------- */
function loginUser(string $email, string $pwd): array
{
    $Admin = ["crodriguez","ngallego", "olorentzena", "tbywater", "ntoothman"];
    
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $email = strtolower(clean($email));
    $pwd   = clean($pwd);

    if (!valid_email($email))
        return ['success'=>false,'error'=>'Invalid e-mail'];

    $db  = get_pdo_connection();
    $sql = 'SELECT UID, Email, Password, IsBanned FROM Users WHERE Email = ?';
    $st  = $db->prepare($sql);
    $st->execute([$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['IsBanned']) {
        return ['success' => false, 'error' => 'This account has been banned.'];
    }

    if (!$row || !password_verify($pwd, $row['Password']))
        return ['success'=>false,'error'=>'Incorrect credentials'];

    if (password_needs_rehash($row['Password'], PASSWORD_DEFAULT)) {
        $new = password_hash($pwd, PASSWORD_DEFAULT);
        $upd = $db->prepare('UPDATE Users SET Password = ? WHERE UID = ?');
        $upd->execute([$new, $row['UID']]);
    }

    /* session */
    session_regenerate_id(true);
    // $_SESSION['logged_in'] = true;
    $_SESSION['uid']       = $row['UID'];
    $_SESSION['username']  = username_from_email($row['Email']);
    if (in_array($_SESSION['username'], $Admin))
        $_SESSION['admin'] = true;

    return ['success'=>true];
}