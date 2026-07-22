<?php

namespace App\Controllers;

use App\Core\Template;
use App\Models\AdminModel;

class AdminController extends Template
{
    public function login()
    {
        adminEnsureSession();

        if ($this->isAuthenticated()) {
            header('Location: ' . url('admin/painel'));
            exit;
        }

        echo $this->render('admin/login.html', [
            'cpf' => '',
            'erro' => null,
        ]);
    }

    public function authenticate()
    {
        adminEnsureSession();

        if (!validateFormToken('admin_login')) {
            header('Location: ' . url('admin'));
            exit;
        }

        $cpf = $this->normalizeCpf($_POST['cpf'] ?? '');
        $senha = (string) ($_POST['senha'] ?? '');

        if (!$this->isValidCpf($cpf) || trim($senha) === '') {
            echo $this->render('admin/login.html', [
                'cpf' => $this->formatCpf($cpf),
                'erro' => 'Informe um CPF válido e a senha para continuar.',
            ]);
            return;
        }

        $admin = (new AdminModel())->findActiveByCpf($cpf);

        if ($admin === null || !password_verify($senha, (string) $admin->senha_hash)) {
            echo $this->render('admin/login.html', [
                'cpf' => $this->formatCpf($cpf),
                'erro' => 'CPF ou senha inválidos.',
            ]);
            return;
        }

        $_SESSION['admin_auth'] = [
            'id' => (int) $admin->id,
            'nome' => (string) $admin->nome,
            'cpf' => $this->formatCpf((string) $admin->cpf),
            'ultimo_login_em' => date('Y-m-d H:i:s'),
        ];

        header('Location: ' . url('admin/painel'));
        exit;
    }

    public function panel()
    {
        adminRequireAuth('admin');

        $model = new AdminModel();
        $usuarios = $model->readAll();

        echo $this->render('admin/panel.html', array_merge([
            'admin' => $this->adminSession(),
            'usuarios_total' => is_array($usuarios) ? count($usuarios) : 0,
        ], $this->adminPageDefaults([
            [
                'kind' => 'link',
                'label' => 'Registros BSC',
                'icon' => 'bi-table',
                'url' => url('bsc/registros'),
                'class' => 'text-info-emphasis',
            ],
            [
                'kind' => 'link',
                'label' => 'Usuários Admin',
                'icon' => 'bi-people',
                'url' => url('admin/usuarios'),
                'class' => 'text-primary',
            ],
        ], [
            [
                'icon' => 'bi-person-badge',
                'text' => 'Admin: ' . $this->adminSession()->nome,
            ],
        ], 'Painel Administrativo', 'Área reservada para gestão de acessos e rotas protegidas')));
    }

    public function users()
    {
        adminRequireAuth('admin');

        $model = new AdminModel();
        $usuarios = $model->readAll();
        $erroSessao = $_SESSION['admin_users_error'] ?? null;
        unset($_SESSION['admin_users_error']);

        echo $this->render('admin/users/index.html', array_merge([
            'admin' => $this->adminSession(),
            'erro' => is_string($usuarios) ? $usuarios : $erroSessao,
            'usuarios' => is_array($usuarios) ? $usuarios : [],
        ], $this->adminPageDefaults([
            [
                'kind' => 'link',
                'label' => 'Novo Usuário',
                'icon' => 'bi-person-plus',
                'url' => url('admin/usuarios/novo'),
                'class' => 'text-success',
            ],
        ], [], 'Usuários Administrativos', 'Cadastro, manutenção e remoção de acessos administrativos')));
    }

    public function createUser()
    {
        adminRequireAuth('admin');

        echo $this->render('admin/users/create.html', array_merge([
            'erro' => null,
            'dados' => $this->defaultUserFormData(),
        ], $this->adminPageDefaults([
            [
                'kind' => 'link',
                'label' => 'Usuários Admin',
                'icon' => 'bi-people',
                'url' => url('admin/usuarios'),
                'class' => 'text-primary',
            ],
        ], [], 'Novo Usuário Administrativo', 'Cadastro de novo acesso administrativo')));
    }

    public function storeUser()
    {
        adminRequireAuth('admin');

        if (!validateFormToken('admin_user_create')) {
            header('Location: ' . url('admin/usuarios/novo'));
            exit;
        }

        $data = $this->userDataFromRequest(true);
        $error = $this->validateUserData($data);

        if ($error !== null) {
            echo $this->render('admin/users/create.html', array_merge([
                'erro' => $error,
                'dados' => (object) $data,
            ], $this->adminPageDefaults([
                [
                    'kind' => 'link',
                    'label' => 'Usuários Admin',
                    'icon' => 'bi-people',
                    'url' => url('admin/usuarios'),
                    'class' => 'text-primary',
                ],
            ], [], 'Novo Usuário Administrativo', 'Cadastro de novo acesso administrativo')));
            return;
        }

        $result = (new AdminModel())->create([
            'nome' => $data['nome'],
            'cpf' => $data['cpf'],
            'senha_hash' => password_hash($data['senha'], PASSWORD_DEFAULT),
            'ativo' => $data['ativo'],
        ]);

        if ($result !== true) {
            echo $this->render('admin/users/create.html', array_merge([
                'erro' => $result,
                'dados' => (object) $data,
            ], $this->adminPageDefaults([
                [
                    'kind' => 'link',
                    'label' => 'Usuários Admin',
                    'icon' => 'bi-people',
                    'url' => url('admin/usuarios'),
                    'class' => 'text-primary',
                ],
            ], [], 'Novo Usuário Administrativo', 'Cadastro de novo acesso administrativo')));
            return;
        }

        header('Location: ' . url('admin/usuarios'));
        exit;
    }

    public function editUser($id)
    {
        adminRequireAuth('admin');

        $usuario = (new AdminModel())->readById((int) $id);

        if (is_string($usuario)) {
            header('Location: ' . url('admin/usuarios'));
            exit;
        }

        echo $this->render('admin/users/edit.html', array_merge([
            'erro' => null,
            'dados' => (object) [
                'id' => (int) $usuario->id,
                'nome' => (string) $usuario->nome,
                'cpf' => $this->formatCpf((string) $usuario->cpf),
                'ativo' => (int) $usuario->ativo,
            ],
            'admin' => $this->adminSession(),
        ], $this->adminPageDefaults([
            [
                'kind' => 'link',
                'label' => 'Usuários Admin',
                'icon' => 'bi-people',
                'url' => url('admin/usuarios'),
                'class' => 'text-primary',
            ],
        ], [], 'Editar Usuário Administrativo', 'Atualização de dados e senha do acesso administrativo')));
    }

    public function updateUser($id)
    {
        adminRequireAuth('admin');

        $id = (int) $id;

        if (!validateFormToken('admin_user_edit_' . $id)) {
            header('Location: ' . url('admin/usuarios/editar/' . $id));
            exit;
        }

        $existing = (new AdminModel())->readById($id);

        if (is_string($existing)) {
            header('Location: ' . url('admin/usuarios'));
            exit;
        }

        $data = $this->userDataFromRequest(false);
        $error = $this->validateUserData($data, $id);

        if ($error === null && $id === (int) $this->adminSession()->id && (int) $data['ativo'] !== 1) {
            $error = 'Você não pode desativar o usuário que está autenticado nesta sessão.';
        }

        if ($error !== null) {
            echo $this->render('admin/users/edit.html', array_merge([
                'erro' => $error,
                'dados' => (object) array_merge($data, ['id' => $id]),
                'admin' => $this->adminSession(),
            ], $this->adminPageDefaults([
                [
                    'kind' => 'link',
                    'label' => 'Usuários Admin',
                    'icon' => 'bi-people',
                    'url' => url('admin/usuarios'),
                    'class' => 'text-primary',
                ],
            ], [], 'Editar Usuário Administrativo', 'Atualização de dados e senha do acesso administrativo')));
            return;
        }

        $result = (new AdminModel())->updateById($id, [
            'nome' => $data['nome'],
            'cpf' => $data['cpf'],
            'ativo' => $data['ativo'],
            'senha_hash' => $data['senha'] !== '' ? password_hash($data['senha'], PASSWORD_DEFAULT) : '',
        ]);

        if ($result !== true) {
            echo $this->render('admin/users/edit.html', array_merge([
                'erro' => $result,
                'dados' => (object) array_merge($data, ['id' => $id]),
                'admin' => $this->adminSession(),
            ], $this->adminPageDefaults([
                [
                    'kind' => 'link',
                    'label' => 'Usuários Admin',
                    'icon' => 'bi-people',
                    'url' => url('admin/usuarios'),
                    'class' => 'text-primary',
                ],
            ], [], 'Editar Usuário Administrativo', 'Atualização de dados e senha do acesso administrativo')));
            return;
        }

        header('Location: ' . url('admin/usuarios'));
        exit;
    }

    public function deleteUser($id)
    {
        adminRequireAuth('admin');

        $id = (int) $id;

        if (!validateFormToken('admin_user_delete_' . $id)) {
            header('Location: ' . url('admin/usuarios'));
            exit;
        }

        if ($id === (int) $this->adminSession()->id) {
            $_SESSION['admin_users_error'] = 'Você não pode excluir o usuário que está autenticado nesta sessão.';
            header('Location: ' . url('admin/usuarios'));
            exit;
        }

        $result = (new AdminModel())->deleteById($id);

        if ($result !== true) {
            $_SESSION['admin_users_error'] = $result;
        }

        header('Location: ' . url('admin/usuarios'));
        exit;
    }

    public function logout()
    {
        adminEnsureSession();

        if (!validateFormToken('admin_logout')) {
            header('Location: ' . url('admin/painel'));
            exit;
        }

        unset($_SESSION['admin_auth']);

        header('Location: ' . url('/'));
        exit;
    }

    private function isAuthenticated(): bool
    {
        return adminIsAuthenticated();
    }

    private function adminSession(): object
    {
        return (object) ($_SESSION['admin_auth'] ?? []);
    }

    private function adminPageDefaults(
        array $menuItems = [],
        array $metaItems = [],
        string $title = 'Área Administrativa',
        string $subtitle = 'Gestão de acessos administrativos'
    ): array {
        return [
            'name' => $title,
            'description' => $subtitle,
            'header_title' => 'Observatório Socioassistencial - SJC',
            'header_subtitle' => $subtitle,
            'header_menu_items' => array_merge([
                [
                    'kind' => 'link',
                    'label' => 'Home',
                    'icon' => 'bi-house-door',
                    'url' => url(''),
                    'class' => 'text-body',
                ],
            ], $menuItems),
            'header_meta_items' => $metaItems,
        ];
    }

    private function defaultUserFormData(): object
    {
        return (object) [
            'nome' => '',
            'cpf' => '',
            'ativo' => 1,
        ];
    }

    private function userDataFromRequest(bool $requirePassword): array
    {
        return [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'cpf' => $this->normalizeCpf((string) ($_POST['cpf'] ?? '')),
            'cpf_formatado' => $this->formatCpf($this->normalizeCpf((string) ($_POST['cpf'] ?? ''))),
            'senha' => trim((string) ($_POST['senha'] ?? '')),
            'ativo' => (int) (($_POST['ativo'] ?? '1') === '0' ? 0 : 1),
            'senha_obrigatoria' => $requirePassword,
        ];
    }

    private function validateUserData(array &$data, ?int $ignoreId = null): ?string
    {
        $data['cpf'] = $this->normalizeCpf($data['cpf'] ?? '');
        $data['cpf_formatado'] = $this->formatCpf($data['cpf']);

        if ($data['nome'] === '' || mb_strlen($data['nome']) > 150) {
            return 'Informe um nome válido com até 150 caracteres.';
        }

        if (!$this->isValidCpf($data['cpf'])) {
            return 'Informe um CPF válido para o usuário administrativo.';
        }

        if ((new AdminModel())->cpfExists($data['cpf'], $ignoreId)) {
            return 'Já existe um usuário administrativo cadastrado com este CPF.';
        }

        if (($data['senha_obrigatoria'] ?? false) && $data['senha'] === '') {
            return 'Informe a senha inicial do usuário administrativo.';
        }

        if ($data['senha'] !== '' && mb_strlen($data['senha']) < 6) {
            return 'A senha deve ter pelo menos 6 caracteres.';
        }

        return null;
    }

    private function normalizeCpf(string $cpf): string
    {
        return preg_replace('/\D+/', '', $cpf) ?? '';
    }

    private function formatCpf(string $cpf): string
    {
        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return preg_replace(
            '/(\d{3})(\d{3})(\d{3})(\d{2})/',
            '$1.$2.$3-$4',
            $cpf
        ) ?: $cpf;
    }

    private function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;

            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
