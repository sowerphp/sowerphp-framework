<div class="page-header"><h1>Enlaces</h1></div>
<?php
enlaces ($enlaces);
function enlaces ($enlaces)
{
    echo '<ul>';
    foreach ($enlaces as $key => &$data) {
        if(is_numeric($key)) {
            echo '<li><a href="',$data['url'],'">',$data['enlace'],'</a></li>';
        } else {
            echo '<li>';
            echo '<strong>',$key,'</strong>';
            enlaces ($data);
            echo '</li>';
        }
    }
    echo '</ul>';
}
