/*
	dfahlivehistorydata.js
	Live data poller from JSON file prepared by DogecoinFah

*/
var chartUpdateRate = 0; // 0 for off, value in miliseconds
var httpreqChart;
var history_chart_data_file = "PublicRoundChartData.json";
var history;
var mode = 0;
var roundUpdated = "";
var changeRoundDelayed = false;
var sortDelayed = false;
var showIdle = false;
var lasthttpreqChart = "";
var chartMaxDays = 0; // 0 = max or days

window.chartColors = {
	red: 'rgb(255, 99, 132)',
	orange: 'rgb(255, 159, 64)',
	yellow: 'rgb(255, 205, 86)',
	green: 'rgb(75, 192, 192)',
	blue: 'rgb(54, 162, 235)',
	purple: 'rgb(153, 102, 255)',
	grey: 'rgb(201, 203, 207)'
};

if (!Date.now){ // IE8 compat
	Date.now = function() { return new Date().getTime(); } 
} 

function logmsg(msg)
{
	var logdom = document.getElementById("history")
	logdom.innerHTML = logdom.innerHTML + msg + "<br/>";
}

function initLiveHistoryChart()
{
	initLiveData();
	
	httpreqChart = new XMLHttpRequest();
	try{
	   // Opera 8.0+, Firefox, Chrome, Safari
	   httpreqChart = new XMLHttpRequest();
	}catch (e){
	   // IE
	   try{
		  httpreqChart = new ActiveXObject("Msxml2.XMLHTTP");
			
	   }catch (e) {
		
		  try{
			 httpreqChart = new ActiveXObject("Microsoft.XMLHTTP");
		  }catch (e){
			 // Something went wrong
			 alert("Your browser broke!");
			 return false;
		  }
			
	   }
	}

	httpreqChart.onreadystatechange = function()
	{
	   if (httpreqChart.responseURL.endsWith(lasthttpreqChart) && httpreqChart.readyState == 4)
	   {
		 var jsonObjHist = JSON.parse(httpreqChart.responseText);
		 
		 var dtUpdated = new Date(jsonObjHist.UTC);
		 roundUpdated = dtUpdated.toLocaleString();
		 
		 history.date = [];
		 history.points = [];
		 history.pay = [];
		 history.workers = [];
		 history.avgpoints = [];
		 history.maxpoints = [];
		 history.avgpay = [];
		 history.maxpay = [];
		 for (var r = 0;r < jsonObjHist.rounds.length;r++)
		 {
			var rdata = jsonObjHist.rounds[r];
			history.date[r] 		= new Date(rdata.utcPaid);
			history.points[r] 		= parseInt(rdata.totalPoints);
			history.pay[r] 			= Math.floor(parseFloat(rdata.totalPay));
			history.workers[r] 		= parseInt(rdata.countPay);
			history.maxpoints[r] 	= Math.round(parseInt(rdata.maxPoints));
			history.avgpoints[r] 	= Math.round(parseInt(rdata.avgPoints));
			history.maxpay[r] 		= Math.round(parseFloat(rdata.maxPay));
			history.avgpay[r] 		= Math.round(parseFloat(rdata.avgPay));
			// rdata.minPoints and rdata.minPay not currently used
		 }
		 buildChart();
		 if (chartUpdateRate != 0)
			setTimeout("requestLiveChartData()", chartUpdateRate);
		 lasthttpreqChart = "";
	   }
	}
	requestLiveChartData();			
} // initLiveHistoryData

function requestLiveChartData()
{
	lasthttpreqChart = history_chart_data_file + "?t=" + Date.now();
	httpreqChart.open("GET", lasthttpreqChart, true);
	httpreqChart.send();
}
function setMode(m)
{
	mode = m;
	buildChart();
}
function setMaxDays(newMax)
{
	chartMaxDays = newMax;
	buildChart();
}

function buildChart()
{
	var chartDiv = document.getElementById("chart")
	
	var chartMode = [ "Summary", "Point Details", "Pay Details" ];
	var html = "<div id='histcont'><div style='overflow-x:auto;' id='histrtabs'>";
	for (var i = 0;i < chartMode.length;i++)
		html += "<div "+(i == mode ? "id='histseltab' " : "")+"onclick='setMode("+i+");'>"+chartMode[i]+"</div>";
	html += "</div><canvas id='histround'></canvas></div>";
	
	var timeSpans = [ [ "Maximum", 0],
					  [ "One Year", 365],
					  [ "Six Months", 182],
					  [ "One Month", 30] ];
	
	html += "<div id='history' style='margin:15px;'>";
	for (var i = 0;i < timeSpans.length;i++)
	{
		html += "<span ";
		if (timeSpans[i][1] == chartMaxDays)
			html += "style='border:2px solid rgb(75, 205, 90);margin:5px;padding:0px 2px;border-radius:10px;'";
		else
			html += "style='border:1px solid black;margin:5px;padding:0px 2px;border-radius:10px;'";
		html += " onclick='setMaxDays(" + timeSpans[i][1] + ");'>" + timeSpans[i][0] + "</span>";
	}

	html += "</div>";
	chartDiv.innerHTML = html;

	var dataDates = [];
	var dataPoints = [];
	var dataPay = [];
	var dataWorkers = [];
	var dataAvgPoints = [];
	var dataMaxPoints = [];
	var dataAvgPay = [];
	var dataMaxPay = [];
	
	if (chartMaxDays == 0)
	{
		for (var r = 0;r < history.date.length;r++)
		{
			dataDates[r]		= history.date[r];
			dataPoints[r] 		= { t: history.date[r], y: history.points[r] };
			dataPay[r] 			= { t: history.date[r], y: history.pay[r] };
			dataWorkers[r] 		= { t: history.date[r], y: history.workers[r] };
			dataAvgPoints[r] 	= { t: history.date[r], y: history.avgpoints[r] };
			dataMaxPoints[r] 	= { t: history.date[r], y: history.maxpoints[r] };
			dataAvgPay[r] 		= { t: history.date[r], y: history.avgpay[r] };
			dataMaxPay[r] 		= { t: history.date[r], y: history.maxpay[r] };
		}
	}
	else
	{
		var dateMax = new Date();
		dateMax.setDate(dateMax.getDate() - chartMaxDays);
		dateMaxValue = dateMax.valueOf();
		for (var r = 0;r < history.date.length;r++)
			if (history.date[r].valueOf() > dateMaxValue)
			{
				dataDates.push(history.date[r]);
				dataPoints.push({ t: history.date[r], y: history.points[r] });
				dataPay.push({ t: history.date[r], y: history.pay[r] });
				dataWorkers.push({ t: history.date[r], y: history.workers[r] });
				dataAvgPoints.push({ t: history.date[r], y: history.avgpoints[r] });
				dataMaxPoints.push({ t: history.date[r], y: history.maxpoints[r] });
				dataAvgPay.push({ t: history.date[r], y: history.avgpay[r] });
				dataMaxPay.push({ t: history.date[r], y: history.maxpay[r] });
			}
	} 

	var datasets = [];
	if (mode == 0 || mode == 1)
	{
		datasets.push(	{
				label: (mode == 0 ? "Points Folded" : "Total Points"),
				borderColor: window.chartColors.red,
				backgroundColor: window.chartColors.red,
				fill: false,
				data: dataPoints,
				yAxisID: "y-L",
			} );
	}
	if (mode == 0 || mode == 2)
	{
		datasets.push(	{
				label: (mode == 0 ? Brand_Unit+" Paid" : "Total Paid"),
				borderColor: window.chartColors.blue,
				backgroundColor: window.chartColors.blue,
				fill: false,
				data: dataPay,
				yAxisID: (mode == 0 ? "y-R" : "y-L")
			} );
	}
	if (mode == 0)
	{
		datasets.push(	{
				label: "Folders Paid",
				borderColor: window.chartColors.purple,
				backgroundColor: window.chartColors.purple,
				fill: false,
				data: dataWorkers,
				yAxisID: "y-H"
			} );		
	}
	if (mode == 1)
	{
		datasets.push(	{
				label: "Avg Points",
				borderColor: window.chartColors.purple,
				backgroundColor: window.chartColors.purple,
				fill: false,
				data: dataAvgPoints,
				yAxisID: "y-R",
			} );
		datasets.push(	{
				label: "Max Points",
				borderColor: window.chartColors.blue,
				backgroundColor: window.chartColors.blue,
				fill: false,
				data: dataMaxPoints,
				yAxisID: "y-R",
			} );
	}

	if (mode == 2)
	{
		datasets.push(	{
				label: "Avg Paid",
				borderColor: window.chartColors.purple,
				backgroundColor: window.chartColors.purple,
				fill: false,
				data: dataAvgPay,
				yAxisID: "y-R",
			} );
		datasets.push(	{
				label: "Max Paid",
				borderColor: window.chartColors.red,
				backgroundColor: window.chartColors.red,
				fill: false,
				data: dataMaxPay,
				yAxisID: "y-R",
			} );
	}

    var lineChartData = {
		labels: dataDates,
        datasets: datasets,
    };

	var scales = [{
					type: "linear", // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
					display: true,
					position: "left",
					id: "y-L",
					scaleLabel: { 
						display: true,
						labelString: (mode == 0 ? "Points" : "Total"),
					},
				}, {
					type: "linear", // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
					display: true,
					position: "right",
					id: "y-R",
					scaleLabel: { 
						display: true,
						labelString: (mode == 0 ? Brand_Unit : "Avg / Max"),
					},

					// grid line settings
					gridLines: {
						drawOnChartArea: false, // only want the grid lines for one axis to show up
					}
				}];
				
	if (mode == 0)
	{
		scales.push( {
					type: "linear", // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
					display: true,
					position: "right",
					id: "y-H",
					scaleLabel: { 
						display: true,
						labelString: "Folders",
					},
					// grid line settings
					gridLines: {
						drawOnChartArea: false, // only want the grid lines for one axis to show up
						}
					} );
	}
	
	if (mode == 0) titleText = "Summary";
	if (mode == 1) titleText = "Points Folded";
	if (mode == 2) titleText = Brand_Unit+" Paid";
	var elements = (chartMaxDays > 30 ? {} : {line:{tension:0}});
	var ctx = document.getElementById("histround").getContext("2d");
	window.myLine = Chart.Line(ctx, {
		data: lineChartData,
		options: {
			responsive: true,
			hoverMode: 'index',
			stacked: false,
			title:{
				display: true,
				text: titleText,
			},
			scales: {
				yAxes: scales,
				
				xAxes: [{
					type: 'time',
					distribution: 'series',
					/*time: {
							unit: 'day'
						}*/
				}],
            },
			elements: elements,
        }
	});	
    window.myLine.update();
}
