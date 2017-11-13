import React, {Component} from "react";
import './DateSelector.css';

class DateSelector extends Component {

  constructor(props) {
    super(props);
    this.state = { type: 'buttons', calendar: new Date() };
  }

  dayArrayShort = [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ];
  dayArrayMed = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
  monthArrayShort = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
  monthArrayLong = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];
  
  toggleDatePicker() {
    this.setState(prevState => ({
      type: prevState.type === 'buttons' ? 'calendar' : 'buttons'
    }));
  }

  dateDiff(diff) {
    let date = this.props.date;
    switch (diff) {
      case '-m':
        date.setMonth(date.getMonth() - 1);
        break;
      case '+m':
        date.setMonth(date.getMonth() + 1);
        break;
      case '-w':
        date.setDate(date.getDate() - 7);
        break;
      case '+w':
        date.setDate(date.getDate() + 7);
        break;
      case '-d':
        date.setDate(date.getDate() - 1);
        break;
      case '+d':
        date.setDate(date.getDate() + 1);
        break;
      default:
        return;
    }
    this.props.setDate(date);
  }

  calendarMove(months) {
    let calendar = this.state.calendar;
    var newMonth = (calendar.getMonth() + months) % 12;
    var newYear = calendar.getFullYear() + parseInt((calendar.getMonth() + months) / 12, 10);
    if (newMonth < 0) {
      newMonth += 12;
      newYear += -1;
    }
    this.setState({ calendar: new Date(newYear, newMonth, 1) });
  }

  dateDmy(date) {
    return date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
  }
  
  render() {
    let nav = null;
    if (this.state.type === 'buttons') {

      nav = <div className="buttons">
        <button onClick={this.dateDiff.bind(this, '-m')}><i className="fa fa-caret-left"/> M</button>
        <button onClick={this.dateDiff.bind(this, '-w')}><i className="fa fa-caret-left"/> W</button>
        <button onClick={this.dateDiff.bind(this, '-d')}><i className="fa fa-caret-left"/> D</button>
        <button onClick={this.dateDiff.bind(this, '+d')}>D <i className="fa fa-caret-right"/></button>
        <button onClick={this.dateDiff.bind(this, '+w')}>W <i className="fa fa-caret-right"/></button>
        <button onClick={this.dateDiff.bind(this, '+m')}>M <i className="fa fa-caret-right"/></button>
      </div>

    } else {

      var thisDate = new Date(this.state.calendar.getFullYear(), this.state.calendar.getMonth(), 1);

      let rows = [];
      for (var r = 0; r < 6; r++) {
        let row = [];
        for (var c = 0; c < 7; c++) {
          if (r === 0 && c < thisDate.getDay()) {
            // Leading blanks
            row.push(<td key={''+r+c}>&nbsp;</td>);
          } else if (thisDate.getMonth() === this.state.calendar.getMonth()) {
            // Date cells
            var cellDate = new Date(thisDate.getFullYear(), thisDate.getMonth(), thisDate.getDate());
            var className = this.dateDmy(this.props.date) === this.dateDmy(cellDate) ? 'selected' : '';
            row.push(<td key={''+r+c} className="date"><a onClick={this.props.setDate.bind(this, cellDate)} className={className}>{cellDate.getDate()}</a></td>);
            thisDate.setDate(thisDate.getDate() + 1);
          } else {
            // Trailing blanks
            row.push(<td key={''+r+c}><a>&nbsp;</a></td>);
          }
        }
        rows.push(<tr key={r}>{row}</tr>);
      }

      nav = <table className="picker">
          <thead>
            <tr>
              <td><a onClick={this.calendarMove.bind(this, -12)}><i className="fa fa-angle-double-left"/></a></td>
              <td><a onClick={this.calendarMove.bind(this, -1)}><i className="fa fa-angle-left"/></a></td>
              <td className="title" colSpan="3">{this.monthArrayShort[this.state.calendar.getMonth()] + ' ' + this.state.calendar.getFullYear()}</td>
              <td><a onClick={this.calendarMove.bind(this, 1)}><i className="fa fa-angle-right"/></a></td>
              <td><a onClick={this.calendarMove.bind(this, 12)}><i className="fa fa-angle-double-right"/></a></td>
            </tr>
            <tr>
              { this.dayArrayShort.map(dayShort => <th key={dayShort}>{dayShort}</th>) }
            </tr>
          </thead>
          <tbody>
            { rows }
          </tbody>
        </table>;

    }

    let dateLong = this.dayArrayMed[this.props.date.getDay()] + ' ' + this.props.date.getDate() + ' ' + this.monthArrayLong[this.props.date.getMonth()] + ' ' + this.props.date.getFullYear();

    return (
      <div id="dateselector">
        <div className="current">{dateLong}<a className="toggle" onClick={this.toggleDatePicker.bind(this)}><i className="fa fa-calendar"/></a></div>
        {nav}
      </div>
    );
  }
}

export default DateSelector;
