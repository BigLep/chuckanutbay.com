/**
 * Shows a window with a link to a PDF that a user can open in the current window or another.
 * @param {String} pdfPath Path of the PDF to link to.
 * Relative links should be relative to display page.
 */
function showPdfDownloadWindow(pdfPath) {
	var fileName = pdfPath.split('/').pop();
	var window = new Ext.Window({
		title : 'Download PDF (click on link)',
		iconCls : 'pdf-icon',
		html : [
			'<div class="download-pdf">',
				'<a href="' + pdfPath + '">', 
					'<img src="' + Ext.BLANK_IMAGE_URL + '" class="pdf-icon"/>',
				'</a>',
				'<br/>',
				'<a href="' + pdfPath + '">', 
					'<span>' + fileName + '</span>',
				'</a>',
				'<br/>',
				'<span>(click on link above)</span>',
			'</div>'
		].join('\n'),
		buttonAlign : 'center',
		modal : true,
		resizable : false
	});
	window.addButton({
		text : 'Done',
		handler : function() {
			window.close();
		}
	});
	window.show();
};

/**
 * @return {String} HTML of the current page, including any dynamically generated content.
 * "script" tags are removed, and "image" links are made absolute.
 */
function getDomHtml() {
	var html = document.getElementsByTagName('html')[0].innerHTML;
	html = stripScriptTags(html);
	html = stripObjectTags(html);
	html = makeImageLinksAbsolute(html);
	html = makeCssLinksAbsolute(html);
	html = '<html>' + html + '</html>';
	return html;
}

/**
 * @param {Object} html HTML to strip "script" tags from.
 * @return {String} provided HTML with all "script" tags stripped.
 */
function stripScriptTags(html) {
	// This RegEx was taken from Ext's Element class.
	var regex = /(?:<script([^>]*)?>)((\n|\r|.)*?)(?:<\/script>)/ig;
	return html.replace(regex, '');
}

/**
 * @param {Object} html HTML to strip "script" tags from.
 * @return {String} provided HTML with all "script" tags stripped.
 */
function stripObjectTags(html) {
	var regex = /(?:<object([^>]*)?>)((\n|\r|.)*?)(?:<\/object>)/ig;
	return html.replace(regex, '');
}

/**
 * @param {Object} html HTML to make links absolute for.
 * @return {String} provided HTML with all relative image links converted to be absolute.
 */
function makeImageLinksAbsolute(html) {
	var newHtml = '';
	var host = document.location.href;
	var regex = /<img([^>]*)? src=([\'\"])(.*?)\2([^>]*)?>((?:\n|\r|.)*?)(<\/img>)?/gim;
	var matches;
	var searchStrs = [];
	var replaceStrs = [];
	while((matches = regex.exec(html))) {
		searchStrs.push(matches[0]);
        var srcUrl = matches[3];
		var newSrcUrl = toAbsoluteLink(srcUrl, host);
		replaceStrs.push('<img' + emptyIfUndefined(matches[1]) + ' src="' + newSrcUrl + '"' + emptyIfUndefined(matches[4]) + '>' + emptyIfUndefined(matches[5]) + emptyIfUndefined(matches[6]));
    }
	return replaceStrings(html, searchStrs, replaceStrs);
}

/**
 * @param {Object} html HTML to make links absolute for.
 * @return {String} provided HTML with all relative css links converted to be absolute.
 */
function makeCssLinksAbsolute(html) {
	var newHtml = '';
	var host = document.location.href;
	var regex = /<link([^>]*)? href=([\'\"])(.*?)\2([^>]*)?>((?:\n|\r|.)*?)(<\/link>)?/gim;
	var matches;
	var searchStrs = [];
	var replaceStrs = [];
	while((matches = regex.exec(html))) {
		searchStrs.push(matches[0]);
        var hrefUrl = matches[3];
		var newHrefUrl = toAbsoluteLink(hrefUrl, host);
		replaceStrs.push('<link' + emptyIfUndefined(matches[1]) + ' href="' + newHrefUrl + '"' + emptyIfUndefined(matches[4]) + '>' + emptyIfUndefined(matches[5]) + emptyIfUndefined(matches[6]));
    }
	return replaceStrings(html, searchStrs, replaceStrs);
}

/**
 * For every searchStr found within str, replaces it with the matching replaceStr.
 * @param {String} str
 * @param {String} searchStrs
 * @param {String} replaceStrs
 * @return {String} For every searchStr found within str, replaces it with the matching replaceStr.
 * The result is returned.
 */
function replaceStrings(str, searchStrs, replaceStrs) {
	var newStr = str;
	for (var i = 0; i < searchStrs.length; i++) {
		newStr = newStr.replace(searchStrs[i], replaceStrs[i]);
	}
	return newStr;
}

/**
 * @param {String} str String to evaluate.
 * @return {String} The provided string if it is non-null, otherwise return the empty string.
 */
function emptyIfUndefined(str) {
	return str ? str : '';
}

/**
 * Converts the provided link to be an absolute link using the provided host.
 * http://www.phpied.com/relative-to-absolute-links-with-javascript/
 * http://github.com/stoyan/etc/blob/master/toAbs/absolute.html
 * @param {String} link relative url to make into an absolute url.
 * Note: I would like to rename this to relativeUrl, but am leaving it as the originally created called it.
 * @param {String} host Path which relative urls should be calculated from.
 * @return {String} Provided relative URL as an absolute URL using the provided host.
 */
function toAbsoluteLink(link, host) {

    var lparts = link.split('/');
    if (/http:|https:|ftp:/.test(lparts[0])) {
        // already abs, return
        return link;
    }
    
    var i, hparts = host.split('/');
    if (hparts.length > 3) {
        hparts.pop(); // strip trailing thingie, either scriptname or blank
    }
    
    if (lparts[0] === '') { // like "/here/dude.png"
        host = hparts[0] + '//' + hparts[2];
        hparts = host.split('/'); // re-split host parts from scheme and domain only
        delete lparts[0];
    }
    
    for (i = 0; i < lparts.length; i++) {
        if (lparts[i] === '..') {
            // remove the previous dir level, if exists
            if (typeof lparts[i - 1] !== 'undefined') {
                delete lparts[i - 1];
            }
            else 
                if (hparts.length > 3) { // at least leave scheme and domain
                    hparts.pop(); // stip one dir off the host for each /../
                }
            delete lparts[i];
        }
        if (lparts[i] === '.') {
            delete lparts[i];
        }
    }
    
    // remove deleted
    var newlinkparts = [];
    for (i = 0; i < lparts.length; i++) {
        if (typeof lparts[i] !== 'undefined') {
            newlinkparts[newlinkparts.length] = lparts[i];
        }
    }
    
    return hparts.join('/') + '/' + newlinkparts.join('/');
    
}
