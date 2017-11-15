import React, {Component} from "react";
import DateSelector from './DateSelector';
import TimeZoneSelector from './TimeZoneSelector';
import axios from 'axios';
import './Data.css';

class Data extends Component {

  constructor(props) {
    super(props);
    this.state = {
      data: null,
      dataLoading: true
    };
    this.date = new Date();
  }

  geocoder = new window.google.maps.Geocoder();

  setDate(date) {
    this.date = date;
    this.loadData();
  }

  setTimeZone(timeZone) {
    if (this.props.location) {
      this.props.location.timeZone = timeZone;
      this.props.location.timeZoneWarning = false;
      this.setState({});
      this.loadData();
    }
  }

  componentDidMount() {
    if (this.props.location) {
      this.geocode(this.props.location);
    }
  }

  componentWillReceiveProps(nextProps) {
    this.geocode(nextProps.location);
  }

  geocode(location) {
    this.setState({ data: null });
    this.geocoder.geocode({'latLng': new window.google.maps.LatLng(location.lat, location.lon)}, (results, status) => {
      if (status === window.google.maps.GeocoderStatus.OK) {
        var locationDetails = this.extractLocationDetails(results);
        if (locationDetails.countryIso2) {
          location.countryIso2 = locationDetails.countryIso2;
        }
        if (!location.name) {
          location.name = locationDetails.name;
        }
      }
      this.loadData();
    });
  }

  extractLocationDetails(results) {
    var countryIso2 = null, admin1 = null, admin2 = null, city = null, town = null;
    results.forEach(address => {
      address.address_components.forEach(address_component => {
        address_component.types.forEach(type => {
          switch(type) {
            case 'administrative_area_level_2': admin2 = address_component.long_name; break;
            case 'administrative_area_level_1': admin1 = address_component.long_name; break;
            case 'locality': city = address_component.long_name; break;
            case 'postal_town': town = address_component.long_name; break;
            case 'country': countryIso2 = address_component.short_name; break;
            default: break;
          }
        })
      });
    });
    return { countryIso2: countryIso2, name: town || city || admin2 || admin1 };
  }

  loadData() {
    const date = new Date(this.date);
    const { location } = this.props;
    if (location && date) {
      this.setState({ dataLoading: true });
      axios.get(process.env.REACT_APP_BASE_URL + 'api/data.php', { params: {
          lat: location.lat,
          lon: location.lon,
          date: this.dateDmy(date),
          tz: location.timeZone ? location.timeZone.id : null,
          country: location.countryIso2 }})
        .then(response => {
          if (location !== this.props.location) {
            console.info('Location changed while loading');
            return;
          }
          if (this.dateDmy(date) !== this.dateDmy(this.date)) {
            console.log('Date changed while loading');
            return;
          }
          const data = response.data;
          location.timeZoneMatches = data.timeZoneMatches;
          if (data.timeZone && !location.timeZone) {
            location.timeZone = data.timeZone;
          } else if (!location.timeZone || (location.timeZoneMatches && location.timeZoneMatches.length > 1)) {
            location.timeZoneWarning = true;
          }
          this.setState({ data: data, dataLoading: false });
        })
        .catch(error => {
          console.error('Failed to load data', error);
          this.setState({ dataLoading: false });
          // TODO Show a message
        });
    }
  }

  showTimeZoneSelection() {
    this.refs.timeZoneSelector.show();
  }

  dateDmy(date) {
    return date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
  }

  displayLatLon(lat, lon) {
    var latDbl = Math.abs(lat);
    var latStr = this.zeroPad(parseInt(latDbl, 10), 2) + '\xB0';
    latDbl = (latDbl - parseInt(latDbl, 10)) * 60;
    latStr += this.zeroPad(parseInt(latDbl, 10), 2) + "'";

    if (lat > 0) {
      latStr += "N";
    } else {
      latStr += "S";
    }

    var lngDbl = Math.abs(lon);
    var lngStr = this.zeroPad(parseInt(lngDbl, 10), 3) + '\xB0';
    lngDbl = (lngDbl - parseInt(lngDbl, 10)) * 60;
    lngStr += this.zeroPad(parseInt(lngDbl, 10), 2) + "'";

    if (lon > 0) {
      lngStr += "E";
    } else {
      lngStr += "W";
    }

    return latStr + " " + lngStr;
  }

  zeroPad(num, length) {
    num = "000" + num;
    return num.substring(num.length - length);
  }

  render() {
    const { location } = this.props;
    const { data, dataLoading } = this.state;
    const date = this.date;
    if (location && data) {
      const { sun, moon } = data;
      let sunElement = null;
      if (sun.type === 'RISEN') {
        sunElement = <div className="row-special">Risen all day</div>;
      } else if (sun.type === 'SET') {
        sunElement = <div className="row-special">Set all day</div>;
      } else {
        sunElement = (<div>
            <div className="row"><span className="label">Rise</span> <span className="value">{sun.rise ? sun.rise.time : 'None'}</span>&nbsp;<span className="zone">{sun.rise ? sun.rise.zoneShort : ''}</span></div>
            <div className="row"><span className="label">Set</span> <span className="value">{sun.set ? sun.set.time : 'None'}</span>&nbsp;<span className="zone">{sun.set ? sun.set.zoneShort : ''}</span></div>
          </div>);
      }
      let moonElement = null;
      if (moon.type === 'RISEN') {
        moonElement = <div className="row-special">Risen all day</div>;
      } else if (moon.type === 'SET') {
        moonElement = <div className="row-special">Set all day</div>;
      } else {
        moonElement = (<div>
          <div className="row"><span className="label">Rise</span> <span className="value">{moon.rise ? moon.rise.time : 'None'}</span>&nbsp;<span className="zone">{moon.rise ? moon.rise.zoneShort : ''}</span></div>
          <div className="row"><span className="label">Set</span> <span className="value">{moon.set ? moon.set.time : 'None'}</span>&nbsp;<span className="zone">{moon.set ? moon.set.zoneShort : ''}</span></div>
        </div>);
      }

      let yearLink = process.env.REACT_APP_BASE_URL + 'year.php?lat=' + location.lat +
        '&lon=' + location.lon +
        '&tz=' + (location.timeZone ? location.timeZone.id : 'UTC') +
        '&year=' + date.getFullYear() +
        '&name=' + encodeURI(location.name);

      return (
        <div>
          <div id="data" className={dataLoading ? 'loading' : ''}>
            <h1>{ location.name || this.displayLatLon(location.lat, location.lon) }</h1>
            { location.name && <h2>{ this.displayLatLon(location.lat, location.lon) }</h2> }
            { location.timeZoneWarning && <div id="tzwarning" onClick={this.showTimeZoneSelection.bind(this)}><i className="fa fa-globe"/> Set time zone</div> }
            <DateSelector date={new Date(this.date)} setDate={this.setDate.bind(this)}/>
            <div id="sun">
              <img src="img/sun.png" alt="Sun" id="sun-image"/>
              {sunElement}
              <a href={yearLink} className="pdf" id="exportyear" target="_blank" rel="noopener noreferrer"><i className="fa fa-cloud-download"/>&nbsp; {date.getFullYear()} calendar (PDF)</a>
            </div>
            <span className="clear"/>
            <div id="moon">
              <img src={'img/moon/moon-' + moon.image + '.png'} alt="Moon" id="moon-image"/>
              {moonElement}
              <div id="phase" className="row"><span className="label">Phase</span> <span className="value">{moon.phase}</span></div>
            </div>
            <span className="clear"/>
            <div id="zone">
              <a id="zone-link" onClick={this.showTimeZoneSelection.bind(this)}><i className="fa fa-globe"/></a>
              <span id="zone-header">{location.timeZone ? location.timeZone.offset : 'UTC' }</span>
              <span id="zone-name">{location.timeZone ? location.timeZone.name : 'Universal Coordinated Time'}</span>
            </div>
          </div>
          <TimeZoneSelector ref="timeZoneSelector" setTimeZone={this.setTimeZone.bind(this)} timeZoneMatches={location.timeZoneMatches}/>
        </div>
      );

    } else {
      return <div id="loading"><i className="fa fa-spinner fa-spin"/><br/>Loading...</div>
    }
  }
}

export default Data;
