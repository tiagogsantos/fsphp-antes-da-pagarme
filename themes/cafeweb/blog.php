<?php $v->layout("_theme"); ?>

<section class="blog_page">
    <header class="blog_page_header">
        <!-- Na validação abaixo ou me trara o que contem no banco como título ou vai deixar como "Blog" -->
        <h1><?= ($title ?? "BLOG"); ?></h1>
        <p><?= ($search ?? $desc ?? "Confira nossas dicas para controlar melhor suas contas"); ?></p>
        <form name="search" action="<?= url("/blog/buscar"); ?>" method="post" enctype="multipart/form-data">
            <label>
                <input type="text" name="s" placeholder="Encontre um artigo:" required/>
                <button class="icon-search icon-notext"></button>
            </label>
        </form>
    </header>

    <!-- No if abaixo eu estou dizendo que não tenho o blog e nem a search  -->
    <?php if (empty($blog) && !empty($search)):?>
        <div class="content content">
            <div class="empty_content">
                <h3 class="empty_content_title">Sua pesquisa não teve resultados</h3>
                <p class="empty_content_desc">Você fez uma pesquisa por <strong> <? $search; ?> </strong>, tente novamente. </p>
                <a class="empty_content_btn gradient gradient-green gradient-hover radius"
                   href="<?= url("/blog"); ?>" title="Blog">...Voltar ao blog</a>
            </div>
        </div>

    <!-- Eu posso ter a pesquisa/search e não tenho o blog -->
    <?php elseif (empty($blog)): ?>
        <div class="content content">
            <div class="empty_content">
                <h3 class="empty_content_title">Ainda estamos trabalhando para trazer o melhor conteudo para você.</h3>
                <p class="empty_content_desc">Nossos editores estão preparando um conteúdo para você</p>
            </div>
        </div>

        <!-- Se passar por ambas validações acima irei exibir o blog -->
    <?php else: ?>
        <div class="blog_content container content">
            <div class="blog_articles">
                <?php foreach ($blog as $post ): ?>
                    <?php $v->insert("blog-list", ["post"=> $post]); ?>
                <?php endforeach; ?>
            </div>
            <?= $paginator; ?>
        </div>
    <?php endif; ?>
</section>