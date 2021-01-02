<? 
//  todo 7 (feature) +0: allow % in all dimentional values
/*
Compose images with provided script

Issue:
	Almost any step is performed by copying previous step image - this is tested to be NOT time consuming.
*/
class PASTA {

private $namesA=[], $ordersA=[], $md5='';
private $tmpFolder=__dir__;



/*
==============OPERATIONS=============
*/

/*
Blank area, transparent by default

ex:
	new width height color
*/
private function do_new ($_in, $_cArgs){
	$out = new Imagick();
	$out->newImage($_cArgs[0], $_cArgs[1], new ImagickPixel(isSet($_cArgs[2])?$_cArgs[2]:'rgba(0%,0%,0%,0)') );

	return $out;
}



/*
Load image from URL

ex:
	take URL
*/
private function do_take($_in, $_cArgs){
	$out = new Imagick(implode(" ",$_cArgs));
		
	return $out;
}



/*
Resize

Specified aspect rectangle will fit optionally cropped into destination size

ex:
	size width height [aspect|* crop:x]
*/
private function do_size($_in, $_cArgs){
	$x = $_cArgs[0];
	$y = $_cArgs[1];

	
	//calc for explicit aspect
	if (isSet($_cArgs[2])){
		$cAspect = ($_cArgs[2]=="*")
			? ($_in->getImageWidth() / $_in->getImageHeight())
			: $_cArgs[2];

		if ( ($x/$y > $cAspect) ^ (isSet($_cArgs[3])) ){
				$x = $y *$cAspect;
		} else {
				$y = $x /$cAspect;
		};
	};


	$out = clone $_in;
	
	$out->resizeImage($x, $y, Imagick::FILTER_CATROM ,1);


	//center
	if (isSet($_cArgs[2]))
		return self::do_crop(
			$out,
			[($x-$_cArgs[0])/2 ,($y-$_cArgs[1])/2, $_cArgs[0],$_cArgs[1]]
		);


	return $out;
}



/*
Crop box

ex:
	crop x y width height
*/
private function do_crop($_in, $_cArgs){
	$out = new Imagick();
	$out->newImage($_cArgs[2],$_cArgs[3], new ImagickPixel('rgba(0%,0%,0%,0)') );
				
	$out->compositeImage($_in,Imagick::COMPOSITE_OVER,-$_cArgs[0],-$_cArgs[1]);

	return $out;
}



/*
Move

ex:
	move x y
*/
private function do_move($_in, $_cArgs){
	$out = new Imagick();
	$out->newImage($_in->getImageWidth() +$_cArgs[0],$_in->getImageHeight() +$_cArgs[1], new ImagickPixel('rgba(0%,0%,0%,0)') );
				
	$out->compositeImage($_in,Imagick::COMPOSITE_OVER,$_cArgs[0],$_cArgs[1]);

	return $out;
}



/*
Rotate

ex:
	rot angle [crop:x]
*/
private function do_rot($_in, $_cArgs){
	$inW = $_in->getImageWidth();
	$inH = $_in->getImageHeight();

	$out = clone $_in;

	$out->rotateImage(new ImagickPixel('rgba(0%,0%,0%,0)'), $_cArgs[0]);

	$outW = $out->getImageWidth();
	$outH = $out->getImageHeight();

	$out->setImagePage($outW, $outH, 0, 0);

	if (isSet($_cArgs[1])){
		$out->cropImage(
			$inW,
			$inH,
			($outW-$inW)/2,
			($outH-$inH)/2
		);
	}

	return $out;
}



/*
Place text over

ex:
	text x y size fontname text
*/
private function do_text($_in, $_cArgs){
	$out = clone $_in;

	if (preg_match('|^http(s)?://.+|', $_cArgs[3])){
		$fnA = explode('/', $_cArgs[3]);
		$fn = $this->tmpFolder . $fnA[count($fnA)-1];

		if (!file_exists($fn))
		  file_put_contents(
				$fn,
				file_get_contents($_cArgs[3])
		  );
	}

	$draw = new ImagickDraw();
	$draw->setFillColor('white');
	$draw->setFont($fn);
	$draw->setFontSize($_cArgs[2]);
	
	$out->annotateImage ($draw,$_cArgs[0],$_cArgs[1],0,implode(" ", array_slice($_cArgs,4)));
	
	return $out;
}


/*
Compose current image with named one

ex:
	mix layer2 mode:dif|mul|add|lay|over|cut [swap:x]
*/
private function do_mix($_in, $_cArgs){
	$l2 = !isSet($_cArgs[2])? $this->namesA[$_cArgs[0]] :$_in;
	
	$out = isSet($_cArgs[2])? clone $this->namesA[$_cArgs[0]] :clone $_in;
	
	$cMethod = [
		'dif'=>Imagick::COMPOSITE_DIFFERENCE,
		'mul'=>Imagick::COMPOSITE_MULTIPLY,
		'add'=>Imagick::COMPOSITE_BLEND,
		'lay'=>Imagick::COMPOSITE_OVERLAY,
		'over'=>Imagick::COMPOSITE_OVER,
		'cut'=>Imagick::COMPOSITE_OUT,
 
	][$_cArgs[1]];
				
	
	$out->compositeImage($l2, $cMethod, 0, 0);

	return $out;
}



/*
Set black and white points

ex:
	level r0 r1 g0 g1 b0 b1
*/
private function do_level($_in, $_cArgs){
	$out = clone $_in;
				
    $q = 65535;
    $out->evaluateimage(Imagick::EVALUATE_MULTIPLY, $_cArgs[1]-$_cArgs[0], Imagick::CHANNEL_RED);
    $out->evaluateimage(Imagick::EVALUATE_ADD, $_cArgs[0] * $q, Imagick::CHANNEL_RED);
    $out->evaluateimage(Imagick::EVALUATE_MULTIPLY, $_cArgs[3]-$_cArgs[2], Imagick::CHANNEL_GREEN);
    $out->evaluateimage(Imagick::EVALUATE_ADD, $_cArgs[2] * $q, Imagick::CHANNEL_GREEN);
    $out->evaluateimage(Imagick::EVALUATE_MULTIPLY, $_cArgs[5]-$_cArgs[4], Imagick::CHANNEL_BLUE);
    $out->evaluateimage(Imagick::EVALUATE_ADD, $_cArgs[4] * $q, Imagick::CHANNEL_BLUE);

	return $out;
}



/*
Set gamma

ex:
	gamma r g b
*/
private function do_gamma($_in, $_cArgs){
	$out = clone $_in;
				
	$out->levelImage(0, $_cArgs[0], 65535, Imagick::CHANNEL_RED);
	$out->levelImage(0, $_cArgs[1], 65535, Imagick::CHANNEL_GREEN);
	$out->levelImage(0, $_cArgs[2], 65535, Imagick::CHANNEL_BLUE);

	return $out;
}



/*
Blur

ex:
	blur size
*/
private function do_blur($_in, $_cArgs){
	$out = clone $_in;
				
	$out->gaussianBlurImage($_cArgs[0], $_cArgs[0]/2, Imagick::CHANNEL_ALL);

	return $out;
}



/*
Saturation

ex:
	sat amt
*/
private function do_sat($_in, $_cArgs){
	$out = clone $_in;
				
	$out->modulateImage(100, $_cArgs[0], 100);

	return $out;
}



/*
Change color space

ex:
	mode space:rgb|lab|xyz|yuv|cmyk|hsb|hsl
*/
private function do_mode($_in, $_cArgs){
	$spacesA = [
		'rgb' => Imagick::COLORSPACE_SRGB,
		'lab' => Imagick::COLORSPACE_LAB,
		'xyz' => Imagick::COLORSPACE_XYZ,
		'yuv' => Imagick::COLORSPACE_YUV,
		'cmyk' => Imagick::COLORSPACE_CMYK,
		'hsb' => Imagick::COLORSPACE_HSB,
		'hsl' => Imagick::COLORSPACE_HSL,
	];

	$out = clone $_in;

	$out->transformimagecolorspace($spacesA[$_cArgs[0]]);

	return $out;
}
/*
========== - OPS ===========
*/



/*
Create new Pasta

$_script
	Operation array of strings in form of
	"[name=]op arg1..."
	where op is 
	  [new|take|size|crop|move|rot|text|mix|level|gamma|blur|sat|mode]

$_tmpFolder
	Dir to store cache
*/
function __construct($_script=[], $_tmpFolder=False){
	if ($_tmpFolder)
	  $this->tmpFolder = $_tmpFolder;


	$comString = "";

	foreach ($_script as $cScript){
		$cCmd = explode('=', $cScript);
		
		$cCmdA = explode(' ', $cCmd[1]); //command args
		switch ($cCmdA[0]){
			case 'new':
			case 'take':
			case 'size':
			case 'crop':
			case 'move':
			case 'rot':
			case 'text':
			case 'mix':
			case 'level':
			case 'gamma':
			case 'blur':
			case 'sat':
			case 'mode':
				$comString.= $cScript;

				$cMethod = "do_${cCmdA[0]}";
				
				$this->ordersA[]= (object)['name'=>$cCmd[0], 'cmd'=>$cMethod, 'args'=>array_slice($cCmdA, 1)];
		}
	}


	$this->md5 = md5($comString);
}



/*
return md5 hash of actual part of script
*/
function md5(){
	return $this->md5;
}



/*
Actual compose

return Imagick object
*/
function bake (){
	$this->namesA = [];
	
	
	$out = new Imagick();
	foreach ($this->ordersA as $cOrder){
		$cCmd = $cOrder->cmd;
		$out = $this->$cCmd($out, $cOrder->args);


		if ($cOrder->name)
		  $this->namesA[$cOrder->name] = $out;
	}

	
	return $out;
}

}
?>
