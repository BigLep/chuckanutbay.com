	// This script cruises the DOM of Apache's XHTML / HTMLTable
// directory output, and injects useful class names throughout.
//
// It's part of Indices: http://antisleep.com/software/indices

function init() {
    var tablerows = document.getElementsByTagName("tr");

    for (var i=0; i < tablerows.length; i++) {
        var currow = tablerows[i];

        if (i == 0) {
            currow.className += " row_header";
        } else if (i == 1) {
            currow.className += " row_parentdir";
        } else {
            currow.className += " row_normal";
        }

        var rowcells = currow.getElementsByTagName((i == 0 ? "th" : "td"));
        rowcells[0].className += " col_icon";
        rowcells[1].className += " col_name";
        rowcells[2].className += " col_date";
        rowcells[3].className += " col_size";
        // apache output is sort of broken-tabley for the description column
        if (rowcells[4]) rowcells[4].className += " col_desc";

        var namecell = rowcells[1];
        var anchors = namecell.getElementsByTagName("a");
        if (anchors.length == 1) {
            var curanchor = anchors[0];

            var anchorcontent = curanchor.innerHTML;
            if (curanchor.parentNode.tagName == "TD") {
                if (anchorcontent.match(/\/$/)) {
                    // add a class for directories, and strip the trailing slash.
                    curanchor.className = "dirlink";
                    anchorcontent = anchorcontent.replace(/\/$/, "");
                } else {
                    curanchor.className = "filelink";
                }
            }

            // insert a div inside each link in the table.  this will cause the entire
            // table cell to be the anchor target, which is nice.
            if (curanchor.parentNode.tagName == "TD" || curanchor.parentNode.tagName == "TH") {
                curanchor.innerHTML = "<div class='linkgrower'>" + anchorcontent + "</div>";
            }
        }

        for (j=0; j < rowcells.length; j++) {
            var curcell = rowcells[j];

            // the "parent directory" row
            if (i == 0) {
                curcell.className += " cell_header";
            } else if (i == 1) {
                curcell.className += " cell_parentdir";
            }
        }
    }

    // Content is hidden by a piece of script in the div tag, to prevent browsers (IE)
    // that show the original content before this JS executes.  So, show it now.
    var container = document.getElementById("pagecontainer");
    container.style.display = 'block';
}

//========================================================================

// Add init() as onload handler.
if (window['addEventListener']) {
    window.addEventListener("load", init, false);
} else if (window['attachEvent']) {
    window.attachEvent("onload", init);
}
