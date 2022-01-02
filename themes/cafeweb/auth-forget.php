<?php $v->layout("_theme"); ?>

<article class="auth">
    <div class="auth_content container content">
        <header class="auth_header">
            <h1>Recuperar senha</h1>
            <p>Informe seu e-mail para receber um link de recuperação.</p>
        </header>

        <form class="auth_form" data-reset="true" action="<?= url("/recuperar"); ?>" method="post" enctype="multipart/form-data">
            <!-- Se eu tiver alguma mensagem na sessão a mesma será exibida -->
            <div class="ajax_response"><?= flash(); ?></div>
            <?= csrf_input(); ?>
            <label>
                <div class="unlock-alt">
                    <span class="icon-envelope">Email:</span>
                    <span><a title="Recuperar senha" href="<?= url("/entrar"); ?>">Voltar e entrar!</a></span>
                </div>
                <input type="email" name="email" placeholder="Informe seu e-mail:" required/>
            </label>
            <button class="auth_form_btn transition gradient gradient-green gradient-hover">Recuperar</button>
        </form>
    </div>
</article>