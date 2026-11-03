<?php
// Login reads the role from the account. The Passenger/Driver tab is only a greeting:
// operator and admin accounts sign in from either tab and still land on the admin side.

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::user()) {
            $this->redirect(Auth::home());
        }
        $tab = $this->input('as') === 'driver' ? 'driver' : 'passenger';
        $this->view('auth/login', ['title' => 'Sign in', 'tab' => $tab], 'auth');
    }

    public function login(): void
    {
        $this->verifyCsrf();
        $username = $this->input('username');
        $password = $_POST['password'] ?? '';
        $tab = $this->input('as') === 'driver' ? 'driver' : 'passenger';

        $user = $username !== '' ? User::findByUsername($username) : null;
        if (!$user || !password_verify($password, $user['password_hash'])) {
            ActivityLog::add($user['user_id'] ?? null, 'login_failed', "Failed sign-in for \"$username\"");
            remember_input(['username']);
            flash('error', 'Wrong username or password.');
            $this->redirect('/login?as=' . $tab);
        }
        if ($user['status'] !== 'active') {
            flash('error', 'This account is deactivated. Ask a 1TAP admin to turn it back on.');
            $this->redirect('/login?as=' . $tab);
        }

        forget_input();
        Auth::login($user);
        ActivityLog::add((int) $user['user_id'], 'login', 'Signed in as ' . $user['role_name']);
        $this->redirect(Auth::home($user['role_name']));
    }

    public function registerForm(): void
    {
        if (Auth::user()) {
            $this->redirect(Auth::home());
        }
        $this->view('auth/register', ['title' => 'Create an account', 'errors' => $_SESSION['errors'] ?? []], 'auth');
        unset($_SESSION['errors']);
    }

    /** Only passengers can sign up. Drivers and admins are added by an admin. */
    public function register(): void
    {
        $this->verifyCsrf();
        $d = [
            'full_name' => $this->input('full_name'),
            'email'     => $this->input('email'),
            'phone'     => preg_replace('/[\s-]/', '', $this->input('phone')),
            'username'  => strtolower($this->input('username')),
            'password'  => $_POST['password'] ?? '',
            'role'      => 'passenger',
        ];
        $errors = self::validate($d, $_POST['password_confirm'] ?? '');
        if ($errors) {
            $_SESSION['errors'] = $errors;
            remember_input(['full_name', 'email', 'phone', 'username']);
            $this->redirect('/register');
        }

        $userId = Model::transaction(function () use ($d) {
            $id = User::create($d);
            Wallet::create($id);
            return $id;
        });
        forget_input();
        ActivityLog::add($userId, 'register', 'New passenger account ' . $d['username']);
        Auth::login(User::find($userId));
        flash('success', 'Welcome to 1TAP! Your ride QR is ready. Load your wallet to start riding.');
        $this->redirect('/passenger');
    }

    public function logout(): void
    {
        if (Auth::user()) {
            ActivityLog::add(Auth::id(), 'logout', 'Signed out');
            Auth::logout();
        }
        $this->redirect('/');
    }

    /** Shared with the admin's Add user form. */
    public static function validate(array $d, ?string $confirm, int $exceptId = 0, bool $passwordRequired = true): array
    {
        $e = [];
        if (mb_strlen($d['full_name']) < 2) $e['full_name'] = 'Enter a full name.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Enter a valid email address.';
        if (!preg_match('/^(\+?63|0)9\d{9}$/', $d['phone'])) $e['phone'] = 'Use a PH mobile number, like 09171234567.';
        if (!preg_match('/^[a-z0-9._]{3,30}$/', $d['username'])) {
            $e['username'] = '3 to 30 characters: letters, numbers, dot or underscore.';
        } elseif (User::usernameTaken($d['username'], $exceptId)) {
            $e['username'] = 'That username is taken. Try another one.';
        }
        if ($passwordRequired || $d['password'] !== '') {
            if (strlen($d['password']) < 8) $e['password'] = 'Use at least 8 characters.';
            elseif ($confirm !== null && $confirm !== $d['password']) $e['password_confirm'] = "The passwords don't match.";
        }
        return $e;
    }
}
