<div class="page-header">
    <h1><?=$title?></h1>
</div>
<?php foreach ($nav as $category) : ?>
    <?php if (!empty($category['name'])) : ?>
        <h2 class="mt-4"><?=$category['name']?></h2>
    <?php endif; ?>
    <div class="row row-cols-sm-2 row-cols-md-4" style="margin-top: -20px;">
        <?php foreach ($category['menu'] as $link => $info): ?>
            <div class="col mt-4">
                <div class="card">
                    <div class="card-body text-center">
                        <a href="<?=$_base,'/',$module,$link?>" title="<?=$info['desc']?>">
                            <i class="<?=$info['icon']?> fa-3x" aria-hidden="true"></i>
                            <p class="card-text small mt-2"><?=$info['name']?></p>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
