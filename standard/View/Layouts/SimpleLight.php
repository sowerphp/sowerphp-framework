<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!-- Design by http://delaf.cl -->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<meta name="generator" content="MiPaGiNa"/>
		<title><?php echo $_header_title; ?></title>
		<link rel="shortcut icon" href="<?php echo $_base; ?>/img/favicon.png" />
		<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $_base; ?>/layouts/<?php echo $_layout; ?>/css/screen.css" />
	</head>
	<body>
		<div id="header_container">
			<div id="header" class="title_<?php echo strpos($_body_title, "\n") ? 'ascii' : 'normal'; ?>">
<?php echo $_body_title; ?>
			</div>
			<div id="navigation">
<?php
$links = array();
foreach($_nav_website as $link => &$name) {
	if($link[0]=='/') $link = $_base.$link;
	if(is_array($name)) $links[] = '<a href="'.$link.'" title="'.$name['title'].'">'.$name['name'].'</a>';
	else $links[] = '<a href="'.$link.'">'.$name.'</a>';
}
echo implode(' | '."\n", $links);
?>
			</div>
		</div>
		<div id="container">
			<div id="content">
<?php
$message = Session::message();
if($message) echo '<div class="session_message">',$message,'</div>';
echo $_content;
?>
			</div>
		</div>
		<div id="footer_container">
			<div id="footer">
				<?php echo is_array($_footer) ? implode (' ', $_footer): $_footer; ?>
			</div>
		</div>
	</body>
</html>
