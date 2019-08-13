/*
	dfahlivedata.js
	Live data poller from JSON file prepared by DogecoinFah

*/
//------Customize--------------
var Brand_Address_Link = "http://dogechain.info/address/";
var Brand_Tx_Link = "http://dogechain.info/tx/";
var Brand_Unit = "DOGE";
//-----------------------------
var updateRate = 180000; // 0 for off, 180,000 msecs / 3 minutes, 300,000 msecs / 5 minutes
var http_request;
var public_data_file = "PublicData.json";
var public_team_data_file = "PublicTeamData.json";
var lastAddress = "";
var lasthttpreq = "";
var lasthttpreqTeam = "";
var teamDataRequested = false;

const C_CONM_NONE = 0;
const C_CONM_FLAT = 1;
const C_CONM_PER  = 2;
const C_CONM_ALL  = 3;
const C_CONM_EACH = 4;

const CONT_AD_MODE_NONE =  			0;
const CONT_AD_MODE_SHOW_ALWAYS = 	1;
const CONT_AD_MODE_SHOW_TITLE = 	2;
const CONT_AD_MODE_SHOW_VALUE = 	4;
const CONT_AD_MODE_SHOW_TEXT =	 	8;
const CONT_AD_MODE_SHOW_IMAGE = 	16;
const CONT_AD_MODE_LINK_TITLE =  	32;
const CONT_AD_MODE_LINK_TEXT =  	64;
const CONT_AD_MODE_LINK_IMAGE = 	128;


if (!Date.now){ // IE8 compat
	Date.now = function() { return new Date().getTime(); } 
} 

function setCharAt(str,index,chr) {
    if(index > str.length-1) return str;
    //console.log("full '" +str+ "' first (" + index +") '" + str.substr(0,index) + "' end '" +  str.substr(index+1) + "' char '" + chr + "'");
    return str.substr(0,index) + chr + str.substr(index+1);
}
function initLiveData()
{
	// ------------------Extra init for email protection
	
	var m1 = document.getElementById("m1");
	var m2 = document.getElementById("m2");
	var m3 = document.getElementById("m3");
	var m4 = document.getElementById("m4");
	if (m1 && m1.href.length > 27)
		m1.href = m1.href.substr(0,10) + '@' + m1.href.substr(13, 11) + '.' + m1.href.substr(27);
	if (m2 && m2.href.length > 27)
		m2.href = m2.href.substr(0,10) + '@' + m2.href.substr(13, 11) + '.' + m2.href.substr(27);
	if (m3 && m3.href.length > 28)
		m3.href = m3.href.substr(0,12) + '@' + m3.href.substr(15, 11) + '.' + m3.href.substr(29);
	if (m4 && m4.href.length > 28)
		m4.href = m4.href.substr(0,12) + '@' + m4.href.substr(15, 11) + '.' + m4.href.substr(29);
	// ------------------ Init html request
	http_request = new XMLHttpRequest();
	try{
	   // Opera 8.0+, Firefox, Chrome, Safari
	   http_request = new XMLHttpRequest();
	}catch (e){
	   // IE
	   try{
		  http_request = new ActiveXObject("Msxml2.XMLHTTP");
			
	   }catch (e) {
		
		  try{
			 http_request = new ActiveXObject("Microsoft.XMLHTTP");
		  }catch (e){
			 // Something went wrong
			 alert("Your browser's JavaScript cannot access a proper XML HTTP request interface.");
			 return false;
		  }
	   }
	}
	// ------------------ Make the first request
	requestLiveData();			
	// ------------------
} // initLiveData

function requestLiveData()
{
	http_request.onreadystatechange = function()
	{
	   if (http_request.responseURL.endsWith(lasthttpreq) && http_request.readyState == 4)
	   {
			updateData(JSON.parse(http_request.responseText));
			if (updateRate != 0)
				setTimeout("requestLiveData()", updateRate);
			if (!teamDataRequested)
			{
				teamDataRequested = true;
				requestLiveTeamData(); // request Live Team Data only once, after first LiveData request
			}
			lasthttpreq = "";
		}
	}
	lasthttpreq = public_data_file + "?t=" + Date.now();
	http_request.open("GET", lasthttpreq, true);
	http_request.send();
}

function requestLiveTeamData()
{
	http_request.onreadystatechange = function()
	{
		if (http_request.responseURL.endsWith(lasthttpreqTeam) && http_request.readyState == 4)
		{
			try
			{
				updateTeamData(JSON.parse(http_request.responseText));		  
			}
			catch(e)
			{
				// request probably failed
			}
			lasthttpreqTeam = "";
		}
	}
	lasthttpreqTeam = public_team_data_file + "?t=" + Date.now();
	http_request.open("GET", lasthttpreqTeam, true);
	http_request.send();
}

function getBalRow(name, val, unit = false){
	return "<tr><td>"+name+"</td><td>"+val+"</td><td>" + (unit == false ? "&nbsp;" : unit ) + "</td></tr>";
}

function getTeamRow(name, val){
	return "<tr><td>"+name+"</td><td>"+val+"</td></tr>";
}

function updateData(jsonObj)
{
	var dcLink = Brand_Address_Link + jsonObj.activeAddress;
	var htmlBal = "<table id='livebalextra'>";
	var fee =  parseFloat(jsonObj.fee);
	var bal = parseFloat(jsonObj.activeBalance);
	var dtUpdated = new Date(jsonObj.UTC);
	var age = ((new Date()) - dtUpdated) / 1000.0 / 60.0; // Date values are in miliseconds, convert to minutes.
	var htmlUpdated = "<div style='line-height:90%;";
	if (age >= 120.0) // Update expected every 5 minutes. Red >= one hour, Yellow >= 10 minutes
		htmlUpdated += "color:red;";
	else if (age >= 30.0)
		htmlUpdated += "color:#FF9205;"; // orange
	else 
		htmlUpdated += "font-size:90%;";
	htmlUpdated += "'>Updated: "+dtUpdated.toLocaleDateString()+" "+dtUpdated.toLocaleTimeString()+"</div>";
	htmlBal += getBalRow("Donations",bal);
	htmlBal += getBalRow("Fee", "-" + fee);
	bal -=  fee; // take fee off top, so % contrs calc correctly
    var DebugBalance = false;
	for (var i = 0;i < jsonObj.contributions.length;i++)
	{
	  var c = jsonObj.contributions[i];
	  var name = c.name;
	  var value = parseFloat(c.value);
	  if (name == "") name = "Contribution " + c.number;
	  if (c.mode != C_CONM_NONE && c.mode != C_CONM_EACH){
		  if (c.mode == C_CONM_PER){
			  bal += (bal * (value / 100.0));
			  value += "%";
		  }
		  else bal += value;
		  htmlBal += getBalRow(name,value, (DebugBalance ? bal : false));
	 }
	}
	htmlBal += getBalRow("Total",bal, Brand_Unit);
	htmlBal += "</table>";

	bal = 0.0;
	var htmlEachBal = "";
	var eachCount = 0;
	for (var i = 0;i < jsonObj.contributions.length;i++)
	   if (jsonObj.contributions[i].mode == C_CONM_EACH)
	   {
		  var value = parseFloat(jsonObj.contributions[i].value);
		  bal += value
		  eachCount++;
		  htmlEachBal += getBalRow(jsonObj.contributions[i].name, value, (DebugBalance ? bal : "each"));
	   }
	if (eachCount > 0)
	{
		htmlBal += "<br/><span style='line-height:90%;font-size:90%;'>Additionally:</span><br/><table id='livebalextra'>";
		htmlBal += htmlEachBal;
		if (eachCount > 1)
			htmlBal += getBalRow("Total", bal, "each");
		htmlBal += "</table>";
	}
	document.getElementById("liveaddr").innerHTML = "<div style='overflow: hidden;text-overflow: ellipsis;'>" + jsonObj.activeAddress + "</div>";
	document.getElementById("liveaddr").href = dcLink;
	document.getElementById("livebal").innerHTML = htmlBal;
	document.getElementById("liveqr").href = dcLink;
	document.getElementById("livedatadate").innerHTML = htmlUpdated;

	if (lastAddress != jsonObj.activeAddress)
	{
	  node = document.getElementById("liveqr");
	  while (node.hasChildNodes()) 
		node.removeChild(node.lastChild);
	  
	  $(document.getElementById("liveqr")).qrcode({	"render": "div",
													"left": 0,
													"top": 0,
													"size": 100,
													"color": "#3a3",
													"text": jsonObj.activeAddress
													});
	 lastAddress = jsonObj.activeAddress;
	}
	updateContributionData(jsonObj);
}

function htmlLPad(str, len)
{
	len -= str.length;
	while (len--)
		str = "&nbsp;" + str;
	return str;
}

function commaNum(num)
{
	return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function updateTeamData(jsonObj)
{
	var htmlTeam = "<div id='liveteamex'>";
	var teamLink = "<a href='http://folding.extremeoverclocking.com/team_summary.php?s=&amp;t="+parseInt(jsonObj.id)+"'>";
	var name = ""+jsonObj.name;
	var rank24 = parseInt(jsonObj.rank24);
	var rank7 = parseInt(jsonObj.rank7);
	var rank24c;
	var rank7c;
	name = name.charAt(0).toUpperCase() + name.substr(1);
	if (rank24 > 0)
	{
		rank24 = "+" + rank24;
		rank24c = "green";
	}
	else if (rank24 == 0) rank24c = "yellow";
	else rank24c = "red";
	if (rank7 > 0)
	{
		rank7 = "+" + rank7;
		rank7c = "green";
	}
	else if (rank7 == 0) rank7c = "yellow";
	else rank7c = "red";
	rank24 = htmlLPad(""+rank24, 4);
	rank7 = htmlLPad(""+rank7, 4);
	htmlTeam += teamLink+name+" #"+parseInt(jsonObj.id)+"</a><br/>";
	htmlTeam += "<div style='color:#a0a0a0;font-size:60%;'>";
	htmlTeam += "Active Members:<span style='font-size:140%;color:white;'>&nbsp;"+commaNum(parseInt(jsonObj.actusers))+"</span>/"+commaNum(parseInt(jsonObj.users));
	htmlTeam += "<div style='padding:3px 0 0 24px;float:left;'><span>Team Rank:</span><span style='font-size:140%;color:white;'>&nbsp;#"+commaNum(parseInt(jsonObj.rank))+"</span></div>";
	htmlTeam += "<div style='padding:0;font-size:90%;font-family:monospace;line-height:90%;padding-bottom:5px;'>";
	htmlTeam += "<span style='color:"+rank24c+";'>"+rank24+"</span>&nbsp;day<br/>";
	htmlTeam += "<span style='color:"+rank7c+";'>"+rank7+"</span>&nbsp;week</div>";
	htmlTeam += "<span style='padding:0 0 5px 25px;'>Work Units:<span style='font-size:140%;color:white;'>&nbsp;"+commaNum(parseInt(jsonObj.wu))+"</span></span></br>";
	htmlTeam += "<span>Points:<span style='font-size:140%;color:white;'>&nbsp;" + commaNum(parseInt(jsonObj.points)) + "</span>";
	htmlTeam += "&nbsp;<span><span style='color:green;'>"+commaNum(parseInt(jsonObj.points24))+"</span>&nbsp;day</span>";
	htmlTeam += "</div></div>";
	document.getElementById("extratitle").innerHTML = htmlTeam;
}

function getContributionBlock(cname, cmode, cvalue, cstyle, clink, ctext, cimage)
{
	var html;
	if (cstyle != "")
		html = "<div class='" + cstyle + "'>";
	else
		html = "<div>";

	if ( (cmode & CONT_AD_MODE_SHOW_TITLE) != 0 && cname != "")
	{
		html += "<div id='cont_title'>";
		if ( (cmode & CONT_AD_MODE_LINK_TITLE) != 0)
			html += "<a href='" + clink + "'>";
		html += cname;
		if ( (cmode & CONT_AD_MODE_LINK_TITLE) != 0)
			html += "</a>";
		html += "</div>";
	}

	if ( (cmode & CONT_AD_MODE_SHOW_VALUE) != 0)
		html += "<div id='cont_value'>" + cvalue + "</div>";

	if ( (cmode & CONT_AD_MODE_SHOW_IMAGE) != 0)
	{
		html += "<div id='cont_img'>";
		if ( (cmode & CONT_AD_MODE_LINK_IMAGE) != 0)
			html += "<a href='" + clink + "'>";
		html += "<img src='" + cimage + "' alt='" + cname + "' />";
		if ( (cmode & CONT_AD_MODE_LINK_IMAGE) != 0)
			html += "</a>";
		html += "</div>";
	}

	if ( (cmode & CONT_AD_MODE_SHOW_TEXT) != 0 && ctext != "")
	{
		html += "<div id='cont_txt'>";

		if ( (cmode & CONT_AD_MODE_LINK_TEXT) != 0)
			html += "<a href='" + clink + "'>" + ctext + "</a>";
		else 
			html += "<span>" + ctext + "</span>";
		html += "</div>";
	}
	
	return html + "</div>";
}

function updateContributionData(jsonObj)
{
	var html = "<ul class=\"contad\" >";
	for (var i = 0;i < jsonObj.contributions.length;i++)
	{
		var c = jsonObj.contributions[i];
		var name = c.name;
		var value = parseFloat(c.value);
		if (name == "") name = "Contribution " + c.number;

		if (c.mode == C_CONM_NONE)
			value = "(none)";
		else if (c.mode == C_CONM_PER)
			value += "%";
		value += "&nbsp;" + Brand_Unit;
		if (c.mode == C_CONM_EACH)
			value += " each";
		if ( ( c.mode != C_CONM_NONE && c.adMode != CONT_AD_MODE_NONE) || (c.adMode & CONT_AD_MODE_SHOW_ALWAYS) != 0 )
			html += "<li>" + getContributionBlock(name, c.adMode, value, c.adStyle, c.adLink, c.adText, c.adImage) + "</li>";
	}
	html += "</ul>";
	document.getElementById("contributions").innerHTML = html;
}

