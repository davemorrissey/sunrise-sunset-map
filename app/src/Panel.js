import React, { Component } from 'react';
import Welcome from './welcome/Welcome';
import Data from './data/Data';
import './Panel.css';

class Panel extends Component {

  componentWillReceiveProps(nextProps) {
    this.setState({location: nextProps.location});
  }

  render() {
    return (
      <div id="panel">
        { !this.props.location && <Welcome setMapCenter={this.props.setMapCenter} setLocation={this.props.setLocation}/> }
        { this.props.location && <Data location={this.state.location}/> }
      </div>
    );
  }

}

export default Panel;
