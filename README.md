Compose images with PHP+Imagemagik

Warning: it's not optimized in any way and thus is slow!

Pasta takes linear composing script and creates merged image.
Sequence of operations is passed as `['op arg1 arg2 ...']` array of strings,
making it easy to achieve form GET arguments by `explode("&", $_SERVER["REQUEST_URI"])`
or something like that.

Any of intermideate results can be named with `name=op arg...` construct, to be used later
for layered composing by 'mix' op.

Sample script provided with GET (in one line without newlines):

```
?take path-to-image.jpg
&crop 100 100 800 800
&size 600 300
&setg .5 1 .2
&back=move -100 300
&take https://d2j6dbq0eux0bg.cloudfront.net/images/19611244/1705862279.jpg
&setb -.2 0 .2
&mix1=mix back over
&new 800 400&t1=text 100 200 100 font.ttf Hi There
&setw 1 .6 .2
&blur 10
&mix t1 cut
&mix mix1 over x
```

Simple  code for calling Pasta is

```
$cPasta = new PASTA($scriptArray, 'jpg');
$cPasta->setFontsDir(__dir__ .'/fonts/');
$cIm = $cPasta->bake();
echo $cIm;
```
