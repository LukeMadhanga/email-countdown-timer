<?php

include 'GIFEncoder.class.php';

/**
 * Class CountdownTimer
 */
class CountdownTimer {

    /**
     * @var object
     */
    private $base;

    /**
     * @var object
     */
    private $box;

    /**
     * @var int
     */
    private $width = 0;

    /**
     * @var int
     */
    private $height = 0;

    /**
     * @var int
     */
    private $xOffset = 0;

    /**
     * @var int
     */
    private $yOffset = 0;

    /**
     * @var int
     */
    private $delay = 100;

    /**
     * @var array
     */
    private $frames = [];

    /**
     * @var array
     */
    private $delays = [];

    /**
     * @var array
     */
    private $date = [];

    /**
     * @var array
     */
    private $fontSettings = [];

    /**
     * @var array
     */
    private $boundingBox = [];

    /**
     * @var int
     */
    private $seconds = 90;
    
    /**
     * @var array
     */
    private $labelOffsetsX;
    
    /**
     * @var int
     */
    private $labelOffsetY = 98;
    
    /**
     * @var boolean
     */
    private $hideLabel = false;
    
    /**
     * @var string
     */
    private $labelColor;
    
    /**
     * @var int
     */
    private $labelSize = 15;
    
    /**
     * @var boolean
     */
    private $centerText = true;
    
    /**
     * @var array
     */
    private $textCoords = [];
    
    /**
     * @var array
     */
    private $labelCoords = [];
    
    /**
     * @var boolean
     */
    private $recenter = false;
    
    /**
     * @var array
     */
    private $labels = ['Days', 'Hrs', 'Mins', 'Secs'];
    
    /**
     * The text to show when the countdown has ended
     * @var type 
     */
    private $endedtext;
    
    /**
     * <b>True</b> if the time is being shown, <b>false</b> if an end screen is being shown
     * @var boolean
     */
    private $showingTime;

    /**
     * CountdownTimer constructor.
     * @param array $settings
     */
    function __construct($settings) {
        $s = $settings + [
            'width' => 640,
            'height' => 110,
            'boxColor' => '#000',
            'xOffset' => 155,
            'yOffset' => 70,
            'centerText' => true,
            'fontColor' => '#FFF',
            'labelOffsetsX' => '1.4,5,8,11',
            'labelOffsetY' => 10,
            'timezone' => 'UTC',
            'time' => date('Y-m-d H:i:s'),
            'font' => 'BebasNeue',
            'fontSize' => 60,
            'hideLabel' => false,
            'labelColor' => null,
            'labelSize' => 15,
            'recenter' => false,
            'endedText' => 'ENDED',
        ];
        
        $this->width = $s['width'];
        $this->height = $s['height'];
        $this->boxColor = $s['boxColor'];
        $this->xOffset = $s['xOffset'];
        $this->yOffset = $s['yOffset'];
        $this->boxColor = $this->hex2rgb($s['boxColor']);
        $this->fontColor = $this->hex2rgb($s['fontColor']);
        $this->labelColor = $s['labelColor'] ? $this->hex2rgb($s['labelColor']) : $this->fontColor;
        
        $this->centerText = $s['centerText'];
        $this->recenter = $s['recenter'];

        $this->labelOffsetsX = explode(',', $s['labelOffsetsX']);
        $this->labelOffsetY = $s['labelOffsetY'];
        $this->hideLabel = $s['hideLabel'];
        $this->labelSize = $s['labelSize'];
        
        $this->endedtext = $s['endedText'];

        $this->date['time'] = $s['time'];
        $this->date['futureDate'] = new DateTime(date('r', strtotime($s['time'])));
        
        $dtnow = new \DateTimeZone($s['timezone']);
        $timenow = new \DateTime('now', $dtnow);
        $time = strtotime($timenow->format('Y-m-d H:i:s'));
        
        $this->date['timeNow'] = $time;
        $this->date['now'] = new DateTime(date('r', $time));

        // create new images
        $this->box = imagecreatetruecolor($this->width, $this->height);
        $this->base = imagecreatetruecolor($this->width, $this->height);

        $this->fontSettings['path'] = __DIR__ . '/fonts/' . $s['font'] . '.ttf';
        $this->fontSettings['color'] = imagecolorallocate($this->box, $this->fontColor[0], $this->fontColor[1], $this->fontColor[2]);
        $this->fontSettings['labelColor'] = imagecolorallocate($this->box, $this->labelColor[0], $this->labelColor[1], $this->labelColor[2]);
        $this->fontSettings['size'] = $s['fontSize'];
        $this->fontSettings['labelSize'] = $s['labelSize'];
        $this->fontSettings['characterWidth'] = imagefontwidth((int) $this->fontSettings['path']);

        // get the width of each character
        $string = "0:";
        $size = $this->fontSettings['size'];
        $angle = 0;
        $fontfile = $this->fontSettings['path'];

        $strlen = strlen($string);
        for ($i = 0; $i < $strlen; $i++) {
            $dimensions = imagettfbbox($size, $angle, $fontfile, $string[$i]);
            $this->fontSettings['characterWidths'][] = [
                $string[$i] => $dimensions[2]
            ];
        }

        $this->images = [
            'box' => $this->box,
            'base' => $this->base,
        ];

        // create empty filled rectangles
        foreach ($this->images as $image) {
            $this->createFilledBox($image);
        }
    }

    /**
     * hex2rgb
     * Convert a hex
     * colour to rgb
     * @param  string $hex
     * @return array
     */
    private function hex2rgb($hex) {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array($r, $g, $b);

        return $rgb;
    }

    /**
     * createFilledBox
     * Create a filled box
     * to use at the base
     * @param  $image
     */
    private function createFilledBox($image) {
        imagefilledrectangle(
                $image, 0, 0, $this->width, $this->height, imagecolorallocate(
                        $image, $this->boxColor[0], $this->boxColor[1], $this->boxColor[2]
                )
        );
    }

    /**
     * createFrames
     * Create all of the frames for 
     * the countdown timer
     * @return void
     */
    function createFrames() {
        $this->boundingBox = imagettfbbox($this->fontSettings['size'], 0, $this->fontSettings['path'], '00:00:00:00');
        $this->characterDimensions = imagettfbbox($this->fontSettings['size'], 0, $this->fontSettings['path'], '0');
        $this->characterWidth = $this->characterDimensions[2];
        $this->characterHeight = abs($this->characterDimensions[1] + $this->characterDimensions[7]);

        $this->base = $this->applyTextToImage($this->base, $this->fontSettings, $this->date);

        // create each frame
        for ($i = 0; $i <= $this->seconds; $i++) {
            $layer = imagecreatetruecolor($this->width, $this->height);
            $this->createFilledBox($layer);

            $this->applyTextToImage($layer, $this->fontSettings, $this->date);
        }
    }

    /**
     * Output the countdown
     * @param boolean $exit <b>True</b> to exit after output
     */
    function output($exit = true) {
        $this->createFrames();
        $this->showImage();

        if ($exit) {
            exit;
        }
    }

    /**
     * applyTextToImage
     * Apply each time stamp
     * to the image
     * @param $image
     * @param $font
     * @param $date
     * @return mixed
     */
    private function applyTextToImage($image, $font, $date) {
        $interval = date_diff($date['futureDate'], $date['now']);
        
        $this->showingTime = true;
        
        if ($date['futureDate'] < $date['now']) {
            $text = $interval->format($this->endedtext);
            $this->loops = false;
            $this->showingTime = false;
        } else {
            $text = $interval->format('%a:%H:%I:%S');
            
            if ($text[1] == ':') {
                // Add a leading 0
                $text = '0' . $text;
            }
            
            $this->loops = 0;
        }
        
        $this->calculateCoords($font, $text);

        if (!$this->hideLabel && $this->showingTime) {
            
            $this->calculateCoordsLabel($font, $text);
        
            // apply the labels to the image $this->yOffset + ($this->characterHeight * 0.8)
            foreach ($this->labels as $key => $label) {
                $labelX = $this->labelCoords[$key];
                $labelY = $this->textCoords['labelOffsetY'] + $this->labelOffsetY;
                $color = $font['labelColor'] ?: $font['color'];
                imagettftext($image, $font['labelSize'], 0, $labelX, $labelY, $color, $font['path'], $label);
            }
        }

        // apply time to new image
        imagettftext($image, $font['size'], 0, $this->textCoords['x'], $this->textCoords['y'], $font['color'], $font['path'], $text);
        
        imagetruecolortopalette($image, false, 16);

        ob_start();
        imagegif($image);
        $this->frames[] = ob_get_contents();
        $this->delays[] = $this->delay;
        ob_end_clean();

        $this->date['now']->modify('+1 second');

        return $image;
    }
    
    /**
     * Calculate the coordinates for the main time text
     * @param array $font An array defining information about the current font setup
     * @param string $timetext The main time text, e.g. 03:23:59:59
     */
    private function calculateCoords($font, $timetext) {
        $calculateCoords = !$this->textCoords;
        
        if ($calculateCoords) {
            // The coordinates have not been calculated yet
            $this->textCoords['x'] = $this->xOffset;
            $this->textCoords['y'] = $this->yOffset;
            $this->textCoords['labelOffsetY'] = 0;
        }
        
        if ($this->centerText && ($calculateCoords || $this->recenter || !$this->showingTime)) {
            // Note, coords are calculated from the bottom of the text
            $typeSpace = imagettfbbox($font['size'], 0, $font['path'], $timetext);
            
            $labelHeight = 0;
            $labelOffset = 0;
            
            if (!$this->hideLabel && $this->showingTime) {
                // Add in metrics for the label
                $labelTypeSpace = imagettfbbox($font['labelSize'], 0, $font['path'], 'Days');
                $labelHeight = abs($labelTypeSpace[5] - $labelTypeSpace[1]);
                $labelOffset = $this->labelOffsetY;
            }
            
            $textWidth = abs($typeSpace[4] - $typeSpace[0]);
            $textHeight = abs($typeSpace[5] - $typeSpace[1]);
            
            $spaceX = $this->width - $textWidth;
            $spaceY = $this->height - $textHeight - ($labelHeight + $labelOffset);
            
            $this->textCoords['x'] = $spaceX / 2;
            $this->textCoords['y'] = $textHeight + ($spaceY / 2);
            
            $this->textCoords['labelOffsetY'] = $this->textCoords['y'] + $labelHeight;
        }
    }
    
    /**
     * Calculate the coordinates of the label
     * @param array $font An array defining information about the current font setup
     * @param string $timetext The main time text, e.g. 03:23:59:59
     */
    private function calculateCoordsLabel($font, $timetext) {
        $calculateCoords = !$this->labelCoords;
        
        if ($calculateCoords) {
            $this->labelCoords = [
                $this->xOffset + ($this->characterWidth * $this->labelOffsetsX[0]),
                $this->xOffset + ($this->characterWidth * $this->labelOffsetsX[1]),
                $this->xOffset + ($this->characterWidth * $this->labelOffsetsX[2]),
                $this->xOffset + ($this->characterWidth * $this->labelOffsetsX[3]),
            ];
        }
        
        if ($this->centerText && ($calculateCoords || $this->recenter || !$this->showingTime)) {
            $parts = explode(':', $timetext);
            
            $colontype = imagettfbbox($font['size'], 0, $font['path'], ':');
            $colonwidth = abs($colontype[4] - $colontype[0]);
            
            $sizesmain = [];
            $sizeslabels = [];
            $offsets = [];
            
            $offsetsinputs = [
                $parts[0],
                "{$parts[0]}:",
                "{$parts[0]}:{$parts[1]}:",
                "{$parts[0]}:{$parts[1]}:{$parts[2]}:",
            ];
            
            foreach ($parts as $x) {
                // Calculate the size of each part of the time array
                $timetype = imagettfbbox($font['size'], 0, $font['path'], $x);
                $sizesmain[] = abs($timetype[4] - $timetype[0]);
            }
            
            foreach ($this->labels as $y) {
                // Calculate the size of each part of the time array
                $labeltype = imagettfbbox($font['labelSize'], 0, $font['path'], $y);
                $sizeslabels[] = abs($labeltype[4] - $labeltype[0]);
            }
            
            foreach ($offsetsinputs as $z) {
                $offsettype = imagettfbbox($font['size'], 0, $font['path'], $z);
                $offsets[] = abs($offsettype[4] - $offsettype[0]);
            }
            
            $this->labelCoords = [];
            $this->labelCoords[] = $this->textCoords['x'] + (($sizesmain[0] - $sizeslabels[0]) / 2);
            $this->labelCoords[] = $this->textCoords['x'] + $offsets[1] + $colonwidth + (($sizesmain[1] - $sizeslabels[1]) / 2);
            $this->labelCoords[] = $this->textCoords['x'] + $offsets[2] + $colonwidth + (($sizesmain[2] - $sizeslabels[2]) / 2);
            $this->labelCoords[] = $this->textCoords['x'] + $offsets[3] + $colonwidth + (($sizesmain[3] - $sizeslabels[3]) / 2);
        }
    }

    /**
     * showImage
     * Create the animated gif
     * @return void
     */
    public function showImage() {
        $gif = new AnimatedGif($this->frames, $this->delays, $this->loops);
        $gif->display();
    }

}

// https://[domain]/countdown.gif?time=2018-07-10+00:00:01&width=640&height=85&boxcolor=%23333&font=BebasNeue&fontcolor=%23FFF&fontsize=60&xoffset=155&yoffset=70&labeloffsety=20&timezone=America/New_York&hidelabel=1
