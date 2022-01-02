<?php $v->layout("_theme"); ?>

<article class="auth">
    <div class="auth_content container content">
        <header class="auth_header">
            <h1>Fazer Login</h1>
            <p>Ainda não tem conta? <a title="Cadastre-se" href="<?= url("/cadastrar"); ?>">Cadastre-se!</a></p>
        </header>

        <form class="auth_form" action="<?= url("/entrar"); ?>" method="post" enctype="multipart/form-data">
            <!-- Se eu tiver alguma mensagem na sessão a mesma será exibida -->
            <div class="ajax_response"><?= flash(); ?></div>
            <?= csrf_input(); ?>
            <label>
                <div><span class="icon-envelope">Email:</span></div>
                <input type="email" name="email" value="<?= ($cookie ?? null); ?>" placeholder="Informe seu e-mail:" required/>
            </label>

            <label>
                <div class="unlock-alt">
                    <span class="icon-unlock-alt">Senha:</span>
                    <span><a title="Recuperar senha" href="<?= url("/recuperar"); ?>">Esqueceu a senha?</a></span>
                </div>
                <input type="password" name="password" placeholder="Informe sua senha:" required/>
            </label>

            <label class="check">
                <input type="checkbox" <?= (!empty($cookie) ? "checked" : ""); ?> name="save"/>
                <span>Lembrar dados?</span>
            </label>

            <button class="auth_form_btn transition gradient gradient-green gradient-hover">Entrar</button>
        </form>
    </div>
</article>