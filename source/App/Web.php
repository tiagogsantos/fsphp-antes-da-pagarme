<?php

namespace Source\App;

use Source\Core\Connect;
use Source\Core\Controller;
use Source\Models\Auth;
use Source\Models\Category;
use Source\Models\Faq\Question;
use Source\Models\Post;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Support\Email;
use Source\Support\Pager;

/**
 * Web Controller
 * @package Source\App
 */

class Web extends Controller
{
    /**
     * Web constructor.
     */

    public function __construct()
    {
        parent::__construct(__DIR__."/../../themes/".CONF_VIEW_THEME."/");

        (new Access())->report();

        (new Online())->report();

    }

    /**
     * Site Home - Aqui estou dizendo que meu template será a home
     */
    public function home(): void
    {
        $head = $this->seo->render(
          CONF_SITE_NAME . "-" . CONF_SITE_TITLE,
          CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("home", [
            "head" => $head,
            "video" => "8nvXVLu3Lxc",
            "blog" => (new Post())->find()->order("post_at DESC")->limit(6)->fetch(true)
        ]);
    }

    /**
     * Inserindo a página about/sobre no thema
     */
    public function about(): void
    {
        $head = $this->seo->render(
            "Descubra o " . CONF_SITE_NAME. "-" . CONF_SITE_DESC,
            CONF_SITE_DESC,
            url("/sobre"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("about", [
            "head" => $head,
            "video" => "8nvXVLu3Lxc",
            "faq" => (new Question())
                ->find("channel_id = :id", "id=1")
                ->order("order_by")
                ->fetch(true)
        ]);
    }

    /**
     * Site Blog parametros e chamada pela Head
     * @param array|null $data
     */
    public function blog(?array $data): void
    {
        $head = $this->seo->render(
            "Blog - " .CONF_SITE_NAME,
            "Confira em nosso blog dicas e sacadas de como controlar e melhorar suas contas. Vamos tomar um café?",
            url("/blog"),
            theme("/assets/images/share.jpg")
        );

        // trazendo a listagem do blog
        $blog = (new Post())->find();
        // Aonde a paginação vai ficar
        $pager = new Pager(url("/blog/p/"));
        // Aqui eu recebo o pager para que contenha a paginação são 3 linhas com 3 colunas
        $pager->pager ($blog->count(), 9, ($data['page'] ?? 1));

        echo $this->view->render("blog", [
            "head" => $head,
            "blog" => $blog->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" =>$pager->render()
        ]);
    }

    /**
     * Autor do Blog
     * @param array $data
     */
    public function blogAuthor(array $data): void {

        $authorUri = filter_var($data["author"], FILTER_SANITIZE_STRIPPED);
        $author = (new Post())->findByUri($authorUri);

        if (!$author) {
            redirect("/home");
        }

        $blogAuthor = (new Post())->find("author = :a", "a={$author->id}");
        $page = (!empty($data['page']) && filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1);
        $pager = new Pager(url("/blog/a/{$author->uri}/"));
        $pager->pager($blogAuthor->count(), 9, $page);

        $head = $this->seo->render(
            "Autor do Post {$author->title}",
            $author->description,
            url("/blog/a/{$author->uri}/{pager}"),
            ($author->cover ? image($author->cover, 1200, 628) : theme("assets/images/share.jpg"))
        );

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "Criador {$author->title}",
            "desc" => $author->description,
            "blog" => $blogAuthor->fetch(true),
            "paginator" => $pager->render()
        ]);
    }

    /**
     * Categorias do Blog
     * @param array $data
     */
    public function blogCategory (array $data): void
    {
        $categoryUri = filter_var($data["category"], FILTER_SANITIZE_STRIPPED);
        $category = (new Category())->findByUri($categoryUri);

        if (!$category) {
            redirect("/blog");
        }

        $blogCategory = (new Post())->find("category = :c", "c={$category->id}");
        $page = (!empty($data['page']) && filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1);
        $pager = new Pager(url("/blog/em/{$category->uri}/"));
        $pager->pager($blogCategory->count(), 9, $page);

        $head = $this->seo->render(
            "Artigos em {$category->title} - ".CONF_SITE_NAME,
            $category->description,
            url("/blog/em/{$category->uri}/{pager}"),
            ($category->cover ? image($category->cover, 1200, 628) : theme("assets/images/share.jpg"))
        );

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "Artigos em {$category->title}",
            "desc" => $category->description,
            "blog" => $blogCategory
                ->limit($pager->limit())
                ->offset($pager->offset())
                ->order("post_at DESC")
                ->fetch(true),
            "paginator" => $pager->render()
        ]);
    }

    /**
     * Site Blog Search/Pesquisa
     * @param array $data
     */
    public function BlogSearch(array $data): void
    {
        // Se eu tiver a pesquisa eu vou redirecionar a pesquisa para trazer os resultados
        if (!empty($data['s'])){
           $search = filter_var($data['s'], FILTER_SANITIZE_STRIPPED);
           echo json_encode(["redirect" => url("/blog/buscar/{$search}/1")]);
           return;
        }

        // Se eu não tiver o termo eu volto para a home do blog
        if (empty($data['terms'])) {
            redirect("/blog");
        }

        // Não tenho a pesquisa porém eu tenho o termo na url
        $search = filter_var($data ['terms'], FILTER_SANITIZE_STRIPPED);
        $page = (filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] :1);

        $head = $this->seo->render(
          "Pesquisa por {$search} - " . CONF_SITE_NAME,
          "Confira os resultados de sua pesquisa para {$search}",
            url("/blog/buscar/{$search}/{$page}"),
            theme("assets/images/share.jpg")
        );

        $blogSearch = (new Post())->find("MATCH(title, subtitle) AGAINST(:s)", "s={$search}");

        // quer dizer que não obtive resultado com minha pesquisa
        if (!$blogSearch->count()) {
            echo $this->view->render("blog", [
               "head" => $head,
               "title" => "PESQUISA POR:",
                "search" => $search
            ]);
            return;
        }

        $pager = new Pager(url("/blog/buscar/{$search}/"));
        $pager->pager($blogSearch->count(), 9, $page);

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "PESQUISA POR:",
            "search" => $search,
            "blog" => $blogSearch->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);
    }

    /**
     * Site Blog com Post, chamada dos posts cadastrados do blog
     * @param array $data
     */
    public function blogPost(array $data): void
    {
        $post = (new Post())->findByUri($data['uri']);
        // Se no if não retornar resultado eu vou redirecionar para a home
        if (!$post) {
            redirect("/404");
        }

        $post->views += 1;
        $post->save();


        $head = $this->seo->render(
            "{$post->title} - " .CONF_SITE_NAME,
            $post->subtitle,
            url("/blog/{$post->uri}"),
            image($post->cover, 1200, 628)
        );

        echo $this->view->render("blog-post", [
            "head" => $head,
            "post" => $post,
            "related" => (new Post())
                ->find("category = :c AND id != :i", "C= {$post->category}&i={$post->id}")
                ->order("rand()")
                ->limit(3)
                ->fetch(true)
        ]);
    }

    /**
     * Site com tela de Login
     * @param null|array $data
     */

    public function login(?array $data): void
    {
        if (Auth::user()) {
            redirect("app");
        }

        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (request_limit("weblogin", 5, 60 * 5)) {
                $json['message'] = $this->message->error("Você já efetuou 3 tentativas, esse é o limite. Por favor, aguarde 5 minutos para tentar novamente!")->render();
                echo json_encode($json);
                return;
            }

            if (empty($data['email']) || empty($data['password'])) {
                $json['message'] = $this->message->warning("Informe seu email e senha para entrar")->render();
                echo json_encode($json);
                return;
            }

            $save = (!empty($data['save']) ? true : false);
            $auth = new Auth();
            $login = $auth->login($data['email'], $data['password'], $save);

            if ($login) {
                $this->message->success("Seja bem-vindo (a) de volta " .  Auth::user()->first_name . "!")->flash();
                $json['redirect'] = url("/app");
            } else {
                $json['message'] = $auth->message()->before("Ooops! ")->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            "Entrar - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/entrar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-login", [
            "head" => $head,
            "cookie" => filter_input(INPUT_COOKIE, "authEmail")
        ]);
    }

    /**
     * Site com tela de recuperar a senha do usuário
     * @param null|array $data
     */

    public function forget(?array $data): void
    {
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, por favor preencha o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (empty($data["email"])) {
                $json['message'] = $this->message->info("Informe seu e-mail para continuar")->render();
                echo json_encode($json);
                return;
            }

            if (request_repeat("webforget", $data["email"])) {
                $json['message'] = $this->message->error("Ooops! Você já tentou este e-mail anteriormente")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            if ($auth->forget($data["email"])) {
                $json["message"] = $this->message->success("Acesse seu e-mail para recuperar sua senha")->render();
            } else {
                $json["message"] = $auth->message()->before("Ooops!")->render();
            }

            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            "Recuperar Senha - " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url("/recuperar"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("auth-forget", [
            "head" => $head
        ]);
    }

    /**
     * Site Forget Reset para resetar a senha
     * @param array $data
     */
    public function reset(array $data): void
    {
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, por favor preencha o formulário")->render();
                echo json_encode($json);
                return;
            }

            // Quer dizer que os dados não foram informados
            if (empty($data["password"]) || empty($data["password_re"])) {
                $json["message"] = $this->message->info("Informe e repita a senha para continuar")->render();
                echo json_encode($json);
                return;
            }

            list($email, $code) = explode("|", $data["code"]);
            $auth = new Auth();

            if ($auth->reset($email, $code, $data["password"], $data["password_re"])) {
                $this->message->success("Senha alterada com sucesso. Vamos controlar?")->flash();
                $json["redirect"] = url("/entrar");
            } else {
                $json["message"] = $auth->message()->before("Ooops!")->render();
            }
            echo json_encode($json);
            return;
        }

        $head = $this->seo->render(
            "Crie sua nova senha no" .CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/recuperar"),
            theme("/assets/images/share.jpg")
        );

        echo $this->view->render("auth-reset", [
            "head" => $head,
            "code" => $data["code"]
        ]);
    }

    /**
     * Site com tela de registro de usuário
     * @param null|array $data
     */

    public function register(?array $data): void
    {
        if (!empty($data['csrf'])) {
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, por favor preencha o formulário")->render();
                echo json_encode($json);
                return;
            }

            if (in_array("", $data)) {
                $json['message'] = $this->message->info("Informe seus dados para criar sua conta.")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            $user = new User();
            $user->bootstrap(
              $data['first_name'],
              $data['last_name'],
              $data['email'],
              $data['password']
            );

            // quer dizer que consegui cadastrar corretamente
            if ($auth->register($user)) {
                $json['redirect'] = url("/confirma");
            } else {
                // Se der erro eu vou retornar a menssagem
                $json['message'] = $auth->message()->before("Ooops!")->render();
            }

            echo json_encode($json);

            return;
        }

        $head = $this->seo->render(
            "Criar Conta - " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url("/cadastrar"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("auth-register", [
            "head" => $head
        ]);
    }

    /**
     * Site com tela de confirmação de cadastro
     */

    public function confirm(): void
    {
        $head = $this->seo->render(
            "Confirme Seu Cadastro - " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url("/confirma"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("optin", [
            "head" => $head,
            "data" => (object) [
                "title" => "Falta pouco! Confirme seu cadastro.",
                "desc" => "Enviamos um link de confirmação para seu e-mail. Acesse e siga as instruções para concluir seu cadastro
                e comece a controlar com o CaféControl",
                "image" => theme("/assets/images/optin-confirm.jpg")
            ]
        ]);
    }

    /**
     * Site com tela de sucesso de bem-vindo
     * @param array $data
     */

    public function success(array $data): void
    {

        $email = base64_decode($data["email"]);
        $user = (new User())->findByEmail($email);

        // se variavel $user for diferente de confirmed eu tenho que fazer a validação dele, caso o contrario continuo rederizando
       if ($user && $user->status != "confirmed") {
           $user->status = "confirmed";
           $user->save();
       }

        $head = $this->seo->render(
            "Bem-vindo ao " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url("/obrigado"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("optin", [
            "head" => $head,
            "data" => (object) [
                "title" => "Tudo pronto. Você já pode controlar",
                "desc" => "Bem-vindo(a) ao seu controle de contas, vamos tomar um café?",
                "image" => theme("/assets/images/optin-success.jpg"),
                "link" => url("/entrar"),
                "linkTitle" => "Fazer Login"
            ]
        ]);
    }

    /**
     * Inserindo a página terms/termos no thema
     */
    public function terms(): void
    {
        $head = $this->seo->render(
            CONF_SITE_NAME . "- Termos de uso",
            CONF_SITE_DESC,
            url("/termos"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("terms", [
            "head" => $head
        ]);
    }

    /**
     * Site Nav Error - Aqui estou inserindo a minha página de erro, que também terá o Oops como menssagem para erro
     * @param array $data
     */
    public function error(array $data): void
    {
        $error = new \stdClass();

        switch ($data['errcode']) {
            case "problemas":
                $error->code = "OPS";
                $error->title = "No momento estamos enfrentando problemas";
                $error->message = "Parece que nosso serviço não está disponivel no monento. Já estamos vendo isso mais caso se precise, envia um e-mail.";
                $error->linkTitle = "Enviar E-Mail";
                $error->link = "mailto:".CONF_MAIL_SUPPORT;
                break;

            case "manutencao":
                $error->code = "OPS";
                $error->title = "Desculpe, Estamos em manutenção";
                $error->message = "Voltamos logo! Por hora estamos trabalhando para melhorar nosso conteúdo para você!";
                $error->linkTitle = null;
                $error->link = null;
                break;

            default:
                $error->code = $data ['errcode'];
                $error->title = "Ooops, Conteúdo indisponivel";
                $error->message = "O conteudo que você deseja acessar não está disponivel";
                $error->linkTitle = "Continue Navegando";
                $error->link = url_back();
                break;
        }

        $head = $this->seo->render(
          "{$error->code} | {$error->title}",
          $error->message,
            url("Ops/{$error->code}"),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("error", [
            "head" => $head,
            "error" => $error
        ]);
    }
}