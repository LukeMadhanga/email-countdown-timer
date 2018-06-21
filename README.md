=Originally from https://github.com/woolm110/email-countdown-timer but my god is it buggy

# Email Countdown Timer

> Create an animated countdown timer for use within HTML emails

## Getting started
- Upload files to server

## Usage
- Navigate to your script in the browser and append the time you want to countdown to in the querystring parameter `time`. e.g. `http://[server-address]/countdown.php?time=2016-12-25+00:00:01`.

To include the countdown timer in your HTML email you simply need to create an image tag and in the `src` set it to the browser address. *Note: Animated gifs are not supported in Outlook and for these the first frame will be shown*.

## Settings

The countdown timer can be customised to fit your style. The follow can be modified using query string parameters.
 - width: defaults to 640,
 - height: defaults to 110,
 - boxColor: defaults to '#000',
 - xOffset: defaults to 155,
 - yOffset: defaults to 70,
 - centerText: defaults to true,
 - fontColor: defaults to '#FFF',
 - labelOffsetsX: defaults to '1.4,5,8,11',
 - labelOffsetY: defaults to 98,
 - timezone: defaults to 'UTC',
 - time: defaults to date('Y-m-d H:i:s'),
 - font: defaults to 'BebasNeue',
 - fontSize: defaults to 60,
 - hideLabel: defaults to false,
 - labelColor: defaults to null,
 - labelSize: defaults to 15,
 - recenter: defaults to false,


An example of this would be `https://[domain]/countdown.php?time=2018-07-10+00:00:01&width=640&height=85&boxcolor=8B2860&font=BebasNeue&fontcolor=EEE&fontsize=60&xoffset=155&yoffset=70&labeloffsety=98&labeloffsetsx=1.4,5,8,11&timezone=America/New_York`.

###Fonts

Any font file can be used as the base font for the countdowm timer. To use a custom font you'll need to upload it to the `fonts` directory and reference the exact name in the query string parameter `font`. *Note: fonts must be uploaded using the `ttf` file extension*.
