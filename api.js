var learner_id = 0;
var learner_etat;
var mytimer;
var ajaxFolder = "ITSec_01_Email";
getuser();

jQuery(document).ready(function(){
  mytimer = new Date();
});

function getuser(){

    // $.ajax({
    //   type: 'POST',
    //   url: "/campus/modules/ajax/"+ajaxFolder+"/user_info.php",
    //   data: { func: "getmonusr" },
    //   success: function(data){
    //     // console.log(data);
    //     // var resp = JSON.parse(data);
    //     // console.log(resp);
    //     if(data.status == 'ok') {
    //       learner_id = data.id;
    //       learner_etat = parseInt(data.etat.fini_formation);
    //       // console.log(data.etat.fini_formation);
    //       if(isNaN(learner_etat)){
    //         learner_etat = 0;
    //       }
    //     }
    //   },
    //   dataType: 'json',
    //   async:false
    // });

}

var ScormFu = {
  get:function(key,callback) {
    if (typeof console === "undefined" || typeof console.log === "undefined") {
      console.log(key);
    }

    // $.ajax({
    //   type: 'POST',
    //   url: "/campus/modules/ajax/"+ajaxFolder+"/scormcall.php",
    //   data: { func: "getscorm", cle: key, learner_id: learner_id},
    //   success: function(data){
    //     // var resp = JSON.parse(data);
    //     // console.log(data);
    //     callback(data.val);
    //   },
    //   dataType: 'json',
    //   async:false
    // });
    callback(localStorage.getItem(key));
      console.warn('GET '+key);
  },
  set:function(key,value) {
    if (typeof console === "undefined" || typeof console.log === "undefined") {
      console.log(key);
      console.log(value);
    }
    localStorage.setItem(key, value);
      console.warn('SET '+key+' '+value);
    // $.post( "/campus/modules/ajax/"+ajaxFolder+"/scormcall.php", { func: "setscorm", cle: key, val: value, learner_id: learner_id}, function( data ) {
    //   //console.log(data);
    // }, "json");

    if(key == "cmi.completion_status" || key == "cmi.core.lesson_status"){
        // console.log(key);
        // console.log(value);
		if(value == "completed") {
			// console.log("FINI FORMATION");
		  // $.post( "/campus/modules/ajax/"+ajaxFolder+"/tracking.php", { tab: {user_id:learner_id, fini_formation: (learner_etat+1)} }, function( data ) {
			// // console.log(data);
		  // }, "json");
		}
    }
    if(key == "scaled_score" || key == "cmi.score.scaled"){
		var theScore = Math.round(value * 100);
		
		// $.post( "/campus/modules/ajax/"+ajaxFolder+"/tracking.php", { tab: {user_id:learner_id, score: theScore} }, function( data ) {
		// 	// console.log(data);
		// }, "json");
	  } 

  },
  session:"",
  last:"",
  complete:false
};
