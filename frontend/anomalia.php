<?php 

/**
 * get_time_form_data is supporting function of timewindow selection panel
 * It checks if a form field given in argument was sent and returns its value. 
 * If it wasn't, default value is returned.
 */
function get_time_form_data($field_name, $default) {

        if (isset($_POST[$field_name])) {
                return $_POST[$field_name];
        } else {
                return $default;
        }
}


/**
 * anomalia_PrintHeaders is called at the beginning of anomalia_Run function
 * and prints links to JavaScript and CSS files and prints javascript code
 */
function anomalia_PrintHeaders() {
	print '
	<!-- ########## JS FILES & LIBRARIES ########### -->
	<script language="Javascript" src="plugins/anomalia/js/jquery-min.js" type="text/javascript"></script>
	<script language="Javascript" src="plugins/anomalia/js/jquery-ui.min.js" type="text/javascript"></script>
	<script language="Javascript" src="plugins/anomalia/js/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
	<script language="Javascript" src="plugins/anomalia/js/jquery.easytabs.min.js" type="text/javascript"></script>
	<script language="Javascript" src="plugins/anomalia/js/highcharts.js" type="text/javascript"></script>

	<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="plugins/anomalia/js/excanvas.min.js"></script><![endif]-->
	<script language="Javascript" src="plugins/anomalia/js/jquery.flot.js" type="text/javascript"></script>
	<!-- ########## CSS FILES & LIBRARIES ########### -->
	<link href="plugins/anomalia/css/jquery-ui.css" rel="stylesheet" type="text/css">
	<link href="plugins/anomalia/css/anomalia.css" rel="stylesheet" type="text/css">
	<script language="Javascript">
	$(document).ready( function() {

		// Activate EasyTabs on #tab-container element
		$("#tab-container").easytabs({animate: false, updateHash: false });
		
		// Time format convertor for time-input-panel
		function reformate_time(timeString) {
			return timeString.replace(/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/,"$2/$3/$1 $4:$5");
		}

		// Datetime format function for time-input-panel
		function getFormatedDateTime(d) {
			var year = d.getFullYear();
			var month_num = d.getMonth() +1;
			if (month_num < 10) month = "0" + month_num; else month = "" + month_num;
			var day_num = d.getDate();
			if (day_num < 10) day = "0" + day_num; else day = "" + day_num;
			var hours_num = d.getHours();
			if (hours_num < 10) hours = "0" + hours_num; else hours = "" + hours_num;
			var minutes_num = d.getMinutes();
			if (minutes_num < 10) minutes = "0" + minutes_num; else minutes = "" + minutes_num;
			rval = "" + year + "-" + month + "-" + day + " " + hours + ":" + minutes;
			return rval;
		}

	
		$("#end_datetime_syndata").datetimepicker({
                	dateFormat: "yy-mm-dd", timeFormat: "hh:mm", firstDay: 1, stepMinute: 5,
		});
		$("#begin_datetime_syndata").datetimepicker({
                	dateFormat: "yy-mm-dd", timeFormat: "hh:mm", firstDay: 1, stepMinute: 5,
		});

		// according to values of Timewindow and End time sets Begin time and en(dis)ables < > Buttons
		function set_begin_syndata() {
			var d = new Date(reformate_time($("#end_datetime_syndata").val()));
			$("#begin_datetime_syndata").attr("disabled", true);
			$("#prev_button_syndata").attr("disabled", false);
			$("#next_button_syndata").attr("disabled", false);
			switch($("#timewindow_syndata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() -300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() -3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() -21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() -43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() -86400000);
				break;
			case "other":
				$("#begin_datetime_syndata").attr("disabled", false);
				$("#prev_button_syndata").attr("disabled", true);
				$("#next_button_syndata").attr("disabled", true);
				return;
				break;
			default:
				$("#begin_datetime_syndata").val("error");
				return;
			}
			$("#begin_datetime_syndata").val(getFormatedDateTime(d));
		}

		$("#end_datetime_syndata, #timewindow_syndata").bind($.browser.msie ? "propertychange": "change", function() {
			set_begin_syndata();
		});

		$("#prev_button_syndata").click(function(e) {
			var d = new Date(reformate_time($("#end_datetime_syndata").val()));

			switch($("#timewindow_syndata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() -300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() -3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() -21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() -43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() -86400000);
				break;
			case "other":
				return;
				break;
			default:
				$("#begin_datetime_syndata").val("error");
				return;
			}
			$("#end_datetime_syndata").val(getFormatedDateTime(d));
			set_begin_syndata();
		});

		$("#next_button_syndata").click(function(e) {
			var d = new Date(reformate_time($("#end_datetime_syndata").val()));

			switch($("#timewindow_syndata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() +300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() +3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() +21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() +43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() +86400000);
				break;
			case "other":
				return;
				break;
			default:
				$("#begin_datetime_syndata").val("error");
				return;
			}
			$("#end_datetime_syndata").val(getFormatedDateTime(d));
			set_begin_syndata();
		});

		$("#time_form_syndata").submit(function() {
			$("#begin_datetime_syndata").attr("disabled", false);
		});


	
		$("#end_datetime_syncloseddata").datetimepicker({
                	dateFormat: "yy-mm-dd", timeFormat: "hh:mm", firstDay: 1, stepMinute: 5,
		});
		$("#begin_datetime_syncloseddata").datetimepicker({
                	dateFormat: "yy-mm-dd", timeFormat: "hh:mm", firstDay: 1, stepMinute: 5,
		});

		// according to values of Timewindow and End time sets Begin time and en(dis)ables < > Buttons
		function set_begin_syncloseddata() {
			var d = new Date(reformate_time($("#end_datetime_syncloseddata").val()));
			$("#begin_datetime_syncloseddata").attr("disabled", true);
			$("#prev_button_syncloseddata").attr("disabled", false);
			$("#next_button_syncloseddata").attr("disabled", false);
			switch($("#timewindow_syncloseddata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() -300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() -3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() -21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() -43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() -86400000);
				break;
			case "other":
				$("#begin_datetime_syncloseddata").attr("disabled", false);
				$("#prev_button_syncloseddata").attr("disabled", true);
				$("#next_button_syncloseddata").attr("disabled", true);
				return;
				break;
			default:
				$("#begin_datetime_syncloseddata").val("error");
				return;
			}
			$("#begin_datetime_syncloseddata").val(getFormatedDateTime(d));
		}

		$("#end_datetime_syncloseddata, #timewindow_syncloseddata").bind($.browser.msie ? "propertychange": "change", function() {
			set_begin_syncloseddata();
		});

		$("#prev_button_syncloseddata").click(function(e) {
			var d = new Date(reformate_time($("#end_datetime_syncloseddata").val()));

			switch($("#timewindow_syncloseddata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() -300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() -3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() -21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() -43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() -86400000);
				break;
			case "other":
				return;
				break;
			default:
				$("#begin_datetime_syncloseddata").val("error");
				return;
			}
			$("#end_datetime_syncloseddata").val(getFormatedDateTime(d));
			set_begin_syncloseddata();
		});

		$("#next_button_syncloseddata").click(function(e) {
			var d = new Date(reformate_time($("#end_datetime_syncloseddata").val()));

			switch($("#timewindow_syncloseddata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() +300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() +3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() +21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() +43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() +86400000);
				break;
			case "other":
				return;
				break;
			default:
				$("#begin_datetime_syncloseddata").val("error");
				return;
			}
			$("#end_datetime_syncloseddata").val(getFormatedDateTime(d));
			set_begin_syncloseddata();
		});

		$("#time_form_syncloseddata").submit(function() {
			$("#begin_datetime_syncloseddata").attr("disabled", false);
		});


	
		$("#end_datetime_nulldata").datetimepicker({
                	dateFormat: "yy-mm-dd", timeFormat: "hh:mm", firstDay: 1, stepMinute: 5,
		});
		$("#begin_datetime_nulldata").datetimepicker({
                	dateFormat: "yy-mm-dd", timeFormat: "hh:mm", firstDay: 1, stepMinute: 5,
		});

		// according to values of Timewindow and End time sets Begin time and en(dis)ables < > Buttons
		function set_begin_nulldata() {
			var d = new Date(reformate_time($("#end_datetime_nulldata").val()));
			$("#begin_datetime_nulldata").attr("disabled", true);
			$("#prev_button_nulldata").attr("disabled", false);
			$("#next_button_nulldata").attr("disabled", false);
			switch($("#timewindow_nulldata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() -300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() -3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() -21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() -43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() -86400000);
				break;
			case "other":
				$("#begin_datetime_nulldata").attr("disabled", false);
				$("#prev_button_nulldata").attr("disabled", true);
				$("#next_button_nulldata").attr("disabled", true);
				return;
				break;
			default:
				$("#begin_datetime_nulldata").val("error");
				return;
			}
			$("#begin_datetime_nulldata").val(getFormatedDateTime(d));
		}

		$("#end_datetime_nulldata, #timewindow_nulldata").bind($.browser.msie ? "propertychange": "change", function() {
			set_begin_nulldata();
		});

		$("#prev_button_nulldata").click(function(e) {
			var d = new Date(reformate_time($("#end_datetime_nulldata").val()));

			switch($("#timewindow_nulldata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() -300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() -3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() -21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() -43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() -86400000);
				break;
			case "other":
				return;
				break;
			default:
				$("#begin_datetime_nulldata").val("error");
				return;
			}
			$("#end_datetime_nulldata").val(getFormatedDateTime(d));
			set_begin_nulldata();
		});

		$("#next_button_nulldata").click(function(e) {
			var d = new Date(reformate_time($("#end_datetime_nulldata").val()));

			switch($("#timewindow_nulldata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() +300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() +3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() +21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() +43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() +86400000);
				break;
			case "other":
				return;
				break;
			default:
				$("#begin_datetime_nulldata").val("error");
				return;
			}
			$("#end_datetime_nulldata").val(getFormatedDateTime(d));
			set_begin_nulldata();
		});

		$("#time_form_nulldata").submit(function() {
			$("#begin_datetime_nulldata").attr("disabled", false);
		});


	
		$("#end_datetime_udpdata").datetimepicker({
                	dateFormat: "yy-mm-dd", timeFormat: "hh:mm", firstDay: 1, stepMinute: 5,
		});
		$("#begin_datetime_udpdata").datetimepicker({
                	dateFormat: "yy-mm-dd", timeFormat: "hh:mm", firstDay: 1, stepMinute: 5,
		});

		// according to values of Timewindow and End time sets Begin time and en(dis)ables < > Buttons
		function set_begin_udpdata() {
			var d = new Date(reformate_time($("#end_datetime_udpdata").val()));
			$("#begin_datetime_udpdata").attr("disabled", true);
			$("#prev_button_udpdata").attr("disabled", false);
			$("#next_button_udpdata").attr("disabled", false);
			switch($("#timewindow_udpdata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() -300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() -3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() -21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() -43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() -86400000);
				break;
			case "other":
				$("#begin_datetime_udpdata").attr("disabled", false);
				$("#prev_button_udpdata").attr("disabled", true);
				$("#next_button_udpdata").attr("disabled", true);
				return;
				break;
			default:
				$("#begin_datetime_udpdata").val("error");
				return;
			}
			$("#begin_datetime_udpdata").val(getFormatedDateTime(d));
		}

		$("#end_datetime_udpdata, #timewindow_udpdata").bind($.browser.msie ? "propertychange": "change", function() {
			set_begin_udpdata();
		});

		$("#prev_button_udpdata").click(function(e) {
			var d = new Date(reformate_time($("#end_datetime_udpdata").val()));

			switch($("#timewindow_udpdata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() -300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() -3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() -21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() -43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() -86400000);
				break;
			case "other":
				return;
				break;
			default:
				$("#begin_datetime_udpdata").val("error");
				return;
			}
			$("#end_datetime_udpdata").val(getFormatedDateTime(d));
			set_begin_udpdata();
		});

		$("#next_button_udpdata").click(function(e) {
			var d = new Date(reformate_time($("#end_datetime_udpdata").val()));

			switch($("#timewindow_udpdata").val())
			{
			case "5 minutes":
				d.setTime(d.getTime() +300000);
				break;
			case "1 hour":
				d.setTime(d.getTime() +3600000);
				break;
			case "6 hours":
				d.setTime(d.getTime() +21600000);
				break;
			case "12 hours":
				d.setTime(d.getTime() +43200000);
				break;
			case "1 day":
				d.setTime(d.getTime() +86400000);
				break;
			case "other":
				return;
				break;
			default:
				$("#begin_datetime_udpdata").val("error");
				return;
			}
			$("#end_datetime_udpdata").val(getFormatedDateTime(d));
			set_begin_udpdata();
		});

		$("#time_form_udpdata").submit(function() {
			$("#begin_datetime_udpdata").attr("disabled", false);
		});

	});
	</script>
<link href="plugins/anomalia/css/ts_style.css" rel="stylesheet" type="text/css" media="screen" />
<script type="text/javascript" src="plugins/anomalia/js/jquery.tablesorter.min.js"></script>
<script type="text/javascript">

$(document).ready(function() {
        $.tablesorter.addParser({
                id: "ipAddr",
                is: function(s) { return /^\d{1,3}[\.]\d{1,3}[\.]\d{1,3}[\.]\d{1,3}$/.test(s); },

                format: function(s) {
                        var a = s.split("."), r = 0, l = a.length;
                        for(var i = 0; i < l; i++) {
                                var item = a[i];
                                if(item.length == 1) {
                                        r += "00" + item;
                                } else if(item.length == 2) {
                                        r += "0" + item;
                                } else {
                                        r += item;
                                }
                        }
                        return $.tablesorter.formatFloat(r);
                },
                type: "numeric"
        });

        $("#sortable_table").tablesorter({headers: {0: {sorter: "ipAddr"}, 1: {sorter: "text"}}}); 
});
        </script>



	';
}


/**
 * anomalia_PrintTimeInputPanel_syndata function is called from the place
 * in anomalia_Run function where the Time input panel is to be printed. It prints
 * the HTML code of the panel. JavaScript code operating the panel is printed by 
 * anomalia_PrintHeaders function.
 *
 * @param string $beginning Begin of interval in YYYY-MM-DD HH:MM format
 * @param string $ending End of interval in YYYY-MM-DD HH:MM format
 * @param string $timewindow Timewindow as a string (e.g. 5 minutes) 
 */
function anomalia_PrintTimeInputPanel_syndata($beginning, $ending, $timewindow) {

        // Should the input box be disabled when reloading the page? (i.e. is the "other" timewindow selected)?
        if("other" == $timewindow) { $begin_disabled = ""; } else { $begin_disabled = "disabled"; }

	// Prepare <option> tags with the timewindow sizes
        $options = array("other","1 hour","6 hours","12 hours","1 day");
        $options_code = "";
        foreach ($options as $option) {
                if($option == $timewindow) {
                        $options_code .= "\t\t\t<option value=\"$option\" selected=\"selected\">$option</option>\n";
                } else {
                        $options_code .= "\t\t\t<option value=\"$option\">$option</option>\n";
                }
        }

        // Print panel itself
        print '

<form action="" id="time_form_syndata" method="POST">
<div class="time_select_panel">
<table cellpadding="0" cellspacing="0" border="0">
<tr>
        <td>
                Begin:<br>
                <input name="begin_datetime_syndata" id="begin_datetime_syndata" '.$begin_disabled.' type="text" size="16" value="'.$beginning.'">
        </td><td>
                <br>
                <input type="button" id="prev_button_syndata" value="&lt;" title="Select previous timewindow" style="width: 2em;">
        </td><td>
                Timewindow:<br>
                <select name="timewindow_syndata" id="timewindow_syndata">'.$options_code.'</select>
        </td><td>
                <br>
                <input type="button" id="next_button_syndata" value="&gt;" title="Select next timewindow" style="width: 2em;">
        </td><td>
                End:<br>
                <input name="end_datetime_syndata" id="end_datetime_syndata" type="text" size="15" value="'.$ending.'">
        </td><td>
                <div style="width: 30px;">&nbsp;</div>
        </td><td>
                <br>
                <input type="submit" value="Show data for selected interval">
        </td><td>
                <div style="width: 30px;">&nbsp;</div>
        </td>
</tr>
</table>
</div>
</form>

<div class="ui-state-highlight ui-corner-all timewindow-info">
<span class="ui-icon ui-icon-info"></span>
	Shown results for the timewindow from <b>'. $beginning .' </b> to <b> '. $ending .'</b>
</div>';


}

/**
 * anomalia_PrintTimeInputPanel_syncloseddata function is called from the place
 * in anomalia_Run function where the Time input panel is to be printed. It prints
 * the HTML code of the panel. JavaScript code operating the panel is printed by 
 * anomalia_PrintHeaders function.
 *
 * @param string $beginning Begin of interval in YYYY-MM-DD HH:MM format
 * @param string $ending End of interval in YYYY-MM-DD HH:MM format
 * @param string $timewindow Timewindow as a string (e.g. 5 minutes) 
 */
function anomalia_PrintTimeInputPanel_syncloseddata($beginning, $ending, $timewindow) {

        // Should the input box be disabled when reloading the page? (i.e. is the "other" timewindow selected)?
        if("other" == $timewindow) { $begin_disabled = ""; } else { $begin_disabled = "disabled"; }

	// Prepare <option> tags with the timewindow sizes
        $options = array("other","1 hour","6 hours","12 hours","1 day");
        $options_code = "";
        foreach ($options as $option) {
                if($option == $timewindow) {
                        $options_code .= "\t\t\t<option value=\"$option\" selected=\"selected\">$option</option>\n";
                } else {
                        $options_code .= "\t\t\t<option value=\"$option\">$option</option>\n";
                }
        }

        // Print panel itself
        print '

<form action="" id="time_form_syncloseddata" method="POST">
<div class="time_select_panel">
<table cellpadding="0" cellspacing="0" border="0">
<tr>
        <td>
                Begin:<br>
                <input name="begin_datetime_syncloseddata" id="begin_datetime_syncloseddata" '.$begin_disabled.' type="text" size="16" value="'.$beginning.'">
        </td><td>
                <br>
                <input type="button" id="prev_button_syncloseddata" value="&lt;" title="Select previous timewindow" style="width: 2em;">
        </td><td>
                Timewindow:<br>
                <select name="timewindow_syncloseddata" id="timewindow_syncloseddata">'.$options_code.'</select>
        </td><td>
                <br>
                <input type="button" id="next_button_syncloseddata" value="&gt;" title="Select next timewindow" style="width: 2em;">
        </td><td>
                End:<br>
                <input name="end_datetime_syncloseddata" id="end_datetime_syncloseddata" type="text" size="15" value="'.$ending.'">
        </td><td>
                <div style="width: 30px;">&nbsp;</div>
        </td><td>
                <br>
                <input type="submit" value="Show data for selected interval">
        </td><td>
                <div style="width: 30px;">&nbsp;</div>
        </td>
</tr>
</table>
</div>
</form>

<div class="ui-state-highlight ui-corner-all timewindow-info">
<span class="ui-icon ui-icon-info"></span>
	Shown results for the timewindow from <b>'. $beginning .' </b> to <b> '. $ending .'</b>
</div>';


}

/**
 * anomalia_PrintTimeInputPanel_nulldata function is called from the place
 * in anomalia_Run function where the Time input panel is to be printed. It prints
 * the HTML code of the panel. JavaScript code operating the panel is printed by 
 * anomalia_PrintHeaders function.
 *
 * @param string $beginning Begin of interval in YYYY-MM-DD HH:MM format
 * @param string $ending End of interval in YYYY-MM-DD HH:MM format
 * @param string $timewindow Timewindow as a string (e.g. 5 minutes) 
 */
function anomalia_PrintTimeInputPanel_nulldata($beginning, $ending, $timewindow) {

        // Should the input box be disabled when reloading the page? (i.e. is the "other" timewindow selected)?
        if("other" == $timewindow) { $begin_disabled = ""; } else { $begin_disabled = "disabled"; }

	// Prepare <option> tags with the timewindow sizes
        $options = array("other","1 hour","6 hours","12 hours","1 day");
        $options_code = "";
        foreach ($options as $option) {
                if($option == $timewindow) {
                        $options_code .= "\t\t\t<option value=\"$option\" selected=\"selected\">$option</option>\n";
                } else {
                        $options_code .= "\t\t\t<option value=\"$option\">$option</option>\n";
                }
        }

        // Print panel itself
        print '

<form action="" id="time_form_nulldata" method="POST">
<div class="time_select_panel">
<table cellpadding="0" cellspacing="0" border="0">
<tr>
        <td>
                Begin:<br>
                <input name="begin_datetime_nulldata" id="begin_datetime_nulldata" '.$begin_disabled.' type="text" size="16" value="'.$beginning.'">
        </td><td>
                <br>
                <input type="button" id="prev_button_nulldata" value="&lt;" title="Select previous timewindow" style="width: 2em;">
        </td><td>
                Timewindow:<br>
                <select name="timewindow_nulldata" id="timewindow_nulldata">'.$options_code.'</select>
        </td><td>
                <br>
                <input type="button" id="next_button_nulldata" value="&gt;" title="Select next timewindow" style="width: 2em;">
        </td><td>
                End:<br>
                <input name="end_datetime_nulldata" id="end_datetime_nulldata" type="text" size="15" value="'.$ending.'">
        </td><td>
                <div style="width: 30px;">&nbsp;</div>
        </td><td>
                <br>
                <input type="submit" value="Show data for selected interval">
        </td><td>
                <div style="width: 30px;">&nbsp;</div>
        </td>
</tr>
</table>
</div>
</form>

<div class="ui-state-highlight ui-corner-all timewindow-info">
<span class="ui-icon ui-icon-info"></span>
	Shown results for the timewindow from <b>'. $beginning .' </b> to <b> '. $ending .'</b>
</div>';


}

/**
 * anomalia_PrintTimeInputPanel_udpdata function is called from the place
 * in anomalia_Run function where the Time input panel is to be printed. It prints
 * the HTML code of the panel. JavaScript code operating the panel is printed by 
 * anomalia_PrintHeaders function.
 *
 * @param string $beginning Begin of interval in YYYY-MM-DD HH:MM format
 * @param string $ending End of interval in YYYY-MM-DD HH:MM format
 * @param string $timewindow Timewindow as a string (e.g. 5 minutes) 
 */
function anomalia_PrintTimeInputPanel_udpdata($beginning, $ending, $timewindow) {

        // Should the input box be disabled when reloading the page? (i.e. is the "other" timewindow selected)?
        if("other" == $timewindow) { $begin_disabled = ""; } else { $begin_disabled = "disabled"; }

	// Prepare <option> tags with the timewindow sizes
        $options = array("other","1 hour","6 hours","12 hours","1 day");
        $options_code = "";
        foreach ($options as $option) {
                if($option == $timewindow) {
                        $options_code .= "\t\t\t<option value=\"$option\" selected=\"selected\">$option</option>\n";
                } else {
                        $options_code .= "\t\t\t<option value=\"$option\">$option</option>\n";
                }
        }

        // Print panel itself
        print '

<form action="" id="time_form_udpdata" method="POST">
<div class="time_select_panel">
<table cellpadding="0" cellspacing="0" border="0">
<tr>
        <td>
                Begin:<br>
                <input name="begin_datetime_udpdata" id="begin_datetime_udpdata" '.$begin_disabled.' type="text" size="16" value="'.$beginning.'">
        </td><td>
                <br>
                <input type="button" id="prev_button_udpdata" value="&lt;" title="Select previous timewindow" style="width: 2em;">
        </td><td>
                Timewindow:<br>
                <select name="timewindow_udpdata" id="timewindow_udpdata">'.$options_code.'</select>
        </td><td>
                <br>
                <input type="button" id="next_button_udpdata" value="&gt;" title="Select next timewindow" style="width: 2em;">
        </td><td>
                End:<br>
                <input name="end_datetime_udpdata" id="end_datetime_udpdata" type="text" size="15" value="'.$ending.'">
        </td><td>
                <div style="width: 30px;">&nbsp;</div>
        </td><td>
                <br>
                <input type="submit" value="Show data for selected interval">
        </td><td>
                <div style="width: 30px;">&nbsp;</div>
        </td>
</tr>
</table>
</div>
</form>

<div class="ui-state-highlight ui-corner-all timewindow-info">
<span class="ui-icon ui-icon-info"></span>
	Shown results for the timewindow from <b>'. $beginning .' </b> to <b> '. $ending .'</b>
</div>';


}

/*
 *anomalia_get_highchart_syndata function is called from the anomalia_Run function.
 * It takes one argument - array otps with options for backend function. It must
 * contain name of the graph (under graph_name key) and unix timestamps of begin
 * and end of the displayed data (under keys begin and end).
 *
 * The function will request data from backend and returns JavaScript code that
 * will draw the graph with Highcharts library.
 */
function anomalia_get_highchart_syndata($opts) {

	// Request the data for the graph from backend function feedGraph
        $out_list = nfsend_query("anomalia::feed_graph", $opts);
        if ( !is_array($out_list) ) {
                print "Error calling backend plugin - feed_graph\n";return FALSE;
        }
        $data_line1      = $out_list["line1"];
        $data_line2      = $out_list["line2"];

	// Generate JavaScript arrays definitions of graph lines
        $js_code = "line1 = [";
        $js_code .= join(',',$data_line1);
        $js_code .= "];";
        $js_code .= "line2 = [";
        $js_code .= join(',',$data_line2);
        $js_code .= "];";

        $js_code .= '

var current_date = new Date();
var current_timezone = current_date.getTimezoneOffset();

chart1 = new Highcharts.Chart({
	chart: {
		renderTo: "highchart-syndata",
		type: "line",
                borderWidth: 1,
                plotBorderWidth: 1
	},
	title: {
		text: "SYN Data",
		style: { color: "black" }

	},
	plotOptions: {
                line: {  marker: { enabled: true,lineWidth: 1,radius: 2 }}

	},
	tooltip: {
		formatter: function() {
			return \'\'+
			Highcharts.dateFormat(\'%e. %b %Y, %H:%M\', this.x) +\': \'+ this.y;
		}
	},
	xAxis: {
		type: "datetime"
	},
	yAxis: {
		title: {
			text: "Quantity"
		}
	},
	series: [
		{ name: "All", data: line1,  pointStart: ('.$opts["begin"].'*1000 - current_timezone * 60 * 1000) ,pointInterval: 5 * 60 * 1000 },
		{ name: "Alert", data: line2,  pointStart: ('.$opts["begin"].'*1000 - current_timezone * 60 * 1000) ,pointInterval: 5 * 60 * 1000 },
	]
});
	';
	return $js_code;
}


/*
 *anomalia_get_highchart_syncloseddata function is called from the anomalia_Run function.
 * It takes one argument - array otps with options for backend function. It must
 * contain name of the graph (under graph_name key) and unix timestamps of begin
 * and end of the displayed data (under keys begin and end).
 *
 * The function will request data from backend and returns JavaScript code that
 * will draw the graph with Highcharts library.
 */
function anomalia_get_highchart_syncloseddata($opts) {

	// Request the data for the graph from backend function feedGraph
        $out_list = nfsend_query("anomalia::feed_graph", $opts);
        if ( !is_array($out_list) ) {
                print "Error calling backend plugin - feed_graph\n";return FALSE;
        }
        $data_line1      = $out_list["line1"];
        $data_line2      = $out_list["line2"];

	// Generate JavaScript arrays definitions of graph lines
        $js_code = "line1 = [";
        $js_code .= join(',',$data_line1);
        $js_code .= "];";
        $js_code .= "line2 = [";
        $js_code .= join(',',$data_line2);
        $js_code .= "];";

        $js_code .= '

var current_date = new Date();
var current_timezone = current_date.getTimezoneOffset();

chart1 = new Highcharts.Chart({
	chart: {
		renderTo: "highchart-syncloseddata",
		type: "line",
                borderWidth: 1,
                plotBorderWidth: 1
	},
	title: {
		text: "SYN Closed Data",
		style: { color: "black" }

	},
	plotOptions: {
                line: {  marker: { enabled: true,lineWidth: 1,radius: 2 }}

	},
	tooltip: {
		formatter: function() {
			return \'\'+
			Highcharts.dateFormat(\'%e. %b %Y, %H:%M\', this.x) +\': \'+ this.y;
		}
	},
	xAxis: {
		type: "datetime"
	},
	yAxis: {
		title: {
			text: "Quantity"
		}
	},
	series: [
		{ name: "All", data: line1,  pointStart: ('.$opts["begin"].'*1000 - current_timezone * 60 * 1000) ,pointInterval: 5 * 60 * 1000 },
		{ name: "Alert", data: line2,  pointStart: ('.$opts["begin"].'*1000 - current_timezone * 60 * 1000) ,pointInterval: 5 * 60 * 1000 },
	]
});
	';
	return $js_code;
}


/*
 *anomalia_get_highchart_nulldata function is called from the anomalia_Run function.
 * It takes one argument - array otps with options for backend function. It must
 * contain name of the graph (under graph_name key) and unix timestamps of begin
 * and end of the displayed data (under keys begin and end).
 *
 * The function will request data from backend and returns JavaScript code that
 * will draw the graph with Highcharts library.
 */
function anomalia_get_highchart_nulldata($opts) {

	// Request the data for the graph from backend function feedGraph
        $out_list = nfsend_query("anomalia::feed_graph", $opts);
        if ( !is_array($out_list) ) {
                print "Error calling backend plugin - feed_graph\n";return FALSE;
        }
        $data_line1      = $out_list["line1"];
        $data_line2      = $out_list["line2"];

	// Generate JavaScript arrays definitions of graph lines
        $js_code = "line1 = [";
        $js_code .= join(',',$data_line1);
        $js_code .= "];";
        $js_code .= "line2 = [";
        $js_code .= join(',',$data_line2);
        $js_code .= "];";

        $js_code .= '

var current_date = new Date();
var current_timezone = current_date.getTimezoneOffset();

chart1 = new Highcharts.Chart({
	chart: {
		renderTo: "highchart-nulldata",
		type: "line",
                borderWidth: 1,
                plotBorderWidth: 1
	},
	title: {
		text: "NULL Data",
		style: { color: "black" }

	},
	plotOptions: {
                line: {  marker: { enabled: true,lineWidth: 1,radius: 2 }}

	},
	tooltip: {
		formatter: function() {
			return \'\'+
			Highcharts.dateFormat(\'%e. %b %Y, %H:%M\', this.x) +\': \'+ this.y;
		}
	},
	xAxis: {
		type: "datetime"
	},
	yAxis: {
		title: {
			text: "Quantity"
		}
	},
	series: [
		{ name: "All", data: line1,  pointStart: ('.$opts["begin"].'*1000 - current_timezone * 60 * 1000) ,pointInterval: 5 * 60 * 1000 },
		{ name: "Alert", data: line2,  pointStart: ('.$opts["begin"].'*1000 - current_timezone * 60 * 1000) ,pointInterval: 5 * 60 * 1000 },
	]
});
	';
	return $js_code;
}


/*
 *anomalia_get_highchart_udpdata function is called from the anomalia_Run function.
 * It takes one argument - array otps with options for backend function. It must
 * contain name of the graph (under graph_name key) and unix timestamps of begin
 * and end of the displayed data (under keys begin and end).
 *
 * The function will request data from backend and returns JavaScript code that
 * will draw the graph with Highcharts library.
 */
function anomalia_get_highchart_udpdata($opts) {

	// Request the data for the graph from backend function feedGraph
        $out_list = nfsend_query("anomalia::feed_graph", $opts);
        if ( !is_array($out_list) ) {
                print "Error calling backend plugin - feed_graph\n";return FALSE;
        }
        $data_line1      = $out_list["line1"];
        $data_line2      = $out_list["line2"];

	// Generate JavaScript arrays definitions of graph lines
        $js_code = "line1 = [";
        $js_code .= join(',',$data_line1);
        $js_code .= "];";
        $js_code .= "line2 = [";
        $js_code .= join(',',$data_line2);
        $js_code .= "];";

        $js_code .= '

var current_date = new Date();
var current_timezone = current_date.getTimezoneOffset();

chart1 = new Highcharts.Chart({
	chart: {
		renderTo: "highchart-udpdata",
		type: "line",
                borderWidth: 1,
                plotBorderWidth: 1
	},
	title: {
		text: "UDP Data",
		style: { color: "black" }

	},
	plotOptions: {
                line: {  marker: { enabled: true,lineWidth: 1,radius: 2 }}

	},
	tooltip: {
		formatter: function() {
			return \'\'+
			Highcharts.dateFormat(\'%e. %b %Y, %H:%M\', this.x) +\': \'+ this.y;
		}
	},
	xAxis: {
		type: "datetime"
	},
	yAxis: {
		title: {
			text: "Quantity"
		}
	},
	series: [
		{ name: "All", data: line1,  pointStart: ('.$opts["begin"].'*1000 - current_timezone * 60 * 1000) ,pointInterval: 5 * 60 * 1000 },
		{ name: "Alert", data: line2,  pointStart: ('.$opts["begin"].'*1000 - current_timezone * 60 * 1000) ,pointInterval: 5 * 60 * 1000 },
	]
});
	';
	return $js_code;
}


/*
 * anomalia_get_flot_syndata is called from anomalia_Run function.
 * It takes one argument - array otps with options for backend function. It must
 * contain name of the graph (under graph_name key) and unix timestamps of begin
 * and end of the displayed data (under keys begin and end).
 *
 * The function will request data from backend and returns JavaScript code that
 * will draw the graph with Highcharts library.
 */
function anomalia_get_flot_syndata($opts) {


	// Request the data for the graph from backend function feedGraph
        $out_list = nfsend_query("anomalia::feed_graph", $opts);
        if ( !is_array($out_list) ) {
                print "Error calling backend plugin - feed_graph\n";return FALSE;
        }

        $data_line1      = $out_list["line1"];
        $data_line2      = $out_list["line2"];

	// Generate JavaScript arrays definitions of graph lines
        $js_code = "line1 = [";
        $js_code .= join(',',$data_line1);
        $js_code .= "];";
        $js_code .= "line2 = [";
        $js_code .= join(',',$data_line2);
        $js_code .= "];";

	// 
        $js_code .= '

	var current_date = new Date();
	var current_timezone = current_date.getTimezoneOffset();

	var d1 = [];
	for (var i = 0; i < line1.length; i++)
        d1.push(['.$opts["begin"].'*1000 - current_timezone*60*1000 + (300*1000*i), line1[i]]);

	var d2 = [];
	for (var i = 0; i < line2.length; i++)
        d2.push(['.$opts["begin"].'*1000 - current_timezone*60*1000 + (300*1000*i), line2[i]]);

        $.plot(
		$("#flot-syndata"),
                [ { data: d1, label: "Line 1" }, { data: d2, label: "Line 2" } ],
                { xaxis: {mode: "time"} }
        );


	';
	return $js_code;
}


/*
 * anomalia_get_flot_syncloseddata is called from anomalia_Run function.
 * It takes one argument - array otps with options for backend function. It must
 * contain name of the graph (under graph_name key) and unix timestamps of begin
 * and end of the displayed data (under keys begin and end).
 *
 * The function will request data from backend and returns JavaScript code that
 * will draw the graph with Highcharts library.
 */
function anomalia_get_flot_syncloseddata($opts) {


	// Request the data for the graph from backend function feedGraph
        $out_list = nfsend_query("anomalia::feed_graph", $opts);
        if ( !is_array($out_list) ) {
                print "Error calling backend plugin - feed_graph\n";return FALSE;
        }

        $data_line1      = $out_list["line1"];
        $data_line2      = $out_list["line2"];

	// Generate JavaScript arrays definitions of graph lines
        $js_code = "line1 = [";
        $js_code .= join(',',$data_line1);
        $js_code .= "];";
        $js_code .= "line2 = [";
        $js_code .= join(',',$data_line2);
        $js_code .= "];";

	// 
        $js_code .= '

	var current_date = new Date();
	var current_timezone = current_date.getTimezoneOffset();

	var d1 = [];
	for (var i = 0; i < line1.length; i++)
        d1.push(['.$opts["begin"].'*1000 - current_timezone*60*1000 + (300*1000*i), line1[i]]);

	var d2 = [];
	for (var i = 0; i < line2.length; i++)
        d2.push(['.$opts["begin"].'*1000 - current_timezone*60*1000 + (300*1000*i), line2[i]]);

        $.plot(
		$("#flot-syncloseddata"),
                [ { data: d1, label: "Line 1" }, { data: d2, label: "Line 2" } ],
                { xaxis: {mode: "time"} }
        );


	';
	return $js_code;
}


/*
 * anomalia_get_flot_nulldata is called from anomalia_Run function.
 * It takes one argument - array otps with options for backend function. It must
 * contain name of the graph (under graph_name key) and unix timestamps of begin
 * and end of the displayed data (under keys begin and end).
 *
 * The function will request data from backend and returns JavaScript code that
 * will draw the graph with Highcharts library.
 */
function anomalia_get_flot_nulldata($opts) {


	// Request the data for the graph from backend function feedGraph
        $out_list = nfsend_query("anomalia::feed_graph", $opts);
        if ( !is_array($out_list) ) {
                print "Error calling backend plugin - feed_graph\n";return FALSE;
        }

        $data_line1      = $out_list["line1"];
        $data_line2      = $out_list["line2"];

	// Generate JavaScript arrays definitions of graph lines
        $js_code = "line1 = [";
        $js_code .= join(',',$data_line1);
        $js_code .= "];";
        $js_code .= "line2 = [";
        $js_code .= join(',',$data_line2);
        $js_code .= "];";

	// 
        $js_code .= '

	var current_date = new Date();
	var current_timezone = current_date.getTimezoneOffset();

	var d1 = [];
	for (var i = 0; i < line1.length; i++)
        d1.push(['.$opts["begin"].'*1000 - current_timezone*60*1000 + (300*1000*i), line1[i]]);

	var d2 = [];
	for (var i = 0; i < line2.length; i++)
        d2.push(['.$opts["begin"].'*1000 - current_timezone*60*1000 + (300*1000*i), line2[i]]);

        $.plot(
		$("#flot-nulldata"),
                [ { data: d1, label: "Line 1" }, { data: d2, label: "Line 2" } ],
                { xaxis: {mode: "time"} }
        );


	';
	return $js_code;
}


/*
 * anomalia_get_flot_udpdata is called from anomalia_Run function.
 * It takes one argument - array otps with options for backend function. It must
 * contain name of the graph (under graph_name key) and unix timestamps of begin
 * and end of the displayed data (under keys begin and end).
 *
 * The function will request data from backend and returns JavaScript code that
 * will draw the graph with Highcharts library.
 */
function anomalia_get_flot_udpdata($opts) {


	// Request the data for the graph from backend function feedGraph
        $out_list = nfsend_query("anomalia::feed_graph", $opts);
        if ( !is_array($out_list) ) {
                print "Error calling backend plugin - feed_graph\n";return FALSE;
        }

        $data_line1      = $out_list["line1"];
        $data_line2      = $out_list["line2"];

	// Generate JavaScript arrays definitions of graph lines
        $js_code = "line1 = [";
        $js_code .= join(',',$data_line1);
        $js_code .= "];";
        $js_code .= "line2 = [";
        $js_code .= join(',',$data_line2);
        $js_code .= "];";

	// 
        $js_code .= '

	var current_date = new Date();
	var current_timezone = current_date.getTimezoneOffset();

	var d1 = [];
	for (var i = 0; i < line1.length; i++)
        d1.push(['.$opts["begin"].'*1000 - current_timezone*60*1000 + (300*1000*i), line1[i]]);

	var d2 = [];
	for (var i = 0; i < line2.length; i++)
        d2.push(['.$opts["begin"].'*1000 - current_timezone*60*1000 + (300*1000*i), line2[i]]);

        $.plot(
		$("#flot-udpdata"),
                [ { data: d1, label: "Line 1" }, { data: d2, label: "Line 2" } ],
                { xaxis: {mode: "time"} }
        );


	';
	return $js_code;
}


/*
 * anomalia_CheckNewSettings function checks if the settings form
 * has been sent. If it has, this function will receive the values,
 * send them to the backend and print message about it.
 */
function anomalia_CheckNewSettings() {


	// Check if the form was sent
	if (isset($_POST['submit_settings'])) {

		// Get the text value
		$opts['settings_syn_probes'] = $_POST['settings_syn_probes'];
		$opts['settings_synclosed_probes'] = $_POST['settings_synclosed_probes'];
		$opts['settings_null_probes'] = $_POST['settings_null_probes'];
		$opts['settings_udp_probes'] = $_POST['settings_udp_probes'];

		#// Get the value of checkbox
		#if (isset ( $_POST['settings_checkbox'] ) ) {
		#	$opts['settings_checkbox'] = "checked";
		#} else {
		#	$opts['settings_checkbox'] = "unchecked";
		#}

		#// Get the value of the Radiobutton
		#$opts['settings_radio'] = $_POST['settings_radio'];

                $out_list = nfsend_query("anomalia::save_settings", $opts);
		print 'Settings changed!  ';
	}

} // End of anomalia_CheckNewSettings



/*
 * anomalia_PrintSettings function loads saved settings from backend
 * and prints the settings form with these values.
 */
function anomalia_PrintSettings() {

	// Initialization of opts array (it has to exist when calling backend)
	$opts['option'] = "";
	// Load current settings from backend
	$out_list = nfsend_query("anomalia::load_settings", $opts);

	if ( !is_array($out_list) ) {
                print "first run of plugin - setting defaults";
                #$out_list['settings_radio']='Radio2';
                #$out_list['settings_checkbox']="checked";
                $out_list['settings_syn_probes']="10";
                $out_list['settings_synclosed_probes']="10";
                $out_list['settings_udp_probes']="10";
                $out_list['settings_null_probes']="10";
	}

	// prepare value of radiobox to be printed
	#$radio1_checked = "";
	#$radio2_checked = "";
	#$radio2_checked = "";
	#if ($out_list['settings_radio'] == 'Radio1') {
	#	$radio1_checked = " checked ";
	#} elseif ($out_list['settings_radio'] == 'Radio2') {
	#	$radio2_checked = " checked ";
	#} elseif ($out_list['settings_radio'] == 'Radio3') {
	#	$radio3_checked = " checked ";
	#}

	print '
	<div style="margin: 20px;">
	<form action="" method="POST">
	<fieldset>
	<legend><h3>Settings of the plugin</h3></legend>

		<br>
		<label for="settings_syn_probes">Minimal SYN Probes: </label><input type="text" id="settings_syn_probes" name="settings_syn_probes" value="'.$out_list['settings_syn_probes'].'"><br><br>
		<label for="settings_synclosed_probes">Minimal SYN Probes: </label><input type="text" id="settings_synclosed_probes" name="settings_synclosed_probes" value="'.$out_list['settings_synclosed_probes'].'"><br><br>
		<label for="settings_null_probes">Minimal Null Probes: </label><input type="text" id="settings_null_probes" name="settings_null_probes" value="'.$out_list['settings_null_probes'].'"><br><br>
		<label for="settings_udp_probes">Minimal UDP Probes: </label><input type="text" id="settings_udp_probes" name="settings_udp_probes" value="'.$out_list['settings_udp_probes'].'"><br><br>
<!--
		<input type="checkbox" name="settings_checkbox" id="settings_checkbox" '.$out_list['settings_checkbox'].'><label for="settings_checkbox">Checkbox</label><br><br>

		<input type="radio" name="settings_radio" id="settings_radio1" value="Radio1" '.$radio1_checked.'><label for="settings_radio1">Radio 1</label><br>
		<input type="radio" name="settings_radio" id="settings_radio2" value="Radio2" '.$radio2_checked.'><label for="settings_radio2">Radio 2</label><br>
		<input type="radio" name="settings_radio" id="settings_radio3" value="Radio3" '.$radio3_checked.'><label for="settings_radio3">Radio 3</label><br><br>
-->
		<input type="reset" value="Reset to current values"><input type="submit" name="submit_settings" value="Save settings"><br>

	</fieldset>
	</form>
	</div>
	';
} // End of  anomalia_PrintSettings


	
/* 
 * anomalia_ParseInput is called prior to any output to the web browser 
 * and is intended for the plugin to parse possible form data. This 
 * function is called only, if this plugin is selected in the plugins tab. 
 * If required, this function may set any number of messages as a result 
 * of the argument parsing.
 * The return value is ignored.
 */
function anomalia_ParseInput( $plugin_id ) {
} // End of anomalia_ParseInput


/*
 * anomalia_Run function is called after the header and the navigation bar have 
 * been sent to the browser. It's now up to this function what to display.
 * This function is called only, if this plugin is selected in the plugins tab
 * Its return value is ignored.
 */
function anomalia_Run( $plugin_id ) {

	anomalia_PrintHeaders();

	// Get the current time and round it to the 5minutes
	$round_numerator = 60 * 5; // 60 seconds per minute * 5 minutes
	$current_rounded_time = ( round ( time() / $round_numerator ) * $round_numerator ); // Calculate time to nearest 5 minutes!
	$current_time = date("Y-m-d H:i", $current_rounded_time);

	// Initialization of time for Time Input Panel (set current time or time sent from the panel)
	$beginning_syndata = get_time_form_data("begin_datetime_syndata", date("Y-m-d H:i", $current_rounded_time -21600 ));
	$ending_syndata = get_time_form_data("end_datetime_syndata", date("Y-m-d H:i", $current_rounded_time));
	$timewindow_syndata = get_time_form_data("timewindow_syndata", "6 hours");
	$end_timestamp_syndata = strtotime("$ending_syndata");
	$begin_timestamp_syndata = strtotime("$beginning_syndata");

	// Initialization of time for Time Input Panel (set current time or time sent from the panel)
	$beginning_syncloseddata = get_time_form_data("begin_datetime_syncloseddata", date("Y-m-d H:i", $current_rounded_time -21600 ));
	$ending_syncloseddata = get_time_form_data("end_datetime_syncloseddata", date("Y-m-d H:i", $current_rounded_time));
	$timewindow_syncloseddata = get_time_form_data("timewindow_syncloseddata", "6 hours");
	$end_timestamp_syncloseddata = strtotime("$ending_syncloseddata");
	$begin_timestamp_syncloseddata = strtotime("$beginning_syncloseddata");

	// Initialization of time for Time Input Panel (set current time or time sent from the panel)
	$beginning_nulldata = get_time_form_data("begin_datetime_nulldata", date("Y-m-d H:i", $current_rounded_time -21600 ));
	$ending_nulldata = get_time_form_data("end_datetime_nulldata", date("Y-m-d H:i", $current_rounded_time));
	$timewindow_nulldata = get_time_form_data("timewindow_nulldata", "6 hours");
	$end_timestamp_nulldata = strtotime("$ending_nulldata");
	$begin_timestamp_nulldata = strtotime("$beginning_nulldata");

	// Initialization of time for Time Input Panel (set current time or time sent from the panel)
	$beginning_udpdata = get_time_form_data("begin_datetime_udpdata", date("Y-m-d H:i", $current_rounded_time -21600 ));
	$ending_udpdata = get_time_form_data("end_datetime_udpdata", date("Y-m-d H:i", $current_rounded_time));
	$timewindow_udpdata = get_time_form_data("timewindow_udpdata", "6 hours");
	$end_timestamp_udpdata = strtotime("$ending_udpdata");
	$begin_timestamp_udpdata = strtotime("$beginning_udpdata");


	// variable to collect dynamicaly generated Javascript code 
	$javascript_code = "";

	print '
	<div id="tab-container" class="tab-container">
	<ul class="menu">
		<li class="tab"><a href="#tab_syndata">SYN Data</a></li>
		<li class="tab"><a href="#tab_syncloseddata">SynClosed Data</a></li>
		<li class="tab"><a href="#tab_nulldata">NULL Data</a></li>
		<li class="tab"><a href="#tab_udpdata">UDP Data</a></li>
		<li class="tab"><a href="#tab_settings">Settings</a></li>
		<li class="tab"><a href="#tab_about">About</a></li>
	</ul>';
	print '	<div class="panel-container">';


	// ======================================================= TAB SYN Data
	print '		<div id="tab_syndata"><br>';


	// Print HTML code of the TimeInputPanel
	anomalia_PrintTimeInputPanel_syndata($beginning_syndata, $ending_syndata, $timewindow_syndata);

	// options for calling backend function using selected timewindow
        $opts['begin'] = "$begin_timestamp_syndata";
        $opts['end'] = "$end_timestamp_syndata";
        $opts['window'] = "$timewindow_syndata";
	// Name of the graph for the backend to know, which data should be sent
	$opts["graph_name"] = "highchart_syndata_sample";
	print '<div id="highchart-syndata" class="highchart_graph ui-corner-all"></div>';
	$javascript_code .= anomalia_get_highchart_syndata($opts);
	// Name of the graph for the backend to know, which data should be sent
	//$opts["graph_name"] = "flot_syndata_sample";	print '<div id="flot-syndata" class="flot_graph"></div>';
	//$javascript_code .= anomalia_get_flot_syndata($opts);

	$opts['option'] = "";
	$opts['type'] = 0; //typetable   
	// call command in backened plugin
	$out_list = nfsend_query("anomalia::get_sqlite_syn", $opts);

	// get result
	// if $out_list == FALSE – it's an error
	if ( !is_array($out_list) ) {
	    SetMessage('error', "Error calling plugin");
	    return FALSE;
	}

	$timestamp = $out_list['timestamp'];
	$score_min = $out_list['score_min'];
	$score_alert = $out_list['score_alert'];

	print "<h3 style=\"margin-left: 30px;\">Table of the last 20 values stored in the Sqlite DB sorted by timestamp</h3>";

	// check the correct number of received strings
	if (count($timestamp) < 1) {
                print "<table class=\"data_table\" cellpadding=\"0\" cellspacing=\"0\">";
                print "<tr style=\"background-color: #cedfda;\"><td><b>Nothing to display! Backend returned empty list. Plugin might not have processed any data yet.</b></td></tr>";
	} else {
	print '<table class="tablesorter" id="sortable_table">
	<thead>
	<tr>
	<th><b>Timeslot</b></th><th><b>Timestamp</b></th><th><b>Score Min</b></th><th><b>Scone Alert</b></th>
	</tr>
	</thead>';

        for ($i = 0; $i < sizeof($timestamp); $i++) {
                print "<tr>";
                $human_time = date("Y-m-d H:i", $timestamp[$i]);
                print "<td>$human_time</td>";
                print "<td>$timestamp[$i]</td>";
                print "<td>$score_min[$i]</td>";
                print "<td>$score_alert[$i]</td>";
                print "</tr>";
        }

	print '</table>';
	}
	print "</table>";

	print '		</div><!-- End of TAB SYN Data -->';

	// ======================================================= TAB SynClosed Data
	print '		<div id="tab_syncloseddata"><br>';


	// Print HTML code of the TimeInputPanel
	anomalia_PrintTimeInputPanel_syncloseddata($beginning_syncloseddata, $ending_syncloseddata, $timewindow_syncloseddata);

	// options for calling backend function using selected timewindow
        $opts['begin'] = "$begin_timestamp_syncloseddata";
        $opts['end'] = "$end_timestamp_syncloseddata";
        $opts['window'] = "$timewindow_syncloseddata";
	// Name of the graph for the backend to know, which data should be sent
	$opts["graph_name"] = "highchart_syncloseddata_sample";
	print '<div id="highchart-syncloseddata" class="highchart_graph ui-corner-all"></div>';
	$javascript_code .= anomalia_get_highchart_syncloseddata($opts);
	// Name of the graph for the backend to know, which data should be sent
	//$opts["graph_name"] = "flot_syncloseddata_sample";	print '<div id="flot-syncloseddata" class="flot_graph"></div>';
	//$javascript_code .= anomalia_get_flot_syncloseddata($opts);

	$opts['option'] = "";
        //$opts['type'] = 1;
        // call command in backened plugin
        $out_list = nfsend_query("anomalia::get_sqlite_synclosed", $opts);

        // get result
        // if $out_list == FALSE \xe2\x80\x93 it's an error
        if ( !is_array($out_list) ) {
            SetMessage('error', "Error calling plugin");
            return FALSE;
        }
        
        $timestamp = $out_list['timestamp'];
        $score_min = $out_list['score_min'];
        $score_alert = $out_list['score_alert'];

        print "<h3 style=\"margin-left: 30px;\">Table of the last 20 values stored in the Sqlite DB sorted by timestamp</h3>";
        
        // check the correct number of received strings
        if (count($timestamp) < 1) {
                print "<table class=\"data_table\" cellpadding=\"0\" cellspacing=\"0\">";
                print "<tr style=\"background-color: #cedfda;\"><td><b>Nothing to display! Backend returned empty list. Plugin might not have processed any data yet.</b></td></tr>
";
        } else {
	print '<table class="tablesorter" id="sortable_table">
        <thead>
        <tr>
        <th><b>Timeslot</b></th><th><b>Timestamp</b></th><th><b>Score Min</b></th><th><b>Scone Alert</b></th>
        </tr>
        </thead>';

        for ($i = 0; $i < sizeof($timestamp); $i++) {
                print "<tr>";
                $human_time = date("Y-m-d H:i", $timestamp[$i]);
                print "<td>$human_time</td>";
                print "<td>$timestamp[$i]</td>";
                print "<td>$score_min[$i]</td>";
                print "<td>$score_alert[$i]</td>";
                print "</tr>";
        }

        print '</table>';
        }
        print "</table>";


	print '		</div><!-- End of TAB SynClosed Data -->';

	// ======================================================= TAB NULL Data
	print '		<div id="tab_nulldata"><br>';


	// Print HTML code of the TimeInputPanel
	anomalia_PrintTimeInputPanel_nulldata($beginning_nulldata, $ending_nulldata, $timewindow_nulldata);

	// options for calling backend function using selected timewindow
        $opts['begin'] = "$begin_timestamp_nulldata";
        $opts['end'] = "$end_timestamp_nulldata";
        $opts['window'] = "$timewindow_nulldata";
	// Name of the graph for the backend to know, which data should be sent
	$opts["graph_name"] = "highchart_nulldata_sample";
	print '<div id="highchart-nulldata" class="highchart_graph ui-corner-all"></div>';
	$javascript_code .= anomalia_get_highchart_nulldata($opts);
	// Name of the graph for the backend to know, which data should be sent
	//$opts["graph_name"] = "flot_nulldata_sample";	print '<div id="flot-nulldata" class="flot_graph"></div>';
	//$javascript_code .= anomalia_get_flot_nulldata($opts);

	$opts['option'] = "";
        //$opts['type'] = 3;
        // call command in backened plugin
        $out_list = nfsend_query("anomalia::get_sqlite_null", $opts);

        // get result
        // if $out_list == FALSE \xe2\x80\x93 it's an error
        if ( !is_array($out_list) ) {
            SetMessage('error', "Error calling plugin");
            return FALSE;
        }
        
        $timestamp = $out_list['timestamp'];
        $score_min = $out_list['score_min'];
        $score_alert = $out_list['score_alert'];

        print "<h3 style=\"margin-left: 30px;\">Table of the last 20 values stored in the Sqlite DB sorted by timestamp</h3>";
        
        // check the correct number of received strings
        if (count($timestamp) < 1) {
                print "<table class=\"data_table\" cellpadding=\"0\" cellspacing=\"0\">";
                print "<tr style=\"background-color: #cedfda;\"><td><b>Nothing to display! Backend returned empty list. Plugin might not have processed any data yet.</b></td></tr>
";
        } else {
	print '<table class="tablesorter" id="sortable_table">
        <thead>
        <tr>
        <th><b>Timeslot</b></th><th><b>Timestamp</b></th><th><b>Score Min</b></th><th><b>Scone Alert</b></th>
        </tr>
        </thead>';

        for ($i = 0; $i < sizeof($timestamp); $i++) {
                print "<tr>";
                $human_time = date("Y-m-d H:i", $timestamp[$i]);
                print "<td>$human_time</td>";
                print "<td>$timestamp[$i]</td>";
                print "<td>$score_min[$i]</td>";
                print "<td>$score_alert[$i]</td>";
                print "</tr>";
        }

        print '</table>';
        }
        print "</table>";
	print '		</div><!-- End of TAB NULL Data -->';

	// ======================================================= TAB UDP Data
	print '		<div id="tab_udpdata"><br>';


	// Print HTML code of the TimeInputPanel
	anomalia_PrintTimeInputPanel_udpdata($beginning_udpdata, $ending_udpdata, $timewindow_udpdata);

	// options for calling backend function using selected timewindow
        $opts['begin'] = "$begin_timestamp_udpdata";
        $opts['end'] = "$end_timestamp_udpdata";
        $opts['window'] = "$timewindow_udpdata";
	// Name of the graph for the backend to know, which data should be sent
	$opts["graph_name"] = "highchart_udpdata_sample";
	print '<div id="highchart-udpdata" class="highchart_graph ui-corner-all"></div>';
	$javascript_code .= anomalia_get_highchart_udpdata($opts);
	// Name of the graph for the backend to know, which data should be sent
	//$opts["graph_name"] = "flot_udpdata_sample";	print '<div id="flot-udpdata" class="flot_graph"></div>';
	//$javascript_code .= anomalia_get_flot_udpdata($opts);

	$opts['option'] = "";
        //$opts['type'] = 2;
        // call command in backened plugin
        $out_list = nfsend_query("anomalia::get_sqlite_udp", $opts);

        // get result
        // if $out_list == FALSE \xe2\x80\x93 it's an error
        if ( !is_array($out_list) ) {
            SetMessage('error', "Error calling plugin");
            return FALSE;
        }
        
        $timestamp = $out_list['timestamp'];
        $score_min = $out_list['score_min'];
        $score_alert = $out_list['score_alert'];

        print "<h3 style=\"margin-left: 30px;\">Table of the last 20 values stored in the Sqlite DB sorted by timestamp</h3>";
        
        // check the correct number of received strings
        if (count($timestamp) < 1) {
                print "<table class=\"data_table\" cellpadding=\"0\" cellspacing=\"0\">";
                print "<tr style=\"background-color: #cedfda;\"><td><b>Nothing to display! Backend returned empty list. Plugin might not have processed any data yet.</b></td></tr>
";
        } else {
	print '<table class="tablesorter" id="sortable_table">
        <thead>
        <tr>
        <th><b>Timeslot</b></th><th><b>Timestamp</b></th><th><b>Score Min</b></th><th><b>Scone Alert</b></th>
        </tr>
        </thead>';

        for ($i = 0; $i < sizeof($timestamp); $i++) {
                print "<tr>";
                $human_time = date("Y-m-d H:i", $timestamp[$i]);
                print "<td>$human_time</td>";
                print "<td>$timestamp[$i]</td>";
                print "<td>$score_min[$i]</td>";
                print "<td>$score_alert[$i]</td>";
                print "</tr>";
        }

        print '</table>';
        }
        print "</table>";
	print '		</div><!-- End of TAB UDP Data -->';

	// ======================================================= TAB Settings
	print '		<div id="tab_settings"><br>';

	anomalia_CheckNewSettings();
	anomalia_PrintSettings();

	print '		</div><!-- End of TAB Settings -->';

	// ======================================================= TAB About
	print '		<div id="tab_about"><br>';


	print '		</div><!-- End of TAB About -->';

	print '	</div>
	</div>';

	// Print all generated JavaScript code (for drawing graphs)
	print '<script>';
	print $javascript_code;
	print '</script>';
} // End of anomalia_Run

