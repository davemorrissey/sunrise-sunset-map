function DatePicker(container, callback) {

    var dayArrayShort = [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ];
    var monthArrayShort = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];

    var date = new Date();
    var calendar = new Date();

    var renderCalendar = function(diff) {

        if (diff) {
            var newMonth = (calendar.getMonth() + diff) % 12;
            var newYear = calendar.getFullYear() + parseInt((calendar.getMonth() + diff) / 12);
            if (newMonth < 0) {
                newMonth += 12;
                newYear += -1;
            }
            calendar = new Date(newYear, newMonth, 1);
        }
        var thisDate = new Date(calendar.getFullYear(), calendar.getMonth(), 1);

        var table = $('<table class="datepicker"></table>');

        // Forward/backward arrows and current month header
        var  thead = $('<tr>' +
            '<td><a id="dp-subyear"><i class="fa fa-angle-double-left"></i></a></td>' +
            '<td><a id="dp-submonth"><i class="fa fa-angle-left"></i></a></td>' +
            '<td class="title" colspan="3">' + monthArrayShort[calendar.getMonth()] + ' ' + calendar.getFullYear() + '</td>' +
            '<td><a id="dp-addmonth"><i class="fa fa-angle-right"></i></a></td>' +
            '<td><a id="dp-addyear"><i class="fa fa-angle-double-right"></i></a></td>' +
            '</tr>');
        thead.find('#dp-subyear').on('click', function() { renderCalendar(-12); });
        thead.find('#dp-submonth').on('click', function() { renderCalendar(-1); });
        thead.find('#dp-addmonth').on('click', function() { renderCalendar(1); });
        thead.find('#dp-addyear').on('click', function() { renderCalendar(12); });
        table.append(thead);

        // Weekdays row
        var row = $('<tr></tr>');
        var i;
        for (i = 0; i < dayArrayShort.length; i++) {
            row.append($('<th>' + dayArrayShort[i] + '</th>'));
        }
        table.append(row);

        // Date rows
        row = $('<tr></tr>');

        // Leading blanks
        for (i = 0; i < thisDate.getDay(); i++) {
            row.append('<td></td>');
        }

        var weeks = 1;

        // Days of the month
        do {
            if (!row) {
                row = $('<tr></tr>');
            }

            (function(thisDate) {
                var cellDate = new Date(thisDate.getFullYear(), thisDate.getMonth(), thisDate.getDate());
                var dayCell = $('<td class="date"></td>');
                var dayLink = $('<a></a>');
                dayLink.text(cellDate.getDate());
                dayLink.on('click', function() { selectDate(cellDate); });
                if (getDateString(date) == getDateString(cellDate)) {
                    dayLink.addClass('selected');
                }
                dayCell.append(dayLink);
                row.append(dayCell);
            })(thisDate);

            var nextDay = new Date(thisDate.getTime());
            nextDay.setDate(nextDay.getDate() + 1);

            // Start a new row after Saturday
            if (thisDate.getDay() == 6 && nextDay.getDate() > 1) {
                table.append(row);
                row = null;
                weeks++;
            }

            // Increment the day
            thisDate.setDate(thisDate.getDate() + 1);
        } while (thisDate.getDate() > 1);

        // Append the last row
        if (row) {
            table.append(row);
        }

        // Make sure 6 rows are included so the height will not vary.
        while (weeks < 6) {
            table.append($('<tr><td><a>&nbsp;</a></td></tr>'));
            weeks++;
        }

        container.empty();
        container.append(table);
    };

    var selectDate = function(newDate) {
        date = newDate;
        calendar = new Date(date.getFullYear(), date.getMonth(), 1);
        callback(newDate);
        renderCalendar();
    };

    var getDateString = function(dateVal) {
        var dayString = '00' + dateVal.getDate();
        var monthString = '00' + (dateVal.getMonth()+1);
        dayString = dayString.substring(dayString.length - 2);
        monthString = monthString.substring(monthString.length - 2);
        return dateVal.getFullYear() + '-' + monthString + '-' + dayString;
    };

    renderCalendar();

    this.updateDate = function(newDate) {
        date = newDate;
        calendar = new Date(date.getFullYear(), date.getMonth(), 1);
        renderCalendar();
    }

}

DatePicker.prototype.setDate = function(date) {
    this.updateDate(date);
};