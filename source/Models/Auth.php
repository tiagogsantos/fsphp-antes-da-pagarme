<?php


namespace Source\Models;


use Source\Core\Model;
use Source\Core\Session;
use Source\Core\View;
use Source\Support\Email;

/**
 * Class Auth
 * @package Source\Models
 */
class Auth extends Model
{

    /**
     * Auth constructor.
     */
    public function __construct()
    {
        parent::__construct("user", ["id"], ["email", "password"]);
    }

    /**
     * @return User|null
     */
    public static function user (): ?User
    {
        $session = new Session();
        if (!$session->has("authUser")) {
            return null;
        }

        return (new User())->findById($session->authUser);
    }

    /**
     * log-out sair
     */
    public static function logout (): void
    {
        $session = new Session();
        $session->unset("authUser");
    }

    /**
     * @param User $user
     * @return bool
     */
    public function register(User $user): bool
    {
        // quer dizer que não consegui registrar o usuário
        if (!$user->save()) {
            $this->message = $user->message;
            return false;
        }

        // se o registro acontecer eu vou disparar o e-mail para confirmação
        $view = new View(__DIR__ . "/../../shared/views/email");
        $message = $view->render("confirm", [
            "first_name" => $user->first_name,
            "confirm_link" => url("/obrigado/". base64_encode($user->email))
        ]);

        (new Email())->bootstrap(
            "Ative sua conta no" .  CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();

        return true;
    }

    /**
     * @param string $email
     * @param string $password
     * @param bool $save
     * @return bool
     */
    public function login (string $email, string $password, bool $save = false): bool
    {
        // Se o e-mail não for valido eu exido a menssagem abaixo
        if (!is_email($email)) {
            $this->message->warning("O e-mail informando não é valido");
            return false;
        }

        // Salvamento do Cookie

        if ($save) {
            setcookie("authEmail", $email, time() + 604800, "/");
        } else {
            setcookie("authEmail", null, time() - 604800, "/");
        }

        // verificando a senha

        if (!is_passwd($password)) {
            $this->message->warning("A senha informada não é válida");
            return false;
        }

        // quer dizer que não existe o e-mail
        $user = (new User())->findByEmail($email);
        if (!$user) {
            $this->message->error("O e-mail informado não está cadastrado");
            return false;
        }

        // quer dizer que a senha não confere
        if (!passwd_verify($password, $user->password)) {
            $this->message->error("A senha informada não confere");
            return false;
        }

        // verificando o reshash e se precisar de atualização o indice save salva
        if (passwd_rehash($user->password)) {
            $user->password = $password;
            $user->save();
        }

        // Login
        (new Session())->set("authUser", $user->id);
        $this->message->success("Login efetuado com sucesso")->flash();
        return true;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function forget(string $email): bool
    {
        // Aqui já sei se o e-mail é valido ou não através do usuário
        $user = (new User())->findByEmail($email);

        if (!$user) {
            $this->message->warning("O e-mail informado não está cadastrado");
            return false;
        }

        $user->forget = md5(uniqid(rand(), true));
        $user->save();

        $view = new View(__DIR__."/../../shared/views/email");
        $message = $view->render("forget", [
            "first_name" => $user->first_name,
            "forget_link" => url("/recuperar/{$user->email}|{$user->forget}")
        ]);

        (new Email())->bootstrap(
            "Recupere sua senha no ".CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();

        return true;
    }

    /**
     * @param string $email
     * @param string $code
     * @param string $password
     * @param string $passwordRe
     * @return bool
     */
    public function reset(string $email, string $code, string $password, string $passwordRe): bool
    {

        $user = (new User())->findByEmail($email);
        // quer dizer que o e-mail não está valido
        if (!$user) {
            $this->message->warning("A conta para recuperar não é valida");
            return false;
        }

        //quer dizer que o código de verificação não é valido
        if ($user->forget != $code) {
            $this->message->error("Desculpe, mais o código de verificação não é valido");
            return false;
        }

        // valido que senha não pode ser menor que 6 e maior que 40 caracteres
        if (!is_passwd($password)) {
            $min = CONF_PASSWD_MIN_LEN;
            $max = CONF_PASSWD_MAX_LEN;
            $this->message->info("Sua conta deve ter entre {$min} e {$max} caracteres");
            return false;
        }

        // se a senha nova que criei for diferente da confirmação eu informo que ambas são diferentes
        if ($password != $passwordRe) {
            $this->message->warning("Você informou duas senhas diferentes");
            return false;
        }

        $user->password = $password;
        $user->forget = null;
        $user->save();
        return true;
    }
}