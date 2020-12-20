<? 
class PASTA {

private $namesA=[], $ordersA=[], $md5='';
private $dirFonts=__dir__;



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

ex:
	size width height
*/
private function do_size($_in, $_cArgs){
	$out = clone $_in;
	
	$out->resizeImage($_cArgs[0],$_cArgs[1],Imagick::FILTER_CATROM ,1);

	return $out;
}



/*
Crop box

ex:
	crop x y width height
*/
private function do_crop($_in, $_cArgs){
	$out = clone $_in;

	$out->cropImage($_cArgs[2],$_cArgs[3],$_cArgs[0],$_cArgs[1]);

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
				
	$out->compositeImage($_in,imagick::COMPOSITE_OVER,$_cArgs[0],$_cArgs[1]);

	return $out;
}



/*
Rotate

ex:
	rot angle x:crop
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

	$draw = new ImagickDraw();
	$draw->setFillColor('white');
	$draw->setFont($this->dirFonts . $_cArgs[3]);
	$draw->setFontSize($_cArgs[2]);
	
	$out->annotateImage ($draw,$_cArgs[0],$_cArgs[1],0,implode(" ", array_slice($_cArgs,4)));
	
	return $out;
}


/*
Compose current image with named one

ex:
	mix 2ndLayer mode(dif|mul|add|lay|over|cut) x:swapInputs
*/
private function do_mix($_in, $_cArgs){
	$l2 = !isSet($_cArgs[2])? $this->namesA[$_cArgs[0]] :$_in;
	
	$out = isSet($_cArgs[2])? clone $this->namesA[$_cArgs[0]] :clone $_in;
	
	$cMethod = [
		'dif'=>imagick::COMPOSITE_DIFFERENCE,
		'mul'=>imagick::COMPOSITE_MULTIPLY,
		'add'=>imagick::COMPOSITE_BLEND,
		'lay'=>imagick::COMPOSITE_OVERLAY,
		'over'=>imagick::COMPOSITE_OVER,
		'cut'=>imagick::COMPOSITE_OUT,
 
	][$_cArgs[1]];
				
	
	$out->compositeImage($l2, $cMethod, 0, 0);

	return $out;
}



/*
Set black and white points

ex:
	setb r0 r1 g0 g1 b0 b1
*/
private function do_level($_in, $_cArgs){
	$out = clone $_in;
				
	$out->levelImage(65535-(65535/(1-$_cArgs[0])), 1, 65535/$_cArgs[1], imagick::CHANNEL_RED);
	$out->levelImage(65535-(65535/(1-$_cArgs[2])), 1, 65535/$_cArgs[3], imagick::CHANNEL_GREEN);
	$out->levelImage(65535-(65535/(1-$_cArgs[4])), 1, 65535/$_cArgs[5], imagick::CHANNEL_BLUE);

	return $out;
}



/*
Set gamma

ex:
	gamma r g b
*/
private function do_gamma($_in, $_cArgs){
	$out = clone $_in;
				
	$out->levelImage(0, $_cArgs[0], 65535, imagick::CHANNEL_RED);
	$out->levelImage(0, $_cArgs[1], 65535, imagick::CHANNEL_GREEN);
	$out->levelImage(0, $_cArgs[2], 65535, imagick::CHANNEL_BLUE);

	return $out;
}



/*
Blur

ex:
	blur size
*/
private function do_blur($_in, $_cArgs){
	$out = clone $_in;
				
	$out->gaussianBlurImage($_cArgs[0], $_cArgs[0]/2, imagick::CHANNEL_ALL);

	return $out;
}



/*
Saturation

ex:
	sat s
*/
private function do_sat($_in, $_cArgs){
	$out = clone $_in;
				
	$out->modulateImage(100, $_cArgs[0], 100);

	return $out;
}



/*
Change color space

ex:
	mode [rgb|lab|xyz|yuv|cmyk|hsb|hsl]
*/
private function do_mode($_in, $_cArgs){
	$spacesA = [
		'rgb' => imagick::COLORSPACE_SRGB,
		'lab' => imagick::COLORSPACE_LAB,
		'xyz' => imagick::COLORSPACE_XYZ,
		'yuv' => imagick::COLORSPACE_YUV,
		'cmyk' => imagick::COLORSPACE_CMYK,
		'hsb' => imagick::COLORSPACE_HSB,
		'hsl' => imagick::COLORSPACE_HSL,
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
*/
function __construct($_script=[]){
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
Set font folder to be accessed in 'text ...' op
*/
function setFontsDir($_root){
	$this->dirFonts = $_root;
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
