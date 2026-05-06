<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\AvatarStorage;

$repository = new UserRepository(Database::connection());
$auth = new AuthService($repository);

if (!$auth->isAuthenticated()) {
    redirect('/login.php');
}

if (!$auth->canManageUsers()) {
    redirect('/dashboard.php');
}

$user = $auth->user();
$feedback = null;
$error = null;
$filterSearch = trim((string) ($_GET['search'] ?? ''));
$filterAccess = (string) ($_GET['access'] ?? '');
$filterPayment = (string) ($_GET['payment'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf'] ?? null)) {
        $error = 'Sessao expirada. Atualize a pagina.';
    } else {
        $action = $_POST['action'] ?? 'create';

        if ($action === 'update_membership') {
            $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
            $role = $_POST['role'] ?? 'member';
            $membershipStatus = $_POST['membership_status'] ?? 'active';
            $paymentStatus = $_POST['payment_status'] ?? 'adimplente';

            if ($targetUserId <= 0) {
                $error = 'Usuario invalido para atualizacao.';
            } elseif (!in_array($role, ['member', 'baba_admin'], true)) {
                $error = 'Papel invalido.';
            } elseif (!in_array($membershipStatus, ['active', 'inactive', 'blocked'], true)) {
                $error = 'Status de acesso invalido.';
            } elseif (!in_array($paymentStatus, ['adimplente', 'inadimplente'], true)) {
                $error = 'Status financeiro invalido.';
            } else {
                $repository->updateMembershipSettings(
                    (int) $user['baba_id'],
                    $targetUserId,
                    $role,
                    $membershipStatus,
                    $paymentStatus
                );
                $repository->createAuditLog(
                    (int) $user['user_id'],
                    (int) $user['baba_id'],
                    'user_membership_updated',
                    $targetUserId,
                    ['role' => $role, 'access' => $membershipStatus, 'payment' => $paymentStatus],
                    clientIp()
                );
                $feedback = 'Configuracoes do usuario atualizadas.';
            }
        } elseif ($action === 'reset_pin') {
            $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
            $newPin = trim($_POST['new_pin'] ?? '');
            if ($targetUserId <= 0 || strlen($newPin) !== 4) {
                $error = 'PIN invalido para reset.';
            } else {
                $repository->resetUserPin($targetUserId, $newPin);
                $repository->createAuditLog(
                    (int) $user['user_id'],
                    (int) $user['baba_id'],
                    'user_pin_reset',
                    $targetUserId,
                    ['pin_reset' => true],
                    clientIp()
                );
                $feedback = 'PIN atualizado com sucesso.';
            }
        } else {
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
            $pin = trim($_POST['pin'] ?? '');
            $role = $_POST['role'] ?? 'member';
            $membershipStatus = $_POST['membership_status'] ?? 'active';
            $paymentStatus = $_POST['payment_status'] ?? 'adimplente';
            $registeredDate = $_POST['registered_date'] ?? date('Y-m-d');
            $registeredTime = $_POST['registered_time'] ?? date('H:i');

            if ($fullName === '' || $phone === '' || strlen($pin) !== 4) {
                $error = 'Preencha nome, telefone e PIN de 4 digitos.';
            } elseif (!in_array($role, ['member', 'baba_admin'], true)) {
                $error = 'Papel invalido.';
            } elseif (!in_array($membershipStatus, ['active', 'inactive', 'blocked'], true)) {
                $error = 'Status de acesso invalido.';
            } elseif (!in_array($paymentStatus, ['adimplente', 'inadimplente'], true)) {
                $error = 'Status financeiro invalido.';
            } else {
                $existingId = $repository->findUserIdByPhone($phone);
                $photoFile = $_FILES['photo'] ?? null;
                $photoOk = is_array($photoFile) && (int) ($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

                if ($existingId === null && !$photoOk) {
                    $error = 'Foto obrigatoria no cadastro (JPG, PNG ou WebP, ate 2 MB).';
                } elseif ($photoOk) {
                    try {
                        AvatarStorage::assertValidUpload($photoFile);
                    } catch (Throwable $e) {
                        $error = $e->getMessage();
                    }
                }

                if ($error === null) {
                    try {
                        $newUserId = $repository->createOrAttachToBaba(
                            (int) $user['baba_id'],
                            $fullName,
                            $phone,
                            $pin,
                            $role,
                            $membershipStatus,
                            $paymentStatus,
                            $registeredDate,
                            $registeredTime
                        );
                        if ($photoOk && is_array($photoFile)) {
                            $relative = AvatarStorage::store($newUserId, $photoFile);
                            $repository->setUserPhoto($newUserId, $relative);
                        }
                        $repository->createAuditLog(
                            (int) $user['user_id'],
                            (int) $user['baba_id'],
                            'user_created_or_attached',
                            null,
                            [
                                'full_name' => $fullName,
                                'phone' => $phone,
                                'role' => $role,
                                'access' => $membershipStatus,
                                'payment' => $paymentStatus,
                                'photo_uploaded' => $photoOk,
                            ],
                            clientIp()
                        );
                        $feedback = 'Usuario cadastrado com sucesso.';
                    } catch (Throwable $exception) {
                        $error = 'Nao foi possivel salvar o usuario. Verifique telefone e dados.';
                    }
                }
            }
        }
    }
}

$users = $repository->listByBaba((int) $user['baba_id'], [
    'search' => $filterSearch,
    'access' => $filterAccess,
    'payment' => $filterPayment,
]);
$totalUsers = $repository->countByBaba((int) $user['baba_id']);
$filteredUsers = count($users);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - Usuarios</title>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page">
<?php require __DIR__ . '/partials/dash-topbar-drawer.php'; ?>
<section class="panel app">
    <h1>Cadastrar usuarios</h1>
    <p class="meta">Adicione pessoas no baba com data e horario de cadastro.</p>
    <form method="post" action="/usuarios.php" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="create">
        <div class="form-grid">
            <div class="field span-2">
                <label for="photo">Foto de perfil <span class="label-req">obrigatoria no novo cadastro</span></label>
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
                <p class="field-hint">JPG, PNG ou WebP. A foto aparece na barra do app e no card do proximo jogo.</p>
            </div>
            <div class="field">
                <label for="full_name">Nome completo</label>
                <input id="full_name" name="full_name" required>
            </div>
            <div class="field">
                <label for="phone">Telefone</label>
                <input id="phone" name="phone" inputmode="numeric" required>
            </div>
            <div class="field">
                <label for="pin">PIN (4 digitos)</label>
                <input id="pin" name="pin" maxlength="4" inputmode="numeric" required>
            </div>
            <div class="field">
                <label for="role">Papel no baba</label>
                <select id="role" name="role">
                    <option value="member">Usuario comum</option>
                    <option value="baba_admin">Administrador do baba</option>
                </select>
            </div>
            <div class="field">
                <label for="membership_status">Status de acesso</label>
                <select id="membership_status" name="membership_status">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                    <option value="blocked">Bloqueado</option>
                </select>
            </div>
            <div class="field">
                <label for="payment_status">Status financeiro</label>
                <select id="payment_status" name="payment_status">
                    <option value="adimplente">Adimplente</option>
                    <option value="inadimplente">Inadimplente</option>
                </select>
            </div>
            <div class="field span-2">
                <label for="registered_date">Data de cadastro</label>
                <input id="registered_date" name="registered_date" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="field span-2">
                <label for="registered_time">Horario de cadastro</label>
                <input id="registered_time" name="registered_time" type="time" value="<?= htmlspecialchars(date('H:i'), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Salvar usuario</button>
    </form>
    <?php if ($feedback !== null): ?><p class="message-ok"><?= htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($error !== null): ?><p class="message-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</section>

<section class="panel app">
    <h2>Usuarios do baba</h2>
    <p class="meta">Total geral: <strong><?= $totalUsers ?></strong> | Exibindo na lista: <strong><?= $filteredUsers ?></strong></p>
    <form method="get" action="/usuarios.php" class="form-grid" style="margin-top:12px;">
        <div class="field span-2">
            <label for="search">Buscar por nome/telefone</label>
            <input id="search" name="search" value="<?= htmlspecialchars($filterSearch, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="access">Filtro acesso</label>
            <select id="access" name="access">
                <option value="">Todos</option>
                <option value="active" <?= $filterAccess === 'active' ? 'selected' : '' ?>>Ativo</option>
                <option value="inactive" <?= $filterAccess === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                <option value="blocked" <?= $filterAccess === 'blocked' ? 'selected' : '' ?>>Bloqueado</option>
            </select>
        </div>
        <div class="field">
            <label for="payment">Filtro financeiro</label>
            <select id="payment" name="payment">
                <option value="">Todos</option>
                <option value="adimplente" <?= $filterPayment === 'adimplente' ? 'selected' : '' ?>>Adimplente</option>
                <option value="inadimplente" <?= $filterPayment === 'inadimplente' ? 'selected' : '' ?>>Inadimplente</option>
            </select>
        </div>
        <div class="field">
            <button class="btn btn-primary" type="submit">Filtrar</button>
        </div>
    </form>
    <table class="table">
        <thead>
        <tr>
            <th>Nome</th>
            <th>Telefone</th>
            <th>Papel</th>
            <th>Acesso</th>
            <th>Financeiro</th>
            <th>Data/Hora</th>
            <th>Acoes</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $member): ?>
            <tr>
                <td><?= htmlspecialchars((string) $member['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $member['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $member['membership_role'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="status-pill <?= $member['membership_status'] === 'active' ? 'pill-active' : ($member['membership_status'] === 'blocked' ? 'pill-blocked' : 'pill-inactive') ?>"><?= htmlspecialchars((string) $member['membership_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="status-pill <?= $member['payment_status'] === 'adimplente' ? 'pill-paid' : 'pill-unpaid' ?>"><?= htmlspecialchars((string) $member['payment_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $member['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <form method="post" action="/usuarios.php" class="table-inline-form">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="update_membership">
                        <input type="hidden" name="target_user_id" value="<?= (int) $member['id'] ?>">
                        <select name="role">
                            <option value="member" <?= $member['membership_role'] === 'member' ? 'selected' : '' ?>>Comum</option>
                            <option value="baba_admin" <?= $member['membership_role'] === 'baba_admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <select name="membership_status">
                            <option value="active" <?= $member['membership_status'] === 'active' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inactive" <?= $member['membership_status'] === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                            <option value="blocked" <?= $member['membership_status'] === 'blocked' ? 'selected' : '' ?>>Bloqueado</option>
                        </select>
                        <select name="payment_status">
                            <option value="adimplente" <?= $member['payment_status'] === 'adimplente' ? 'selected' : '' ?>>Adimplente</option>
                            <option value="inadimplente" <?= $member['payment_status'] === 'inadimplente' ? 'selected' : '' ?>>Inadimplente</option>
                        </select>
                        <button class="btn btn-primary btn-sm" type="submit">Atualizar</button>
                    </form>
                    <form method="post" action="/usuarios.php" class="table-inline-form" style="margin-top:8px;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="reset_pin">
                        <input type="hidden" name="target_user_id" value="<?= (int) $member['id'] ?>">
                        <input name="new_pin" maxlength="4" inputmode="numeric" placeholder="Novo PIN" style="height:38px;width:92px;">
                        <button class="btn btn-primary btn-sm" type="submit">Reset PIN</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<nav class="nav" aria-label="Navegacao principal">
    <a href="/dashboard.php">Home</a>
    <a href="/calendario.php">Calendario</a>
    <a href="/sorteio.php">Sorteio</a>
    <a href="/mercado.php">Mercado</a>
    <a class="active" href="/usuarios.php">Usuarios</a>
</nav>
<?php
$drawerUser = $user;
$drawerCanManageUsers = $auth->canManageUsers();
$drawerActive = 'usuarios';
require __DIR__ . '/partials/app-drawer.php';
?>
</body>
</html>
