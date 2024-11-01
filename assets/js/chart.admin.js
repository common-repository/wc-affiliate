jQuery(function($){

	google.charts.load("current", {packages:['corechart','line','table']});
	// visits chart
	var wf_visits = document.getElementById("wf-visits");
    if(typeof(wf_visits) != 'undefined' && wf_visits != null){
		google.charts.setOnLoadCallback(drawVisitsChart);
		function drawVisitsChart() {
			var data = google.visualization.arrayToDataTable(WCAFFILIATE.charts.visits);
			var view = new google.visualization.DataView(data);
			var options = { legend: { position: "none" }, isStacked: true, colors: [ '#76a501' ] };
			var chart = new google.visualization.ColumnChart(wf_visits);
			chart.draw(view, options);
		}
	}

	// referrals chart
	var wf_referrals = document.getElementById("wf-referrals");
    if(typeof(wf_referrals) != 'undefined' && wf_referrals != null){
		google.charts.setOnLoadCallback(drawReferralsChart);
		function drawReferralsChart() {
			var data = google.visualization.arrayToDataTable(WCAFFILIATE.charts.referrals);
			var view = new google.visualization.DataView(data);
			var options = { legend: { position: "none" }, isStacked: true, colors: [ '#ff8e01' ] };
			var chart = new google.visualization.ColumnChart(wf_referrals);
			chart.draw(view, options);
		}
	}

	// earnings chart
	var wf_earnings = document.getElementById("wf-earnings");
    if(typeof(wf_earnings) != 'undefined' && wf_earnings != null){
		google.charts.setOnLoadCallback(drawEarningsChart);
		function drawEarningsChart() {
			var data = google.visualization.arrayToDataTable(WCAFFILIATE.charts.earnings);
			var view = new google.visualization.DataView(data);
			var options = { legend: { position: "none" }, isStacked: true, colors: [ '#5447c8' ] };
			var chart = new google.visualization.ColumnChart(wf_earnings);
			chart.draw(view, options);
		}
	}

	// visits-referrals-earnings
	var wf_vre = document.getElementById("wf-visits-referrals-earnings");
    if(typeof(wf_vre) != 'undefined' && wf_vre != null){
		google.charts.setOnLoadCallback(drawVisitsReferralsEarningsChart);
		function drawVisitsReferralsEarningsChart() {
			var data = new google.visualization.DataTable();
			data.addColumn('string', '');
			data.addColumn('number', 'Visits');
			data.addColumn('number', 'Referrals');
			data.addColumn('number', 'Earnings');
			data.addRows(WCAFFILIATE.charts.visits_referrals_earnings);
			var options = { chart: {}, width: '100%', colors: [ '#76a501', '#ff8e01', '#5447c8' ] };
			var chart = new google.charts.Line(wf_vre);
			chart.draw(data, google.charts.Line.convertOptions(options));
		}
	}

	// conversion chart
	var wf_conversions = document.getElementById("wf-conversions");
    if(typeof(wf_conversions) != 'undefined' && wf_conversions != null){
		google.charts.setOnLoadCallback(drawConversionsChart);
		function drawConversionsChart() {
			var data = google.visualization.arrayToDataTable(Object.entries(WCAFFILIATE.charts.conversions));
			var options = { is3D: false, colors: [ '#0099c6', '#dd4477' ] }; /* colors: [ converted, non-converted ] */
			var chart = new google.visualization.PieChart(wf_conversions);
			chart.draw(data, options);
		}
	}

	// products chart
	var wf_products = document.getElementById("wf-products");
    if(typeof(wf_products) != 'undefined' && wf_products != null){
		google.charts.setOnLoadCallback(drawProductsChart);
		function drawProductsChart() {
			var data = google.visualization.arrayToDataTable(Object.entries(WCAFFILIATE.charts.products));
			var options = { is3D: true };
			var chart = new google.visualization.PieChart(wf_products);
			chart.draw(data, options);
		}
	}

	// affiliates table
	var wf_affiliates = document.getElementById("wf-affiliates");
    if(typeof(wf_affiliates) != 'undefined' && wf_affiliates != null){
		google.charts.setOnLoadCallback(drawAffiliatesTable);
		function drawAffiliatesTable() {
			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Affiliate Name');
			data.addColumn('number', 'Visits');
			data.addColumn('number', 'Referrals');
			data.addColumn('number', 'Earnings');
			data.addRows(WCAFFILIATE.charts.afiliates);
			var options = {showRowNumber: true, width: '100%', height: '200', sortColumn: 3, sortAscending: false, cssClassNames: {headerRow:'cx-chart-header'}};
			var table = new google.visualization.Table(wf_affiliates);
			table.draw(data, options);
		}
	}

	// landingpages table
	var wf_landingpages = document.getElementById("wf-landingpages");
    if(typeof(wf_landingpages) != 'undefined' && wf_landingpages != null){
		google.charts.setOnLoadCallback(drawLandingpagesTable);
		function drawLandingpagesTable() {
			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Page URL');
			data.addColumn('number', 'Visits');
			data.addColumn('number', 'Referrals');
			data.addRows(WCAFFILIATE.charts.landingpages);
			var options = {showRowNumber: true, width: '100%', height: '200', sortColumn: 1, sortAscending: false, cssClassNames: {headerRow:'cx-chart-header'}};
			var table = new google.visualization.Table(wf_landingpages);
			table.draw(data, options);
		}
	}

	// referralurls table
	var wf_referralurls = document.getElementById("wf-referralurls");
    if(typeof(wf_referralurls) != 'undefined' && wf_referralurls != null){
		google.charts.setOnLoadCallback(drawReferralurlsTable);
		function drawReferralurlsTable() {
			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Page URL');
			data.addColumn('number', 'Visits');
			data.addColumn('number', 'Referrals');
			data.addRows(WCAFFILIATE.charts.referralurls);
			var options = {showRowNumber: true, width: '100%', height: '200', sortColumn: 1, sortAscending: false, cssClassNames: {headerRow:'cx-chart-header'}};
			var table = new google.visualization.Table(wf_referralurls);
			table.draw(data, options);
		}
	}
})