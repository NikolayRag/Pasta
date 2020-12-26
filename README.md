Compose images with PHP+Imagemagik

Warning: it's not optimized in any way and thus is slow!

Pasta takes linear composing script and creates merged image.
Sequence of operations is passed as `['op arg1 arg2 ...']` array of strings,
making it easy to achieve form GET arguments by `explode("&", $_SERVER["REQUEST_URI"])` or something like that.

Any of intermideate results can be named with `name=op arg...` construct, to be used later for layered composing by 'mix' op.

Op's are:

```
take path-to-image.jpg
&crop 100 100 800 800
&size 600 300
&rot 15
&gamma .5 1 .1
&back=move -100 100
&take path-to-image2.jpg
&sat 300
&level -.2 1 0 1 .2 1
&crop 0 0 1000 500
&mix1=mix back over
&new 800 400
&t1=text 100 200 100 font.ttf Hi There
&level 0 .2 0 1 0 .6
&blur 10
&mix t1 cut x
&mix mix1 add x
&mode hsb
&level 0 1.5 0 1 0 1
```

Simple  code for calling Pasta is

```
$cPasta = new PASTA($scriptArray, 'jpg');
$cPasta->setFontsDir(__dir__ .'/fonts/');
$cIm = $cPasta->bake();
echo $cIm;
```
