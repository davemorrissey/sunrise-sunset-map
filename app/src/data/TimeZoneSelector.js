import React, {Component} from "react";
import './TimeZoneSelector.css';
import axios from 'axios';

class TimeZoneSelector extends Component {

  constructor(props) {
    super(props);
    this.state = { state: null };
  }

  componentDidMount() {
    axios.get('http://testsunrisesunsetmap.com/timezones.php') // FIXME use relative
      .then(response => {
        this.setState({timeZones: response.data})
      })
      .catch(error => {
        console.error('Failed to load time zones', error);
        this.setState({timeZones: {"id": "UTC", "offset": "UTC", "name": "Coordinated Universal Time"}});
      });
  }

  show() {
    this.setState({ state: 'open'});
  }

  hide() {
    this.setState({ state: 'close' });
  }

  setTimeZone(timeZone) {
    this.props.setTimeZone(timeZone);
    this.hide();
  }

  render() {
    const { state, timeZones } = this.state;
    const { timeZoneMatches } = this.props;
    if (!timeZones) {
      return <div/>;
    }
    let className = '';
    if (state === 'open') {
      className = 'slide-in';
    } else if (state === 'close') {
      className = 'slide-out';
    }

    let matches = '';
    if (timeZoneMatches && timeZoneMatches.length > 0) {
      matches = (
        <div id="tzmatchcontainer">
          <h2>Best matches</h2>
          <div id="tzmatchlist">
            {
              timeZoneMatches.map(tz => <a key={tz.id} onClick={this.setTimeZone.bind(this, tz)}><span>{tz.offset}</span>{tz.name}</a>)
            }
          </div>
          <h2>All time zones</h2>
        </div>
      )
    }

    return (
      <div id="tzselection" className={className}>
        <h1>Time zone selection <a onClick={this.hide.bind(this)}><i className="fa fa-times"/></a></h1>
        <div id="tzlistswrapper">
          {matches}
          <div id="tzalllist">
            {
              timeZones.map(tz => <a key={tz.id} onClick={this.setTimeZone.bind(this, tz)}><span>{tz.offset}</span>{tz.name}</a>)
            }
          </div>
        </div>
      </div>
    )

  }
}

export default TimeZoneSelector;
