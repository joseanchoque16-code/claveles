// **------ tab js**
$(document).on('click','.tab-link',function () {
	var tabID = $(this).attr('data-tab');
	
	$(this).addClass('active').siblings().removeClass('active');
	
	$('#tab-'+tabID).addClass('active').siblings().removeClass('active');
});

"use strict";
$(function() {
    var tooltip_init = {
        init: function () {
            $("a").tooltip();
        }
    };
    tooltip_init.init()
});



function getActions() {
	document.querySelectorAll('.delete-button').forEach(function (btn) {
		btn.addEventListener('click', function (event) {
			const deletbtn = event.target.closest(".project-card");
			// document.querySelector('#delet-tab-pane .row').appendChild(deletbtn.cloneNode(true));
			deletbtn.closest(".project-card").remove();
		});
	});
}

function resetModalInputs() {
  $('#pName').val('');
  $('#startDate').val('');
  $('#endDate').val('');
  $('#pricing').val('');
  $('#projectDescription').val('');
  document.querySelector(".file_upload").value = '';
}

document.querySelector('#addCard').onclick = function (event) {
	const rowElement = document.querySelector("#tab-1 .row");
	rowElement.insertAdjacentHTML('afterbegin', projectCardContent());
  resetModalInputs();
	$("#projectCard").modal("hide");
	getActions();	
}
getActions();	