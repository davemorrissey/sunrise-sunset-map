# sunrisesunsetmap.com

This is the source code for [sunrisesunsetmap.com](https://sunrisesunsetmap.com).

This site was originally built in 2005, pre-dating even jQuery, when IE6 was the dominant browser.
It was intended to run on cheap shared hosting.

I have left it alone until recently but have now rebuilt it to modern standards, using ReactJS
and SCSS and replacing the location database and automatic timezone code with Google APIs.

### Calculations

PHP has built in sunrise/sunset calculation but this site uses its own, thanks to a long history of
being re-written from JavaScript to Java to PHP, with customisations for better time zone support, solar
transit, position calculation etc. The original is in the public domain, used with the kind permission of the
NOAA, and can be found [here](https://www.esrl.noaa.gov/gmd/grad/solcalc/sunrise.html).

I have lost the source of the moon calculations but believe they were also public domain.

### Recent changes

* Mobile support
* Geolocation
* React
* Webpack
* Replaced database of places with Google Geocoding API
* Prefer time zone selection using Google Time Zone API
* Use composer for TCPDF dependency
* Flag sprites
* SCSS
* SCSS Lint

### Next steps

* Tests

For better performance and a cleaner deployment the APIs could be separated from the front end, with the APIs
run from any PaaS service and the front end hosted on a CDN. The site doesn't have enough traffic to justify
the cost and complexity involved.

### Requirements

* PHP > ~5.5
* Node > ~6.4
* Composer

### Running locally

The production server does not support CORS requests so if you want to run this app locally, you will need to
set up an Apache or nginx server to run the PHP, with CORS enabled.

    composer install
    cd app
    npm install
    npm start

### Running on Heroku

I run this app on a VPS to minimise hosting costs, but it can be deployed on Heroku. Two Heroku buildpacks
are required - PHP to serve the APIs and generate PDFs, and Node (at deployment time only) to generate the static site.

* Add the app to Heroku: `heroku create your-app-name`
* In the Heroku console, add the PHP and Node buildpacks
* In the Heroku console, add a config var: `API_KEY=<your-google-api-key>`
* You should also change the Google API key in `.env`
* Deploy the app: `git push heroku master`
