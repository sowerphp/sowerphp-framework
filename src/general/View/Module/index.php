<?php $i = 1; ?>
<div class="page-header">
    <h1><?=$title?></h1>
</div>
<div class="container">
    <div class="text-center">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md<?php if (count($nav) >= 4) : ?>-4<?php endif; ?> row-cols-lg<?php if (count($nav) >= 4) : ?>-4<?php endif; ?> justify-content-center" style="margin-top: -20px;">
            <?php foreach ($nav as $link=>&$info): ?>
                <div class="col mt-4">
                    <div class="card">
                        <div class="card-body">
                            <a href="<?=$_base,'/',$module,$link?>" title="<?=$info['desc']?>">
                                <i class="<?=$info['icon']?> fa-3x" aria-hidden="true"></i>
                                <p class="card-text small mt-2"><?=$info['name']?></p>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
