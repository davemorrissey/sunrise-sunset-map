import React, { Component } from 'react';
import './Map.css';


class Map extends Component {

  constructor(props) {
    super(props);
    this.api = window.google.maps;
    this.mapTypes = {
      normal: { name: 'Normal', typeId: this.api.MapTypeId.ROADMAP },
      satellite: { name: 'Satellite', typeId: this.api.MapTypeId.SATELLITE },
      hybrid: { name: 'Hybrid', typeId: this.api.MapTypeId.HYBRID },
      terrain: { name: 'Terrain', typeId: this.api.MapTypeId.TERRAIN }
    };
    this.marker = new this.api.Marker({
      icon: new this.api.MarkerImage('https://sunrisesunsetmap.com/img/pushpin.png',
        new this.api.Size(32, 32),
        new this.api.Point(0,0),
        new this.api.Point(9, 32)),
      shadow: new this.api.MarkerImage('https://sunrisesunsetmap.com/img/pushpin_shadow.png',
        new this.api.Size(59, 32),
        new this.api.Point(0,0),
        new this.api.Point(9, 32))
    });
    this.state = {
      showTypeSelector: false,
      mapType: this.mapTypes['normal']
    };
  }

  zoomIn() {
    if (this.map) {
      this.map.setZoom(this.map.getZoom() + 1);
    }
  }

  zoomOut() {
    if (this.map) {
      this.map.setZoom(this.map.getZoom() - 1);
    }
  }

  setMapType(type) {
    let newMapType = this.mapTypes[type];
    this.map.setMapTypeId(newMapType.typeId);
    this.setState({
      showTypeSelector: false,
      mapType: newMapType
    });
  }

  showMapTypeSelector() {
    this.setState(prevState => ({
      showTypeSelector: true
    }));
  }

  componentDidMount() {
    if (this.mapElement) {
      var mapOptions = {
        center: new this.api.LatLng(54.5, -3.5),
        zoom: 5,
        mapTypeId: this.api.MapTypeId.ROADMAP,
        disableDefaultUI: true
      };
      this.map = new this.api.Map(this.mapElement, mapOptions);
      this.api.event.addListener(this.map, 'click', function(event) {
        if (event.latLng) {
          this.props.setLocation(event.latLng.lat(), event.latLng.lng());
        }
      }.bind(this));
    }
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.location && this.map) {
      this.marker.setPosition(new this.api.LatLng(nextProps.location.lat, nextProps.location.lon));
      this.marker.setMap(this.map);
    }
  }

  setCenterAndZoom(lat, lon, zoom) {
    if (this.map) {
      this.map.setCenter(new this.api.LatLng(lat, lon));
      this.map.setZoom(zoom);
    }
  }

  render() {
    return (
      <div id="map-container">
        <div id="map-controls">
          <a onClick={this.zoomOut.bind(this)} id="zoom-out"><i className="fa fa-minus"/></a>
          <a onClick={this.zoomIn.bind(this)} id="zoom-in"><i className="fa fa-plus"/></a>
          <a onClick={this.showMapTypeSelector.bind(this)} id="type-select">{this.state.mapType.name}<i className="fa fa-caret-down"/></a>
          <div id="type-selection" className={this.state.showTypeSelector ? 'active' : 'inactive'}>
            <ul>
              <li><a onClick={this.setMapType.bind(this, 'normal')}>Normal</a></li>
              <li><a onClick={this.setMapType.bind(this, 'satellite')}>Satellite</a></li>
              <li><a onClick={this.setMapType.bind(this, 'hybrid')}>Hybrid</a></li>
              <li><a onClick={this.setMapType.bind(this, 'terrain', this)}>Terrain</a></li>
            </ul>
          </div>
        </div>
        <div id="map" ref={(map) => { this.mapElement = map; }}/>
      </div>
    );
  }
}

export default Map;
