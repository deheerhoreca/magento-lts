/*
 dragtable v1.0
 June 26, 2008
 Dan Vanderkam, http://danvk.org/dragtable/
 http://code.google.com/p/dragtable/
 
 Instructions:
 - Download this file
 - Add <script src="dragtable.js"></script> to your HTML.
 - Add class="draggable" to any table you might like to reorder.
 - Drag the headers around to reorder them.
 
 This is code was based on:
 - Stuart Langridge's SortTable (kryogenix.org/code/browser/sorttable)
 - Mike Hall's draggable class (http://www.brainjar.com/dhtml/drag/)
 - A discussion of permuting table columns on comp.lang.javascript
 
 Licensed under the MIT license.
 */

// Here's the notice from Mike Hall's draggable script:
//*****************************************************************************
// Do not remove this notice.
//
// Copyright 2001 by Mike Hall.
// See http://www.brainjar.com for terms of use.
//*****************************************************************************
dragtable = {
    // How far should the mouse move before it's considered a drag, not a click?
    dragRadius2: 100,
    clickDelay: 300, //delay to click contenteditable=true
    classHandle: '', // 'mytable-move-column',//handle class
    //downTimer: null, //down timer for click
    setMinDragDistance: function(x) {
        dragtable.dragRadius2 = x * x;
    },
    // How long should cookies persist? (in days)
    cookieDays: 365,
    setCookieDays: function(x) {
        dragtable.cookieDays = x;
    },
    // Determine browser and version.
    // TODO: eliminate browser sniffing except where it's really necessary.
    Browser: function() {
        var ua, s, i;

        this.isIE = false;
        this.isNS = false;
        this.version = null;
        ua = navigator.userAgent;

        s = "MSIE";
        if ((i = ua.indexOf(s)) >= 0) {
            this.isIE = true;
            this.version = parseFloat(ua.substr(i + s.length));
            return;
        }

        s = "Netscape6/";
        if ((i = ua.indexOf(s)) >= 0) {
            this.isNS = true;
            this.version = parseFloat(ua.substr(i + s.length));
            return;
        }

        // Treat any other "Gecko" browser as NS 6.1.
        s = "Gecko";
        if ((i = ua.indexOf(s)) >= 0) {
            this.isNS = true;
            this.version = 6.1;
            return;
        }
    },
    browser: null,
    // Detect all draggable tables and attach handlers to their headers.
    init: function() {
        // Don't initialize twice
        if (arguments.callee.done)
            return;
        arguments.callee.done = true;
        if (_dgtimer)
            clearInterval(_dgtimer);
        if (!document.createElement || !document.getElementsByTagName)
            return;

        dragtable.dragObj.zIndex = 0;
        dragtable.browser = new dragtable.Browser();
        forEach(document.getElementsByTagName('table'), function(table) {
            if (table.className.search(/\bdraggable\b/) != -1) {
                dragtable.makeDraggable(table);
            }
        });
    },
    // The thead business is taken straight from sorttable.
    makeDraggable: function(table) {
        if (table == undefined) {
            return;
        }
        if (table.getElementsByTagName('thead').length == 0) {
            the = document.createElement('thead');
            the.appendChild(table.rows[0]);
            table.insertBefore(the, table.firstChild);
        }
        //var $dragtooltip = $('<div id="dragtable-tooltip" class="dragtable-tooltip-hold control-ui" style="height: 20px; position: absolute; top: -20px; left: 0px;"></div>'); //hold to move
        if (this.classHandle != '') { //if defined handle
            var pos = $(table).position();
            //console.log(table);
            //console.log(pos);
            //add handles el
            dragtable.dragObj.$handle = $('<table id="' + table.id + '-handle" class="' + table.id + '-handle" border=0 cellpadding=0 cellspacing=0 style="border-collapse: collapse;"></table>');
            dragtable.dragObj.$handle.css("position", "absolute");
            dragtable.dragObj.$handle.css("top", pos.top);
            dragtable.dragObj.$handle.css("left", pos.left);
            dragtable.dragObj.$handle.css("z-index", 9999999);
            dragtable.dragObj.$handle.css("width", "auto");
            //dragtable.dragObj.$handle.css("background-color", "red");
            //var $handle = dragtable.dragObj.$handle;
            var cells = table.getElementsByTagName('tr')[0].cells;
            var $tr = $('<tr></tr>').height(cells[0].offsetHeight);
            for (var i = 0; i < cells.length; i++) {
                //$handle.$td.index = i;
                $(cells[i]).css('position', 'relative');
                $tr.append(
                        $('<td style="position: relative;"></td>').width(cells[i].offsetWidth)
                        .append('<div class="' + this.classHandle + '"></div>')
                        .on('mousedown', dragtable.dragStart)
                        .hover(
                                function() {
                                    $(this).addClass('dragtable-hover');
                                },
                                function() {
                                    $(this).removeClass('dragtable-hover');
                                })//table tooltip
                        .mouseover(function(e) {
                            if ($(this).find('.editContentTable').length <= 0) {
                                //Append the tooltip template and its value
                                $('body').append('<div id="tooltip" class="tooltip control-ui">' + tooltipText + '</div>');
                                //Show the tooltip with faceIn effect
                                $('#tooltip').fadeIn('500');
                                //$('#tooltip').fadeTo('10',0.9);
                            }
                        })
                        .mousemove(function(e) {
                            if ($('#tooltip').length) {
                                //Keep changing the X and Y axis for the tooltip, thus, the tooltip move along with the mouse
                                $('#tooltip').css('top', e.pageY - 20);
                                $('#tooltip').css('left', e.pageX + 10);
                            }
                        })
                        .mouseout(function() {
                            if ($('#tooltip').length) {
                                //Remove the appended tooltip template
                                $('#tooltip').remove();
                            }
                        })
                        );

            }
            dragtable.dragObj.$handle.append($tr);
            table.parentNode.insertBefore(dragtable.dragObj.$handle.get(0), table);
            //dragtable.dragObj.$handle.appendTo('body');

        } else {
            // Safari doesn't support table.tHead, sigh
            if (table.tHead == null) {
                table.tHead = table.getElementsByTagName('thead')[0];
            }
            var headers = table.tHead.rows[0].cells;
            for (var i = 0; i < headers.length; i++) {
                headers[i].onmousedown = dragtable.dragStart;
                //make hover tooltip using jquery
                $(headers[i]).hover(function() {
                    $(this).addClass('dragtable-hover');
                }, function() {
                    $(this).removeClass('dragtable-hover');
                });

                var timeRemove;
                //table tooltip
                $(headers[i]).mouseover(function(e) {
                    if ($(this).find('.editContentTable').length <= 0) {
                        //Append the tooltip template and its value
                        $('body').append('<div id="tooltip" class="tooltip control-ui" style="display: none;"><div class="tooltip-content">' + tooltipText + '</div></div>');
                        //Show the tooltip with faceIn effect
                        $('#tooltip').fadeIn('300');
                        //$('#tooltip').fadeTo('10',0.9);
                        //delay to hide
                        clearTimeout(timeRemove);
                        timeRemove = setTimeout(function(){
                            $('#tooltip').remove();
                        }, 3000);
                    }
                }).mousemove(function(e) {
                    if ($('#tooltip').length) {
                        //Keep changing the X and Y axis for the tooltip, thus, the tooltip move along with the mouse
                        $('#tooltip').css('top', e.pageY - 20);
                        $('#tooltip').css('left', e.pageX + 10);
                        
                        clearTimeout(timeRemove);
                        timeRemove = setTimeout(function(){
                            $('#tooltip').remove();
                        }, 3000);
                    }
                }).mouseout(function() {
                    if ($('#tooltip').length) {
                        //Remove the appended tooltip template
                        $('#tooltip').remove();
                    }
                });
            }
        }
        //disable can select text in heade cell
        $(table).find('tr:first th, tr:first td').not('div').disableSelection();
        // Replay reorderings from cookies if there are any.
        if (dragtable.cookiesEnabled() && table.id &&
                table.className.search(/\bforget-ordering\b/) == -1) {
            //dragtable.replayDrags(table); //not use replayDrags function
        }

        return this;
    },
    // Global object to hold drag information.
    dragObj: new Object(),
    // Climb up the DOM until there's a tag that matches.
    findUp: function(elt, tag) {
        do {
            if (elt.nodeName && elt.nodeName.search(tag) != -1)
                return elt;
        } while (elt = elt.parentNode);
        return null;
    },
    // clone an element, copying its style and class.
    fullCopy: function(elt, deep) {
        var new_elt = elt.cloneNode(deep);
        new_elt.className = elt.className;
        forEach(elt.style,
                function(value, key, object) {
                    if (value == null)
                        return;
                    if (typeof (value) == "string" && value.length == 0)
                        return;

                    new_elt.style[key] = elt.style[key];
                });
        return new_elt;
    },
    eventPosition: function(event) {
        var x, y;
        if (dragtable.browser.isIE) {
            x = window.event.clientX + document.documentElement.scrollLeft
                    + document.body.scrollLeft;
            y = window.event.clientY + document.documentElement.scrollTop
                    + document.body.scrollTop;
            return {x: x, y: y};
        }
        return {x: event.pageX, y: event.pageY};
    },
    // Determine the position of this element on the page. Many thanks to Magnus
    // Kristiansen for help making this work with "position: fixed" elements.
    absolutePosition: function(elt, stopAtRelative) {
        var ex = 0, ey = 0;
        do {
            var curStyle = dragtable.browser.isIE ? elt.currentStyle
                    : window.getComputedStyle(elt, '');
            var supportFixed = !(dragtable.browser.isIE &&
                    dragtable.browser.version < 7);
            if (stopAtRelative && curStyle.position == 'relative') {
                break;
            } else if (supportFixed && curStyle.position == 'fixed') {
                // Get the fixed el's offset
                ex += parseInt(curStyle.left, 10);
                ey += parseInt(curStyle.top, 10);
                // Compensate for scrolling
                ex += document.body.scrollLeft;
                ey += document.body.scrollTop;
                // End the loop
                break;
            } else {
                ex += elt.offsetLeft;
                ey += elt.offsetTop;
            }
        } while (elt = elt.offsetParent);
        return {x: ex, y: ey};
    },
    // MouseDown handler -- sets up the appropriate mousemove/mouseup handlers
    // and fills in the global dragtable.dragObj object.
    dragStart: function(event) {
        var _this = dragtable;
        var downTimer;

        var el;
        var x, y;
        var dragObj = dragtable.dragObj;

        var browser = dragtable.browser;
        if (dragtable.classHandle != '') { //if use handle
            //console.log('drag of ' + this.index);
        } else {
            if (browser.isIE)
                dragObj.origNode = window.event.srcElement;
            else
                dragObj.origNode = event.target;
        }
        var pos = dragtable.eventPosition(event);

        // Drag the entire table cell, not just the element that was clicked.
        dragObj.origNode = dragtable.findUp(dragObj.origNode, /T[DH]/);

        // Since a column header can't be dragged directly, duplicate its contents
        // in a div and drag that instead.
        // TODO: I can assume a tHead...
        var table = dragtable.findUp(dragObj.origNode, "TABLE");
        dragObj.table = table;
        dragObj.startCol = dragtable.findColumn(table, pos.x);
        if (dragObj.startCol == -1)
            return;

        var new_elt = dragtable.fullCopy(table, false);
        new_elt.style.margin = '0';

        // Copy the entire column
        var copySectionColumn = function(sec, col) {
            var new_sec = dragtable.fullCopy(sec, false);
            forEach(sec.rows, function(row) {
                var cell = row.cells[col];
                var new_tr = dragtable.fullCopy(row, false);
                if (row.offsetHeight)
                    new_tr.style.height = row.offsetHeight + "px";
                var new_td = dragtable.fullCopy(cell, true);
                if (cell.offsetWidth)
                    new_td.style.width = cell.offsetWidth + "px";
                new_tr.appendChild(new_td);
                new_sec.appendChild(new_tr);
            });
            return new_sec;
        };

        // First the heading
        if (table.tHead) {
            new_elt.appendChild(copySectionColumn(table.tHead, dragObj.startCol));
        }
        forEach(table.tBodies, function(tb) {
            new_elt.appendChild(copySectionColumn(tb, dragObj.startCol));
        });
        if (table.tFoot) {
            new_elt.appendChild(copySectionColumn(table.tFoot, dragObj.startCol));
        }

        var obj_pos = dragtable.absolutePosition(dragObj.origNode, true);
        new_elt.style.position = "absolute";
        new_elt.style.left = obj_pos.x + "px";
        new_elt.style.top = obj_pos.y + "px";
        new_elt.style.width = dragObj.origNode.offsetWidth + "px";
        new_elt.style.height = dragObj.origNode.offsetHeight + "px";
        new_elt.style.opacity = 0.7;

        // Hold off adding the element until this is clearly a drag.
        dragObj.addedNode = false;
        dragObj.tableContainer = dragObj.table.parentNode || document.body;
        dragObj.elNode = new_elt;

        // Save starting positions of cursor and element.
        dragObj.cursorStartX = pos.x;
        dragObj.cursorStartY = pos.y;
        dragObj.elStartLeft = parseInt(dragObj.elNode.style.left, 10);
        dragObj.elStartTop = parseInt(dragObj.elNode.style.top, 10);

        if (isNaN(dragObj.elStartLeft))
            dragObj.elStartLeft = 0;
        if (isNaN(dragObj.elStartTop))
            dragObj.elStartTop = 0;

        // Update element's z-index.
        dragObj.elNode.style.zIndex = ++dragObj.zIndex;
        
        clearTimeout(downTimer);
        downTimer = setTimeout(function() {
            
            // Capture mousemove and mouseup events on the page.
//            if (browser.isIE) {
//                document.attachEvent("onmousemove", dragtable.dragMove);
//                document.attachEvent("onmouseup", dragtable.dragEnd);
//                window.event.cancelBubble = true;
//                window.event.returnValue = false;
//            } else {
            //remove tooltip when move
            $('#tooltip').remove();
            //disable text selection when drag
            //$(table).disableSelection();
            //active color can move
            var targetCol = dragtable.findColumn(table, pos.x);
            $(table).find('th').removeClass('column-hover');
            $(table).find('td').removeClass('column-hover');
            $(table).find('th').eq(targetCol).addClass('column-hover');
            $(table).find('td').eq(targetCol).addClass('column-hover');
            //do event move
            document.addEventListener("mousemove", dragtable.dragMove, true);
            document.addEventListener("mouseup", dragtable.dragEnd, true);
            event.preventDefault();
//            }
        }, dragtable.clickDelay);
        
        mouseMoveClearTimeout = function(clEvt){
            var ctPos = dragtable.eventPosition(clEvt);
            if(Math.abs(ctPos.x - pos.x) > 1){
                clearTimeout(downTimer);
            }
        };
        
        $(table).mousemove(mouseMoveClearTimeout);
        //mouse up and clear time out
        this.onmouseup = function(evt) {
            clearTimeout(downTimer);
            $(table).unbind('mousemove', mouseMoveClearTimeout);
            $(this).unbind('mouseup');
        };
        $(table).mouseup(function(){
            $(this).unbind('mousemove');
            $(this).unbind('mouseup');
        });
    },
    // Move the floating column header with the mouse
    // TODO: Reorder columns as the mouse moves for a more interactive feel.
    dragMove: function(event) {
        var x, y;
        var dragObj = dragtable.dragObj;
        //disable text selection when drag
        //$(dragObj.table).disableSelection();
        // Get cursor position with respect to the page.
        var pos = dragtable.eventPosition(event);

        var dx = dragObj.cursorStartX - pos.x;
        var dy = dragObj.cursorStartY - pos.y;
        // Move drag element by the same amount the cursor has moved.
        var style = dragObj.elNode.style;
        if (dragObj.table.offset_top == undefined)
            dragObj.table.offset_top = 0;
        if (dragObj.table.offset_left == undefined)
            dragObj.table.offset_left = 0;
        //dragObj.table.offset_top = 0, dragObj.table.offset_left = 0;
        var new_offset_top = (dragObj.elStartTop + pos.y - dragObj.cursorStartY);
        var new_offset_left = (dragObj.elStartLeft + pos.x - dragObj.cursorStartX);
        //add node moving
        if (!dragObj.addedNode && dx * dx + dy * dy > dragtable.dragRadius2) {
            //re calculate position bug
            dragObj.table.offset_top = $(dragObj.table).position().top;
            dragObj.table.offset_left = $(dragObj.table).position().left;
            dragObj.tableContainer.insertBefore(dragObj.elNode, dragObj.table);
            style.cursor = 'move';
            dragObj.addedNode = true;
        }
        style.top = new_offset_top + dragObj.table.offset_top + "px";
        style.left = new_offset_left + dragObj.table.offset_left + "px";

        if (dragtable.browser.isIE) {
            window.event.cancelBubble = true;
            window.event.returnValue = false;
        } else {
            event.preventDefault();
        }
        //hover column on dragmove
        var targetCol = dragtable.findColumn(dragObj.table, pos.x);
        $(dragObj.table).find('th').removeClass('column-hover');
        $(dragObj.table).find('td').removeClass('column-hover');
        $(dragObj.table).find('th').eq(targetCol).addClass('column-hover');
        $(dragObj.table).find('td').eq(targetCol).addClass('column-hover');
        $(dragObj.table).trigger('dragg_moving', [dx, dy]);
    },
    // Stop capturing mousemove and mouseup events.
    // Determine which (if any) column we're over and shuffle the table.
    dragEnd: function(event) {
//        if (dragtable.browser.isIE) {
//            document.detachEvent("onmousemove", dragtable.dragMove);
//            document.detachEvent("onmouseup", dragtable.dragEnd);
//        } else {
        document.removeEventListener("mousemove", dragtable.dragMove, true);
        document.removeEventListener("mouseup", dragtable.dragEnd, true);
//        }

        // If the floating header wasn't added, the mouse didn't move far enough.
        var dragObj = dragtable.dragObj;
        //not clear timeout dragStart
        $(dragObj.table).unbind('mousemove');
        //remove class hover dragend
        $(dragObj.table).find('th').removeClass('column-hover');
        $(dragObj.table).find('td').removeClass('column-hover');
        if (!dragObj.addedNode) {
            return;
        }
        dragObj.tableContainer.removeChild(dragObj.elNode);

        // Determine whether the drag ended over the table, and over which column.
        var pos = dragtable.eventPosition(event);
        $(dragObj.table).trigger('dragg_end_before', [pos.x, pos.y]);
        var table_pos = dragtable.absolutePosition(dragObj.table);
        if (pos.y < table_pos.y ||
                pos.y > table_pos.y + dragObj.table.offsetHeight) {
            //return; //not creenter column
        } else {
            var targetCol = dragtable.findColumn(dragObj.table, pos.x);
            if (targetCol != -1 && targetCol != dragObj.startCol) {
                dragtable.moveColumn(dragObj.table, dragObj.startCol, targetCol);
                if (dragObj.table.id && dragtable.cookiesEnabled() &&
                        dragObj.table.className.search(/\bforget-ordering\b/) == -1) {
                    //dragtable.rememberDrag(dragObj.table.id, dragObj.startCol, targetCol);
                }
            }
        }

        //after column swapped
        $(dragObj.table).trigger('dragg_end_after', [pos.x, pos.y]);
    },
    // Which column does the x value fall inside of? x should include scrollLeft.
    findColumn: function(table, x) {
        var header = table.tHead.rows[0].cells;
        for (var i = 0; i < header.length; i++) {
            //var left = header[i].offsetLeft;
            var pos = dragtable.absolutePosition(header[i]);
            //if (left <= x && x <= left + header[i].offsetWidth) {
            if (pos.x <= x && x <= pos.x + header[i].offsetWidth) {
                return i;
            }
        }
        return -1;
    },
    // Move a column of table from start index to finish index.
    // Based on the "Swapping table columns" discussion on comp.lang.javascript.
    // Assumes there are columns at sIdx and fIdx
    moveColumn: function(table, sIdx, fIdx) {
        var row, cA;
        var i = table.rows.length;
        while (i--) {
            row = table.rows[i];
            if (row.cells[sIdx] != undefined) {
                var x = row.removeChild(row.cells[sIdx]);
                if (fIdx < row.cells.length) {
                    row.insertBefore(x, row.cells[fIdx]);
                } else {
                    row.appendChild(x);
                }
            }
        }

        // For whatever reason, sorttable tracks column indices this way.
        // Without a manual update, clicking one column will sort on another.
        var headrow = table.tHead.rows[0].cells;
        for (var i = 0; i < headrow.length; i++) {
            headrow[i].sorttable_columnindex = i;
        }

        //after move end to move column
        $(table).trigger('column_swapped', [sIdx, fIdx]);
    },
    // Are cookies enabled? We should not attempt to set cookies on a local file.
    cookiesEnabled: function() {
        return (window.location.protocol != 'file:') && navigator.cookieEnabled;
    },
    // Store a column swap in a cookie for posterity.
    rememberDrag: function(id, a, b) {
        var cookieName = "dragtable-" + id;
        var prev = dragtable.readCookie(cookieName);
        var new_val = "";
        if (prev)
            new_val = prev + ",";
        new_val += a + "/" + b;
        dragtable.createCookie(cookieName, new_val, dragtable.cookieDays);
    },
    // Replay all column swaps for a table.
    replayDrags: function(table) {
        if (!dragtable.cookiesEnabled())
            return;
        var dragstr = dragtable.readCookie("dragtable-" + table.id);
        if (!dragstr)
            return;
        var drags = dragstr.split(',');
        for (var i = 0; i < drags.length; i++) {
            var pair = drags[i].split("/");
            if (pair.length != 2)
                continue;
            var a = parseInt(pair[0]);
            var b = parseInt(pair[1]);
            if (isNaN(a) || isNaN(b))
                continue;
            dragtable.moveColumn(table, a, b);
        }
    },
    // Cookie functions based on http://www.quirksmode.org/js/cookies.html
    // Cookies won't work for local files.
    cookiesEnabled: function() {
        return (window.location.protocol != 'file:') && navigator.cookieEnabled;
    },
            createCookie: function(name, value, days) {
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    var expires = "; expires=" + date.toGMTString();
                }
                else
                    var expires = "";

                var path = document.location.pathname;
                document.cookie = name + "=" + value + expires + "; path=" + path;
            },
    readCookie: function(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ')
                c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0)
                return c.substring(nameEQ.length, c.length);
        }
        return null;
    },
    eraseCookie: function(name) {
        dragtable.createCookie(name, "", -1);
    }

}

/* ******************************************************************
 Supporting functions: bundled here to avoid depending on a library
 ****************************************************************** */

// Dean Edwards/Matthias Miller/John Resig
// has a hook for dragtable.init already been added? (see below)
var dgListenOnLoad = false;

/* for Mozilla/Opera9 */
if (document.addEventListener) {
    dgListenOnLoad = true;
    document.addEventListener("DOMContentLoaded", dragtable.init, false);
}

/* for Internet Explorer */
/*@cc_on @*/
/*@if (@_win32)
 dgListenOnLoad = true;
 document.write("<script id=__dt_onload defer src=//0)><\/script>");
 var script = document.getElementById("__dt_onload");
 script.onreadystatechange = function() {
 if (this.readyState == "complete") {
 dragtable.init(); // call the onload handler
 }
 };
 /*@end @*/

/* for Safari */
if (/WebKit/i.test(navigator.userAgent)) { // sniff
    dgListenOnLoad = true;
    var _dgtimer = setInterval(function() {
        if (/loaded|complete/.test(document.readyState)) {
            dragtable.init(); // call the onload handler
        }
    }, 10);
}

/* for other browsers */
/* Avoid this unless it's absolutely necessary (it breaks sorttable) */
if (!dgListenOnLoad) {
    window.onload = dragtable.init;
}

// Dean's forEach: http://dean.edwards.name/base/forEach.js
/*
 forEach, version 1.0
 Copyright 2006, Dean Edwards
 License: http://www.opensource.org/licenses/mit-license.php
 */

// array-like enumeration
if (!Array.forEach) { // mozilla already supports this
    Array.forEach = function(array, block, context) {
        for (var i = 0; i < array.length; i++) {
            block.call(context, array[i], i, array);
        }
    };
}

// generic enumeration
Function.prototype.forEach = function(object, block, context) {
    for (var key in object) {
        if (typeof this.prototype[key] == "undefined") {
            block.call(context, object[key], key, object);
        }
    }
};

// character enumeration
String.forEach = function(string, block, context) {
    Array.forEach(string.split(""), function(chr, index) {
        block.call(context, chr, index, string);
    });
};

// globally resolve forEach enumeration
var forEach = function(object, block, context) {
    if (object) {
        var resolve = Object; // default
        if (object instanceof Function) {
            // functions have a "length" property
            resolve = Function;
        } else if (object.forEach instanceof Function) {
            // the object implements a custom forEach method so use that
            object.forEach(block, context);
            return;
        } else if (typeof object == "string") {
            // the object is a string
            resolve = String;
        } else if (typeof object.length == "number") {
            // the object is array-like
            resolve = Array;
        }
        resolve.forEach(object, block, context);
    }
};
