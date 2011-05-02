<?php

function bkpwp_display_message($msg) {
	?>
	<div id="message" class="updated fade"><p><?php 
	if (is_array($msg)) {
		foreach ($msg as $m) {
			echo $m."<br />";
		}
	} else {
		echo $msg;
	}?></p>
	</div>
	<br />
	<?php
}

function bkpwp_load_css_and_js() {
	echo "<style type='text/css'>
	h2 {
		padding-top: 20px;
	}
	legend {
		font-weight: bold;
	}
	input[type=submit] {
		cursor: pointer;
	}
       .error { 
	       color: red;
       }
       .success { 
	       color: green;
       }
       .bkpwp_manage_backups_newrow {
	       background: #99FF66;
       }
	</style>
	";
	?>
	<script type="text/javascript">
	
	var startTime=new Date();
	
	function currentTime(){
	  var a=Math.floor((new Date()-startTime)/100)/10;
	  if (a%1==0) a+=".0";
	  document.getElementById("endTime").innerHTML=a;
	}
	 
	<!-- displays a loading animation while doing ajax requests -->
	function is_loading(divid) {
		document.getElementById(divid).style.display = 'block';
		document.getElementById(divid).innerHTML="<img src='<?php bloginfo("wpurl"); ?>/wp-content/plugins/backupwordpress/images/loading.gif' />";
	}	
	</script>

	<script type="text/javascript">
	
	/***********************************************
	* Dynamic Countdown script- © Dynamic Drive (http://www.dynamicdrive.com)
	* This notice MUST stay intact for legal use
	* Visit http://www.dynamicdrive.com/ for this script and 100s more.
	***********************************************/
	
	function cdtime(container, targetdate){
	if (!document.getElementById || !document.getElementById(container)) return
	this.container=document.getElementById(container)
	this.currentTime=new Date("<?php echo date("F j, Y H:i:s"); ?>")
	this.targetdate=new Date(targetdate)
	this.timesup=false
	this.updateTime()
	}
	
	cdtime.prototype.updateTime=function(){
	var thisobj=this
	this.currentTime.setSeconds(this.currentTime.getSeconds()+1)
	setTimeout(function(){thisobj.updateTime()}, 1000) //update time every second
	}
	
	cdtime.prototype.displaycountdown=function(baseunit, functionref){
	this.baseunit=baseunit
	this.formatresults=functionref
	this.showresults()
	}
	
	cdtime.prototype.showresults=function(){
	var thisobj=this
	
	
	var timediff=(this.targetdate-this.currentTime)/1000 //difference btw target date and current date, in seconds
	if (timediff<0){ //if time is up
	this.timesup=true
	this.container.innerHTML=this.formatresults()
	return
	}
	var oneMinute=60 //minute unit in seconds
	var oneHour=60*60 //hour unit in seconds
	var oneDay=60*60*24 //day unit in seconds
	var dayfield=Math.floor(timediff/oneDay)
	var hourfield=Math.floor((timediff-dayfield*oneDay)/oneHour)
	var minutefield=Math.floor((timediff-dayfield*oneDay-hourfield*oneHour)/oneMinute)
	var secondfield=Math.floor((timediff-dayfield*oneDay-hourfield*oneHour-minutefield*oneMinute))
	if (this.baseunit=="hours"){ //if base unit is hours, set "hourfield" to be topmost level
	hourfield=dayfield*24+hourfield
	dayfield="n/a"
	}
	else if (this.baseunit=="minutes"){ //if base unit is minutes, set "minutefield" to be topmost level
	minutefield=dayfield*24*60+hourfield*60+minutefield
	dayfield=hourfield="n/a"
	}
	else if (this.baseunit=="seconds"){ //if base unit is seconds, set "secondfield" to be topmost level
	var secondfield=timediff
	dayfield=hourfield=minutefield="n/a"
	}
	this.container.innerHTML=this.formatresults(dayfield, hourfield, minutefield, secondfield)
	setTimeout(function(){thisobj.showresults()}, 1000) //update results every second
	}
	
	/////CUSTOM FORMAT OUTPUT FUNCTIONS BELOW//////////////////////////////
	
	//Create your own custom format function to pass into cdtime.displaycountdown()
	//Use arguments[0] to access "Days" left
	//Use arguments[1] to access "Hours" left
	//Use arguments[2] to access "Minutes" left
	//Use arguments[3] to access "Seconds" left
	
	//The values of these arguments may change depending on the "baseunit" parameter of cdtime.displaycountdown()
	//For example, if "baseunit" is set to "hours", arguments[0] becomes meaningless and contains "n/a"
	//For example, if "baseunit" is set to "minutes", arguments[0] and arguments[1] become meaningless etc
	
	
	function formatresultsh(){
	if (this.timesup==false){//if target date/time not yet met
		var displaystring=arguments[1]+" h "+arguments[2]+"' "+arguments[3]+"''"
	} else { //else if target date/time met
		var displaystring = "<?php _e("scheduling...","bkpwp");?>"
	}
	return displaystring
	}
	
	function formatresultsd(){
	if (this.timesup==false){//if target date/time not yet met
		var displaystring=arguments[0]+" days "+arguments[1]+"h"
	} else { //else if target date/time met
		var displaystring = "<?php _e("scheduling...","bkpwp");?>"
	}
	return displaystring
	}
	
	function formatresultsdh(){
	if (this.timesup==false){//if target date/time not yet met
		var displaystring=arguments[0]+" days "+arguments[1]+"h "+arguments[2]+"' "+arguments[3]+"''"
	} else { //else if target date/time met
		var displaystring = "<?php _e("scheduling...","bkpwp");?>"
	}
	return displaystring
	}
	
	</script>


	<?php
}
?>
