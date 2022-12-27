<?php $i=1; ?>
<div class="page-header">
    <h1><?=$title?></h1>
</div>
<div>
    <div class="mb-4 text-center">
        <div class="row row-cols<?php if (count($nav)>=4) : ?>-4<?php endif; ?> justify-content-center" style="margin-top: -20px;">
            <?php foreach ($nav as $link=>&$info): ?>
                <div class="col mt-4">
                    <div class="card">
                        <div class="card-body">
                            <a href="<?=$_base,'/',$module,$link?>" title="<?=$info['desc']?>">
                                <i class="<?=$info['icon']?> fa-3x" aria-hidden="true"></i>
                                <p class="card-text small mt-2"><?=$info['name']?></p>
                            </a>
                            <?php if ($i++%4==0) : ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
