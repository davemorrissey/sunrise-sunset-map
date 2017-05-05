# sunrisesunsetmap.com

This is the source code for [sunrisesunsetmap.com](https://sunrisesunsetmap.com).

This site was originally built in 2005, pre-dating even jQuery, when IE6 was the dominant browser.
It was intended to run on cheap shared hosting.

I have left it alone until recently but am now gradually rebuilding it to modern standards. My focus
has been on responsive design and improved UX, so much the legacy procedural code remains. The next
step is to replace jQuery DOM manipulation with a framework, probably React.

### Calculations

PHP has built in sunrise/sunset calculation but this site uses its own, thanks to a long history of
being re-written from JavaScript to Java to PHP, with customisations for better time zone support, solar
transit, position calculation etc. The original is in the public domain, used with the kind permission of the
NOAA, and can be found [here](https://www.esrl.noaa.gov/gmd/grad/solcalc/sunrise.html).

I have lost the source of the moon calculations but believe they were also public domain.

### Recent changes

* Mobile support
* Geolocation
* Replaced database of places with Google Geocoding API
* Prefer time zone selection using Google Time Zone API
* Use composer for TCPDF dependency

### Next steps

* React
* Sass
* Webpack
* Lint
* Tests
* Flag sprites

### Requirements

* PHP > ~5.5
* Composer