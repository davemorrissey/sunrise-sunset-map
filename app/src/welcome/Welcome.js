import React, { Component } from 'react';
import CitySelector from './CitySelector';
import './Welcome.css';

class Welcome extends Component {

  constructor(props) {
    super(props);
    this.state = { glStatus: !!navigator.geolocation ? 'idle' : 'unavailable' };
  }

  countries = [
    { iso2: 'gb', lat: 54.5, lon: -3.5, zoom: 5 },
    { iso2: 'us', lat: 39.6, lon: -97.4, zoom: 3 },
    { iso2: 'au', lat: -27.8, lon: 133, zoom: 4 },
    { iso2: 'nz', lat: -42, lon: 172, zoom: 5 },
    { iso2: 'fr', lat: 47, lon: 2.6, zoom: 5 },
    { iso2: 'es', lat: 40.1, lon: -3.2, zoom: 6 },
    { iso2: 'de', lat: 51.3, lon: 9.9, zoom: 6 },
    { iso2: 'it', lat: 42.3, lon: 12.6, zoom: 6 },
    { iso2: 'mx', lat: 23.9, lon: -102.4, zoom: 4 },
    { iso2: 'br', lat: -14.3, lon: -56.6, zoom: 4 }
  ];

  goToCountry(country) {
    this.props.setMapCenter(country.lat, country.lon, country.zoom);
  }

  toggleCitySelection() {
    this.refs.citySelector.show();
  }

  geolocate() {
    this.setState({ glStatus: 'loading', glError: null });
    navigator.geolocation.getCurrentPosition(position => {
      if (position && position.coords && position.coords.latitude && position.coords.longitude) {
        this.setState({ glStatus: 'idle'});
        this.props.setMapCenter(position.coords.latitude, position.coords.longitude, 9);
        this.props.setLocation(position.coords.latitude, position.coords.longitude);
      } else {
        this.setState({ glStatus: 'error', glError: 'Location Unavailable'});
      }
    }, error => {
      let glError = error.code === error.PERMISSION_DENIED ? 'Permission Denied' : 'Location Unavailable';
      this.setState({ glStatus: 'error', glError: glError });
    });
  }

  render() {
    const { glStatus, glError } = this.state;
    let glButton = '';
    if (glStatus !== 'unavailable') {
      glButton = <div className="geolocate">
        <a onClick={this.geolocate.bind(this)} className={glStatus}>
          { glStatus === 'loading'&& <i className="fa fa-spin fa-spinner"/>}
          { glStatus === 'idle' && <span><i className="fa fa-map-marker"/> My Location</span>}
          <span>{glError}</span>
        </a>
      </div>;
    }

    return (
      <div id="welcome">
        <h1>Welcome</h1>
        <p className="intro-top">
          To start, browse the map and click your location.
        </p>
        <p className="intro-sub">
          With this map, you can find sunrise, sunset, moonrise and moonset times for 2018,
          2019 and beyond for any location worldwide, simply by clicking the map.
        </p>
        <div className="search">
          {glButton}
          <a className="cities" onClick={this.toggleCitySelection.bind(this)}>Major Cities</a>
          <div className="countries">
            {
              this.countries.map(country =>
                <a key={country.iso2} onClick={this.goToCountry.bind(this, country)}><span className={'flag flag-' + country.iso2}/></a>
              )
            }
          </div>
        </div>
        <div className="credit">
          &copy;2005-2018 <a href="http://www.davemorrissey.com/" target="_blank" rel="noopener noreferrer">David Morrissey</a>
          <br/>
          <a href="https://github.com/davemorrissey/sunrise-sunset-map" target="_blank" rel="noopener noreferrer"><i className="fa fa-github"/> View source on GitHub</a>
        </div>
        <CitySelector ref="citySelector" setLocation={this.props.setLocation}/>
      </div>
    );
  }
}

export default Welcome;
