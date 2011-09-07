<?php

require_once(dirname(__FILE__).'/../bootstrap/unit.php');

// setup test objects
$t = new lime_test(16);
$c = new skInlineCSS(array(
	'apply_style_blocks' => false
));
$d = new DOMDocument();

// sample html
$html = <<<HTML
<!doctype html> 
<html lang="en"> 
	<head> 
		<meta charset="utf-8" /> 
		<title>skInlineCSS Test Page</title>
		<style type="text/css">
			body h1 {
				margin:0 0 2em;
			}

			em {
				color:blue;
			}
		</style>
	</head>

	<body>
		<h1>skInlineCSS Test Page</h1>

		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sit amet nisi nibh, at tempus nunc. Vivamus pellentesque quam vitae libero tristique sed luctus nulla mattis. Etiam aliquam massa sed ante auctor varius.</p>

		<p>Praesent <strong style="color: blue;">ultricies vulputate vehicula</strong>. Nam pellentesque leo at urna varius iaculis. Donec neque dolor, dapibus commodo consectetur ut, vehicula quis purus. Donec laoreet, tellus eget varius auctor, enim mauris tristique felis, <a href="http://www.studioskylab.com/">id ornare</a> mi enim quis tortor. Integer non orci vel justo lacinia mollis eget posuere lacus.</p>

		<div>
			<p>Aenean porttitor dolor ut ante vehicula laoreet. In congue posuere eleifend. <em>Nulla laoreet</em>, diam egestas malesuada placerat, velit neque laoreet risus, ultrices porttitor turpis eros vel nibh. Duis molestie, odio et porta vulputate, neque risus sollicitudin lacus, euismod rutrum sapien lectus sit amet magna.</p>
		</div>

		<div id="myDiv" class="myDivClass">
			<ul class="myUlClass">
				<li>Lorem</li>
				<li>Ipsum</li>
				<li>Dolor</li>
				<li>Sit</li>
				<li>Amet</li>
			</ul>
		</div>
	</body>
</html>
HTML;

// allow protected methods to be called as public
function getMethod($name)
{
	$class = new ReflectionClass('skInlineCSS');
	$method = $class->getMethod($name);
	$method->setAccessible(true);
	return $method;
}



// applying passed CSS
$d->loadHTML(str_replace('<h1>', '<h1 style="color:red;">', $html));
$t->is($c->convert($html, 'h1 {color:red;}'), $d->saveHTML());

// applying multiple CSS properties
$d->loadHTML(str_replace('<h1>', '<h1 style="color:red;font-size:20px;">', $html));
$t->is($c->convert($html, 'h1 {color:red; font-size:20px;}'), $d->saveHTML());

// applying CSS properties to multiple elements
$d->loadHTML(str_replace('<p>', '<p style="font-weight:bold;">', $html));
$t->is($c->convert($html, 'p {font-weight:bold;}'), $d->saveHTML());

// applying multiple CSS selectors
$d->loadHTML(str_replace(array('<h1>','<p>'), array('<h1 style="color:red;">','<p style="font-weight:bold;">'), $html));
$t->is($c->convert($html, 'h1 {color:red;} p {font-weight:bold;}'), $d->saveHTML());

// testing various selectors
$temphtml = preg_replace('/<p>/', '<p style="color:red;">', $html, 2);
$temphtml = str_replace('<p>', '<p style="color:red;font-weight:bold;">', $temphtml);
$d->loadHTML($temphtml);
$t->is($c->convert($html, 'p {color:red;} div > p {font-weight:bold;}'), $d->saveHTML());

// applying multiple CSS selectors with different specificities to the same elements
$d->loadHTML(str_replace('<p>', '<p style="font-weight:bold;color:red;">', $html));
$t->is($c->convert($html, 'p {font-weight:bold;} body p {color:red;}'), $d->saveHTML());

// applying multiple CSS selectors with equal specificities to the same elements
$d->loadHTML(str_replace('<p>', '<p style="font-weight:bold;color:red;">', $html));
$t->is($c->convert($html, 'p {font-weight:bold;} p {color:red;}'), $d->saveHTML());

// overriding selectors by passing one with a higher specificity after
$d->loadHTML(str_replace('<p>', '<p style="font-weight:bold;">', $html));
$t->is($c->convert($html, 'p {font-weight:normal;} body p {font-weight:bold;}'), $d->saveHTML());

// overriding selectors by passing one with a higher specificity before
$t->is($c->convert($html, 'body p {font-weight:bold;} p {font-weight:normal;}'), $d->saveHTML());

// overriding specific selector properties by passing a selector with a higher specificity
$d->loadHTML(str_replace('<p>', '<p style="color:red;font-weight:bold;">', $html));
$t->is($c->convert($html, 'p {font-weight:normal; color:red;} body p {font-weight:bold;}'), $d->saveHTML());

// specificities
$tempcss = <<<CSS
#myDiv .myUlClass li {
	background:pink; /* should be applied */
	border:1px solid pink; /* should be applied */
}
#myDiv li {
	background:yellow;
	border:1px solid yellow;
	color:yellow; /* should be applied */
}
.myDivClass .myUlClass li {
	background:blue;
	border:1px solid blue;
	color:blue;
	margin-bottom:13px; /* should be applied */
}
.myUlClass li {
	background:green;
	border:1px solid green;
	color:green;
	margin-bottom:12px;
	padding-left:12px; /* should be applied */
}
ul li {
	background:red;
	border:1px solid red;
	color:red;
	font-size:11px; /* should be applied */
	margin-bottom:11px;
	padding-left:11px;
}
li {
	background:white;
	border:1px solid white;
	color:white;
	font-size:10px;
	font-weight:bold; /* should be applied */
	margin-bottom:10px;
	padding-left:10px;
}
CSS;
$d->loadHTML(str_replace('<li>', '<li style="background:pink;border:1px solid pink;color:yellow;font-size:11px;font-weight:bold;margin-bottom:13px;padding-left:12px;">', $html));
$t->is($c->convert($html, $tempcss), $d->saveHTML());

// add properties to existing inline css
$d->loadHTML(str_replace('<strong style="color: blue;">', '<strong style="font-style: italic; color: blue;">', $html));
$t->is($c->convert($html, 'strong {font-style:italic;}'), $d->saveHTML());

// existing inline CSS shouldn't be overwritten by passed CSS
$d->loadHTML($html);
$t->is($c->convert($html, 'strong {color:green;}'), $d->saveHTML());

// applying style blocks from html
$c->setOption('apply_style_blocks', true);
$d->loadHTML(str_replace(array('<h1>','<em>'), array('<h1 style="margin:0 0 2em;">','<em style="color:blue;">'), $html));
$t->is($c->convert($html), $d->saveHTML());

// passed CSS should obey specificity rules
$t->is($c->convert($html, 'h1 {margin:0;}'), $d->saveHTML());

// passed CSS should override inline styles if the specificity is equal
$d->loadHTML(str_replace(array('<h1>','<em>'), array('<h1 style="margin:0 0 2em;">','<em style="color:red;">'), $html));
$t->is($c->convert($html, 'em {color:red;}'), $d->saveHTML());