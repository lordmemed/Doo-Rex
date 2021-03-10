/* Toggle between adding and removing the "responsive" class to topnav when the user clicks or lost focus on the fnavbar icon */
function fnavbar() {
	var x = document.getElementById("itopnav");
	if (x.className==="topnav") {
		x.className += " responsive";
	}else{
		x.className = "topnav";
	}
}