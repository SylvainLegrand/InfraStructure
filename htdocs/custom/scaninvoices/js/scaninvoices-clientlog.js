/*
 * ScanInvoices - client-side error reporter.
 *
 * Sends JS-side errors to the server (api.php/clientlog) so support can find them
 * in dolibarr.log by reference (grep 'ScanInvoices:CLIENT') instead of asking the
 * user for a screenshot. Fire-and-forget: it never blocks or breaks the UI.
 *
 * Requires jQuery and, for CSRF, window.SCANINVOICES_TOKEN (injected by the PHP
 * page). Returns a short client reference to display to the user so the same id
 * appears both on screen and in the server log.
 */
function scaninvoicesReportClientError(context, message, details) {
	var ref = 'CLI-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
	try {
		var token = (typeof window.SCANINVOICES_TOKEN === 'string') ? window.SCANINVOICES_TOKEN : '';
		$.ajax({
			url: 'api.php/clientlog/',
			type: 'POST',
			timeout: 8000,
			data: {
				level: 'error',
				context: String(context || ''),
				ref: ref,
				message: String(message || '').slice(0, 300),
				details: String(details || '').slice(0, 2000),
				token: token
			}
		});
	} catch (e) {
		/* never let logging break the UI */
	}
	return ref;
}
