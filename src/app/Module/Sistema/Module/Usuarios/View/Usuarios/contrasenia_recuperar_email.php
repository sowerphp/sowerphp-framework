<?=$nombre?>,

Usted, o alguien más en su nombre, desde la IP <?=$ip?>, ha solicitado
recuperar su contraseña. Para hacer esto ingrese a la siguiente dirección web:

    <?=$_url?>/usuarios/contrasenia/recuperar/<?=urlencode($usuario)?>/<?=$hash."\n"?>

Si usted no ha sido quien ha solicitado recuperar su contraseña puede ignorar
sin problemas este correo electrónico.

Saludos,

PD: este correo es generado de forma automática, por favor no contestar.
