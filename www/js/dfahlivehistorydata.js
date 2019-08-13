/*
	dfahlivehistorydata.js
	Live data poller from JSON file prepared by DogecoinFah

*/
var historyUpdateRate = 0; // 0 for off, value in miliseconds
var httpreqHistory;
var history_data_file = "PublicRoundData.json";
var sortCol = -2;
var selRound = 0;
var round = [];
var txids = [];
var multipleTxids = false;
var workers = [];
var roundUpdated = "";
var changeRoundDelayed = false;
var sortDelayed = false;
var showIdle = false;
var lasthttpreqHistory = "";

if (!Date.now){ // IE8 compat
	Date.now = function() { return new Date().getTime(); } 
} 

function getRow(name, val, unit = false){
	return "<tr><td>"+name+"</td><td>"+val+"</td></tr>";
}

function getWorkerRow(address, points, pay, txid)
{
	var isSpecial = false
	if (isNaN(address) || address > workers.length)
	{
		if (address.charAt(0) == "_") // special case to show Min/Max/Avg/Total labels
		{
			isSpecial = true;
			address = address.substr(1);
		}
		else
		{
			logmsg("Lookup address failed, length: " + workers.length + ", address idx: " + address);
			address = "[error]";
		}
	}
	else address = workers[address];

	var html = "<tr><td"+(isSpecial ? " style='text-align:right;'>"+address : "><a href='"+Brand_Address_Link+address+"'>"+address+"</a>")+"</td><td>"+points+"</td><td>"+parseFloat(pay).toFixed(4)+"</td>";

	if (txid == false)
		html += "</tr>";
	else
		html += "<td>"+(txid != "[none]" ? "<a href='"+Brand_Tx_Link+txid+"'>"+txid+"</a>" : "&nbsp;")+"</td></tr>";
	return html;
}

function logmsg(msg)
{
	var logdom = document.getElementById("history")
	logdom.innerHTML = logdom.innerHTML + msg + "<br/>";
}

function sortWorkers(workerData)
{
	var i;
	var col;
	var asc;
	var a, b;
	if (workerData.length <= 1)
		return;
	if (sortCol < 0){
		col = sortCol * (-1);
		asc = false;
	}
	else{
		col = sortCol;
		asc = true;
	}
	var isString = (col == 1 || col == 4 ? true : false);
	var done = false;
	//logmsg("sorting "+sortCol+", col "+col+", asc "+asc+", string "+(isString ? "Y" : "N")+", first val "+workerData[1][col]);
	while (!done)
	{
		done = true;
		for (i = 0;i < workerData.length-1;i++)
		{
			a = workerData[  workerData[i][0]][col];
			b = workerData[workerData[i+1][0]][col];
			if (isString)
				cmp = (a.localeCompare(b) == (asc ? 1 : -1) ? true : false); 
			else
				cmp = (asc ? (a > b ? true : false) : (a < b ? true : false));
			//logmsg("swap="+(cmp?"T":"F")+", i="+i+", idx="+workerData[i][0]+", idx-1="+workerData[i+1][0]+", val="+workerData[workerData[i][0]][col]+", val-1="+workerData[workerData[i+1][0]][col]);
			if (cmp)
			{
				cmp = workerData[i][0];
				workerData[i][0] = workerData[i+1][0];
				workerData[i+1][0] = cmp;
				done = false;
			}
		}
	}
}

function initLiveHistoryData()
{
	initLiveData();
	
	httpreqHistory = new XMLHttpRequest();
	try{
	   // Opera 8.0+, Firefox, Chrome, Safari
	   httpreqHistory = new XMLHttpRequest();
	}catch (e){
	   // IE
	   try{
		  httpreqHistory = new ActiveXObject("Msxml2.XMLHTTP");
			
	   }catch (e) {
		
		  try{
			 httpreqHistory = new ActiveXObject("Microsoft.XMLHTTP");
		  }catch (e){
			 // Something went wrong
			 alert("Your browser broke!");
			 return false;
		  }
			
	   }
	}

	httpreqHistory.onreadystatechange = function()
	{
	   if (httpreqHistory.responseURL.endsWith(lasthttpreqHistory) && httpreqHistory.readyState == 4)
	   {
		 var jsonObjHist = JSON.parse(httpreqHistory.responseText);
		 
		 var dtUpdated = new Date(jsonObjHist.UTC);
		 roundUpdated = dtUpdated.toLocaleString();
		 
		 txids = [];
		 multipleTxids = 0;
		 for (var i = 0;i < jsonObjHist.txids.length;i++)
		 {
			txids[i] = jsonObjHist.txids[i];
			if (txids[i] != "[none]")
				multipleTxids++;
		 }
		 multipleTxids = (multipleTxids > 1 ? true : false);
		 workers = [];
		 for (var i = 0;i < jsonObjHist.workers.length;i++)
			workers[i] = jsonObjHist.workers[i];
		 round = [];
		 for (var r = 0;r < jsonObjHist.rounds.length;r++)
		 {
			var rdata = jsonObjHist.rounds[r];
			round[r] = ['comment', 'dtPoll', 'dtPay', 'tPoints', 'tPay', 'paidWorkers', 'minPoints', 'maxPoints', 'avgPoints', 'minPay', 'maxPay', 'avgPay', 'workers', 'txid'];
			round[r].comment	= rdata.comment;
			round[r].dtPoll 	= new Date(rdata.utcStats);
			round[r].dtPay 		= new Date(rdata.utcPaid);
			round[r].tPoints 	= parseInt(rdata.totalPoints);
			round[r].tPay 		= parseFloat(rdata.totalPay);
			round[r].paidWorkers = parseInt(rdata.countPay);
			round[r].minPoints 	= parseInt(rdata.minPoints);
			round[r].maxPoints 	= parseInt(rdata.maxPoints);
			round[r].avgPoints 	= parseInt(rdata.avgPoints);
			round[r].minPay 	= parseFloat(rdata.minPay);
			round[r].maxPay 	= parseFloat(rdata.maxPay);
			round[r].avgPay 	= parseFloat(rdata.avgPay);
			round[r].txid 		= "";
			round[r].workers 	= [];

			var lastTxid = -1;
			for (var i = 0;i < rdata.workers.length;i++)//selRound
			{
				curTxid = parseInt(rdata.workers[i].txid);
				points = parseFloat(rdata.workers[i].points);
				if (isNaN(points))
					points = rdata.workers[i].points; // set back to unparsed text
				pay = parseFloat(rdata.workers[i].pay)
				if (isNaN(pay))
					pay = parseFloat(rdata.workers[i].pay); // set back to unparsed text
				round[r].workers[i] = [i, parseInt(rdata.workers[i].address), points, pay, curTxid];
				if (curTxid != lastTxid && txids[curTxid] != "[none]")
				{
					if (round[r].txid != "")
					{
						
						round[r].txid = false; // not all match
					}
					else
						round[r].txid  = txids[curTxid];
					lastTxid = curTxid;
				}
			}
			if (round[r].txid == "")
				round[r].txid = "[none]";
		 }
		 buildRound();
		 if (historyUpdateRate != 0)
			setTimeout("requestLiveHistoryData()", historyUpdateRate);
		 lasthttpreqHistory = "";
	   }
	   else if ( httpreqHistory.readyState == 4) alert("History - Skipping state: " + httpreqHistory.readyState + ", req: " + httpreqHistory.responseURL + ", last req: " + lasthttpreqHistory);
	}
	requestLiveHistoryData();			
} // initLiveHistoryData

function requestLiveHistoryData()
{
	if (lasthttpreqHistory != "")
	{
		alert("requestLiveHistoryData double request, last was: " + lasthttpreqHistory);
		return;
	}
	lasthttpreqHistory = history_data_file + "?t=" + Date.now();
	httpreqHistory.open("GET", lasthttpreqHistory, true);
	httpreqHistory.send();
}
function buildRound()
{
	if (round.length != 0)
	{
		if (selRound >= round.length)
			selRound = 0;
		var html = "<div id='histcont'><div id='histup'>Updated: "+roundUpdated+"</div><div style='overflow-x:auto;' id='histrtabs'>";
		for (var i = 0;i < round.length;i++)
			html += "<div "+(i == selRound ? "id='histseltab' " : "")+"onclick='roundSelect("+i+");'>"+round[i].dtPoll.toLocaleDateString()+"</div>";
		html += "</div><div id='histround'><h2>Round for week of " + round[selRound].dtPoll.toLocaleDateString() + "</h2><table id='histdetails'>";
		html += getRow("Comment", 			round[selRound].comment);
		html += getRow("Points polled", 	round[selRound].dtPoll.toLocaleString());
		html += getRow("Paid", 				round[selRound].dtPay.toLocaleString());
		html += getRow("Total Points", 		round[selRound].tPoints);
		html += getRow("Total Paid", 		round[selRound].tPay.toFixed(4));
		html += getRow("Paid Folders", 		round[selRound].paidWorkers);
		if (round[selRound].txid != false) // all txid's match
			html += getRow("Payment Transaction", (round[selRound].txid == "[none]" ? round[selRound].txid : "<a href='"+Brand_Tx_Link+round[selRound].txid+"'>"+round[selRound].txid.substring(0,32)+"...<br/>..."+round[selRound].txid.substring(32)+"</a>"));
		html += "</table></div>";

		var hasIdle = false;
		var workerData = round[selRound].workers;
		for (var i = 0;i < workerData.length;i++)
			if (workerData[workerData[i][0]][2] == 0)
			{
				hasIdle = true;
				break;
			}
		if (hasIdle == true)
			html += "<div style='text-align:left;'><input style='display:inline;z-index:0;-moz-appearance:checkbox;float:none;opacity:1.0;margin-right:5px;' id='show_idle' type='checkbox' value='1' onclick='showIdleClicked();' " + (showIdle ? "checked='checked' " : "") + " />Show unpaid folders</div>";
		html += "<div id='histworkers'></div>";//<div id='log'></div>";
		document.getElementById("history").innerHTML = html;
		buildWorkersTable();
	}
	else document.getElementById("history").innerHTML = "";	
	//logmsg("Loaded " + txids.length + " txids, " + workers.length + " worker addresses, and  " + round.length + " rounds");
}
function tHeader(col, str)
{
	var selCol = (sortCol < 0 ? sortCol * (-1) : sortCol);
	return "<th " + (selCol == col ? "id='" + (selCol == sortCol ? "histselhead" : "histselheadasc") + "' " : "") + "onclick='workerSort("+col+");'>"+str+"</th>";
}

function buildWorkersTable()
{
	var filler;
	var workerData = round[selRound].workers;
	sortWorkers(workerData);
	var html = "<table id='histworkerstbl'><tr>";
	html += tHeader(1, "Address");
	html += tHeader(2, "Points");
	html += tHeader(3, "Paid ("+Brand_Unit+")");
	if (round[selRound].txid == false) // all txid's don't match
	{
		filler = "[none]"; // txid column filler for stats
		html += tHeader(4, "Transaction ID");
	}
	else filler = false;
	for (var i = 0;i < workerData.length;i++)
		if (showIdle || workerData[workerData[i][0]][2] != 0)
			html += getWorkerRow( workerData[workerData[i][0]][1], workerData[workerData[i][0]][2], workerData[workerData[i][0]][3], (filler == false ? false : workerData[workerData[i][0]][4]) );
	html += getWorkerRow("_minimum", parseFloat(round[selRound].minPoints).toFixed(0), round[selRound].minPay, filler);
	html += getWorkerRow("_maximum", parseFloat(round[selRound].maxPoints).toFixed(0), round[selRound].maxPay, filler);
	html += getWorkerRow("_average", parseFloat(round[selRound].avgPoints).toFixed(0), round[selRound].avgPay, filler);
	html += "</table><div id='csv' onclick='csvClicked();'>&lt;CSV&gt;</div></div>"
	document.getElementById("histworkers").innerHTML = html;
}

function buildWorkersCSV()
{
	var href = "data:application/octet-stream,address%2Cpoints%2Cpaid%2Ctxid%0A";
	var workerData = round[selRound].workers;
	sortWorkers(workerData);
	for (var i = 0;i < workerData.length;i++)
		if (showIdle || workerData[workerData[i][0]][4] != "[none]")
		{
			pay = parseFloat(workerData[workerData[i][0]][3]);
			if (isNaN(pay))
				pay = workerData[workerData[i][0]][3]; // keep text message if not a number
			else
				pay = pay.toFixed(4) // number value, fix to 4 decimals
			href += workers[workerData[workerData[i][0]][1]] + "%2C" + workerData[workerData[i][0]][2] + "%2C" + pay + "%2C" + txids[workerData[workerData[i][0]][4]] + "%0A";
		}
	return href;
}

function roundSelect(rnd)
{
	if (changeRoundDelayed)
		return;
	setTimeout(function(){changeRoundDelayed = false; }, 300);
	changeRoundDelayed = true;
	selRound = rnd;
	buildRound();
}

function workerSort(col)
{
	if (sortDelayed)
		return;
	sortDelayed = true;
	setTimeout(function(){sortDelayed = false; }, 300);
	col = parseInt(col);
	if (sortCol == col) sortCol = sortCol * (-1);
	else if ((sortCol * (-1)) == col) sortCol = col;
	else sortCol = col;
	buildWorkersTable();
}

function showIdleClicked()
{
	var obj = document.getElementById("show_idle");
	if (!obj)
		return;
	var wantShow = (obj.checked ? true : false);
	if (wantShow == showIdle)
		return;
	showIdle = wantShow;
	buildWorkersTable();	
}

function csvClicked()
{
	document.getElementById("csv").onclick = null;
	document.getElementById("csv").innerHTML = "<a href='" + buildWorkersCSV() + "' download='round.csv'>&lt;Download CSV&gt;</a>";
}
