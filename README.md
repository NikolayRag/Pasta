Compose images with PHP+Imagemagik

Warning: it's not optimized in any way and thus is slow!

Pasta takes linear composing script and creates merged image.
Sequence of operations is passed as `['op arg1 arg2 ...']` array of strings,
making it easy to achieve form GET arguments by `explode("&", $_SERVER["REQUEST_URI"])` or something like that.

Any of intermideate results can be named with `name=op arg...` construct, to be used later for layered composing by 'mix' op.

Op's are:

```
new width height color
take URL
size width height
crop x y width height
move x y
rot angle [crop:x]
text x y size fontname text
mix layer2 mode:dif|mul|add|lay|over|cut [swap:x]
level r0 r1 g0 g1 b0 b1
gamma r g b
blur size
sat amt
mode space:rgb|lab|xyz|yuv|cmyk|hsb|hsl
```

Simple  code for calling Pasta is

```
$cPasta = new PASTA($scriptArray, $fontsRoot);
$cIm = $cPasta->bake();
echo $cIm;
```
