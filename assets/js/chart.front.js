jQuery(function($){
	// stats
	document.getElementById('wf-visits-count').innerHTML = WCAFFILIATE.charts.stats.visits;
	document.getElementById('wf-referrals-count').innerHTML = WCAFFILIATE.charts.stats.referrals;
	document.getElementById('wf-earnings-count').innerHTML = WCAFFILIATE.charts.stats.earnings;

	// visits chart
	google.charts.load("current", {packages:['corechart','line','table']});
	google.charts.setOnLoadCallback(drawVisitsChart);
	function drawVisitsChart() {
		var data = google.visualization.arrayToDataTable(WCAFFILIATE.charts.visits);
		var view = new google.visualization.DataView(data);
		var options = { legend: { position: "none" }, isStacked: true, colors: [ '#76a501' ] };
		var chart = new google.visualization.ColumnChart(document.getElementById("wf-visits"));
		chart.draw(view, options);
	}

	// referrals chart
	google.charts.setOnLoadCallback(drawReferralsChart);
	function drawReferralsChart() {
		var data = google.visualization.arrayToDataTable(WCAFFILIATE.charts.referrals);
		var view = new google.visualization.DataView(data);
		var options = { legend: { position: "none" }, isStacked: true, colors: [ '#ff8e01' ] };
		var chart = new google.visualization.ColumnChart(document.getElementById("wf-referrals"));
		chart.draw(view, options);
	}

	// earnings chart
	google.charts.setOnLoadCallback(drawEarningsChart);
	function drawEarningsChart() {
		var data = google.visualization.arrayToDataTable(WCAFFILIATE.charts.earnings);
		var view = new google.visualization.DataView(data);
		var options = { legend: { position: "none" }, isStacked: true, colors: [ '#5447c8' ] };
		var chart = new google.visualization.ColumnChart(document.getElementById("wf-earnings"));
		chart.draw(view, options);
	}

	// visits-referrals-earnings
	google.charts.setOnLoadCallback(drawVisitsReferralsEarningsChart);
	function drawVisitsReferralsEarningsChart() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', '');
		data.addColumn('number', 'Visits');
		data.addColumn('number', 'Referrals');
		data.addColumn('number', 'Earnings');
		data.addRows(WCAFFILIATE.charts.visits_referrals_earnings);
		var options = { chart: {}, width: '100%', colors: [ '#76a501', '#ff8e01', '#5447c8' ] };
		var chart = new google.charts.Line(document.getElementById('wf-visits-referrals-earnings'));
		chart.draw(data, google.charts.Line.convertOptions(options));
	}

	// conversion chart
	google.charts.setOnLoadCallback(drawConversionsChart);
	function drawConversionsChart() {
		var data = google.visualization.arrayToDataTable(Object.entries(WCAFFILIATE.charts.conversions));
		var options = { is3D: false, colors: [ '#0099c6', '#dd4477' ] }; /* colors: [ converted, non-converted ] */
		var chart = new google.visualization.PieChart(document.getElementById('wf-conversions'));
		chart.draw(data, options);
	}

	// landingpages table
	google.charts.setOnLoadCallback(drawLandingpagesTable);
	function drawLandingpagesTable() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Page URL');
		data.addColumn('number', 'Visits');
		data.addColumn('number', 'Referrals');
		data.addRows(WCAFFILIATE.charts.landingpages);
		var options = {showRowNumber: true, width: '100%', height: '200', sortColumn: 1, sortAscending: false, cssClassNames: {headerRow:'cx-chart-header'}};
		var table = new google.visualization.Table(document.getElementById('wf-landingpages'));
		table.draw(data, options);
	}

	// referralurls table
	google.charts.setOnLoadCallback(drawReferralurlsTable);
	function drawReferralurlsTable() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Page URL');
		data.addColumn('number', 'Visits');
		data.addColumn('number', 'Referrals');
		data.addRows(WCAFFILIATE.charts.referralurls);
		var options = {showRowNumber: true, width: '100%', height: '200', sortColumn: 1, sortAscending: false, cssClassNames: {headerRow:'cx-chart-header'}};
		var table = new google.visualization.Table(document.getElementById('wf-referralurls'));
		table.draw(data, options);
	}
})