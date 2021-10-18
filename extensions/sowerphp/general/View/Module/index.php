<?php $i=1; ?>
<div class="page-header"><h1><?=$title?></h1></div>
<div class="card-deck">
<?php foreach ($nav as $link=>&$info): ?>
    <div class="card mb-4 text-center">
        <div class="card-body">
            <a href="<?=$_base,'/',$module,$link?>" title="<?=$info['desc']?>" class="nav-link p-0">
                <i class="<?=$info['icon']?> fa-3x" aria-hidden="true"></i>
                <p class="card-text small"><?=$info['name']?></p>
            </a>
        </div>
    </div>
<?php if ($i++%4==0) : ?>
</div>
<div class="card-deck">
<?php endif; ?>
<?php endforeach; ?>
</div>
