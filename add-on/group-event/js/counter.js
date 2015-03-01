var ev_timezone = new Date();

var ev_gmtHours = - ev_timezone.getTimezoneOffset()/60;//получаем свой часовой пояс
//alert(ev_gmtHours);
//ev_month= --ev_month;

//dateFuture = new Date(ev_year,ev_month,ev_day,ev_hour+ev_gmtHours-ev_hour_round,ev_min,ev_sec);
function GetCount(){

		var amount = dateFuture*1000 - ev_dateNow*1000;
		ev_dateNow++;
        /* time is already past */
        if(amount < 0){
			if(html_end){
			 out = html_end;
			 document.getElementById('countbox').innerHTML="";
			 document.getElementById('end_text').innerHTML=out;
			}else{
                out=
				"<div id='days'><span></span>0<div id='days_text'></div></div>" + 
				"<div id='hours'><span></span>0<div id='hours_text'></div></div>" + 
				"<div id='mins'><span></span>0<div id='mins_text'></div></div>" + 
				"<div id='secs'><span></span>0<div id='secs_text'></div></div>" ;
				document.getElementById('countbox').innerHTML=out;     
			}
                       
        }
        /* date is still good */
        else{
                ev_days=0;ev_hours=0;ev_mins=0;ev_secs=0;out="";

                amount = Math.floor(amount/1000); /* kill the milliseconds */

                ev_days=Math.floor(amount/86400); /* days */
                amount=amount%86400;

                ev_hours=Math.floor(amount/3600); /* hours */
                amount=amount%3600;

                ev_mins=Math.floor(amount/60); /* minutes */
                amount=amount%60;

                
                ev_secs=Math.floor(amount); /* seconds */


                out=
				"<div id='days'><span></span>" + ev_days +"<div id='days_text'></div></div>" + 
				"<div id='hours'><span></span>" + ev_hours +"<div id='hours_text'></div></div>" + 
				"<div id='mins'><span></span>" + ev_mins +"<div id='mins_text'></div></div>" + 
				"<div id='secs'><span></span>" + ev_secs +"<div id='secs_text'></div></div>" ;
                document.getElementById('countbox').innerHTML=out;
			

                setTimeout("GetCount()", 1000);
        }
}

window.onload=function(){
	GetCount();
}


