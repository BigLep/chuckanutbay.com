function getDomHtml() {
	var html = document.getElementsByTagName('html')[0].innerHTML;
	html = stripScriptTags(html);
	html = makeLinksAbsolute(html);
	console.log(html);
	// Send back to server
	// Get PDF
}

function stripScriptTags(html) {
	var regex = /(?:<script([^>]*)?>)((\n|\r|.)*?)(?:<\/script>)/ig;
	return html.replace(regex, '');
}

function makeLinksAbsolute(html) {
	var newHtml = '';
	var host = document.location.href;
	var regex = /<img([^>]*)? src=([\'\"])(.*?)\2([^>]*)?>((?:\n|\r|.)*?)(<\/img>)?/ig;
	if (!regex.test(html)) {
		return html;
	}
	while((match = regex.exec(html))) {
		newHtml += RegExp.leftContext;
        var src = match[3];
		var newSrc = toAbsoluteLink(src, host);
		newHtml += '<img' + match[1] + ' src="' + newSrc + '"' + match[4] + '>' + match[5] + match[6];
		newHtml += RegExp.rightContext;
    }
	return newHtml;
}

/**
 * Converts the provided link to be an absolute link using the provided host.
 * http://www.phpied.com/relative-to-absolute-links-with-javascript/
 * http://github.com/stoyan/etc/blob/master/toAbs/absolute.html
 * @param {Object} link
 * @param {Object} host Path which relative urls should be calculated from.
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
