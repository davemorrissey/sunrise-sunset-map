import React, { Component } from 'react';
import Map from './Map';
import Panel from './Panel';
import './App.css';

class App extends Component {
  constructor(props) {
    super(props);
    this.state = { location: null };
    this.setLocation = this.setLocation.bind(this);
    this.setMapCenter = this.setMapCenter.bind(this);
  }

  setLocation(lat, lon, name) {
    console.info(`Set location: ${lat}, ${lon} (${name})`);
    this.setState({
      location: {
        lat: lat,
        lon: lon,
        name: name
      }
    });
    if (name) {
      this.setMapCenter(lat, lon, 9);
    }
  }

  setMapCenter(lat, lon, zoom) {
    console.info(`Set center: ${lat}, ${lon}, ${zoom}`);
    this.refs.map.setCenterAndZoom(lat, lon, zoom);
  }

  render() {
    return (
      <div className="App">
        <Map setLocation={this.setLocation} location={this.state.location} ref="map"/>
        <Panel setMapCenter={this.setMapCenter} setLocation={this.setLocation} location={this.state.location}/>
      </div>
    );
  }
}

export default App;
