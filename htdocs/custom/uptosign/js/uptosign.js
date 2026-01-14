$(document).ready(function () {
	if ($('#onlinesignatureurl').length > 0) {
		old = $('#onlinesignatureurl').val();
		if (typeof old == "string") {
			newurl = old.replace('/public/onlinesign/newonlinesign.php', "/custom/uptosign/public/newonlinesign.php");
			$('#onlinesignatureurl').val(newurl);
			$(".urllink a").prop('href', newurl);
		}
	}
});
